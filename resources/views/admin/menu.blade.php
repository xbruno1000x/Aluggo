{{-- resources/views/admin/menu.blade.php --}}
@extends('layouts.app')

@section('title', 'Menu Principal')
@section('header', 'Menu Principal')

@section('content')
<div class="d-flex flex-column align-items-center">
    <div class="w-100" style="max-width: 1200px;">

        <h2 class="mb-4 text-warning text-center">Bem-vindo ao menu de gestão</h2>
        <p class="text-center">
            Escolha uma das opções no menu superior ou nos cards abaixo para começar a gerenciar seus investimentos imobiliários.
        </p>

        <div class="row mt-4 g-4">
            <!-- Gestão de Imóveis -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Gestão de Imóveis</h5>
                        <p class="card-text flex-grow-1">Acesse o cadastro e gestão de propriedades e imóveis.</p>
                        <a href="{{ route('propriedades.index') }}" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Gestão de Obras e Manutenções -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Gestão de Obras</h5>
                        <p class="card-text flex-grow-1">Controle obras e manutenções de imóveis.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Controle Financeiro -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Controle Financeiro</h5>
                        <p class="card-text flex-grow-1">Registre transações e acompanhe a saúde financeira.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Cadastro de Transações de Compra e Venda -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Transações</h5>
                        <p class="card-text flex-grow-1">Cadastre compras e vendas de imóveis.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Relatórios e Rentabilidade -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Relatórios</h5>
                        <p class="card-text flex-grow-1">Visualize relatórios e rentabilidade do seu portfólio.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Cadastro de Locatários -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Locatários</h5>
                        <p class="card-text flex-grow-1">Cadastre e gerencie os locatários dos imóveis.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Gestão de Contratos de Aluguel -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Contratos</h5>
                        <p class="card-text flex-grow-1">Gerencie contratos de aluguel ativos e históricos.</p>
                        <a href="#" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Configurações da Conta -->
            <div class="col-md-3">
                <div class="card bg-secondary text-light h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">Configurações</h5>
                        <p class="card-text flex-grow-1">Gerencie autenticação, perfil e preferências da conta.</p>
                        <a href="{{ route('account.settings') }}" class="btn btn-warning w-100 mt-auto">Acessar</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection