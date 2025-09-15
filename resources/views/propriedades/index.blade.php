{{-- resources/views/propriedades/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestão de Propriedades')
@section('header', 'Gestão de Propriedades')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100">

        <!-- Botão Cadastrar -->
        <div class="mb-3 text-end">
            <a href="{{ route('propriedades.create') }}" class="btn btn-success">
                Cadastrar Nova Propriedade
            </a>
        </div>

        <!-- Tabela responsiva -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-dark align-middle">
                <thead class="table-warning text-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Endereço</th>
                        <th>Descrição</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($propriedades as $propriedade)
                        <tr>
                            <td>{{ $propriedade->nome }}</td>
                            <td>{{ $propriedade->endereco }}</td>
                            <td>{{ $propriedade->descricao }}</td>
                            <td class="text-center">
                                <a href="{{ route('propriedades.edit', $propriedade) }}" class="btn btn-sm btn-warning">
                                    Editar
                                </a>
                                <form action="{{ route('propriedades.destroy', $propriedade) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if($propriedades->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center text-light">Nenhuma propriedade cadastrada.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection