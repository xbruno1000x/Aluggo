{{-- resources/views/alugueis/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
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
                    <td>{{ $aluguel->imovel->nome ?? 'N/D' }}</td>
                    <td>{{ $aluguel->imovel->propriedade->nome ?? 'N/D' }}</td>
                    <td>{{ $aluguel->locatario->nome ?? 'N/D' }}</td>
                    <td>
                        {{ $aluguel->valor_mensal !== null ? 'R$ ' . number_format($aluguel->valor_mensal, 2, ',', '.') : 'N/A' }}
                    </td>
                    <td>{{ $aluguel->data_inicio ? \Carbon\Carbon::parse($aluguel->data_inicio)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $aluguel->data_fim ? \Carbon\Carbon::parse($aluguel->data_fim)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center d-flex gap-2 justify-content-center">
                        <a href="{{ route('alugueis.edit', $aluguel) }}" class="btn btn-sm btn-warning">Editar</a>

                        <form action="{{ route('alugueis.destroy', $aluguel) }}" method="POST" class="d-inline" data-confirm data-confirm-text="Deseja realmente excluir este contrato?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
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
@endsection