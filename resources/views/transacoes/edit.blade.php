@extends('layouts.app')

@section('content')
    <h1>Editar Transação</h1>
    <form action="{{ route('transacoes.update', $transacao) }}" method="post">
        @csrf
        @method('PUT')
        <label>Imóvel
            <select name="imovel_id">
                @foreach($imoveis as $imovel)
                    <option value="{{ $imovel->id }}" @if($imovel->id === $transacao->imovel_id) selected @endif>{{ $imovel->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>Valor
            <input type="text" name="valor_venda" value="{{ $transacao->valor_venda }}" />
        </label>
        <label>Data
            <input type="date" name="data_venda" value="{{ $transacao->data_venda->toDateString() }}" />
        </label>
        <button type="submit">Salvar</button>
    </form>
@endsection
