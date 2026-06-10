<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

use Carbon\Carbon;

final class BrokerageNote
{
    /**
     * @param Trade[] $trades
     * @param array<string, float> $summary
     */
    public function __construct(
        public readonly string          $broker,
        public readonly string          $noteNumber,
        public readonly ?Carbon         $tradeDate,
        public readonly ?Carbon         $settlementDate,
        public readonly Customer        $customer,
        public readonly array           $trades,
        public readonly array           $summary,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'broker'          => $this->broker,
            'note_number'     => $this->noteNumber,
            'trade_date'      => $this->tradeDate?->toDateString(),
            'settlement_date' => $this->settlementDate?->toDateString(),
            'customer'        => [
                'name' => $this->customer->name,
                'cpf'  => $this->customer->cpf,
            ],
            'trades'  => array_map(fn(Trade $t) => $t->toArray(), $this->trades),
            'summary' => $this->summary,
        ];
    }
}
