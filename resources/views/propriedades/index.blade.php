{{-- resources/views/propriedades/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Gestão de Propriedades')
@section('header', 'Gestão de Propriedades')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
</div>
<div class="d-flex justify-content-center">
    <div class="w-100">
        <thead class=>
        <!-- Tabela responsiva -->
        <div class="table-responsive">
            <table class="table table-striped table-hover table-dark align-middle">
                <thead class="table-primary text-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Endereço</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($propriedades as $propriedade)
                        <tr>
                            <td>{{ $propriedade->nome }}</td>
                            <td>{{ $propriedade->endereco }}</td>
                            <td>{{ $propriedade->descricao }}</td>
                            <td class="d-flex gap-2">
                                <a href="{{ route('propriedades.edit', $propriedade) }}" class="btn btn-sm btn-warning">
                                    Editar
                                </a>
                                <form action="{{ route('propriedades.destroy', $propriedade) }}" 
                                    method="POST" 
                                    data-confirm 
                                    data-confirm-title="Confirmação"
                                    data-confirm-text="Deseja realmente excluir a propriedade {{ $propriedade->nome }}? Essa ação irá excluir todos os imóveis associados a esta propriedade.">
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