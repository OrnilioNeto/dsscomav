<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$hash = \Illuminate\Support\Facades\Hash::make('password');
echo $hash;
