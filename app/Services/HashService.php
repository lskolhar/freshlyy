<?php

namespace App\Services;

class HashService
{
    /**
     * Generate SHA512 hash for Omniware
     */
    public function generate(array $parameters, string $salt): string
    {
        // Remove empty values
        $filtered = array_filter($parameters, function ($value) {
            return $value !== null && $value !== '';
        });

        // Sort alphabetically
        ksort($filtered);

        // Start with salt
        $hashString = $salt;

        foreach ($filtered as $value) {
            $hashString .= '|'.trim($value);
        }

        return strtoupper(hash('sha512', $hashString));
    }
}
