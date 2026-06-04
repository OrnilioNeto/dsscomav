#!/usr/bin/env php
<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "\n=== VERIFICAÇÃO DE DADOS REAIS DE RANKING ===\n\n";

// 1. Contar certificados
echo "1. CERTIFICADOS REAIS NO BANCO:\n";
$certsCount = DB::table('certificates')->count();
echo "   Total de certificados: $certsCount\n\n";

// 2. Certificados por usuário
echo "2. CERTIFICADOS POR USUÁRIO:\n";
$certsByUser = DB::table('certificates')
    ->join('users', 'certificates.user_id', '=', 'users.id')
    ->select('users.id', 'users.name', DB::raw('COUNT(certificates.id) as cert_count'))
    ->groupBy('users.id', 'users.name')
    ->orderByDesc('cert_count')
    ->limit(10)
    ->get();

foreach ($certsByUser as $row) {
    $count = $row->cert_count;
    echo "   Usuário ID {$row->id}: {$row->name} - $count certificados\n";
}

// 3. Distribuição de certificados por mês/ano
echo "\n3. CERTIFICADOS POR MÊS/ANO:\n";
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
    $month = str_pad($row->month, 2, '0', STR_PAD_LEFT);
    echo "   $month/{$row->year}: {$row->count} certificados\n";
}

// 4. Dados em ranking_monthly_scores
echo "\n4. DADOS EM ranking_monthly_scores:\n";
$monthlyCount = DB::table('ranking_monthly_scores')->count();
echo "   Total de registros: $monthlyCount\n";

if ($monthlyCount > 0) {
    $monthlyData = DB::table('ranking_monthly_scores')
        ->join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
        ->select('users.name', 'ranking_monthly_scores.month_reference', 'ranking_monthly_scores.year_reference', 'ranking_monthly_scores.average_score')
        ->orderByDesc('ranking_monthly_scores.average_score')
        ->limit(10)
        ->get();

    fore$month = str_pad($row->month_reference, 2, '0', STR_PAD_LEFT);
        echo "   {$row->name}: $month
        echo "   {$row->name}: {$row->month_reference:02d}/{$row->year_reference} = {$row->average_score}\n";
    }
} else {
    echo "   ⚠️ VAZIO! Ranking mensal não foi calculado.\n";
}

// 5. Dados em ranking_scores
echo "\n5. DADOS EM ranking_scores:\n";
$scoresCount = DB::table('ranking_scores')->count();
echo "   Total de registros: $scoresCount\n";

if ($scoresCount > 0) {
    $topScores = DB::table('ranking_scores')
        ->join('users', 'ranking_scores.user_id', '=', 'users.id')
        ->select('users.name', 'ranking_scores.month_reference', 'ranking_scores.year_reference', 'ranking_scores.raw_score', 'ranking_scores.normalized_score')
        ->orderByDesc('ranking_scores.raw_score')
        ->limit(10)
        ->get();

    fore$month = str_pad($row->month_reference, 2, '0', STR_PAD_LEFT);
        echo "   {$row->name}: $month
        echo "   {$row->name}: {$row->month_reference:02d}/{$row->year_reference} | Raw: {$row->raw_score} | Norm: {$row->normalized_score}\n";
    }
}

// 6. Verificar dados de certificados do mês/ano atual
echo "\n6. CERTIFICADOS DO MÊS ATUAL (Maio 2026):\n";
$now = \Carbon\Carbon::now();
$currentCerts = DB::table('certificates')
    ->join('users', 'certificates.user_id', '=', 'users.id')
    ->join('trainings', 'certificates.training_id', '=', 'trainings.id')
    ->select('users.name', 'trainings.titulo', 'certificates.data_emissao')
    ->whereMonth('data_emissao', $now->month)
    ->whereYear('data_emissao', $now->year)
    ->orderByDesc('data_emissao')
    ->get();

$countCurrent = $currentCerts->count();
echo "   Total: $countCurrent certificados\n";

if ($countCurrent > 0) {
    foreach ($currentCerts->take(15) as $cert) {
        echo "   - {$cert->name}: {$cert->titulo} ({$cert->data_emissao})\n";
    }
    if ($countCurrent > 15) {
        echo "   ... e mais " . ($countCurrent - 15) . " certificados\n";
    }
} else {
    echo "   ⚠️ Nenhum certificado em maio de 2026!\n";
}

// 7. Status do cálculo
echo "\n7. RESUMO:\n";
if ($monthlyCount == 0 && $scoresCount == 0) {
    echo "   ❌ Dados de ranking NÃO foram calculados\n";
    echo "   ℹ️ Execute: php artisan ranking:recalculate\n";
} else {
    echo "   ✅ Dados de ranking foram calculados\n";
}

echo "\nFim da verificação.\n\n";
