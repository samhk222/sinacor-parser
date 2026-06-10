<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

use Samhk222\SinacorParser\Enums\OperationType;
use Samhk222\SinacorParser\Resolvers\AssetResolverInterface;

final class Movement
{
    public function __construct(
        public readonly string        $movementHash,
        public readonly string        $broker,
        public readonly string        $noteNumber,
        public readonly ?string       $tradeDate,
        public readonly string        $brokerAsset,
        public readonly ?string       $ticker,
        public readonly bool          $tickerMapped,
        public readonly string        $mappingSource,
        public readonly ?string       $assetType,
        public readonly OperationType $operation,
        public readonly int           $quantity,
        public readonly float         $unitPrice,
        public readonly float         $grossAmount,
    ) {}

    public static function fromTrade(
        BrokerageNote          $note,
        Trade                  $trade,
        AssetResolverInterface $resolver,
    ): self {
        $resolved = $resolver->resolve($trade->brokerAsset);

        $hash = hash('sha256', implode('|', [
            $note->broker,
            $note->noteNumber,
            $note->tradeDate?->toDateString(),
            $trade->brokerAsset,
            $trade->operation->value,
            $trade->quantity,
            $trade->unitPrice,
        ]));

        return new self(
            movementHash:  $hash,
            broker:        $note->broker,
            noteNumber:    $note->noteNumber,
            tradeDate:     $note->tradeDate?->toDateString(),
            brokerAsset:   $trade->brokerAsset,
            ticker:        $resolved['ticker'],
            tickerMapped:  $resolved['mapped'],
            mappingSource: $resolved['source'],
            assetType:     $resolved['type'] ?? null,
            operation:     $trade->operation,
            quantity:      $trade->quantity,
            unitPrice:     $trade->unitPrice,
            grossAmount:   $trade->grossAmount,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'movement_hash'  => $this->movementHash,
            'broker'         => $this->broker,
            'note_number'    => $this->noteNumber,
            'trade_date'     => $this->tradeDate,
            'broker_asset'   => $this->brokerAsset,
            'ticker'         => $this->ticker,
            'ticker_mapped'  => $this->tickerMapped,
            'mapping_source' => $this->mappingSource,
            'asset_type'     => $this->assetType,
            'operation'      => $this->operation->value,
            'quantity'       => $this->quantity,
            'unit_price'     => $this->unitPrice,
            'gross_amount'   => $this->grossAmount,
        ];
    }
}
