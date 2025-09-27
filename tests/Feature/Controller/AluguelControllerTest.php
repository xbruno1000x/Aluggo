<?php

namespace Tests\Feature\Controller;

use Tests\TestCase;
use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AluguelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_store_rejects_overlapping_contracts()
    {
        $imovel = Imovel::factory()->create(['status' => 'disponível']);
        $loc1 = Locatario::factory()->create();
        $loc2 = Locatario::factory()->create();

        // contrato existente: 2025-10-22 a 2025-10-28
        Aluguel::factory()->create([
            'imovel_id' => $imovel->id,
            'locatario_id' => $loc1->id,
            'data_inicio' => '2025-10-22',
            'data_fim' => '2025-10-28',
            'valor_mensal' => 1000,
        ]);

        // tentativa sobreposta: 2025-10-24 a 2025-10-27 (deve falhar)
        $payload = [
            'imovel_id' => $imovel->id,
            'locatario_id' => $loc2->id,
            'valor_mensal' => 1100,
            'data_inicio' => '2025-10-24',
            'data_fim' => '2025-10-27',
        ];

        $response = $this->post(route('alugueis.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('imovel_id');

        // garantir que não foi criado
        $this->assertDatabaseCount('alugueis', 1);
    }

    /*public function test_destroy_deletes_and_updates_imovel_status_when_no_active_contracts()
    {
        $imovel = Imovel::factory()->create(['status' => 'disponível']);
        $loc = Locatario::factory()->create();

        $aluguel = Aluguel::factory()->create([
            'imovel_id' => $imovel->id,
            'locatario_id' => $loc->id,
            'data_inicio' => Carbon::today()->subDays(1)->toDateString(),
            'data_fim' => Carbon::today()->addDays(1)->toDateString(),
            'valor_mensal' => 900,
        ]);

        // garantir que está alugado antes de excluir (simulando que store teria feito isso)
        $imovel->update(['status' => 'alugado']);
        $this->assertEquals('alugado', $imovel->fresh()->status);

        $response = $this->delete(route('alugueis.destroy', $aluguel));

        $response->assertRedirect(route('alugueis.index'));
        $this->assertDatabaseMissing('alugueis', ['id' => $aluguel->id]);

        $imovel->refresh();
        $this->assertEquals('disponivel', $imovel->status);
    }*/

    public function test_index_returns_view_with_alugueis()
    {
        Aluguel::factory()->count(3)->create();

        $response = $this->get(route('alugueis.index'));

        $response->assertStatus(200);
        $response->assertViewIs('alugueis.index');
        $response->assertViewHas('alugueis');
    }
}