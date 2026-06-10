<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Resolvers;

/**
 * Resolve ativos a partir de um array de configuração.
 *
 * Uso:
 *   $resolver = new ConfigAssetResolver([
 *       'ISHARE SP500'  => ['ticker' => 'IVVB11', 'type' => 'ETF'],
 *       'IT NOW IDIV'   => ['ticker' => 'IDIV11', 'type' => 'ETF'],
 *       'GARE REND'     => ['ticker' => 'GARE11', 'type' => 'FII'],
 *   ]);
 */
final class ConfigAssetResolver implements AssetResolverInterface
{
    public function __construct(
        /** @var array<string, array{ticker: string, type?: string}> */
        private readonly array $map = [],
    ) {}

    /** @return array{ticker: string|null, mapped: bool, source: string, type: string|null} */
    public function resolve(string $brokerAsset): array
    {
        $key = strtoupper(trim($brokerAsset));

        if (isset($this->map[$key])) {
            return [
                'ticker' => $this->map[$key]['ticker'],
                'mapped' => true,
                'source' => 'config',
                'type'   => $this->map[$key]['type'] ?? null,
            ];
        }

        return [
            'ticker' => null,
            'mapped' => false,
            'source' => 'config',
            'type'   => null,
        ];
    }
}
