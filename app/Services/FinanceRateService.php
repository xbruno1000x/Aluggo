<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FinanceRateService
{
    protected string $provider;
    protected string $baseUrl;

    public function __construct()
    {
        // Use only config() here; env() must not be called outside config files (cache-safe)
        $this->provider = config('finance.provider', 'bacen');
        $this->baseUrl = rtrim(config('finance.bacen_base', 'https://api.bcb.gov.br/dados/serie/bcdata.sgs'), '/');
    }

    /**
     * Retorna o retorno acumulado (decimal) para o período entre duas datas.
     * Se o provider for 'bacen' tenta buscar as séries do Bacen, respeitando o limite de 10 anos por chamada
     * e usando cache. Se falhar ou provider for 'config', usa taxa anual do config como fallback (retornando taxa anual).
     *
     * @param string $startYmd  yyyy-mm-dd
     * @param string $endYmd    yyyy-mm-dd
     * @param string $which     'selic'|'ipca'
     * @return float|null       se retornado valor entre 0 e 1 => cumulativo; se retornado >1 => taxa anual (decimal) fallback; null se indisponível
     */
    /**
     * Retorna um array com formato ['value' => float, 'type' => 'cumulative'|'annual'] ou null se indisponível.
     *
     * @param string $startYmd
     * @param string $endYmd
     * @param string $which
     * @return array{value:float,type:'cumulative'|'annual'}|null
     */
    public function getCumulativeReturn(string $startYmd, string $endYmd, string $which): ?array
    {
        if ($this->provider === 'bacen') {
            $seriesId = $this->getSeriesIdFor($which);
            if ($seriesId) {
                // Bacen limit: max 10 years per request. Vamos fragmentar em chunks de 10 anos
                try {
                    $start = \Carbon\Carbon::parse($startYmd);
                    $end = \Carbon\Carbon::parse($endYmd);
                } catch (\Exception $e) {
                    return null;
                }

                $chunks = $this->splitIntoChunks($start, $end, 3650); // 10 anos ~ 3650 dias
                $allValues = [];
                foreach ($chunks as $c) {
                    [$s, $e] = $c;
                    $cacheKey = "finance_series_{$seriesId}_{$s->format('Ymd')}_{$e->format('Ymd')}";
                    $vals = null;
                    try {
                        $vals = Cache::remember($cacheKey, now()->addHours(6), function () use ($seriesId, $s, $e) {
                            return $this->fetchSeriesValues($seriesId, $s->format('Y-m-d'), $e->format('Y-m-d'));
                        });
                    } catch (\Exception $ex) {
                        // se cache falhar (ex.: driver não configurado), buscar diretamente
                        $vals = $this->fetchSeriesValues($seriesId, $s->format('Y-m-d'), $e->format('Y-m-d'));
                    }
                    if (!empty($vals)) {
                        $allValues = array_merge($allValues, $vals);
                    }
                }

                if (!empty($allValues)) {
                    return ['value' => $this->cumulativeFromValues($allValues), 'type' => 'cumulative'];
                }
            }
        }

        // fallback: retornar taxa anual do config
        $annual = null;
        if ($which === 'selic') {
            $annual = config('finance.selic_annual');
        } elseif ($which === 'ipca') {
            $annual = config('finance.ipca_annual');
        }

        return $annual !== null ? ['value' => (float) $annual, 'type' => 'annual'] : null;
    }

    protected function getSeriesIdFor(string $which): ?string
    {
        if ($which === 'selic') {
            /** @psalm-suppress MixedReturnStatement */
            return (string) config('finance.selic_series', '11');
        }
        if ($which === 'ipca') {
            return (string) config('finance.ipca_series', '10844');
        }
        return null;
    }

    /**
     * Split period into chunks with maxDays each (inclusive)
     */
    /**
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @param int $maxDays
     * @return array<int,array{\Carbon\Carbon,\Carbon\Carbon}>
     */
    protected function splitIntoChunks(\Carbon\Carbon $start, \Carbon\Carbon $end, int $maxDays): array
    {
        $chunks = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $chunkEnd = $cursor->copy()->addDays($maxDays - 1);
            if ($chunkEnd->greaterThan($end)) {
                $chunkEnd = $end->copy();
            }
            $chunks[] = [$cursor->copy(), $chunkEnd->copy()];
            $cursor = $chunkEnd->copy()->addDay();
        }
        return $chunks;
    }

    /**
     * Busca valores da série no Bacen (SGS). Retorna array de floats.
     * Espera datas no formato yyyy-mm-dd.
     */
    /**
     * @param string $seriesId
     * @param string $startYmd
     * @param string $endYmd
     * @return float[] parsed decimal return values
     */
    protected function fetchSeriesValues(string $seriesId, string $startYmd, string $endYmd): array
    {
        try {
            $start = \Carbon\Carbon::parse($startYmd)->format('d/m/Y');
            $end = \Carbon\Carbon::parse($endYmd)->format('d/m/Y');
        } catch (\Exception $e) {
            return [];
        }

    // BCData/SGS expects the series in the form `bcdata.sgs.{seriesId}` (dot before id)
    // e.g. https://api.bcb.gov.br/dados/serie/bcdata.sgs.11/dados
    $base = $this->baseUrl . '.' . $seriesId . '/dados';

        // tentar via Http facade (Guzzle). Se houver erro de SSL/cURL no ambiente local, capturamos e fazemos fallback
        $data = null;
        try {
            $resp = Http::acceptJson()->get($base, [
                'dataInicial' => $start,
                'dataFinal' => $end,
                'formato' => 'json',
            ]);

            if ($resp->ok()) {
                $data = $resp->json();
                Log::debug("FinanceRateService: fetched {$seriesId} via Http for {$startYmd} to {$endYmd}, items=" . (is_array($data) ? count($data) : 0));
            } else {
                Log::warning("FinanceRateService: Http fetch for series {$seriesId} returned status {$resp->status()} for {$startYmd} to {$endYmd}");
            }
        } catch (\Exception $e) {
            // falha na requisição via Guzzle (ex.: cURL SSL). Tentaremos fallback com file_get_contents
            Log::warning('FinanceRateService: Http exception: ' . $e->getMessage());
            $data = null;
        }

        if ($data === null) {
            // tentar construir a URL e usar file_get_contents (funciona mesmo sem CA bundle em muitas setups locais)
            $query = http_build_query([
                'formato' => 'json',
                'dataInicial' => $start,
                'dataFinal' => $end,
            ]);
            $full = $base . '?' . $query;
            $opts = ['http' => ['method' => 'GET', 'header' => "Accept: application/json\r\n"]];
            $ctx = stream_context_create($opts);
            $res = @file_get_contents($full, false, $ctx);
            if ($res !== false) {
                $decoded = json_decode($res, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                    Log::debug("FinanceRateService: fetched {$seriesId} via file_get_contents for {$startYmd} to {$endYmd}, items=" . count($decoded));
                } else {
                    Log::warning("FinanceRateService: file_get_contents returned non-array for {$full}");
                }
            } else {
                Log::warning("FinanceRateService: file_get_contents failed for URL: {$full}");
            }
        }

        // se ainda não temos dados, tentar fallback /ultimos/20 via Http (ou file_get_contents)
        if ($data === null) {
            try {
                // use the same dot notation for the ultimos endpoint
                $u2 = $this->baseUrl . '.' . $seriesId . '/dados/ultimos/20';
                $resp2 = Http::acceptJson()->get($u2, ['formato' => 'json']);
                if ($resp2->ok()) {
                    $data = $resp2->json();
                    Log::debug("FinanceRateService: fetched ultimos/20 for {$seriesId}, items=" . (is_array($data) ? count($data) : 0));
                } else {
                    Log::warning("FinanceRateService: ultimos/20 Http fetch returned status {$resp2->status()} for series {$seriesId}");
                }
            } catch (\Exception $e) {
                // fallback via file_get_contents
                $full2 = $this->baseUrl . '.' . $seriesId . '/dados/ultimos/20?formato=json';
                $res2 = @file_get_contents($full2);
                if ($res2 !== false) {
                    $decoded2 = json_decode($res2, true);
                    if (is_array($decoded2)) {
                        $data = $decoded2;
                        Log::debug("FinanceRateService: fetched ultimos/20 via file_get_contents for {$seriesId}, items=" . count($decoded2));
                    } else {
                        Log::warning("FinanceRateService: ultimos/20 file_get_contents returned non-array for {$full2}");
                    }
                } else {
                    Log::warning("FinanceRateService: ultimos/20 file_get_contents failed for URL: {$full2}");
                }
            }
        }

        // At this point $data should be an array or null; enforce array shape for parsing
        if (!is_array($data)) {
            return [];
        }

        $values = [];
        foreach ($data as $row) {
            if (!isset($row['valor'])) {
                continue;
            }
            $raw = trim((string) $row['valor']);
            if ($raw === '') {
                continue;
            }
            // remover símbolo de percent caso exista
            $raw = str_replace('%', '', $raw);

            // Se o valor vem no formato brasileiro com vírgula como separador decimal
            if (strpos($raw, ',') !== false) {
                // remover separador de milhares (pontos) e trocar vírgula por ponto
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                // caso não haja vírgula, assumimos ponto decimal (ex: 0.050788) e mantemos
                $raw = $raw;
            }

            // garantir que agora é um número válido
            if ($raw === '' || !is_numeric($raw)) {
                continue;
            }

            $f = (float) $raw;

                // Heurística de normalização:
                // - Para a série SELIC (diária) o Bacen fornece valores como 0.040168 que representam porcentagem
                //   (0.040168% => 0.00040168 decimal). Então aplicamos divisão por 100 para SELIC quando o valor < 1.
                $selicSeries = (string) config('finance.selic_series', '11');
                if ((string)$seriesId === (string)$selicSeries && $f < 1.0) {
                    $f = $f / 100.0;
                } else {
                    // valores entre 0.01 e 1000 são prováveis percentuais (ex: 0.40 => 0.40%), portanto dividimos por 100
                    if ($f >= 0.01 && $f <= 1000) {
                        $f = $f / 100.0;
                    }
                }

            $values[] = $f;
        }

        // debug: if any parsed value looks abnormal (> 1), log the first few raw entries and parsed values
        $abnormal = array_filter($values, function ($v) { return $v > 1.0 || $v < -1.0; });
        if (count($abnormal) > 0) {
            $sampleRaw = array_slice($data, 0, 8);
            Log::warning('FinanceRateService: abnormal parsed values for series ' . $seriesId . '. count=' . count($values) . ' abnormal=' . count($abnormal) . '. sample_raw=' . json_encode($sampleRaw) . '. parsed_sample=' . json_encode(array_slice($values,0,8)));
        } else {
            Log::debug('FinanceRateService: parsed ' . count($values) . ' values for series ' . $seriesId . '. sample_parsed=' . json_encode(array_slice($values,0,8)));
        }

        return $values;
    }

    /**
     * Calcula retorno acumulado a partir de uma série de valores (assume que cada valor é um período de retorno)
     */
    /**
     * @param float[] $values
     * @return float
     */
    protected function cumulativeFromValues(array $values): float
    {
        $prod = 1.0;
        foreach ($values as $v) {
            $prod *= (1 + (float) $v);
        }
        return $prod - 1.0;
    }
}
