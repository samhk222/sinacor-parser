<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Resolvers;

use Samhk222\SinacorParser\Exceptions\ResolverException;

/**
 * Resolve ativos a partir de um arquivo CSV.
 *
 * Formato esperado do CSV (sem cabeçalho obrigatório, mas recomendado):
 *   broker_asset,ticker,type
 *   ISHARE SP500,IVVB11,ETF
 *   IT NOW IDIV,IDIV11,ETF
 *   GARE REND,GARE11,FII
 */
final class CsvAssetResolver implements AssetResolverInterface
{
    /** @var array<string, array{ticker: string, type?: string}> */
    private array $map = [];

    public function __construct(string $csvPath)
    {
        $this->loadCsv($csvPath);
    }

    /** @return array{ticker: string|null, mapped: bool, source: string, type: string|null} */
    public function resolve(string $brokerAsset): array
    {
        $key = strtoupper(trim($brokerAsset));

        if (isset($this->map[$key])) {
            return [
                'ticker' => $this->map[$key]['ticker'],
                'mapped' => true,
                'source' => 'csv',
                'type'   => $this->map[$key]['type'] ?? null,
            ];
        }

        return [
            'ticker' => null,
            'mapped' => false,
            'source' => 'csv',
            'type'   => null,
        ];
    }

    private function loadCsv(string $path): void
    {
        if (!file_exists($path)) {
            throw new ResolverException("CSV não encontrado: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new ResolverException("Não foi possível abrir o CSV: {$path}");
        }

        $firstLine = true;

        while (($row = fgetcsv($handle)) !== false) {
            // Pula cabeçalho se a primeira coluna não for um código de ativo
            if ($firstLine && isset($row[0]) && strtolower(trim($row[0])) === 'broker_asset') {
                $firstLine = false;
                continue;
            }

            $firstLine = false;

            if (count($row) < 2) {
                continue;
            }

            $key = strtoupper(trim($row[0]));

            $entry = ['ticker' => strtoupper(trim($row[1]))];

            if (isset($row[2]) && trim($row[2]) !== '') {
                $entry['type'] = strtoupper(trim($row[2]));
            }

            $this->map[$key] = $entry;
        }

        fclose($handle);
    }
}
