<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Exception\ValidationException;
use App\Mapper\Request\MeForgotPasswordRequestMapper;
use App\Mapper\Request\MeResetPasswordRequestMapper;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/password', name: 'password_')]
#[IsGranted('PUBLIC_ACCESS')] // whole controller is public
final class AuthPasswordController extends AbstractController
{
    public function __construct(
        private readonly UserManager $manager,
        private readonly MeForgotPasswordRequestMapper $forgotMapper,
        private readonly MeResetPasswordRequestMapper $resetMapper,
        private readonly ValidatorInterface $validator,
        #[Autowire(service: 'limiter.forgot_ip')]    private readonly RateLimiterFactory $forgotIp,
        #[Autowire(service: 'limiter.forgot_email')] private readonly RateLimiterFactory $forgotEmail,
    ) {}

    /**
     * POST /password/forgot
     * Body: { "email": "user@example.com" }
     * Always returns 200 (generic) to prevent user enumeration.
     */
    #[Route('/forgot', name: 'forgot', methods: ['POST'])]
    public function forgot(Request $req): JsonResponse
    {
        $dto = $this->forgotMapper->fromRequest($req);
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        // Per-IP throttle first
        $ip = $req->getClientIp() ?? '0.0.0.0';
        if (!$this->forgotIp->create($ip)->consume(1)->isAccepted()) {
            return $this->json(['message' => 'If that email exists, a reset link has been sent.']);
        }

        // Per-email throttle inside the manager flow via your limiter config (optional here)
        // Kick off the flow; manager handles “email may not exist” silently
        $this->manager->startPasswordReset($dto);

        return $this->json(['message' => 'If the email exists, a reset link has been sent'], Response::HTTP_OK);
    }

    /**
     * POST /password/reset
     * Body: { "uid": number, "token": "plainToken", "password": "NewP@ss" }
     */
    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $req): JsonResponse
    {
        $dto = $this->resetMapper->fromRequest($req);
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        try {
            $this->manager->confirmPasswordReset($dto);
        } catch (\DomainException $e) {
            return $this->json(
                ['error' => ['code' => 'INVALID_TOKEN', 'details' => ['message' => $e->getMessage()]]],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['message' => 'Password updated'], Response::HTTP_OK);
    }
}
