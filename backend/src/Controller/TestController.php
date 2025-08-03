<?php

namespace App\Controller;

// src/Controller/TestController.php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/test-auth', name: 'api_test_auth')]
class TestController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function test(Request $request, Security $security): JsonResponse
    {
        return $this->json([
            'headers' => $request->headers->all(),
            'user' => $security->getUser()?->getUserIdentifier(),
            'roles' => $security->getUser()?->getRoles(),
        ]);
    }

}
