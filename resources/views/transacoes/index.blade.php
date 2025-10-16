@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Transações')
@section('header', 'Transações')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-warning">@yield('header')</h2>
        <a href="{{ route('transacoes.create') }}" class="btn btn-success">Registrar Venda</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
            <thead class="table-primary text-dark">
                <tr>
                    <th>Imóvel</th>
                    <th>Valor da Venda</th>
                    <th>Data da Venda</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transacoes as $t)
                    <tr>
                        <td>{{ $t->imovel->nome ?? '-' }}{{ $t->imovel && $t->imovel->numero ? ' (nº ' . $t->imovel->numero . ')' : '' }}</td>
                        <td>R$ {{ number_format($t->valor_venda, 2, ',', '.') }}</td>
                        <td>{{ optional($t->data_venda) ? $t->data_venda->format('d/m/Y') : '' }}</td>
                            <td class="d-flex gap-2">
                            <a href="{{ route('transacoes.show', $t) }}" class="btn btn-sm btn-info btn-details">Detalhes</a>
                            <a href="{{ route('transacoes.edit', $t) }}" class="btn btn-sm btn-warning">Editar</a>
                            <form action="{{ route('transacoes.destroy', $t) }}" method="POST" data-confirm
                                  data-confirm-title="Confirmação"
                                  data-confirm-text="Deseja realmente excluir a transação #{{ $t->id }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $transacoes->links() }}

@push('scripts-body')
    @vite(['resources/ts/transacoes-details.ts'])
@endpush

    @if($transacoes->isEmpty())
        <p class="text-center text-light mt-4">Nenhuma transação registrada ainda.</p>
    @endif
@endsection
