<?php
// tests/Fixtures/Controller/TestExceptionController.php
declare(strict_types=1);

namespace App\Tests\Fixtures\Controller;

use App\Http\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TestExceptionController
{
    #[Route('/_test/throw-validation', name: 'test_throw_validation', methods: ['GET'])]
    public function __invoke(): Response
    {
        throw new ValidationException(['email' => 'Invalid']);
    }
}
