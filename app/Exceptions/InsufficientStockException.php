<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forProduct(string $productName, int $available, int $requested): self
    {
        return new self(
            "Cannot remove {$requested} units of {$productName}: only {$available} units are available."
        );
    }
}
