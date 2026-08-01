<?php

namespace App\Controller;

use App\Service\GreetingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController
{
    public function __construct(private readonly GreetingService $greetingService)
    {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return new Response($this->greetingService->message('DevSecOps'));
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'service' => 'php-symfony-ci-demo',
        ]);
    }
}
