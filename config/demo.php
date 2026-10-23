<?php

return [
    'enabled' => (bool) env('DEMO_MODE', false),
    'connection' => 'sqlite_demo',
    'demo_email_domain' => 'demo.plataformadss.local',
    'demo_password' => 'Demo@2026',
    'anon_password' => 'Teste@123',
];
