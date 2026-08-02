<?php

namespace App\Tests\Service;

use App\Service\GreetingService;
use PHPUnit\Framework\TestCase;

final class GreetingServiceTest extends TestCase
{
    public function testMessageUsesGivenName(): void
    {
        putenv('APP_ENV=dev');
        putenv('APP_WELCOME_MESSAGE=Bienvenue sur DEV');

        $service = new GreetingService();

        self::assertSame('Bienvenue sur DEV - dev environment - Hello DevSecOps', $service->message('DevSecOps'));
    }

    public function testMessageFallsBackToWorld(): void
    {
        putenv('APP_ENV');
        putenv('APP_WELCOME_MESSAGE');

        $service = new GreetingService();

        self::assertSame('Hello from Symfony CI - unknown environment - Hello World', $service->message('   '));
    }
}
