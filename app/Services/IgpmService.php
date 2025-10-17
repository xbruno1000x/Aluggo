<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IgpmService
{
    private string $seriesId;
    private string $baseUrl;

    public function __construct()
    {
        $this->seriesId = (string) config('finance.igpm_series', '189');
        $this->baseUrl = rtrim(config('finance.bacen_base', 'https://api.bcb.gov.br/dados/serie/bcdata.sgs'), '/');
    }

    /**
     * Retorna o percentual acumulado do IGP-M entre duas datas (inclusive).
     *
     * @return array{percent:float,from:Carbon,to:Carbon}
     */
    public function accumulatedPercent(Carbon $start, Carbon $end): array
    {
        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $cacheKey = sprintf(
            'igpm_%s_%s_%s',
            $this->seriesId,
            $start->format('Ymd'),
            $end->format('Ymd')
        );

        $payload = Cache::remember($cacheKey, now()->addHours(6), function () use ($start, $end) {
            return $this->fetchRange($start, $end);
        });

        if (empty($payload)) {
            throw new RuntimeException('Não foi possível obter dados do IGP-M no período solicitado.');
        }

        $factor = 1.0;
        $firstDate = null;
        $lastDate = null;

        foreach ($payload as $row) {
            $value = $this->normalizePercent($row['valor'] ?? null);
            if ($value === null) {
                continue;
            }

            $factor *= (1 + ($value / 100));

            $date = $this->parseDate($row['data'] ?? null);
            if ($date) {
                $firstDate ??= $date;
                $lastDate = $date;
            }
        }

        if ($factor <= 0 || !$firstDate || !$lastDate) {
            throw new RuntimeException('Dados insuficientes para calcular o IGP-M acumulado.');
        }

        $percent = ($factor - 1) * 100;

        return [
            'percent' => $percent,
            'from' => $firstDate,
            'to' => $lastDate,
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function fetchRange(Carbon $start, Carbon $end): array
    {
        $query = [
            'formato' => 'json',
            'dataInicial' => $start->format('d/m/Y'),
            'dataFinal' => $end->format('d/m/Y'),
        ];

        $endpoint = sprintf('%s.%s/dados', $this->baseUrl, $this->seriesId);

        try {
            $response = Http::acceptJson()->timeout(10)->get($endpoint, $query);
            if ($response->ok()) {
                $json = $response->json();
                return is_array($json) ? $json : [];
            }
        } catch (\Throwable $e) {
            // fallback abaixo
        }

        $url = $endpoint . '?' . http_build_query($query);
        $contents = @file_get_contents($url);
        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePercent(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        $s = str_replace('%', '', $s);
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        if (!is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
