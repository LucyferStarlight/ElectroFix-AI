<?php

namespace App\Services;

class AiTokenEstimator
{
    public function estimateFromChars(int $chars): int
    {
        if ($chars <= 0) {
            return 0;
        }

        return (int) ceil($chars / 4);
    }
}

