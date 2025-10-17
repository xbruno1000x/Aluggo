@extends('layouts.app')

@section('title','Taxas')
@section('header','Taxas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('taxas.create') }}" class="btn btn-success">Nova Taxa</a>
</div>
@if(request()->filled('start') || request()->filled('end'))
    <div class="mb-3">
        <strong>Período:</strong>
        @if(request()->filled('start'))
            {{ 
                \Illuminate\Support\Carbon::parse(request('start'))->format('d/m/Y') 
            }}
        @else
            --
        @endif
        &nbsp;—&nbsp;
        @if(request()->filled('end'))
            {{ 
                \Illuminate\Support\Carbon::parse(request('end'))->format('d/m/Y') 
            }}
        @else
            --
        @endif
    </div>
@endif
<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="propriedade_id" class="form-select">
            <option value="">Todas as propriedades</option>
            @foreach($propriedades as $p)
                <option value="{{ $p->id }}" @selected(request('propriedade_id') == $p->id)>{{ $p->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="imovel_id" class="form-select">
            <option value="">Todos os imóveis</option>
            @foreach($imoveis as $i)
                <option value="{{ $i->id }}" @selected(request('imovel_id') == $i->id)>{{ $i->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <input type="text" name="tipo" value="{{ request('tipo') }}" class="form-control" placeholder="Tipo">
    </div>
    <div class="col-auto">
        <select name="pagador" class="form-select">
            <option value="">Qualquer pagador</option>
            <option value="proprietario" @selected(request('pagador')=='proprietario')>Proprietário</option>
            <option value="locatario" @selected(request('pagador')=='locatario')>Locatário</option>
        </select>
    </div>
    <div class="col-auto d-flex align-items-center">
        <label class="form-label mb-0 me-2 small">Início</label>
        <input type="date" name="start" value="{{ request('start') }}" class="form-control form-control-sm" style="max-width:160px;" placeholder="Data início">
    </div>
    <div class="col-auto d-flex align-items-center">
        <label class="form-label mb-0 me-2 small">Fim</label>
        <input type="date" name="end" value="{{ request('end') }}" class="form-control form-control-sm" style="max-width:160px;" placeholder="Data fim">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-danger">Filtrar</button>
        <a href="{{ route('taxas.index') }}" class="btn btn-outline-danger">Limpar</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-dark table-striped align-middle">
        <thead class="table-primary text-dark">
            <tr>
                <th>Vinculo</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Pagador</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($taxas as $t)
                <tr>
                        <td>
                            @if(!empty($t->propriedade))
                                {{ $t->propriedade->nome }} <small class="text-muted">(propriedade)</small>
                            @elseif(!empty($t->imovel))
                                {{ $t->imovel->nome }}
                            @else
                                -
                            @endif
                        </td>
                    <td>{{ ucfirst($t->tipo) }}</td>
                    <td>R$ {{ number_format($t->valor,2,',','.') }}</td>
                    <td>{{ optional($t->data_pagamento)?->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($t->pagador) }}</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('taxas.edit', $t) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('taxas.destroy', $t) }}" method="POST" data-confirm data-confirm-title="Confirmação" data-confirm-text="Excluir taxa?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center">
    {{ $taxas->links() }}
</div>
@endsection
