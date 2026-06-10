<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Resolvers;

/**
 * Encadeia múltiplos resolvers — o primeiro que mapear ganha.
 *
 * Uso:
 *   $resolver = new ChainAssetResolver(
 *       new ConfigAssetResolver($myMap),
 *       new CsvAssetResolver('/path/to/assets.csv'),
 *   );
 */
final class ChainAssetResolver implements AssetResolverInterface
{
    /** @var AssetResolverInterface[] */
    private array $resolvers;

    public function __construct(AssetResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /** @return array{ticker: string|null, mapped: bool, source: string, type: string|null} */
    public function resolve(string $brokerAsset): array
    {
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->resolve($brokerAsset);

            if ($result['mapped'] === true) {
                return array_merge($result, ['source' => 'chain']);
            }
        }

        return [
            'ticker' => null,
            'mapped' => false,
            'source' => 'chain',
            'type'   => null,
        ];
    }
}
