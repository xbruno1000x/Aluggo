{{-- resources/views/obras/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Gestão de Obras')
@section('header', 'Gestão de Obras')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('obras.create') }}" class="btn btn-success">Nova Obra</a>
</div>

<div class="table-responsive">
    <table class="table table-dark table-striped table-hover align-middle">
        <thead class="table-primary text-dark">
            <tr>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Imóvel</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($obras as $obra)
                <tr>
                    <td>{{ Str::limit($obra->descricao, 80) }}</td>
                    <td>R$ {{ number_format($obra->valor, 2, ',', '.') }}</td>
                    <td>{{ $obra->data_inicio ? \Carbon\Carbon::parse($obra->data_inicio)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $obra->data_fim ? \Carbon\Carbon::parse($obra->data_fim)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $obra->imovel->nome ?? 'N/D' }}</td>
                    <td class="text-center d-flex gap-2 justify-content-center">
                        <a href="{{ route('obras.edit', $obra) }}" class="btn btn-sm btn-warning">Editar</a>

                        <form action="{{ route('obras.destroy', $obra) }}" method="POST" class="d-inline" data-confirm data-confirm-text="Deseja realmente excluir esta obra?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-light">Nenhuma obra cadastrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $obras->links() }}
</div>
@endsection