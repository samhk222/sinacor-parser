<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

final class FinancialSummary
{
    public function __construct(
        public readonly float $grossOperations,
        public readonly float $liquidationFee,
        public readonly float $exchangeFee,
        public readonly float $custodyFee,
        public readonly float $netValue,
    ) {}

    /** @return array<string, float> */
    public function toArray(): array
    {
        return [
            'gross_operations' => $this->grossOperations,
            'liquidation_fee'  => $this->liquidationFee,
            'exchange_fee'     => $this->exchangeFee,
            'custody_fee'      => $this->custodyFee,
            'net_value'        => $this->netValue,
        ];
    }
}
