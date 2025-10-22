@extends('layouts.app')

@section('title', 'Confirmação de Pagamentos')
@section('header', 'Confirmação de Pagamentos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">Pagamentos — {{ \Carbon\Carbon::parse($ref)->format('m/Y') }}</h2>

    <div class="d-flex gap-2 align-items-center">
        @php $aluguelId = request()->query('aluguel_id'); @endphp
        <form method="GET" action="{{ route('pagamentos.index') }}" class="d-flex gap-2 align-items-center">
            @if($aluguelId)
                <input type="hidden" name="aluguel_id" value="{{ $aluguelId }}">
            @endif
            <a href="{{ route('pagamentos.index', array_merge(request()->query(), ['month' => \Carbon\Carbon::parse($ref)->subMonth()->format('m/Y')])) }}" class="btn btn-outline-danger">&laquo; Mês anterior</a>
            <input type="text" name="month" value="{{ \Carbon\Carbon::parse($ref)->format('m/Y') }}" class="form-control form-control-sm" placeholder="MM/YYYY">
            <button class="btn btn-outline-danger">Ir</button>
            <a href="{{ route('pagamentos.index', array_merge(request()->query(), ['month' => \Carbon\Carbon::parse($ref)->addMonth()->format('m/Y')])) }}" class="btn btn-outline-danger">Próximo mês &raquo;</a>
        </form>
    </div>
</div>

<div class="table-responsive">
    <div class="mb-3">
        <div class="card bg-dark text-light">
            <div class="card-body p-2">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="small">
                        <strong>Legenda:</strong>
                    </div>
                    <div class="small">✅ <span class="text-light">— Pago</span></div>
                    <div class="small">⚠️ <span class="text-danger">— Em atraso</span></div>
                    <div class="small">☑️ <span class="text-warning">— Pago parcialmente</span></div>
                    <div class="small">🔜 <span class="text-danger">— A vencer</span></div>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-dark table-striped table-hover align-middle">
        <thead class="table-primary text-dark">
            <tr>
                <th>Imóvel</th>
                <th>Locatário</th>
                <th>Valor devido</th>
                <th>Valor recebido</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagamentos as $p)
                <tr>
                    <td>{{ $p->aluguel->imovel->nome ?? '—' }}{{ isset($p->aluguel->imovel->numero) && $p->aluguel->imovel->numero ? ' (nº ' . $p->aluguel->imovel->numero . ')' : '' }}</td>
                    <td>{{ $p->aluguel->locatario->nome ?? '—' }}</td>
                    <td>R$ {{ number_format($p->valor_devido,2,',','.') }}</td>
                    <td>R$ {{ number_format($p->valor_recebido,2,',','.') }}</td>
                    @php
                        $dueDate = null;
                        try {
                            $referencia = \Carbon\Carbon::parse($p->referencia_mes);
                            if ($referencia->day !== 1) {
                                $dueDate = $referencia->copy();
                            } elseif (!empty($p->aluguel) && !empty($p->aluguel->data_inicio)) {
                                $refMonth = $referencia->copy()->startOfMonth();
                                $startDay = \Carbon\Carbon::parse($p->aluguel->data_inicio)->day;
                                $lastDay = $refMonth->copy()->endOfMonth()->day;
                                $day = min($startDay, $lastDay);
                                $dueDate = $refMonth->copy()->day($day);
                            } else {
                                $dueDate = $referencia->copy()->endOfMonth();
                            }
                        } catch (\Exception $e) {
                            $dueDate = null;
                        }
                        $isOverdue = $dueDate ? $dueDate->lt(\Carbon\Carbon::today()) : false;
                    @endphp
                    <td>{{ $dueDate ? $dueDate->format('d/m/Y') : '-' }}</td>
                    <td>
                        @if($p->status === 'paid')
                            <span class="text-center">✅</span>
                        @elseif($isOverdue && $p->status !== 'paid')
                            <span class="text-danger">⚠️</span>
                        @elseif($p->status === 'partial')
                            <span class="text-warning">☑️</span>
                        @else
                            <span class="text-success">🔜</span>
                        @endif
                    </td>
                    <td class="text-center d-flex gap-2 justify-content-center">
                        @if($p->status !== 'paid')
                        {{-- Full payment (existing) --}}
                        <form method="POST" action="{{ route('pagamentos.markPaid', $p) }}" class="d-inline" data-spinner>
                            @csrf
                            <input type="hidden" name="valor_recebido" value="{{ $p->valor_devido }}">
                            <button class="btn btn-sm btn-outline-danger">
                                <span class="btn-text">Marcar Pago</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>

                        {{-- Open modal to register partial or custom payment --}}
                        <button type="button"
                            class="btn btn-sm btn-outline-danger open-partial-btn"
                            data-action="{{ route('pagamentos.markPaid', $p) }}"
                            data-valor-devido="{{ number_format($p->valor_devido, 2, '.', '') }}"
                            data-aluguel="{{ $p->aluguel_id }}">
                            Parcial
                        </button>
                        @endif
                        @if(in_array($p->status, ['paid', 'partial']))
                        <form method="POST" action="{{ route('pagamentos.revert', $p) }}" class="d-inline" data-spinner>
                            @csrf
                            <button class="btn btn-sm btn-outline-danger">
                                <span class="btn-text">Desfazer</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-light">Nenhum pagamento cadastrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $pagamentos->links() }}
</div>

<div class="mt-4 d-flex justify-content-end">
    <form id="mark-all-form" method="POST" action="{{ route('pagamentos.markAll') }}" data-spinner>
        @csrf
        <input type="hidden" name="month" value="{{ \Carbon\Carbon::parse($ref)->format('Y-m-d') }}">
        @if(request()->query('aluguel_id'))
            <input type="hidden" name="aluguel_id" value="{{ request()->query('aluguel_id') }}">
        @endif
        <button class="btn btn-danger">
            <span class="btn-text">Marcar todos como pagos</span>
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
    </form>
</div>

@if(!empty($overdues))
    <div class="mt-4">
        <h5 class="text-warning">Inquilinos em atraso</h5>
        <div class="list-group list-group-flush">
            @foreach($overdues as $ov)
                <div class="list-group-item bg-dark text-light d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $ov['locatario']->nome }}</strong>
                        @if(!empty($ov['locatario']->telefone))
                            <div class="small text-warning">{{ $ov['locatario']->telefone }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        @foreach($ov['months'] as $m)
                            <span class="badge bg-danger ms-1">{{ $m }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection

@push('scripts-body')
<div class="modal fade" id="partialPaymentModal" tabindex="-1" aria-labelledby="partialPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="partialPaymentForm" method="POST" action="" data-spinner>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="partialPaymentModalLabel">Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valor_recebido_input" class="form-label">Valor recebido (R$)</label>
                        <input type="text" class="form-control" id="valor_recebido_input" name="valor_recebido" placeholder="0,00">
                        <div class="form-text">Use vírgula ou ponto como separador decimal.</div>
                    </div>
                    <div class="mb-3">
                        <label for="observacao_input" class="form-label">Observação (opcional)</label>
                        <textarea class="form-control" id="observacao_input" name="observacao" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success">
                        <span class="btn-text">Registrar</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@vite(['resources/ts/pagamentos-modal.ts', 'resources/ts/form-spinner.ts'])
@endpush
