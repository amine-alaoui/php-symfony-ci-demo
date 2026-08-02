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

        $environment = getenv('APP_ENV') ?: 'unknown';
        $welcomeMessage = getenv('APP_WELCOME_MESSAGE') ?: 'Hello from Symfony CI';

        return sprintf('%s - %s environment - Hello %s', $welcomeMessage, $environment, $cleanName);
    }
}
