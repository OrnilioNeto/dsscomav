<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Permite executar em produção}';

    protected $description = 'Remove apenas os dados demo (usuários @demo.plataformadss.local)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Bloqueado em produção. Use --force para confirmar.');

            return 1;
        }

        $domain = config('demo.demo_email_domain', 'demo.plataformadss.local');

        DB::transaction(function () use ($domain) {
            $userIds = DB::table('users')
                ->where('email', 'like', '%@'.$domain)
                ->pluck('id')
                ->all();

            if (empty($userIds)) {
                $this->info('Nenhum usuário demo encontrado.');

                return;
            }

            DB::table('user_progress')->whereIn('user_id', $userIds)->delete();
            DB::table('certificates')->whereIn('user_id', $userIds)->delete();
            DB::table('employee_epis')->whereIn('user_id', $userIds)->delete();
            DB::table('employee_trainings')->whereIn('user_id', $userIds)->delete();
            DB::table('user_vacations')->whereIn('user_id', $userIds)->delete();
            DB::table('training_assignments')->whereIn('user_id', $userIds)->delete();

            $postIds = DB::table('social_posts')->whereIn('user_id', $userIds)->pluck('id')->all();
            if (! empty($postIds)) {
                DB::table('social_likes')->whereIn('post_id', $postIds)->delete();
                DB::table('social_comments')->whereIn('post_id', $postIds)->delete();
                DB::table('social_posts')->whereIn('id', $postIds)->delete();
            }

            DB::table('social_follows')->whereIn('follower_id', $userIds)->orWhereIn('following_id', $userIds)->delete();

            DB::table('users')->whereIn('id', $userIds)->delete();
            $this->info(count($userIds).' usuários demo removidos.');
        });

        try {
            Artisan::call('ranking:recalculate', [], $this->output);
        } catch (\Throwable $e) {
            $this->warn('ranking:recalculate falhou: '.$e->getMessage());
        }

        return 0;
    }
}
