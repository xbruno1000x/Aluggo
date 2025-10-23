@extends('layouts.app')

@section('title', 'Resultado da Simulação - ' . $imovel->nome)
@section('header', 'Resultado da Simulação de Venda')

@section('content')
<div class="w-100 d-flex justify-content-center">
    <div style="max-width: 900px; width:100%;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <div class="alert alert-warning d-flex align-items-center mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                <strong>Simulação</strong> - Esta é uma análise hipotética. Nenhuma transação foi registrada no sistema.
            </div>
        </div>

        <div class="card bg-secondary text-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Imóvel: {{ $imovel->nome ?? '-' }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }}</h5>
                <p class="card-text">Valor de Aquisição: {{ $imovel->valor_compra ? 'R$ ' . number_format($imovel->valor_compra, 2, ',', '.') : '-' }}</p>
                <p class="card-text">Valor Simulado de Venda: <span class="text-warning fw-bold">R$ {{ number_format($simulacao['valor_venda'], 2, ',', '.') }}</span></p>
                <p class="card-text">Data Simulada: {{ \Carbon\Carbon::parse($simulacao['data_venda'])->format('d/m/Y') }}</p>
            </div>
            <div class="card-footer bg-dark text-light">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="me-3 text-center mb-2">
                        <strong>Lucro Bruto Estimado:</strong>
                        @if(isset($lucro) && $lucro !== null)
                            <div><span class="{{ $lucro >= 0 ? 'text-dolar' : 'text-danger' }} fs-5">R$ {{ number_format($lucro, 2, ',', '.') }}</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>

                    <div class="mb-2 text-center">
                        <strong>ROI Estimado:</strong>
                        @if(isset($porcentagem) && $porcentagem !== null)
                            <div><span class="{{ $porcentagem >= 0 ? 'text-dolar' : 'text-danger' }} fs-5">{{ number_format($porcentagem, 2, ',', '.') }}%</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        <div class="border border-secondary rounded p-2">
                            <strong class="d-block small">Receita com Aluguéis</strong>
                            @if(isset($rentalIncome) && $rentalIncome !== null)
                                <span class="{{ $rentalIncome >= 0 ? 'text-dolar' : 'text-danger' }} fs-6">
                                    R$ {{ number_format($rentalIncome, 2, ',', '.') }}
                                </span>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="border border-secondary rounded p-2">
                            <strong class="d-block small">Despesas com Obras</strong>
                            @if(isset($obraExpenses) && $obraExpenses !== null)
                                <span class="{{ $obraExpenses > 0 ? 'text-danger' : 'text-dolar' }} fs-6">
                                    R$ {{ number_format($obraExpenses, 2, ',', '.') }}
                                </span>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="border border-secondary rounded p-2">
                            <strong class="d-block small">Despesas com Taxas</strong>
                            @if(isset($taxExpenses) && $taxExpenses !== null)
                                <span class="{{ $taxExpenses > 0 ? 'text-danger' : 'text-dolar' }} fs-6">
                                    R$ {{ number_format($taxExpenses, 2, ',', '.') }}
                                </span>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 text-center">
                        <div class="border border-warning rounded p-2 bg-dark">
                            <strong class="d-block small text-warning">Lucro Líquido Ajustado</strong>
                            @if(isset($adjustedProfit) && $adjustedProfit !== null)
                                <span class="{{ $adjustedProfit >= 0 ? 'text-warning' : 'text-danger' }} fs-5 fw-bold">
                                    R$ {{ number_format($adjustedProfit, 2, ',', '.') }}
                                </span>
                                <div class="small text-light mt-1">(venda + aluguéis - obras - taxas)</div>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="text-warning mb-3">📊 Análise Comparativa de Investimento</h6>
                    
                    @if(!empty($periodText))
                        <div class="mb-2">
                            <i class="bi bi-calendar-range text-info"></i> 
                            <strong>Período de posse:</strong> {{ $periodText }}
                        </div>
                    @endif

                    @if(!empty($selicText))
                        <div class="mb-2">
                            <i class="bi bi-graph-up {{ str_contains($selicText, 'indisponível') ? 'text-muted' : 'text-success' }}"></i> 
                            {{ $selicText }}
                        </div>
                    @endif

                    @if(!empty($ipcaText))
                        <div class="mb-2">
                            <i class="bi bi-cash-coin {{ str_contains($ipcaText, 'indisponível') ? 'text-muted' : 'text-warning' }}"></i> 
                            {{ $ipcaText }}
                        </div>
                    @endif

                    @if(empty($periodText) && empty($selicText) && empty($ipcaText))
                        <div class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Configure a data de aquisição do imóvel para visualizar análises comparativas de investimento.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card bg-dark text-light mb-3">
            <div class="card-header bg-secondary">
                <h6 class="mb-0">💡 Análise de Decisão</h6>
            </div>
            <div class="card-body">
                @if(isset($adjustedProfit) && isset($porcentagem))
                    @if($adjustedProfit > 0 && $porcentagem > 10)
                        <div class="alert alert-success mb-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>Cenário Favorável:</strong> A simulação indica lucro positivo com ROI superior a 10%. 
                            Este pode ser um bom momento para considerar a venda.
                        </div>
                    @elseif($adjustedProfit > 0)
                        <div class="alert alert-warning mb-2">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <strong>Lucro Moderado:</strong> A venda geraria lucro, mas o retorno é relativamente baixo. 
                            Considere se não seria melhor manter o imóvel gerando renda.
                        </div>
                    @else
                        <div class="alert alert-danger mb-2">
                            <i class="bi bi-x-circle-fill"></i>
                            <strong>Prejuízo:</strong> A simulação indica prejuízo nesta venda. 
                            Recomenda-se aguardar valorização ou ajustar o valor de venda.
                        </div>
                    @endif
                @endif

                <p class="mb-2"><strong>Pontos a considerar:</strong></p>
                <ul class="mb-0">
                    <li>Compare o ROI com outras opções de investimento disponíveis</li>
                    <li>Avalie o potencial de valorização futura do imóvel</li>
                    <li>Considere custos de transação (corretagem, impostos) não incluídos nesta simulação</li>
                    <li>Analise a necessidade de liquidez versus geração de renda passiva</li>
                </ul>
            </div>
        </div>

        <div class="d-flex justify-content-between flex-wrap gap-2">
            <a href="{{ route('imoveis.index') }}" class="btn btn-secondary">
                <i class="bi bi-house-door"></i> Voltar para Imóveis
            </a>
            <a href="{{ route('imoveis.simular-venda', $imovel) }}" class="btn btn-info">
                <i class="bi bi-arrow-repeat"></i> Simular Outro Cenário
            </a>
            @if($imovel->status !== 'vendido')
                <a href="{{ route('transacoes.create') }}?imovel_id={{ $imovel->id }}&valor={{ $simulacao['valor_venda'] }}" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Efetivar Venda com Este Valor
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
