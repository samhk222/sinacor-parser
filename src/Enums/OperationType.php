<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\Enums;

enum OperationType: string
{
    case BUY  = 'BUY';
    case SELL = 'SELL';

    public static function fromSinacor(string $code): self
    {
        return match (strtoupper(trim($code))) {
            'C'     => self::BUY,
            'V'     => self::SELL,
            default => throw new \ValueError("Código SINACOR desconhecido: {$code}"),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BUY  => 'Compra',
            self::SELL => 'Venda',
        };
    }
}
