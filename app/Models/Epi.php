<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Epi extends Model
{
    use Auditable;
    use BelongsToTenant;

    protected $table = 'ss_epi';

    protected $primaryKey = 'ss_e_nb_id';

    public $timestamps = false;

    protected $fillable = [
        'ss_e_tx_grupo',
        'ss_e_tx_subgrupo',
        'ss_e_tx_item',
        'ss_e_tx_descricao',
        'ss_e_tx_fabricante',
        'ss_e_tx_ca',
        'ss_e_tx_validade_ca',
        'ss_e_nb_vida_util_dias',
        'ss_e_tx_status',
        'ss_e_tx_cadastro_tipo',
        'ss_e_tx_foto',
        'ss_e_tx_modelo',
        'ss_e_nb_userCadastro',
        'ss_e_tx_dataCadastro',
    ];

    public function estoques()
    {
        return $this->hasMany(EpiEstoque::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }

    public function entregas()
    {
        return $this->hasMany(EpiEntrega::class, 'ss_e_nb_epi_id', 'ss_e_nb_id');
    }

    public function variacoes()
    {
        return $this->hasMany(EpiVariacao::class, 'ss_ev_nb_epi_id', 'ss_e_nb_id');
    }

    /**
     * Situação do Certificado de Aprovação (CA) do EPI.
     * Valores: sem_info | sem_data | vencido | expirando_30 | expirando_60 | expirando_90 | valido
     */
    public function getStatusCaAttribute(): string
    {
        if (empty($this->ss_e_tx_ca) && empty($this->ss_e_tx_validade_ca)) {
            return 'sem_info';
        }
        if (empty($this->ss_e_tx_validade_ca)) {
            return 'sem_data';
        }

        try {
            $vencimento = Carbon::parse($this->ss_e_tx_validade_ca)->startOfDay();
        } catch (\Throwable $e) {
            return 'sem_data';
        }

        $hoje = now()->startOfDay();

        if ($vencimento->lt($hoje)) {
            return 'vencido';
        }

        $dias = (int) ceil($vencimento->floatDiffInDays($hoje));

        if ($dias <= 30) {
            return 'expirando_30';
        }
        if ($dias <= 60) {
            return 'expirando_60';
        }
        if ($dias <= 90) {
            return 'expirando_90';
        }

        return 'valido';
    }

    /**
     * Rótulo e classes CSS do badge de situação do CA (exibido nas views).
     */
    public function getStatusCaBadgeAttribute(): array
    {
        $mapa = [
            'vencido' => ['CA Vencido', 'bg-rose-100 text-rose-700'],
            'expirando_30' => ['CA vence em 30 dias', 'bg-amber-100 text-amber-800'],
            'expirando_60' => ['CA vence em 60 dias', 'bg-yellow-100 text-yellow-800'],
            'expirando_90' => ['CA vence em 90 dias', 'bg-yellow-50 text-yellow-700'],
            'sem_data' => ['Sem validade de CA', 'bg-gray-200 text-gray-600'],
            'sem_info' => ['CA não informado', 'bg-gray-100 text-gray-400'],
            'valido' => ['CA válido', 'bg-emerald-100 text-emerald-700'],
        ];

        return $mapa[$this->status_ca] ?? $mapa['sem_info'];
    }

    /**
     * Conta itens ativos com CA crítico: vencido, vencendo em até $diasAlerta
     * ou com CA informado mas sem data de validade.
     */
    public static function contarCaCriticos(int $diasAlerta = 30): array
    {
        $itens = self::where('ss_e_tx_status', 'ativo')
            ->whereNotNull('ss_e_tx_ca')
            ->get(['ss_e_tx_ca', 'ss_e_tx_validade_ca']);

        $vencidos = 0;
        $expirando = 0;
        $semData = 0;

        $hoje = now()->startOfDay();
        $limite = $hoje->copy()->addDays($diasAlerta);

        foreach ($itens as $item) {
            if (empty($item->ss_e_tx_validade_ca)) {
                $semData++;

                continue;
            }

            try {
                $vencimento = Carbon::parse($item->ss_e_tx_validade_ca)->startOfDay();
            } catch (\Throwable $e) {
                $semData++;

                continue;
            }

            if ($vencimento->lt($hoje)) {
                $vencidos++;
            } elseif ($vencimento->lte($limite)) {
                $expirando++;
            }
        }

        return [
            'total' => $vencidos + $expirando + $semData,
            'vencidos' => $vencidos,
            'expirando' => $expirando,
            'sem_data' => $semData,
        ];
    }

    /**
     * Saldos agregados do estoque deste EPI, quando pré-carregados
     * (evita N+1: 2 queries por chamada de saldo).
     *
     * Estrutura: [variacaoKey|null => [empresaId => [tipo => quantidade]]]
     *
     * @var array<string, array<int, array<string, int>>>|null
     */
    protected ?array $saldoMap = null;

    /**
     * Pré-carrega os saldos de uma coleção de EPIs em uma única query.
     *
     * @param  iterable<int, Epi>  $epis
     */
    public static function preloadSaldosFor(iterable $epis): void
    {
        $collection = $epis instanceof Collection ? $epis : collect($epis);

        $ids = $collection->pluck('ss_e_nb_id')->filter()->unique()->values()->all();

        if ($ids === []) {
            return;
        }

        $rows = DB::table('ss_epi_estoque')
            ->whereTenant('ss_epi_estoque')
            ->whereIn('ss_e_nb_epi_id', $ids)
            ->selectRaw('ss_e_nb_epi_id, ss_e_nb_variacao_id, ss_e_nb_empresa_id, ss_e_tx_tipo, SUM(ss_e_nb_quantidade) as total')
            ->groupBy('ss_e_nb_epi_id', 'ss_e_nb_variacao_id', 'ss_e_nb_empresa_id', 'ss_e_tx_tipo')
            ->get()
            ->groupBy('ss_e_nb_epi_id');

        foreach ($collection as $epi) {
            if ($epi instanceof self) {
                $epi->saldoMap = self::buildSaldoMap($rows->get($epi->ss_e_nb_id) ?? collect());
            }
        }
    }

    private static function buildSaldoMap(Collection $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $variacaoKey = $row->ss_e_nb_variacao_id === null ? 'null' : (string) $row->ss_e_nb_variacao_id;
            $empresaKey = (int) ($row->ss_e_nb_empresa_id ?? 0);

            $map[$variacaoKey][$empresaKey][$row->ss_e_tx_tipo] =
                ($map[$variacaoKey][$empresaKey][$row->ss_e_tx_tipo] ?? 0) + (int) $row->total;
        }

        return $map;
    }

    /**
     * Calcula o saldo a partir do mapa pré-carregado, respeitando a mesma
     * semântica de getSaldoPorFilial()/getSaldoTotalRede().
     */
    private function saldoDoMapa($empresaId, $variacaoId): int
    {
        $variacoes = $variacaoId === null
            ? $this->saldoMap
            : [(string) $variacaoId => $this->saldoMap[(string) $variacaoId] ?? []];

        $entradas = 0;
        $saidas = 0;

        foreach ($variacoes as $porEmpresa) {
            foreach ($porEmpresa as $empresa => $tipos) {
                if ($empresaId !== null && $empresaId !== '') {
                    $empresaInt = (int) $empresaId;

                    if ($empresaInt === 0) {
                        if ($empresa !== 0) {
                            continue;
                        }
                    } elseif ($empresa !== $empresaInt) {
                        continue;
                    }
                }

                $entradas += ($tipos['entrada'] ?? 0) + ($tipos['devolucao'] ?? 0);
                $saidas += ($tipos['saida'] ?? 0) + ($tipos['substituicao'] ?? 0);
            }
        }

        return max(0, $entradas - $saidas);
    }

    /**
     * Calcula o saldo do EPI para uma filial específica (0/null = Matriz) ou consolidado.
     */
    public function getSaldoPorFilial($empresaId = null, $variacaoId = null): int
    {
        if ($this->saldoMap !== null) {
            return $this->saldoDoMapa($empresaId, $variacaoId);
        }

        $query = DB::table('ss_epi_estoque')
            ->whereTenant('ss_epi_estoque')
            ->where('ss_e_nb_epi_id', $this->ss_e_nb_id);

        if ($empresaId !== null && $empresaId !== '') {
            $empresaIdInt = (int) $empresaId;
            if ($empresaIdInt === 0) {
                $query->where(function ($q) {
                    $q->whereNull('ss_e_nb_empresa_id')
                        ->orWhere('ss_e_nb_empresa_id', 0);
                });
            } else {
                $query->where('ss_e_nb_empresa_id', $empresaIdInt);
            }
        }

        if ($variacaoId !== null) {
            $query->where('ss_e_nb_variacao_id', $variacaoId);
        }

        $entradas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['entrada', 'devolucao'])->sum('ss_e_nb_quantidade');
        $saidas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');

        return max(0, $entradas - $saidas);
    }

    /**
     * Retorna o saldo total somado em TODAS as filiais da rede.
     */
    public function getSaldoTotalRede($variacaoId = null): int
    {
        if ($this->saldoMap !== null) {
            return $this->saldoDoMapa(null, $variacaoId);
        }

        $query = DB::table('ss_epi_estoque')
            ->whereTenant('ss_epi_estoque')
            ->where('ss_e_nb_epi_id', $this->ss_e_nb_id);

        if ($variacaoId !== null) {
            $query->where('ss_e_nb_variacao_id', $variacaoId);
        }

        $entradas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['entrada', 'devolucao'])->sum('ss_e_nb_quantidade');
        $saidas = (int) (clone $query)->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade');

        return max(0, $entradas - $saidas);
    }
}
