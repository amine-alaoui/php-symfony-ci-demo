<?php

namespace App\Service;

final class GreetingService
{
    public function message(string $name): string
    {
        $cleanName = trim($name);

        if ($cleanName === '') {
            $cleanName = 'World';
        }

        return sprintf('Hello %s from Symfony CI', $cleanName);
    }
}
