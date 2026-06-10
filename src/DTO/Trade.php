<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

use Samhk222\SinacorParser\Enums\OperationType;

final class Trade
{
    public function __construct(
        public readonly OperationType $operation,
        public readonly string        $market,
        public readonly string        $brokerAsset,
        public readonly int           $quantity,
        public readonly float         $unitPrice,
        public readonly float         $grossAmount,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'operation'    => $this->operation->value,
            'market'       => $this->market,
            'broker_asset' => $this->brokerAsset,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unitPrice,
            'gross_amount' => $this->grossAmount,
        ];
    }
}
