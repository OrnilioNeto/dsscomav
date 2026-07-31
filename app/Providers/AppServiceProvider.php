<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\Certificate;
use App\Observers\CertificateObserver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->autoMigrate();
        $this->ensureUserOperationalColumns();
        $this->ensureRankingTablesExist();
        $this->seedDefaultRankingCriteria();
        // Registrar observer para Certificate para acionar recálculo do ranking
        try {
            if (class_exists(Certificate::class)) {
                Certificate::observe(CertificateObserver::class);
            }
        } catch (\Throwable $e) {
            logger()->warning('Falha ao registrar CertificateObserver: ' . $e->getMessage());
        }
    }

    private function ensureUserOperationalColumns(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            Schema::table('users', function ($table) {
                if (! Schema::hasColumn('users', 'ferias_inicio')) {
                    $table->date('ferias_inicio')->nullable()->after('responsavel');
                }

                if (! Schema::hasColumn('users', 'ferias_fim')) {
                    $table->date('ferias_fim')->nullable()->after('ferias_inicio');
                }

                if (! Schema::hasColumn('users', 'usuario_teste')) {
                    $table->boolean('usuario_teste')->default(false)->after('ferias_fim');
                }

                // Campos de fardamento (controle de EPIs/uniformes)
                if (! Schema::hasColumn('users', 'camisa_tamanho')) {
                    $table->string('camisa_tamanho', 20)->nullable()->after('cargo');
                }

                if (! Schema::hasColumn('users', 'calca_tamanho')) {
                    $table->string('calca_tamanho', 20)->nullable()->after('camisa_tamanho');
                }

                if (! Schema::hasColumn('users', 'bota_numero')) {
                    $table->string('bota_numero', 20)->nullable()->after('calca_tamanho');
                }
            });
        } catch (\Throwable $e) {
            logger()->warning('Falha ao garantir colunas operacionais de users: ' . $e->getMessage());
        }
    }

    private function ensureRankingTablesExist(): void
    {
        try {
            // 1. Tabela de Configurações
            if (!Schema::hasTable('ranking_settings')) {
                Schema::create('ranking_settings', function ($table) {
                    $table->id();
                    $table->boolean('is_active')->default(true);
                    $table->string('default_period')->default('monthly');
                    $table->timestamps();
                });
            }

            // 2. Tabela de Critérios (Velocidade, Foco, etc.)
            if (!Schema::hasTable('ranking_criteria')) {
                Schema::create('ranking_criteria', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->text('description')->nullable();
                    $table->integer('sort_order')->default(0);
                    $table->timestamps();
                });
            }

            // 3. Tabela de Regras de Pontuação (Faixas de valores)
            if (!Schema::hasTable('ranking_rules')) {
                Schema::create('ranking_rules', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('criterion_id');
                    $table->string('label');
                    $table->double('min_value')->nullable();
                    $table->double('max_value')->nullable();
                    $table->double('points')->default(0);
                    $table->integer('sort_order')->default(0);
                    $table->timestamps();
                    $table->foreign('criterion_id')->references('id')->on('ranking_criteria')->onDelete('cascade');
                });
            }

            // 4. Tabela de Scores Individuais (por conteúdo)
            if (!Schema::hasTable('ranking_scores')) {
                Schema::create('ranking_scores', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('training_id')->nullable();
                    $table->unsignedBigInteger('content_id')->nullable();
                    $table->integer('month_reference');
                    $table->integer('year_reference');
                    $table->double('raw_score')->default(0);
                    $table->double('max_possible_score')->default(0);
                    $table->double('normalized_score')->default(0);
                    $table->timestamp('calculated_at')->nullable();
                    $table->timestamps();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            }

            // 5. Tabela de Ranking Consolidado Mensal
            if (!Schema::hasTable('ranking_monthly_scores')) {
                Schema::create('ranking_monthly_scores', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->integer('month_reference');
                    $table->integer('year_reference');
                    $table->double('average_score')->default(0);
                    $table->integer('position')->nullable();
                    $table->timestamps();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            }
        } catch (\Throwable $e) {
            logger()->error('Falha ao garantir schema de ranking: ' . $e->getMessage());
        }
    }

    /**
     * Popula os critérios básicos de ranking automaticamente se estiverem vazios.
     */
    private function seedDefaultRankingCriteria(): void
    {
        try {
            if (!Schema::hasTable('ranking_criteria') || DB::table('ranking_criteria')->count() > 0) {
                return;
            }

            // Inserir critérios básicos
            $criteria = [
                ['name' => 'Velocidade de Início', 'slug' => 'start_time', 'description' => 'Horas decorridas entre a liberação e o início do conteúdo', 'sort_order' => 1],
                ['name' => 'Tempo de Conclusão', 'slug' => 'completion_time', 'description' => 'Dias decorridos entre o início e a finalização', 'sort_order' => 2],
                ['name' => 'Resultado da Avaliação', 'slug' => 'quiz_result', 'description' => 'Número de tentativas para aprovação', 'sort_order' => 3],
            ];

            foreach ($criteria as $criterion) {
                $id = DB::table('ranking_criteria')->insertGetId(array_merge($criterion, ['created_at' => now(), 'updated_at' => now()]));

                // Inserir regras padrão para cada critério
                if ($criterion['slug'] === 'start_time') {
                    DB::table('ranking_rules')->insert([
                        ['criterion_id' => $id, 'label' => 'Pioneiro (até 24h)', 'min_value' => 0, 'max_value' => 24, 'points' => 100, 'sort_order' => 1],
                        ['criterion_id' => $id, 'label' => 'Rápido (até 48h)', 'min_value' => 24.1, 'max_value' => 48, 'points' => 50, 'sort_order' => 2],
                        ['criterion_id' => $id, 'label' => 'Normal (após 48h)', 'min_value' => 48.1, 'max_value' => 9999, 'points' => 10, 'sort_order' => 3],
                    ]);
                }

                if ($criterion['slug'] === 'completion_time') {
                    DB::table('ranking_rules')->insert([
                        ['criterion_id' => $id, 'label' => 'Foco Total (mesmo dia)', 'min_value' => 0, 'max_value' => 0, 'points' => 50, 'sort_order' => 1],
                        ['criterion_id' => $id, 'label' => 'Intermediário (até 3 dias)', 'min_value' => 1, 'max_value' => 3, 'points' => 30, 'sort_order' => 2],
                        ['criterion_id' => $id, 'label' => 'Lento (mais de 3 dias)', 'min_value' => 3.1, 'max_value' => 999, 'points' => 5, 'sort_order' => 3],
                    ]);
                }

                if ($criterion['slug'] === 'quiz_result') {
                    DB::table('ranking_rules')->insert([
                        ['criterion_id' => $id, 'label' => 'Excelente (1ª tentativa)', 'min_value' => 1, 'max_value' => 1, 'points' => 100, 'sort_order' => 1],
                        ['criterion_id' => $id, 'label' => 'Bom (2ª tentativa)', 'min_value' => 2, 'max_value' => 2, 'points' => 50, 'sort_order' => 2],
                        ['criterion_id' => $id, 'label' => 'Recuperação (3+ tentativas)', 'min_value' => 3, 'max_value' => 99, 'points' => 20, 'sort_order' => 3],
                    ]);
                }
            }

            logger()->info('Dados mestres de ranking semeados automaticamente.');
        } catch (\Throwable $e) {
            logger()->warning('Falha ao semear critérios de ranking: ' . $e->getMessage());
        }
    }

    /**
     * Executa as migrations pendentes de forma automática ao carregar as páginas,
     * detectando mudanças na pasta de migrations e otimizando a checagem via Cache.
     */
    private function autoMigrate(): void
    {
        // Se estiver rodando via linha de comando, evita loops recursivos de Artisan::call
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $migrationsPath = database_path('migrations');
            $files = glob($migrationsPath . '/*.php');
            if (empty($files)) {
                return;
            }

            // Gera um hash único baseado no nome e na data de modificação dos arquivos
            $hashingSource = '';
            foreach ($files as $file) {
                $hashingSource .= basename($file) . filemtime($file);
            }
            $hash = md5($hashingSource);

            // Executa as migrations se houver alguma alteração ou novos arquivos detectados
            if (\Illuminate\Support\Facades\Cache::get('db_migrations_hash') !== $hash) {
                // Roda Artisan migrate --force programaticamente
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                
                // Grava o novo hash no cache indefinidamente
                \Illuminate\Support\Facades\Cache::forever('db_migrations_hash', $hash);
                
                logger()->info('Auto-migration executada com sucesso.');
            }
        } catch (\Throwable $e) {
            logger()->error('Falha ao executar auto-migration: ' . $e->getMessage());
        }
    }
}
