@extends('layouts.app')

@section('title', 'Relatórios Financeiros')
@section('header', 'Relatórios Financeiros')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-warning">@yield('header')</h2>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <label class="form-label">Imóvel (opcional)</label>
            <select name="imovel_id" class="form-select">
                <option value="">Todos</option>
                @foreach($imoveis as $im)
                    <option value="{{ $im->id }}" {{ ($imovelId && $imovelId == $im->id) ? 'selected' : '' }}>{{ $im->nome }}{{ $im->numero ? ' (nº '.$im->numero.')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Início</label>
            <input type="date" name="start" value="{{ $start }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Fim</label>
            <input type="date" name="end" value="{{ $end }}" class="form-control">
        </div>
        <div class="col-md-3 align-self-end">
            <button class="btn btn-outline-danger">Gerar Relatório</button>
        </div>
    </form>

    <div class="row mb-4">
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-3">
                <strong>Receita de Aluguéis</strong>
                <div class="fs-4">R$ {{ number_format($data['aggregates']['rentalIncome'] ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-3">
                <strong>Despesas de Obras</strong>
                <div class="fs-4">R$ {{ number_format($data['aggregates']['obraExpenses'] ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-3">
                <strong>Taxas (total)</strong>
                <div class="fs-4">R$ {{ number_format($data['aggregates']['taxasTotal'] ?? 0, 2, ',', '.') }}</div>
                @if(!empty($data['aggregates']['taxasByPagador']))
                    <small class="text-muted">por: </small>
                    <div class="d-flex gap-2 mt-2">
                        <small class="text-muted">Proprietário: R$ {{ number_format($data['aggregates']['taxasByPagador']['proprietario'] ?? 0, 2, ',', '.') }}</small>
                        <small class="text-muted">Locatário: R$ {{ number_format($data['aggregates']['taxasByPagador']['locatario'] ?? 0, 2, ',', '.') }}</small>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-3">
                <strong>Vendas</strong>
                <div class="fs-4">R$ {{ number_format($data['aggregates']['sales'] ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="card p-3">
                <strong>Saldo Líquido</strong>
                <div class="fs-4">R$ {{ number_format($data['aggregates']['net'] ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card border-secondary mb-4">
        <div class="card-body">
            <h5 class="card-title text-secondary">Filtros Dinâmicos</h5>
            <p class="text-muted small mb-3">Desmarque um item para excluir seus valores do gráfico e da tabela.</p>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-2">
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input js-relatorio-filter" type="checkbox" id="filterSales" data-key="monthSales">
                        <label class="form-check-label" for="filterSales">Incluir vendas</label>
                    </div>
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input js-relatorio-filter" type="checkbox" id="filterPurchases" data-key="monthPurchases">
                        <label class="form-check-label" for="filterPurchases">Incluir compras</label>
                    </div>
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input js-relatorio-filter" type="checkbox" id="filterRent" data-key="monthRent" checked>
                        <label class="form-check-label" for="filterRent">Incluir aluguéis</label>
                    </div>
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input js-relatorio-filter" type="checkbox" id="filterObra" data-key="monthObra" checked>
                        <label class="form-check-label" for="filterObra">Incluir obras</label>
                    </div>
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input js-relatorio-filter" type="checkbox" id="filterTax" data-key="monthTaxas" checked>
                        <label class="form-check-label" for="filterTax">Incluir taxas</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h4>Série Mensal</h4>
    <div class="mb-4">
        <canvas id="patrimonioChart" height="120" data-series='@json($data['series'] ?? [])'></canvas>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead class="table-primary text-dark">
                <tr>
                    <th>Mês</th>
                    <th>Vendas</th>
                    <th>Compras</th>
                    <th>Aluguéis</th>
                    <th>Obras</th>
                    <th>Taxas</th>
                    <th>Delta</th>
                    <th>Acumulado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['series'] ?? [] as $s)
                    <tr data-series-row>
                        <td data-field="label">{{ $s['label'] }}</td>
                        <td data-field="monthSales">R$ {{ number_format($s['monthSales'], 2, ',', '.') }}</td>
                        <td data-field="monthPurchases">R$ {{ number_format($s['monthPurchases'], 2, ',', '.') }}</td>
                        <td data-field="monthRent">R$ {{ number_format($s['monthRent'], 2, ',', '.') }}</td>
                        <td data-field="monthObra">R$ {{ number_format($s['monthObra'], 2, ',', '.') }}</td>
                        <td data-field="monthTaxas">R$ {{ number_format($s['monthTaxas'] ?? 0, 2, ',', '.') }}</td>
                        <td data-field="delta">R$ {{ number_format($s['delta'], 2, ',', '.') }}</td>
                        <td data-field="cumulative">R$ {{ number_format($s['cumulative'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@push('scripts-body')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/ts/relatorios-chart.ts'])
@endpush
</div>
@endsection
