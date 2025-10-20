{{-- resources/views/imoveis/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cadastrar Novo Imóvel')
@section('header', 'Cadastrar Novo Imóvel')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 500px;">

        <!-- Título centralizado -->
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <!-- Formulário centralizado -->
        <form action="{{ route('imoveis.store') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" required class="form-control">
            </div>

            <div class="col-12">
                <label for="numero" class="form-label">Número:</label>
                <input type="text" id="numero" name="numero" class="form-control" placeholder="Ex: 101, Bloco A - 12">
            </div>

            <div class="col-12">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" required class="form-select">
                    <option value="apartamento">Apartamento</option>
                    <option value="terreno">Terreno</option>
                    <option value="loja">Loja</option>
                    <option value="casa">Casa</option>
                    <option value="garagem">Garagem</option>
                </select>
            </div>

            <div class="col-12">
                <label for="status" class="form-label">Status:</label>
                <select id="status" name="status" required class="form-select">
                    <option value="disponível">Disponível</option>
                    <option value="vendido">Vendido</option>
                    <option value="alugado">Alugado</option>
                </select>
            </div>

            <div class="col-12">
                <label for="valor_compra" class="form-label">Valor de Compra:</label>
                <input type="number" step="1000" min="0" id="valor_compra" name="valor_compra" class="form-control"
                       placeholder="Ex: 250000">
            </div>

            <div class="col-12">
                <label for="data_aquisicao" class="form-label">Data de Aquisição:</label>
                <input type="date" id="data_aquisicao" name="data_aquisicao" required class="form-control">
            </div>

            <!-- SELECT com botão que abre modal para criar nova Propriedade -->
            <div class="col-12">
                <label for="propriedade_id" class="form-label">Propriedade:</label>
                <div class="input-group">
                    <select id="propriedade_id" name="propriedade_id" required class="form-select">
                        @foreach($propriedades as $propriedade)
                        <option value="{{ $propriedade->id }}">{{ $propriedade->nome }}</option>
                        @endforeach
                    </select>
                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#propriedadeModal"
                        title="Adicionar Propriedade">
                        <span class="fw-bold">+</span>
                    </button>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100" id="btn-submit-imovel">
                    <span class="btn-text">Cadastrar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <!-- Botão Voltar -->
        <div class="mt-3 text-center">
            <a href="{{ route('imoveis.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>

    </div>
</div>

<!-- Modal: Criar Propriedade -->
<div class="modal fade" id="propriedadeModal" tabindex="-1" aria-labelledby="propriedadeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="propriedade-form" class="needs-validation" novalidate
                data-endpoint="{{ route('propriedades.store') }}"
                data-csrf="{{ csrf_token() }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="propriedadeModalLabel">Nova Propriedade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="propriedade-form-alert" class="alert d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="p_nome" class="form-label">Nome</label>
                        <input type="text" id="p_nome" name="nome" class="form-control" required
                               placeholder="Digite o nome da propriedade">
                    </div>

                    <div class="mb-3">
                        <label for="p_endereco" class="form-label">Endereço</label>
                        <input type="text" id="p_endereco" name="endereco" class="form-control" required
                               placeholder="Ex: Rua das Palmeiras, 123">
                    </div>

                    <div class="mb-3">
                        <label for="p_cep" class="form-label">CEP</label>
                        <input type="text" id="p_cep" name="cep" class="form-control"
                               placeholder="Ex: 28000-000">
                    </div>

                    <div class="mb-3">
                        <label for="p_cidade" class="form-label">Cidade</label>
                        <input type="text" id="p_cidade" name="cidade" class="form-control"
                               placeholder="Informe a cidade">
                    </div>

                    <div class="mb-3">
                        <label for="p_estado" class="form-label">Estado</label>
                        <input type="text" id="p_estado" name="estado" class="form-control"
                               placeholder="Ex: RJ">
                    </div>

                    <div class="mb-3">
                        <label for="p_bairro" class="form-label">Bairro</label>
                        <input type="text" id="p_bairro" name="bairro" class="form-control" required
                               placeholder="Informe o bairro">
                    </div>

                    <div class="mb-3">
                        <label for="p_descricao" class="form-label">Descrição</label>
                        <textarea id="p_descricao" name="descricao" rows="3" class="form-control"
                                  placeholder="Adicione uma breve descrição da propriedade"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn-submit-propriedade">
                        <span class="btn-text">Criar Propriedade</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@vite(['resources/ts/propriedade-modal.ts', 'resources/ts/imovel-form-submit.ts'])
@endsection