<?php

namespace App\Tests\Service;

use App\Service\GreetingService;
use PHPUnit\Framework\TestCase;

final class GreetingServiceTest extends TestCase
{
    public function testMessageUsesGivenName(): void
    {
        $service = new GreetingService();

        self::assertSame('Hello DevSecOps from Symfony CI', $service->message('DevSecOps'));
    }

    public function testMessageFallsBackToWorld(): void
    {
        $service = new GreetingService();

        self::assertSame('Hello World from Symfony CI', $service->message('   '));
    }
}
