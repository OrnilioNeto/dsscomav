<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\EmployeeEpi;
use App\Models\Role;
use App\Models\SocialComment;
use App\Models\SocialLike;
use App\Models\SocialPost;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserVacation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoMarketingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roleUsuario = Role::firstOrCreate(
                ['nome' => 'usuario'],
                ['descricao' => 'Usuário - Acesso a treinamentos conforme seu tipo']
            );

            $domain = config('demo.demo_email_domain', 'demo.plataformadss.local');
            $password = Hash::make(config('demo.demo_password', 'Demo@2026'));

            $pessoas = [
                ['nome' => 'Carlos Eduardo Silva', 'tipo' => 'motorista', 'setor' => 'Transporte', 'cargo' => 'Motorista', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Ana Paula Oliveira', 'tipo' => 'motorista', 'setor' => 'Transporte', 'cargo' => 'Motorista', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Marcos Vinícius Santos', 'tipo' => 'motorista_monitor', 'setor' => 'Transporte', 'cargo' => 'Motorista Monitor', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Fernanda Costa Lima', 'tipo' => 'motorista', 'setor' => 'Transporte', 'cargo' => 'Motorista', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Rafael Souza Martins', 'tipo' => 'motorista', 'setor' => 'Transporte', 'cargo' => 'Motorista', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Juliana Alves Pereira', 'tipo' => 'funcionario', 'setor' => 'Operacional', 'cargo' => 'Analista', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Bruno Henrique Rocha', 'tipo' => 'funcionario', 'setor' => 'Manutenção', 'cargo' => 'Técnico', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Patrícia Gomes Ferreira', 'tipo' => 'funcionario', 'setor' => 'Qualidade', 'cargo' => 'Inspetora', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Diego Barbosa Nunes', 'tipo' => 'funcionario', 'setor' => 'Logística', 'cargo' => 'Auxiliar', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Larissa Mendes Araújo', 'tipo' => 'funcionario', 'setor' => 'RH', 'cargo' => 'Assistente', 'empresa' => 'DSS Soluções'],
                ['nome' => 'Thiago Ribeiro Campos', 'tipo' => 'terceirizado', 'setor' => 'Serviços', 'cargo' => 'Operador', 'empresa' => 'Empresa X'],
                ['nome' => 'Camila Duarte Freitas', 'tipo' => 'terceirizado', 'setor' => 'Limpeza', 'cargo' => 'Auxiliar', 'empresa' => 'Empresa X'],
                ['nome' => 'Gustavo Pinto Carvalho', 'tipo' => 'terceirizado', 'setor' => 'Segurança', 'cargo' => 'Vigilante', 'empresa' => 'Empresa Y'],
                ['nome' => 'Beatriz Nogueira Teixeira', 'tipo' => 'funcionario', 'setor' => 'Administrativo', 'cargo' => 'Coordenadora', 'empresa' => 'DSS Soluções'],
            ];

            $telefones = ['(11) 98765-4321', '(11) 98765-4322', '(11) 98765-4323', '(11) 98765-4324', '(11) 98765-4325', '(21) 99876-5432', '(31) 98765-1234'];

            $usuarios = [];
            foreach ($pessoas as $i => $p) {
                $base = '9000000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                $cpf = $this->gerarCpf($base);

                $email = strtolower(str_replace(' ', '.', $p['nome'])).'@'.$domain;

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'nome' => $p['nome'],
                        'cpf' => $cpf,
                        'password' => $password,
                        'telefone' => $telefones[$i % count($telefones)],
                        'data_nascimento' => now()->subYears(rand(22, 45))->subDays(rand(0, 365))->toDateString(),
                        'tipo_usuario' => $p['tipo'],
                        'status' => 'ativo',
                        'role_id' => $roleUsuario->id,
                        'setor' => $p['setor'],
                        'cargo' => $p['cargo'],
                        'empresa' => $p['empresa'],
                        'participa_treinamentos' => true,
                        'usuario_teste' => false,
                    ]
                );

                $usuarios[] = $user;
            }

            // Treinamentos existentes
            $trainings = Training::all();

            // Atribuições + progresso + certificados
            foreach ($usuarios as $user) {
                foreach ($trainings as $training) {
                    $permitidos = is_array($training->tipo_usuario_permitido)
                        ? $training->tipo_usuario_permitido
                        : (json_decode($training->tipo_usuario_permitido, true) ?? []);

                    if (! in_array('todos', $permitidos, true) && ! in_array($user->tipo_usuario, $permitidos, true)) {
                        // motorista_monitor equivale a motorista
                        if (! ($user->tipo_usuario === 'motorista_monitor' && in_array('motorista', $permitidos, true))) {
                            continue;
                        }
                    }

                    // Atribuição (necessária para treinamentos direcionados)
                    DB::table('training_assignments')->insertOrIgnore([
                        'training_id' => $training->id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Sorteia status: 30% não iniciado, 30% em andamento, 40% concluído
                    $r = rand(1, 100);
                    if ($r <= 30) {
                        continue;
                    }

                    $dataInicio = now()->subDays(rand(1, 45))->subHours(rand(0, 12));

                    if ($r <= 60) {
                        // em andamento
                        UserProgress::updateOrCreate(
                            ['user_id' => $user->id, 'training_id' => $training->id],
                            [
                                'data_inicio' => $dataInicio,
                                'tempo_assistido' => rand(60, 600),
                                'data_conclusao' => null,
                                'concluido' => false,
                                'porcentagem_assistida' => rand(30, 85),
                                'avaliacao_aprovada' => false,
                                'avaliacao_tentativas' => 0,
                            ]
                        );

                        continue;
                    }

                    // concluído
                    $dataConclusao = (clone $dataInicio)->addHours(rand(1, 6));
                    $tentativas = rand(1, 3);
                    $aprovado = $tentativas <= 2;

                    $progress = UserProgress::updateOrCreate(
                        ['user_id' => $user->id, 'training_id' => $training->id],
                        [
                            'data_inicio' => $dataInicio,
                            'tempo_assistido' => rand(900, 3600),
                            'data_conclusao' => $dataConclusao,
                            'concluido' => true,
                            'porcentagem_assistida' => 100,
                            'avaliacao_aprovada' => $aprovado,
                            'avaliacao_tentativas' => $tentativas,
                            'avaliacao_resposta_usuario' => (int) $training->avaliacao_resposta_correta,
                            'avaliacao_nota' => $aprovado ? 100 : 60,
                        ]
                    );

                    // Certificado
                    $codigo = strtoupper(substr(md5($user->id.$training->id.mt_rand()), 0, 12));
                    while (Certificate::where('codigo_certificado', $codigo)->exists()) {
                        $codigo = strtoupper(substr(md5($user->id.$training->id.mt_rand()), 0, 12));
                    }

                    Certificate::withoutEvents(function () use ($user, $training, $codigo, $dataConclusao, $dataInicio) {
                        Certificate::firstOrCreate(
                            ['user_id' => $user->id, 'training_id' => $training->id],
                            [
                                'codigo_certificado' => $codigo,
                                'data_emissao' => $dataConclusao,
                                'data_inicio_assistencia' => $dataInicio,
                                'data_finalizacao_assistencia' => $dataConclusao,
                                'tempo_assistido_segundos' => rand(900, 3600),
                                'porcentagem_assistida' => 100,
                                'foi_reassistido' => false,
                                'valido' => true,
                                'template_version' => 2,
                            ]
                        );
                    });
                }
            }

            // EPIs por colaborador
            $epis = [
                ['nome' => 'Capacete de Segurança', 'ca' => 'CA 12345'],
                ['nome' => 'Luvas de Proteção', 'ca' => 'CA 23456'],
                ['nome' => 'Óculos de Proteção', 'ca' => 'CA 34567'],
                ['nome' => 'Bota de Segurança', 'ca' => 'CA 45678'],
                ['nome' => 'Cinto de Segurança', 'ca' => 'CA 56789'],
            ];

            foreach ($usuarios as $user) {
                $qtd = rand(2, 3);
                for ($k = 0; $k < $qtd; $k++) {
                    $e = $epis[array_rand($epis)];
                    EmployeeEpi::create([
                        'user_id' => $user->id,
                        'nome' => $e['nome'],
                        'ca' => $e['ca'],
                        'quantidade' => 1,
                        'data_entrega' => now()->subDays(rand(1, 90))->toDateString(),
                        'observacoes' => 'Entrega de demonstração',
                    ]);
                }
            }

            // Férias para alguns
            foreach (array_slice($usuarios, 0, 3) as $user) {
                $inicio = now()->addDays(rand(5, 20))->toDateString();
                $fim = now()->addDays(rand(25, 40))->toDateString();
                $user->update(['ferias_inicio' => $inicio, 'ferias_fim' => $fim]);
                UserVacation::firstOrCreate([
                    'user_id' => $user->id,
                    'data_inicio' => $inicio,
                    'data_fim' => $fim,
                ]);
            }

            // Rede social
            $postsData = [
                ['caption' => 'Treinamento concluído com sucesso! #segurança', 'training' => true],
                ['caption' => 'Equipe reunida para o DSS da semana.', 'training' => false],
                ['caption' => 'Uso correto dos EPIs salva vidas.', 'training' => false],
                ['caption' => 'Orgulho da equipe DSS!', 'training' => false],
                ['caption' => 'Mais um certificado conquistado.', 'training' => true],
                ['caption' => 'Segurança em primeiro lugar.', 'training' => false],
            ];

            $posts = [];
            foreach ($postsData as $pd) {
                $autor = $usuarios[array_rand($usuarios)];
                $trainingId = null;
                if ($pd['training']) {
                    $t = $trainings->first();
                    $trainingId = $t ? $t->id : null;
                }
                $post = SocialPost::create([
                    'user_id' => $autor->id,
                    'caption' => $pd['caption'],
                    'location' => 'Sede',
                    'training_id' => $trainingId,
                    'training_score' => $trainingId ? rand(80, 100) : null,
                    'ranking_position' => $trainingId ? rand(1, 5) : null,
                ]);
                $posts[] = $post;
            }

            foreach ($posts as $post) {
                // likes
                foreach (array_rand($usuarios, min(5, count($usuarios))) as $idx) {
                    $u = $usuarios[$idx];
                    SocialLike::firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $u->id,
                    ]);
                }
                // comments
                for ($c = 0; $c < rand(1, 3); $c++) {
                    $autor = $usuarios[array_rand($usuarios)];
                    SocialComment::create([
                        'post_id' => $post->id,
                        'user_id' => $autor->id,
                        'content' => 'Parabéns! Excelente iniciativa.',
                    ]);
                }
            }

            // follows
            foreach ($usuarios as $u) {
                $alvo = $usuarios[array_rand($usuarios)];
                if ($alvo->id !== $u->id) {
                    DB::table('social_follows')->insertOrIgnore([
                        'follower_id' => $u->id,
                        'following_id' => $alvo->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    private function gerarCpf(string $base9): string
    {
        $digits = array_map('intval', str_split($base9));

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $digits[$i] * (10 - $i);
        }
        $rest = $sum % 11;
        $dv1 = $rest < 2 ? 0 : 11 - $rest;
        $digits[] = $dv1;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $digits[$i] * (11 - $i);
        }
        $rest = $sum % 11;
        $dv2 = $rest < 2 ? 0 : 11 - $rest;

        return $base9.$dv1.$dv2;
    }
}
