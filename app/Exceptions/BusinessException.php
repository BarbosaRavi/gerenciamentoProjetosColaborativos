<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class BusinessException extends Exception {

    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int {
        return $this->status;
    }

    public function errors(): array {
        return $this->errors;
    }
}
