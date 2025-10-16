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
        $this->provider = config('finance.provider', 'bacen');
        $this->baseUrl = rtrim(config('finance.bacen_base', 'https://api.bcb.gov.br/dados/serie/bcdata.sgs'), '/');
    }

    /**
     * Obtém o retorno cumulativo ou anual para uma série.
     *
     * @param string $startYmd Data inicial no formato Y-m-d
     * @param string $endYmd Data final no formato Y-m-d
     * @param string $which Identificador da série ('selic' ou 'ipca')
     * @return array{value:float,type:'cumulative'|'annual'}|null Retorna um array com 'value' e 'type' ou null se indisponível
     */
    public function getCumulativeReturn(string $startYmd, string $endYmd, string $which): ?array
    {
        if ($this->provider === 'bacen') {
            $seriesId = $this->getSeriesIdFor($which);
            if ($seriesId) {
                try {
                    $start = \Carbon\Carbon::parse($startYmd);
                    $end = \Carbon\Carbon::parse($endYmd);
                } catch (\Exception $e) {
                    return null;
                }

                $chunks = $this->splitIntoChunks($start, $end, 3650); 
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
            return (string) config('finance.selic_series', '11');
        }
        if ($which === 'ipca') {
            return (string) config('finance.ipca_series', '10844');
        }
        return null;
    }

    /**
     * Divide um intervalo de datas em pedaços (chunks) com até $maxDays cada.
     *
     * @param \Carbon\Carbon $start Data inicial
     * @param \Carbon\Carbon $end Data final
     * @param int $maxDays Máximo de dias por chunk
     * @return array<int,array{0:\Carbon\Carbon,1:\Carbon\Carbon}> Lista de pares [start,end] como objetos Carbon
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
     * Busca os valores da série e retorna um array de decimais normalizados (float) para cada entrada.
     *
     * @param string $seriesId Identificador da série no Bacen
     * @param string $startYmd Data inicial no formato Y-m-d
     * @param string $endYmd Data final no formato Y-m-d
     * @return float[] Valores normalizados como decimais (por exemplo 0.0123 para 1,23%)
     */
    protected function fetchSeriesValues(string $seriesId, string $startYmd, string $endYmd): array
    {
        try {
            $start = \Carbon\Carbon::parse($startYmd)->format('d/m/Y');
            $end = \Carbon\Carbon::parse($endYmd)->format('d/m/Y');
        } catch (\Exception $e) {
            return [];
        }

    $base = $this->baseUrl . '.' . $seriesId . '/dados';

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
            Log::warning('FinanceRateService: Http exception: ' . $e->getMessage());
            $data = null;
        }

        if ($data === null) {
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

        if ($data === null) {
            try {
                $u2 = $this->baseUrl . '.' . $seriesId . '/dados/ultimos/20';
                $resp2 = Http::acceptJson()->get($u2, ['formato' => 'json']);
                if ($resp2->ok()) {
                    $data = $resp2->json();
                    Log::debug("FinanceRateService: fetched ultimos/20 for {$seriesId}, items=" . (is_array($data) ? count($data) : 0));
                } else {
                    Log::warning("FinanceRateService: ultimos/20 Http fetch returned status {$resp2->status()} for series {$seriesId}");
                }
            } catch (\Exception $e) {
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
            $raw = str_replace('%', '', $raw);

            if (strpos($raw, ',') !== false) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = $raw;
            }

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
                    if ($f >= 0.01 && $f <= 1000) {
                        $f = $f / 100.0;
                    }
                }

            $values[] = $f;
        }

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
     * Calcula o retorno cumulativo a partir de uma lista de retornos periódicos em decimais.
     *
     * @param float[] $values Valores periódicos em decimal (por exemplo 0.01 para 1%)
     * @return float Retorno cumulativo (por exemplo 0.1234 para 12,34%)
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
