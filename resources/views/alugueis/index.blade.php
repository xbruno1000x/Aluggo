{{-- resources/views/alugueis/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite([
        'resources/ts/delete-confirm.ts',
        'resources/ts/aluguel-adjust.ts',
        'resources/ts/form-spinner.ts'
    ])
@endpush

@section('title', 'Gestão de Aluguéis')
@section('header', 'Gestão de Aluguéis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('alugueis.create') }}" class="btn btn-success">Novo Contrato</a>
</div>

<div class="table-responsive">
    <table class="table table-dark table-striped table-hover align-middle">
        <thead class="table-primary text-dark">
            <tr>
                <th>Imóvel</th>
                <th>Propriedade</th>
                <th>Locatário</th>
                <th>Valor mensal</th>
                <th>Início</th>
                <th>Fim</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alugueis as $aluguel)
                <tr>
                    <td>{{ $aluguel->imovel->nome ?? 'N/D' }}{{ isset($aluguel->imovel->numero) && $aluguel->imovel->numero ? ' (nº ' . $aluguel->imovel->numero . ')' : '' }}</td>
                    <td>{{ $aluguel->imovel->propriedade->nome ?? 'N/D' }}</td>
                    <td>{{ $aluguel->locatario->nome ?? 'N/D' }}</td>
                    <td>
                        {{ $aluguel->valor_mensal !== null ? 'R$ ' . number_format($aluguel->valor_mensal, 2, ',', '.') : 'N/A' }}
                    </td>
                    <td>{{ $aluguel->data_inicio ? \Carbon\Carbon::parse($aluguel->data_inicio)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $aluguel->data_fim ? \Carbon\Carbon::parse($aluguel->data_fim)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center d-flex gap-2 justify-content-center">
                        <a href="{{ route('alugueis.edit', $aluguel) }}" class="btn btn-sm btn-warning">Editar</a>

                        <a href="{{ route('pagamentos.index', ['month' => now()->startOfMonth()->toDateString(), 'aluguel_id' => $aluguel->id]) }}" class="btn btn-sm btn-info">Pagamentos</a>

                        <button type="button"
                            class="btn btn-sm btn-success open-adjust-btn"
                            data-action="{{ route('alugueis.adjust', $aluguel) }}"
                            data-aluguel-id="{{ $aluguel->id }}"
                            data-valor="{{ $aluguel->valor_mensal !== null ? number_format($aluguel->valor_mensal, 2, '.', '') : '' }}"
                            data-valor-formatado="{{ $aluguel->valor_mensal !== null ? 'R$ ' . number_format($aluguel->valor_mensal, 2, ',', '.') : 'N/A' }}"
                            data-imovel="{{ e($aluguel->imovel->nome ?? 'N/D') }}"
                            data-imovel-numero="{{ $aluguel->imovel->numero ?? '' }}"
                            data-propriedade="{{ e(optional($aluguel->imovel->propriedade)->nome ?? '') }}"
                            data-locatario="{{ e($aluguel->locatario->nome ?? 'N/D') }}"
                            data-data-inicio="{{ $aluguel->data_inicio ? \Carbon\Carbon::parse($aluguel->data_inicio)->format('Y-m-d') : '' }}"
                            data-data-inicio-br="{{ $aluguel->data_inicio ? \Carbon\Carbon::parse($aluguel->data_inicio)->format('d/m/Y') : '-' }}"
                            data-data-fim="{{ $aluguel->data_fim ? \Carbon\Carbon::parse($aluguel->data_fim)->format('Y-m-d') : '' }}"
                            data-data-fim-br="{{ $aluguel->data_fim ? \Carbon\Carbon::parse($aluguel->data_fim)->format('d/m/Y') : '-' }}">
                            Reajuste
                        </button>

                        <form method="POST" action="{{ route('alugueis.renew', $aluguel) }}" class="d-inline" data-spinner>
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <span class="btn-text">Renovar</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>

                        <form action="{{ route('alugueis.destroy', $aluguel) }}" method="POST" class="d-inline" data-confirm data-confirm-text="Deseja realmente excluir este contrato?" data-spinner>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <span class="btn-text">Excluir</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-light">Nenhum contrato de aluguel encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $alugueis->links() }}
</div>

<div class="modal fade" id="adjustRentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reajustar aluguel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="adjustRentForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <div id="adjustContractInfo" class="small text-muted mb-2"></div>
                        <div id="adjustCurrentValue" class="fw-semibold mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="adjustModeManual" value="manual" checked>
                            <label class="form-check-label" for="adjustModeManual">
                                Reajuste manual
                            </label>
                        </div>
                        <div id="adjustManualGroup" class="mt-2">
                            <label for="adjustManualValue" class="form-label">Novo valor (R$)</label>
                            <input type="text" class="form-control" id="adjustManualValue" name="novo_valor" placeholder="Ex.: 1.500,00">
                            <div class="form-text">Informe o valor final em reais. Use vírgula para centavos.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="adjustModeIgpm" value="igpm">
                            <label class="form-check-label" for="adjustModeIgpm">
                                Aplicar IGP-M acumulado nos últimos 12 meses
                            </label>
                        </div>
                        <div id="adjustIgpmGroup" class="border rounded mt-2 p-3 d-none">
                            <p class="mb-1" id="igpmPeriodText">Selecione o modo IGP-M para calcular automaticamente.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="igpmPreviewBtn">Simular novo valor</button>
                            <div id="igpmPreviewResult" class="mt-2 small text-muted"></div>
                        </div>
                    </div>

                    <div id="adjustErrors" class="alert alert-danger py-2 px-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="adjustSubmitBtn">Aplicar reajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection