{{-- resources/views/imoveis/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Imóvel')
@section('header', 'Editar Imóvel')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <form action="{{ route('imoveis.update', $imovel) }}" method="POST" class="row g-3" data-spinner>
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome', $imovel->nome) }}" required class="form-control">
            </div>

            <div class="col-12">
                <label for="numero" class="form-label">Número:</label>
                <input type="text" id="numero" name="numero" value="{{ old('numero', $imovel->numero) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" required class="form-select">
                    <option value="apartamento" {{ $imovel->tipo === 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                    <option value="terreno" {{ $imovel->tipo === 'terreno' ? 'selected' : '' }}>Terreno</option>
                    <option value="loja" {{ $imovel->tipo === 'loja' ? 'selected' : '' }}>Loja</option>
                    <option value="casa" {{ $imovel->tipo === 'casa' ? 'selected' : '' }}>Casa</option>
                    <option value="garagem" {{ $imovel->tipo === 'garagem' ? 'selected' : '' }}>Garagem</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Status:</label>
                <select id="status" name="status" required class="form-select">
                    <option value="disponível" {{ $imovel->status === 'disponível' ? 'selected' : '' }}>Disponível</option>
                    <option value="vendido" {{ $imovel->status === 'vendido' ? 'selected' : '' }}>Vendido</option>
                    <option value="alugado" {{ $imovel->status === 'alugado' ? 'selected' : '' }}>Alugado</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="valor_compra" class="form-label">Valor da Compra:</label>
                <input type="number" step="0.01" id="valor_compra" name="valor_compra" value="{{ old('valor_compra', $imovel->valor_compra) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label for="data_aquisicao" class="form-label">Data de Aquisição:</label>
                <input type="date" id="data_aquisicao" name="data_aquisicao" value="{{ old('data_aquisicao', $imovel->data_aquisicao) }}" class="form-control">
            </div>

            <!-- SELECT com botão que abre modal para criar nova Propriedade -->
            <div class="col-12">
                <label for="propriedade_id" class="form-label">Propriedade:</label>
                <div class="input-group">
                    <select id="propriedade_id" name="propriedade_id" required class="form-select">
                        @foreach ($propriedades as $propriedade)
                            <option value="{{ $propriedade->id }}" {{ $imovel->propriedade_id === $propriedade->id ? 'selected' : '' }}>
                                {{ $propriedade->nome }}
                            </option>
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
                    <span class="btn-text">Salvar Alterações</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
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
                               placeholder="Ex: Condominio das Flores">
                    </div>

                    <div class="mb-3">
                        <label for="p_endereco" class="form-label">Endereço</label>
                        <input type="text" id="p_endereco" name="endereco" class="form-control" required
                               placeholder="Ex: Rua das Palmeiras, 123">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="p_cep" class="form-label">CEP</label>
                            <input type="text" id="p_cep" name="cep" class="form-control"
                                   placeholder="00000-000">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="p_bairro" class="form-label">Bairro</label>
                            <input type="text" id="p_bairro" name="bairro" class="form-control"
                                   placeholder="Informe o bairro">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="p_cidade" class="form-label">Cidade</label>
                            <input type="text" id="p_cidade" name="cidade" class="form-control"
                                   placeholder="Nome da cidade">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="p_estado" class="form-label">Estado</label>
                            <input type="text" id="p_estado" name="estado" class="form-control"
                                   placeholder="UF" maxlength="2">
                        </div>
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

<!-- Script do modal -->
@vite(['resources/ts/propriedade-modal.ts', 'resources/ts/form-spinner.ts'])
@endsection