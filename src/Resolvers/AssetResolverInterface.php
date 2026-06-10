<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Resolvers;

interface AssetResolverInterface
{
    /**
     * Resolve o nome do ativo conforme aparece na nota para o ticker oficial.
     *
     * Retorna um array com:
     *   - ticker  (string|null)  → ticker oficial (ex: "IVVB11")
     *   - mapped  (bool)         → se o ativo foi mapeado com sucesso
     *   - source  (string)       → "config" | "csv" | "chain" | "none"
     *   - type    (string|null)  → "ETF" | "FII" | "ACAO" | null
     *
     * @return array{ticker: string|null, mapped: bool, source: string, type: string|null}
     */
    public function resolve(string $brokerAsset): array;
}
