<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$tables = ['ranking_monthly_scores', 'ranking_scores', 'ranking_settings', 'ranking_criteria', 'ranking_rules', 'ranking_histories'];

foreach ($tables as $table) {
    $columns = DB::select("DESCRIBE $table");
    echo "\n=== TABELA: $table ===\n";
    echo str_pad('Field', 30) . ' | ' . str_pad('Type', 20) . ' | ' . str_pad('Null', 5) . ' | ' . str_pad('Key', 5) . ' | Default\n';
    echo str_repeat('-', 100) . "\n";
    
    foreach ($columns as $col) {
        echo str_pad($col->Field, 30) . ' | ' . 
             str_pad($col->Type, 20) . ' | ' . 
             str_pad($col->Null, 5) . ' | ' . 
             str_pad($col->Key, 5) . ' | ' . 
             ($col->Default ?? 'NULL') . "\n";
    }
}

// Verificar índices unique
echo "\n\n=== ÍNDICES ÚNICOS E CHAVES ESTRANGEIRAS ===\n";

$constraints = DB::select("
    SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'dss_db' AND TABLE_NAME IN ('ranking_monthly_scores', 'ranking_scores', 'ranking_settings', 'ranking_criteria', 'ranking_rules', 'ranking_histories')
");

foreach ($constraints as $constraint) {
    if ($constraint->REFERENCED_TABLE_NAME) {
        echo "{$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME} ({$constraint->CONSTRAINT_NAME})\n";
    } else {
        echo "{$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} ({$constraint->CONSTRAINT_NAME})\n";
    }
}

echo "\nDone!\n";
