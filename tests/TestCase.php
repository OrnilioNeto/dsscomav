<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * O Blade `@component` (ManagesComponents) abre um buffer de saída por
     * renderização; no PHP ZTS/Windows o balanceamento do framework deixa
     * buffers pendentes. Limpa o excesso para não marcar testes como risky.
     */
    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        parent::tearDown();
    }
}
