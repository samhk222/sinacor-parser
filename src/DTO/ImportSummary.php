<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

final class ImportSummary
{
    /**
     * @param string[] $unmappedAssets
     */
    public function __construct(
        public readonly int   $totalNotes,
        public readonly int   $totalMovements,
        public readonly int   $totalUnmapped,
        public readonly array $unmappedAssets,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_notes'      => $this->totalNotes,
            'total_movements'  => $this->totalMovements,
            'total_unmapped'   => $this->totalUnmapped,
            'unmapped_assets'  => $this->unmappedAssets,
        ];
    }
}
