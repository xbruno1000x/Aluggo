<?php

namespace Tests\Feature\Model;

use Tests\TestCase;
use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AluguelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_store_creates_aluguel_and_sets_imovel_status_to_alugado_if_active_today()
    {
        $imovel = Imovel::factory()->create(['status' => 'disponivel']);
        $locatario = Locatario::factory()->create();

        $data = [
            'imovel_id' => $imovel->id,
            'locatario_id' => $locatario->id,
            'valor_mensal' => 1500,
            'data_inicio' => Carbon::today()->toDateString(),
            'data_fim' => Carbon::today()->addMonth()->toDateString(),
        ];

        $response = $this->post(route('alugueis.store'), $data);

        $response->assertRedirect(route('alugueis.index'));
        $this->assertDatabaseHas('alugueis', [
            'imovel_id' => $imovel->id,
            'locatario_id' => $locatario->id,
            'valor_mensal' => 1500,
        ]);

        $imovel->refresh();
        $this->assertEquals('alugado', $imovel->status);
    }

    public function test_store_allows_non_active_contracts_without_changing_status()
    {
        $imovel = Imovel::factory()->create(['status' => 'disponivel']);
        $locatario = Locatario::factory()->create();

        $data = [
            'imovel_id' => $imovel->id,
            'locatario_id' => $locatario->id,
            'valor_mensal' => 1000,
            'data_inicio' => Carbon::today()->addDays(10)->toDateString(),
            'data_fim' => Carbon::today()->addDays(20)->toDateString(),
        ];

        $this->post(route('alugueis.store'), $data)->assertRedirect(route('alugueis.index'));

        $imovel->refresh();
        $this->assertEquals('disponivel', $imovel->status);
    }
}