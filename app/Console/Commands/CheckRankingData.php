<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckRankingData extends Command
{
    protected $signature = 'ranking:check';
    protected $description = 'Verifica dados reais de ranking';

    public function handle()
    {
        $this->line("\n=== VERIFICACAO DE DADOS REAIS DE RANKING ===\n");

        // 1. Contar certificados
        $this->line('1. CERTIFICADOS REAIS NO BANCO:');
        $certsCount = DB::table('certificates')->count();
        $this->line("   Total de certificados: $certsCount\n");

        // 2. Certificados por usuário
        $this->line('2. CERTIFICADOS POR USUARIO:');
        $certsByUser = DB::table('certificates')
            ->join('users', 'certificates.user_id', '=', 'users.id')
            ->select('users.id', 'users.nome', DB::raw('COUNT(certificates.id) as cert_count'))
            ->groupBy('users.id', 'users.nome')
            ->orderByDesc('cert_count')
            ->limit(10)
            ->get();

        foreach ($certsByUser as $row) {
            $count = $row->cert_count;
            $this->line("   User ID $row->id: $row->nome - $count certs");
        }

        // 3. Distribuição de certificados por mês/ano
        $this->line("\n3. CERTIFICADOS POR MES/ANO:");
        $certsByMonth = DB::table('certificates')
            ->select(
                DB::raw('MONTH(data_emissao) as month'),
                DB::raw('YEAR(data_emissao) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month', 'year')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        foreach ($certsByMonth as $row) {
            $m = $row->month < 10 ? '0' . $row->month : $row->month;
            $this->line("   $m/$row->year: $row->count certs");
        }

        // 4. Dados em ranking_monthly_scores
        $this->line("\n4. DADOS EM ranking_monthly_scores:");
        $monthlyCount = DB::table('ranking_monthly_scores')->count();
        $this->line("   Total de registros: $monthlyCount");

        if ($monthlyCount > 0) {
            $monthlyData = DB::table('ranking_monthly_scores')
                ->join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->select('users.nome', 'ranking_monthly_scores.month_reference', 'ranking_monthly_scores.year_reference', 'ranking_monthly_scores.average_score')
                ->orderByDesc('ranking_monthly_scores.average_score')
                ->limit(10)
                ->get();

            foreach ($monthlyData as $row) {
                $m = $row->month_reference < 10 ? '0' . $row->month_reference : $row->month_reference;
                $score = number_format($row->average_score, 2);
                $this->line("   $row->nome: $m/$row->year_reference = $score");
            }
        } else {
            $this->warn("   VAZIO! Ranking mensal nao foi calculado.");
        }

        // 5. Dados em ranking_scores
        $this->line("\n5. DADOS EM ranking_scores:");
        $scoresCount = DB::table('ranking_scores')->count();
        $this->line("   Total de registros: $scoresCount");

        if ($scoresCount > 0) {
            $topScores = DB::table('ranking_scores')
                ->join('users', 'ranking_scores.user_id', '=', 'users.id')
                ->select('users.nome', 'ranking_scores.month_reference', 'ranking_scores.year_reference', 'ranking_scores.raw_score', 'ranking_scores.normalized_score')
                ->orderByDesc('ranking_scores.raw_score')
                ->limit(10)
                ->get();

            foreach ($topScores as $row) {
                $m = $row->month_reference < 10 ? '0' . $row->month_reference : $row->month_reference;
                $this->line("   $row->nome: $m/$row->year_reference | Raw: $row->raw_score | Norm: $row->normalized_score");
            }
        }

        // 6. Verificar dados de certificados do mês/ano atual
        $this->line("\n6. CERTIFICADOS DO MES ATUAL (Maio 2026):");
        $now = \Carbon\Carbon::now();
        $month = $now->month;
        $year = $now->year;

        $currentCerts = DB::table('certificates')
            ->join('users', 'certificates.user_id', '=', 'users.id')
            ->join('trainings', 'certificates.training_id', '=', 'trainings.id')
            ->select('users.nome', 'trainings.titulo', 'certificates.data_emissao')
            ->whereMonth('data_emissao', $month)
            ->whereYear('data_emissao', $year)
            ->orderByDesc('data_emissao')
            ->get();

        $countCurrent = count($currentCerts);
        $this->line("   Total: $countCurrent certificados em $month/$year");

        if ($countCurrent > 0) {
            $limit = min(15, $countCurrent);
            for ($i = 0; $i < $limit; $i++) {
                $cert = $currentCerts[$i];
                $this->line("   - $cert->nome: $cert->titulo ($cert->data_emissao)");
            }
            if ($countCurrent > 15) {
                $this->line("   ... e mais " . ($countCurrent - 15) . " certificados");
            }
        } else {
            $this->warn("   Nenhum certificado em $month de $year!");
        }

        // 7. Status do cálculo
        $this->line("\n7. RESUMO:");
        if ($monthlyCount == 0 && $scoresCount == 0) {
            $this->error("   ERRO: Dados de ranking NAO foram calculados");
            $this->info("   ACAO: Execute: php artisan ranking:recalculate");
        } else {
            $this->info("   OK: Dados de ranking foram calculados");
        }

        $this->line("\nFim da verificacao.\n");
    }
}
