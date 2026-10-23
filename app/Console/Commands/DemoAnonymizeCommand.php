<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoAnonymizeCommand extends Command
{
    protected $signature = 'demo:anonymize {--force : Permite executar em produção} {--truncate-audit : Limpa audit_logs} {--mark-test : Marca usuários como usuario_teste}';

    protected $description = 'Anonimiza PII do banco ativo (para testes com dump real)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Bloqueado em produção. Use --force para confirmar.');

            return 1;
        }

        $anonPass = Hash::make(config('demo.anon_password', 'Teste@123'));

        DB::transaction(function () use ($anonPass) {
            $users = DB::table('users')->get();

            foreach ($users as $u) {
                // Mantém super_admin como usuário da plataforma, mas anonimiza também
                $cpf = $this->gerarCpf('9'.str_pad((string) $u->id, 8, '0', STR_PAD_LEFT));
                // garante 11 dígitos
                $cpf = substr(str_pad($cpf, 11, '0', STR_PAD_LEFT), 0, 11);

                DB::table('users')->where('id', $u->id)->update([
                    'nome' => 'Colaborador '.$u->id,
                    'email' => 'user'.$u->id.'@teste.local',
                    'cpf' => $cpf,
                    'telefone' => null,
                    'password' => $anonPass,
                    'usuario_teste' => (bool) $this->option('mark-test'),
                ]);
            }

            // Limpa conteúdo potencialmente sensível da rede social
            if (DB::getSchemaBuilder()->hasTable('social_posts')) {
                DB::table('social_posts')->update([
                    'caption' => DB::raw("CONCAT('Conteúdo de teste #', id)"),
                    'photo_path' => null,
                    'location' => null,
                ]);
            }

            if ($this->option('truncate-audit') && DB::getSchemaBuilder()->hasTable('audit_logs')) {
                DB::table('audit_logs')->truncate();
            }

            $this->info('Anonimização concluída para '.count($users).' usuários.');
        });

        try {
            Artisan::call('ranking:recalculate', [], $this->output);
        } catch (\Throwable $e) {
            $this->warn('ranking:recalculate falhou: '.$e->getMessage());
        }

        return 0;
    }

    private function gerarCpf(string $base9): string
    {
        $base9 = substr(str_pad($base9, 9, '0', STR_PAD_LEFT), 0, 9);
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
