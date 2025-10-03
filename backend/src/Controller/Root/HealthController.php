<?php
// src/Controller/Root/HealthController.php
namespace App\Controller\Root;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController
{
#[Route('/', name: 'health', methods: ['GET'])]
public function __invoke(): JsonResponse
{
return new JsonResponse(['status' => 'ok', 'app' => 'business-first-overhaul']);
}
}
