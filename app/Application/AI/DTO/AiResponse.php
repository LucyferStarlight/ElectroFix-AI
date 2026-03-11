<?php

namespace App\Application\AI\DTO;

class AiResponse
{
    public function __construct(
        public readonly array $content,
        public readonly ?int $tokensUsed,
        public readonly bool $success,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null
    ) {
    }

    public static function success(array $content, ?int $tokensUsed = null): self
    {
        return new self(
            content: $content,
            tokensUsed: $tokensUsed,
            success: true,
            errorCode: null,
            errorMessage: null
        );
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        return new self(
            content: [],
            tokensUsed: null,
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage
        );
    }
}

