<?php

declare(strict_types=1);

namespace Samhk222\SinacorParser\DTO;

final class Customer
{
    public function __construct(
        public readonly string $name,
        public readonly string $cpf,
    ) {}
}
