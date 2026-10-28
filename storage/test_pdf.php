<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cert = App\Models\Certificate::find(456);
echo "Cert 456 tempo_assistido_segundos no banco: " . $cert->tempo_assistido_segundos . PHP_EOL;

$service = app(App\Services\CertificatePdfService::class);
$pdf = $service->output($cert, 'S');

preg_match_all('/\d{2}:\d{2}:\d{2}/', $pdf, $m);
echo "Horarios encontrados no PDF: " . json_encode($m[0]) . PHP_EOL;

if (strpos($pdf, '00:26:00') !== false) {
    echo "TEMPO 00:26:00 ENCONTRADO NO PDF!\n";
} else {
    echo "00:26:00 NAO encontrado no PDF.\n";
}

// Let us also check all certificates of Bruno Fernando
$allCerts = App\Models\Certificate::where('user_id', 47)->get();
foreach ($allCerts as $c) {
    echo "Cert #{$c->id} | Training #{$c->training_id} | tempo_assistido_segundos: {$c->tempo_assistido_segundos} (" . gmdate('H:i:s', (int)$c->tempo_assistido_segundos) . ")\n";
}

