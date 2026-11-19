<?php

namespace Tests\Unit;

use App\Support\PdfTableWidths;
use PHPUnit\Framework\TestCase;

/**
 * O TCPDF usa a primeira linha do <tbody> como referência de largura das
 * colunas; sem replicar as larguras dos <th>, o corpo fica com colunas iguais
 * e os dados saem desalinhados do cabeçalho.
 */
class PdfTableWidthsTest extends TestCase
{
    public function test_replica_as_larguras_do_cabecalho_na_primeira_linha_do_corpo(): void
    {
        $html = <<<'HTML'
            <table class="data">
                <thead>
                    <tr><th style="width:40%;">Grupo</th><th style="width:35%;">Item</th><th style="width:25%;">CA</th></tr>
                </thead>
                <tbody>
                    <tr><td>Proteção</td><td>Capacete</td><td>12345</td></tr>
                    <tr><td>Mãos</td><td>Luva</td><td>23456</td></tr>
                </tbody>
            </table>
            HTML;

        $resultado = PdfTableWidths::align($html);

        $this->assertStringContainsString('<td width="40%">Proteção</td>', $resultado);
        $this->assertStringContainsString('<td width="35%">Capacete</td>', $resultado);
        $this->assertStringContainsString('<td width="25%">12345</td>', $resultado);
        // Somente a primeira linha é alterada (as demais herdam as larguras).
        $this->assertStringContainsString('<tr><td>Mãos</td><td>Luva</td><td>23456</td></tr>', $resultado);
    }

    public function test_distribui_o_espaco_restante_entre_colunas_sem_largura(): void
    {
        $html = '<table class="data"><thead><tr>'
            .'<th style="width: 40%;">Treinamento</th>'
            .'<th>Participações</th><th>Com nota</th><th>Aprovados</th>'
            .'</tr></thead><tbody><tr><td>Curso</td><td>10</td><td>8</td><td>6</td></tr></tbody></table>';

        $resultado = PdfTableWidths::align($html);

        $this->assertStringContainsString('<td width="40%">Curso</td>', $resultado);
        $this->assertStringContainsString('<td width="20%">10</td>', $resultado);
        $this->assertStringContainsString('<td width="20%">8</td>', $resultado);
        $this->assertStringContainsString('<td width="20%">6</td>', $resultado);
    }

    public function test_nao_altera_tabela_sem_cabecalho_ou_sem_larguras(): void
    {
        $semThead = '<table class="data"><tbody><tr><td>A</td><td>B</td></tr></tbody></table>';
        $this->assertSame($semThead, PdfTableWidths::align($semThead));

        $semLargura = '<table class="data"><thead><tr><th>A</th><th>B</th></tr></thead><tbody><tr><td>x</td><td>y</td></tr></tbody></table>';
        $this->assertSame($semLargura, PdfTableWidths::align($semLargura));
    }

    public function test_nao_altera_linha_de_registro_vazio_com_colspan(): void
    {
        $html = '<table class="data"><thead><tr><th style="width:50%;">A</th><th style="width:50%;">B</th></tr></thead>'
            .'<tbody><tr><td colspan="2" class="empty">Nenhum registro</td></tr></tbody></table>';

        $this->assertSame($html, PdfTableWidths::align($html));
    }

    public function test_nao_altera_quando_a_quantidade_de_celulas_nao_confere(): void
    {
        $html = '<table class="data"><thead><tr><th style="width:50%;">A</th><th style="width:50%;">B</th></tr></thead>'
            .'<tbody><tr><td>somente uma</td></tr></tbody></table>';

        $this->assertSame($html, PdfTableWidths::align($html));
    }
}
