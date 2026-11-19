<?php

namespace App\Support;

/**
 * Alinha as colunas das tabelas nos relatórios em PDF gerados pelo TCPDF.
 *
 * O TCPDF calcula as larguras das colunas do <tbody> a partir da primeira
 * linha do corpo. Larguras definidas apenas nos <th> do <thead> são aplicadas
 * ao cabeçalho, mas o corpo fica com todas as colunas do mesmo tamanho —
 * fazendo os dados "escorregarem" para a coluna vizinha.
 *
 * Esta rotina recalcula as larguras (preenchendo as colunas sem largura com o
 * espaço restante), reescreve os <th> e replica o resultado na primeira linha
 * do <tbody>, que é a linha usada pelo TCPDF como referência.
 * Tabelas sem nenhuma largura explícita são devolvidas intactas.
 */
class PdfTableWidths
{
    public static function align(string $html): string
    {
        return preg_replace_callback(
            '/<table\b[^>]*>(.*?)<\/table>/is',
            [self::class, 'alignTable'],
            $html
        ) ?? $html;
    }

    /**
     * @param  array<int, string>  $match
     */
    private static function alignTable(array $match): string
    {
        $table = $match[0];

        // Só tabelas com <thead> e pelo menos uma largura explícita nos <th>.
        if (! preg_match('/<thead\b[^>]*>(.*?)<\/thead>/is', $match[1], $thead)) {
            return $table;
        }

        preg_match_all('/<th\b[^>]*>/is', $thead[1], $thTags);

        if ($thTags[0] === []) {
            return $table;
        }

        $widths = [];
        $specified = 0.0;
        $missing = 0;

        foreach ($thTags[0] as $th) {
            if (preg_match('/\bwidth\s*:\s*([\d.]+)%/i', $th, $width)
                || preg_match('/\bwidth\s*=\s*["\']?([\d.]+)%/i', $th, $width)) {
                $widths[] = (float) $width[1];
                $specified += (float) $width[1];
            } else {
                $widths[] = null;
                $missing++;
            }
        }

        if ($specified <= 0) {
            return $table;
        }

        // Colunas sem largura recebem o espaço restante; se as larguras
        // informadas passarem de 100%, tudo é normalizado proporcionalmente.
        if ($missing > 0) {
            $restante = (100 - $specified) / $missing;

            if ($restante <= 0) {
                return $table;
            }

            foreach ($widths as $index => $width) {
                if ($width === null) {
                    $widths[$index] = $restante;
                }
            }
        } elseif (abs($specified - 100) > 0.01) {
            foreach ($widths as $index => $width) {
                $widths[$index] = $width / $specified * 100;
            }
        }

        $widths = array_map([self::class, 'formatPercent'], $widths);

        // Reescreve o cabeçalho para que o TCPDF use exatamente estas larguras.
        $index = 0;
        $alignedThead = preg_replace_callback('/<th\b[^>]*>/is', function (array $tag) use (&$index, $widths) {
            return self::withWidth($tag[0], $widths[$index++]);
        }, $thead[1]);

        if ($alignedThead !== null) {
            $table = str_replace($thead[1], $alignedThead, $table);
        }

        if (! preg_match('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $table, $tbody, PREG_OFFSET_CAPTURE)) {
            return $table;
        }

        $tbodyInner = $tbody[1][0];
        $tbodyOffset = $tbody[1][1];

        if (! preg_match('/<tr\b[^>]*>.*?<\/tr>/is', $tbodyInner, $row, PREG_OFFSET_CAPTURE)) {
            return $table;
        }

        $rowHtml = $row[0][0];
        $rowStart = $tbodyOffset + $row[0][1];

        // Linha de "nenhum registro" com colspan não pode receber larguras.
        if (preg_match('/<td\b[^>]*\bcolspan\s*=/i', $rowHtml)) {
            return $table;
        }

        if (preg_match_all('/<td\b/i', $rowHtml) !== count($widths)) {
            return $table;
        }

        $index = 0;
        $alignedRow = preg_replace_callback('/<td\b/i', function () use (&$index, $widths) {
            return '<td width="'.$widths[$index++].'"';
        }, $rowHtml);

        return substr_replace($table, $alignedRow, $rowStart, strlen($rowHtml));
    }

    /**
     * Adiciona/substitui a largura no atributo style do <th>.
     */
    private static function withWidth(string $tag, string $width): string
    {
        if (preg_match('/\bwidth\s*:\s*[\d.]+%/i', $tag)) {
            return preg_replace('/\bwidth\s*:\s*[\d.]+%/i', 'width:'.$width, $tag) ?? $tag;
        }

        if (preg_match('/\bstyle\s*=\s*"([^"]*)"/i', $tag, $style)) {
            $novoStyle = rtrim(trim($style[1]), ';');

            return str_replace('style="'.$style[1].'"', 'style="'.$novoStyle.';width:'.$width.'"', $tag);
        }

        return preg_replace('/<th\b/i', '<th style="width:'.$width.'"', $tag, 1) ?? $tag;
    }

    private static function formatPercent(float $width): string
    {
        return rtrim(rtrim(number_format($width, 2, '.', ''), '0'), '.').'%';
    }
}
