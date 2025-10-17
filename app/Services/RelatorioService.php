<?php

namespace App\Services;

use App\Models\Imovel;
use App\Models\Aluguel;
use App\Models\Pagamento;
use App\Models\Obra;
use App\Models\Transacao;
use App\Models\Propriedade;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioService
{
    /**
     * Gera agregados e série temporal para o período informado.
     *
     * @param int|null $imovelId
     * @param string $startYmd
     * @param string $endYmd
     * @param int $proprietarioId
     * @return array{aggregates:array<string,mixed>,series:list<array<string,mixed>>}
     */
    public function getReport(?int $imovelId, string $startYmd, string $endYmd, int $proprietarioId): array
    {
        $start = Carbon::parse($startYmd)->startOfMonth();
        $end = Carbon::parse($endYmd)->endOfMonth();

        $imoveisQuery = Imovel::whereHas('propriedade', function ($q) use ($proprietarioId) {
            $q->where('proprietario_id', $proprietarioId);
        });
        if ($imovelId) $imoveisQuery->where('id', $imovelId);
        $imovelIds = $imoveisQuery->pluck('id')->all();

        // alugueis relacionados
        $aluguelIds = Aluguel::whereIn('imovel_id', $imovelIds)->pluck('id')->all();

        // receitas de aluguel no período
        $rentalIncome = Pagamento::whereIn('aluguel_id', $aluguelIds)
            ->whereDate('referencia_mes', '>=', $start->toDateString())
            ->whereDate('referencia_mes', '<=', $end->toDateString())
            ->sum('valor_recebido');

        // despesas de obra no período: considerar a data de início como data do gasto
        $obraExpenses = Obra::whereIn('imovel_id', $imovelIds)
            ->whereDate('data_inicio', '>=', $start->toDateString())
            ->whereDate('data_inicio', '<=', $end->toDateString())
            ->sum('valor');

        // vendas no período
        $sales = Transacao::whereIn('imovel_id', $imovelIds)
            ->whereDate('data_venda', '>=', $start->toDateString())
            ->whereDate('data_venda', '<=', $end->toDateString())
            ->sum('valor_venda');

        // compras (aquisições) no período
        $purchases = Imovel::whereIn('id', $imovelIds)
            ->whereDate('data_aquisicao', '>=', $start->toDateString())
            ->whereDate('data_aquisicao', '<=', $end->toDateString())
            ->sum('valor_compra');

        $net = (float) $sales + (float) $rentalIncome - (float) $purchases - (float) $obraExpenses;

        // taxas no período: consider two modes:
        // - when an explicit $imovelId is provided, only select taxas that are directly
        //   related to that imovel (imovel_id, aluguel.imovel_id, or propriedade that
        //   contains that imovel). Do NOT include proprietor-global taxas in this mode.
        // - when no $imovelId is provided, select taxas for all imoveis/propriedades of the owner
    /** @var int[] $propriedadeIds */
    $propriedadeIds = Propriedade::where('proprietario_id', $proprietarioId)->pluck('id')->all();

        $taxasQuery = \App\Models\Taxa::with(['propriedade', 'imovel', 'aluguel.imovel'])
            ->whereDate('data_pagamento', '>=', $start->toDateString())
            ->whereDate('data_pagamento', '<=', $end->toDateString());

        if (!empty($imovelId)) {
            // narrow to taxas connected to this specific imovel
            $taxasQuery->where(function ($q) use ($imovelId) {
                $q->where('imovel_id', $imovelId)
                  ->orWhereHas('aluguel', function ($q2) use ($imovelId) {
                      $q2->where('imovel_id', $imovelId);
                  })
                  ->orWhereHas('propriedade', function ($q3) use ($imovelId) {
                      $q3->whereHas('imoveis', function ($q4) use ($imovelId) {
                          $q4->where('id', $imovelId);
                      });
                  });
            });
        } else {
            // owner-wide report: include taxas linked to owner's imoveis/propriedades/alugueis
            $taxasQuery->where(function ($q) use ($imovelIds, $propriedadeIds, $aluguelIds, $proprietarioId) {
                $q->whereIn('imovel_id', $imovelIds)
                  ->orWhereIn('propriedade_id', $propriedadeIds)
                  ->orWhereIn('aluguel_id', $aluguelIds)
                  ->orWhere('proprietario_id', $proprietarioId);
            });
        }

        $taxas = $taxasQuery->get();

    // taxasTotal: visible total (both pagadores)
    // taxasImpact: only taxas pagas pelo proprietario (impactam o resultado)
    $taxasTotal = 0.0;
    $taxasImpact = 0.0;
    /** @var array<string,float> $taxasByPagador */
    $taxasByPagador = ['proprietario' => 0.0, 'locatario' => 0.0];

        foreach ($taxas as $t) {
            // 1) taxa diretamente no imovel
                if (!empty($t->imovel_id) && in_array($t->imovel_id, $imovelIds, true)) {
                $val = (float) $t->valor;
                $taxasTotal += $val;
                $key = (string) $t->pagador;
                if (!isset($taxasByPagador[$key])) $taxasByPagador[$key] = 0.0;
                $taxasByPagador[$key] += $val;
                if (($t->pagador ?? '') === 'proprietario') {
                    $taxasImpact += $val;
                }
                continue;
            }

            // 2) taxa vinculada a um aluguel -> atribuir ao imovel do aluguel
                if (!empty($t->aluguel) && !empty($t->aluguel->imovel) && in_array($t->aluguel->imovel->id, $imovelIds, true)) {
                $val = (float) $t->valor;
                $taxasTotal += $val;
                $key = (string) $t->pagador;
                if (!isset($taxasByPagador[$key])) $taxasByPagador[$key] = 0.0;
                $taxasByPagador[$key] += $val;
                if (($t->pagador ?? '') === 'proprietario') {
                    $taxasImpact += $val;
                }
                continue;
            }

            // 3) taxa vinculada a propriedade -> dividir entre imoveis da propriedade
            if (!empty($t->propriedade_id) && in_array($t->propriedade_id, $propriedadeIds, true)) {
                $prop = $t->propriedade;
                if ($prop) {
                    /** @var \App\Models\Propriedade $prop */
                    $imoveisOfProp = $prop->imoveis()->pluck('id')->all();
                    if (!empty($imoveisOfProp)) {
                        $intersect = array_values(array_intersect($imoveisOfProp, $imovelIds));
                        if (!empty($intersect)) {
                            $share = (float) $t->valor / count($imoveisOfProp);
                            $portion = $share * count($intersect);
                            $taxasTotal += $portion;
                            $key = (string) $t->pagador;
                            if (!isset($taxasByPagador[$key])) $taxasByPagador[$key] = 0.0;
                            $taxasByPagador[$key] += $portion;
                            if (($t->pagador ?? '') === 'proprietario') {
                                $taxasImpact += $portion;
                            }
                        }
                    }
                }
                continue;
            }

            // 4) taxa criada pelo proprietario sem vinculo direto: contar integralmente
            // NOTE: when the report is filtered to a single imovel ($imovelId is provided),
            // do not count proprietario-global taxas here because they're not tied to that
            // specific imovel. These global taxas will only be included when no specific
            // imovel filter is applied (full-owner report).
            if (!empty($t->proprietario_id) && $t->proprietario_id === $proprietarioId) {
                if (empty($imovelId)) {
                    $val = (float) $t->valor;
                    $taxasTotal += $val;
                            $key = (string) $t->pagador;
                            if (!isset($taxasByPagador[$key])) $taxasByPagador[$key] = 0.0;
                            $taxasByPagador[$key] += $val;
                }
                continue;
            }
        }

    // somente taxas pagas pelo proprietario impactam o resultado
    $net = $net - (float) $taxasImpact;

        // série temporal mensal do patrimônio (acumulado)
        $series = [];
        $cursor = $start->copy();
        $cumulative = 0.0;
        while ($cursor->lessThanOrEqualTo($end)) {
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd = $cursor->copy()->endOfMonth();

            $monthSales = Transacao::whereIn('imovel_id', $imovelIds)
                ->whereDate('data_venda', '>=', $mStart->toDateString())
                ->whereDate('data_venda', '<=', $mEnd->toDateString())
                ->sum('valor_venda');

            $monthPurchases = Imovel::whereIn('id', $imovelIds)
                ->whereDate('data_aquisicao', '>=', $mStart->toDateString())
                ->whereDate('data_aquisicao', '<=', $mEnd->toDateString())
                ->sum('valor_compra');

            $monthRent = Pagamento::whereIn('aluguel_id', $aluguelIds)
                ->whereDate('referencia_mes', '>=', $mStart->toDateString())
                ->whereDate('referencia_mes', '<=', $mEnd->toDateString())
                ->sum('valor_recebido');

            // despesas de obra contadas apenas no mês da data_inicio
            $monthObra = Obra::whereIn('imovel_id', $imovelIds)
                ->whereDate('data_inicio', '>=', $mStart->toDateString())
                ->whereDate('data_inicio', '<=', $mEnd->toDateString())
                ->sum('valor');

            // taxas do mês: somar taxas atreladas diretamente aos imoveis e parcelas de taxas atreladas a propriedades
            $monthTaxas = 0.0;

            // fetch taxas for the month that are relevant (imovel/propriedade/aluguel/proprietario)
            $monthTaxas = 0.0;
            $monthTaxasQuery = \App\Models\Taxa::with(['propriedade', 'aluguel.imovel'])
                ->whereDate('data_pagamento', '>=', $mStart->toDateString())
                ->whereDate('data_pagamento', '<=', $mEnd->toDateString());

            if (!empty($imovelId)) {
                $monthTaxasQuery->where(function ($q) use ($imovelId) {
                    $q->where('imovel_id', $imovelId)
                      ->orWhereHas('aluguel', function ($q2) use ($imovelId) {
                          $q2->where('imovel_id', $imovelId);
                      })
                      ->orWhereHas('propriedade', function ($q3) use ($imovelId) {
                          $q3->whereHas('imoveis', function ($q4) use ($imovelId) {
                              $q4->where('id', $imovelId);
                          });
                      });
                });
            } else {
                $monthTaxasQuery->where(function ($q) use ($imovelIds, $propriedadeIds, $aluguelIds, $proprietarioId) {
                    $q->whereIn('imovel_id', $imovelIds)
                      ->orWhereIn('propriedade_id', $propriedadeIds)
                      ->orWhereIn('aluguel_id', $aluguelIds)
                      ->orWhere('proprietario_id', $proprietarioId);
                });
            }

            $monthTaxasAll = $monthTaxasQuery->get();

            foreach ($monthTaxasAll as $t) {
                if (!empty($t->imovel_id) && in_array($t->imovel_id, $imovelIds, true)) {
                    $monthTaxas += (float) $t->valor;
                    continue;
                }
                if (!empty($t->aluguel) && !empty($t->aluguel->imovel) && in_array($t->aluguel->imovel->id, $imovelIds, true)) {
                    $monthTaxas += (float) $t->valor;
                    continue;
                }
                if (!empty($t->propriedade_id) && in_array($t->propriedade_id, $propriedadeIds, true)) {
                    $prop = $t->propriedade;
                    if (!$prop) continue;
                    /** @var \App\Models\Propriedade $prop */
                    $imoveisOfProp = $prop->imoveis()->pluck('id')->all();
                    if (empty($imoveisOfProp)) continue;
                    $intersect = array_values(array_intersect($imoveisOfProp, $imovelIds));
                    if (empty($intersect)) continue;
                    $share = (float) $t->valor / count($imoveisOfProp);
                    $monthTaxas += $share * count($intersect);
                    continue;
                }
                if (!empty($t->proprietario_id) && $t->proprietario_id === $proprietarioId) {
                    // only include global proprietario taxas in monthly totals when not
                    // filtering by a specific imovel
                    if (empty($imovelId)) {
                        $monthTaxas += (float) $t->valor;
                    }
                    continue;
                }
            }

            $delta = (float) $monthSales + (float) $monthRent - (float) $monthPurchases - (float) $monthObra;
            // monthTaxas contém ambos; precisamos computar apenas a parcela que impacta (proprietario)
            $monthTaxasImpact = 0.0;
            foreach ($monthTaxasAll as $t) {
                $counted = false;
                if (!empty($t->imovel_id) && in_array($t->imovel_id, $imovelIds, true)) {
                    $counted = true;
                } elseif (!empty($t->aluguel) && !empty($t->aluguel->imovel) && in_array($t->aluguel->imovel->id, $imovelIds, true)) {
                    $counted = true;
                } elseif (!empty($t->propriedade_id) && in_array($t->propriedade_id, $propriedadeIds, true)) {
                    $prop = $t->propriedade;
                    if ($prop) {
                        $imoveisOfProp = $prop->imoveis()->pluck('id')->all();
                        if (!empty($imoveisOfProp)) {
                            $intersect = array_values(array_intersect($imoveisOfProp, $imovelIds));
                            if (!empty($intersect)) {
                                $counted = true;
                            }
                        }
                    }
                } elseif (!empty($t->proprietario_id) && $t->proprietario_id === $proprietarioId) {
                    // only mark proprietario-global taxas as counted when the report is not
                    // filtered by a specific imovel
                    if (empty($imovelId)) {
                        $counted = true;
                    }
                }

                if ($counted && ($t->pagador ?? '') === 'proprietario') {
                    // attribute the same logic used above for property shares
                    if (!empty($t->propriedade_id) && in_array($t->propriedade_id, $propriedadeIds, true)) {
                        $prop = $t->propriedade;
                        if ($prop) {
                            $imoveisOfProp = $prop->imoveis()->pluck('id')->all();
                            if (!empty($imoveisOfProp)) {
                                $intersect = array_values(array_intersect($imoveisOfProp, $imovelIds));
                                if (!empty($intersect)) {
                                    $share = (float) $t->valor / count($imoveisOfProp);
                                    $monthTaxasImpact += $share * count($intersect);
                                }
                            }
                        }
                    } else {
                        $monthTaxasImpact += (float) $t->valor;
                    }
                }
            }

            $delta = $delta - (float) $monthTaxasImpact;
            $cumulative += $delta;


            $series[] = [
                'month' => $mStart->format('Y-m'),
                'label' => $mStart->format('M/Y'),
                'monthSales' => (float) $monthSales,
                'monthPurchases' => (float) $monthPurchases,
                'monthRent' => (float) $monthRent,
                'monthObra' => (float) $monthObra,
                'monthTaxas' => (float) $monthTaxasImpact,
                'delta' => $delta,
                'cumulative' => $cumulative,
            ];

            $cursor->addMonth();
        }

        return [
            'aggregates' => [
                'rentalIncome' => (float) $rentalIncome,
                'obraExpenses' => (float) $obraExpenses,
                'sales' => (float) $sales,
                'purchases' => (float) $purchases,
                'taxasTotal' => (float) $taxasTotal,
                'taxasByPagador' => $taxasByPagador,
                'net' => (float) $net,
            ],
            'series' => $series,
        ];
    }
}
