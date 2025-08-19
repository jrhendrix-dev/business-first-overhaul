<?php
// src/Http/ValidationResponder.php
namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationResponder
{
    public static function bad(ConstraintViolationListInterface $viol): JsonResponse
    {
        $errs = [];
        foreach ($viol as $v) {
            $errs[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
        }
        return new JsonResponse(['errors' => $errs], 400);
    }
}
