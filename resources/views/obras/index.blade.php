{{-- resources/views/obras/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Gestão de Obras e Manutenções')
@section('header', 'Gestão de Obras e Manutenções')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('obras.create') }}" class="btn btn-success">Nova Obra ou Manutenção</a>
</div>

@if(request()->filled('data_inicio_from') || request()->filled('data_inicio_to'))
    <div class="mb-3">
        <strong>Período:</strong>
        @if(request()->filled('data_inicio_from'))
            {{ \Illuminate\Support\Carbon::parse(request('data_inicio_from'))->format('d/m/Y') }}
        @else
            --
        @endif
        &nbsp;—&nbsp;
        @if(request()->filled('data_inicio_to'))
            {{ \Illuminate\Support\Carbon::parse(request('data_inicio_to'))->format('d/m/Y') }}
        @else
            --
        @endif
    </div>
@endif

<form method="GET" action="{{ route('obras.index') }}" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar por descrição" value="{{ request('q') }}">
    </div>
    <div class="col-auto">
        <select name="imovel_id" class="form-select form-select-sm">
            <option value="">Todos os imóveis</option>
            @foreach($imoveis as $i)
                <option value="{{ $i->id }}" @selected(request('imovel_id') == $i->id)>{{ $i->nome }}{{ isset($i->numero) && $i->numero ? ' (nº ' . $i->numero . ')' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto d-flex align-items-center">
        <label class="form-label mb-0 me-2 small">Início</label>
        <input type="date" name="data_inicio_from" value="{{ request('data_inicio_from') }}" class="form-control form-control-sm" style="max-width:160px;" placeholder="Data início">
    </div>
    <div class="col-auto d-flex align-items-center">
        <label class="form-label mb-0 me-2 small">Fim</label>
        <input type="date" name="data_inicio_to" value="{{ request('data_inicio_to') }}" class="form-control form-control-sm" style="max-width:160px;" placeholder="Data fim">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-danger">Filtrar</button>
        <a href="{{ route('obras.index') }}" class="btn btn-outline-danger">Limpar</a>
    </div>
</form>

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
                    <td>{{ $obra->data_fim ? \Carbon\Carbon::parse($obra->data_fim)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $obra->imovel->nome ?? 'N/D' }}{{ isset($obra->imovel->numero) && $obra->imovel->numero ? ' (nº ' . $obra->imovel->numero . ')' : '' }}</td>
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
    {{ $obras->appends(request()->query())->links() }}
</div>
@endsection