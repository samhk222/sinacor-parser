<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Resolvers;

/**
 * Resolver padrão: não faz nenhum mapeamento.
 * O brokerAsset é retornado como ticker sem mapeamento.
 */
final class NullAssetResolver implements AssetResolverInterface
{
    /** @return array{ticker: string|null, mapped: bool, source: string, type: string|null} */
    public function resolve(string $brokerAsset): array
    {
        return [
            'ticker' => null,
            'mapped' => false,
            'source' => 'none',
            'type'   => null,
        ];
    }
}
