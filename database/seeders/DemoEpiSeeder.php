<?php

namespace Database\Seeders;

use App\Models\Epi;
use App\Models\EpiColaborador;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoEpiSeeder extends Seeder
{
    public function run(): void
    {
        // Garante colaboradores a partir dos usuários
        foreach (User::all() as $u) {
            $cpf = preg_replace('/\D/', '', (string) $u->cpf);
            if ($cpf === '') {
                continue;
            }
            $exists = DB::table('ss_colaborador')->where('ss_c_tx_cpf', $cpf)->exists();
            if (! $exists) {
                DB::table('ss_colaborador')->insert([
                    'ss_c_tx_nome' => $u->nome,
                    'ss_c_tx_cpf' => $cpf,
                    'ss_c_tx_matricula' => 'MAT-'.str_pad($u->id, 5, '0', STR_PAD_LEFT),
                    'ss_c_tx_cargo' => $u->cargo ?: 'Funcionário',
                    'ss_c_tx_status' => $u->status ?: 'ativo',
                    'ss_c_nb_empresa_id' => 0,
                    'tenant_id' => null,
                ]);
            }
        }

        // Catálogo de EPIs
        $epis = [];
        $catalogo = [
            ['grupo' => 'Proteção da Cabeça', 'sub' => 'Capacetes', 'item' => 'Capacete de Segurança', 'ca' => 'CA 12345', 'vida' => 365],
            ['grupo' => 'Proteção das Mãos', 'sub' => 'Luvas', 'item' => 'Luva de Raspa', 'ca' => 'CA 23456', 'vida' => 180],
            ['grupo' => 'Proteção dos Olhos', 'sub' => 'Óculos', 'item' => 'Óculos de Segurança', 'ca' => 'CA 34567', 'vida' => 365],
            ['grupo' => 'Proteção dos Pés', 'sub' => 'Botas', 'item' => 'Bota de Segurança', 'ca' => 'CA 45678', 'vida' => 365],
        ];

        foreach ($catalogo as $c) {
            $epis[] = Epi::firstOrCreate(
                ['ss_e_tx_item' => $c['item']],
                [
                    'ss_e_tx_grupo' => $c['grupo'],
                    'ss_e_tx_subgrupo' => $c['sub'],
                    'ss_e_tx_descricao' => $c['item'].' - demonstração',
                    'ss_e_tx_fabricante' => '3M',
                    'ss_e_tx_ca' => $c['ca'],
                    'ss_e_tx_validade_ca' => now()->addMonths(12)->toDateString(),
                    'ss_e_nb_vida_util_dias' => $c['vida'],
                    'ss_e_tx_status' => 'ativo',
                    'ss_e_tx_cadastro_tipo' => 'manual',
                    'ss_e_nb_userCadastro' => 1,
                    'ss_e_tx_dataCadastro' => now()->toDateString(),
                ]
            );
        }

        // Estoque inicial
        foreach ($epis as $epi) {
            EpiEstoque::firstOrCreate(
                ['ss_e_nb_epi_id' => $epi->ss_e_nb_id, 'ss_e_tx_tipo' => 'entrada'],
                [
                    'ss_e_nb_empresa_id' => 0,
                    'ss_e_nb_quantidade' => 50,
                    'ss_e_db_valor_unitario' => 45.90,
                    'ss_e_db_valor_total' => 2295.00,
                    'ss_e_tx_data_recebimento' => now()->subDays(30)->toDateString(),
                    'ss_e_tx_data' => now()->subDays(30)->toDateString(),
                    'ss_e_nb_userCadastro' => 1,
                    'ss_e_nb_variacao_id' => null,
                ]
            );
        }

        // Entregas para os primeiros colaboradores
        $colaboradores = EpiColaborador::orderBy('ss_c_nb_id')->take(6)->get();
        foreach ($colaboradores as $i => $colab) {
            $epi = $epis[$i % count($epis)];
            EpiEntrega::create([
                'ss_e_nb_colaborador_id' => $colab->ss_c_nb_id,
                'ss_e_nb_epi_id' => $epi->ss_e_nb_id,
                'ss_e_nb_empresa_id' => 0,
                'ss_e_tx_data_entrega' => now()->subDays(rand(1, 20))->toDateString(),
                'ss_e_nb_quantidade' => 1,
                'ss_e_tx_vencimento' => now()->addDays($epi->ss_e_nb_vida_util_dias)->toDateString(),
                'ss_e_tx_status' => 'ativo',
                'ss_e_tx_requer_assinatura' => true,
                'ss_e_tx_status_assinatura' => (rand(0, 1) ? 'assinado' : 'pendente'),
                'ss_e_nb_userCadastro' => 1,
                'ss_e_tx_dataCadastro' => now()->toDateString(),
                'ss_e_nb_variacao_id' => null,
                'ss_e_tx_grupo_assinatura' => null,
            ]);
        }
    }
}
