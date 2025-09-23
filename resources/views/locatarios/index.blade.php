{{-- resources/views/locatarios/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Gestão de Locatários')
@section('header', 'Gestão de Locatários')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('locatarios.create') }}" class="btn btn-success">Novo Locatário</a>
</div>

<div class="d-flex justify-content-center">
    <div class="w-100">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-dark align-middle">
                <thead class="table-primary text-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locatarios as $locatario)
                        <tr>
                            <td>{{ $locatario->nome }}</td>
                            <td>{{ $locatario->telefone }}</td>
                            <td>{{ $locatario->email }}</td>
                            <td class="d-flex gap-2">
                                <a href="{{ route('locatarios.edit', $locatario) }}" class="btn btn-sm btn-warning">
                                    Editar
                                </a>
                                <form action="{{ route('locatarios.destroy', $locatario) }}" 
                                      method="POST" 
                                      data-confirm 
                                      data-confirm-title="Confirmação"
                                      data-confirm-text="Deseja realmente excluir o locatário {{ $locatario->nome }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-light">Nenhum locatário cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection