<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordResetManager;
use App\Service\ResetPasswordMailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JSON API for password reset requests and confirmation.
 */
#[Route('/api')]
final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetManager $passwordResetManager,
        private readonly ResetPasswordMailer $mailer,
        #[Autowire(service: 'limiter.forgot_ip')]    private readonly RateLimiterFactory $forgotIp,
        #[Autowire(service: 'limiter.forgot_email')] private readonly RateLimiterFactory $forgotEmail,
    ) {}

    /**
     * Request a password reset. Always returns 200 to avoid user enumeration.
     *
     * Body: { "email": "user@example.com" }
     * @throws \JsonException
     */
    #[Route('/password/forgot', name: 'api_password_forgot', methods: ['POST'])]
    public function forgot(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR) ?? [];
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $ip    = $request->getClientIp() ?? '0.0.0.0';

        // Per-IP limiter
        if (!$this->forgotIp->create($ip)->consume(1)->isAccepted()) {
            return $this->json(['message' => 'If that email exists, a reset link has been sent.']);
        }

        if ($email !== '') {
            $user = $this->users->findOneBy(['email' => $email]);
            if ($user instanceof User) {
                // Per-email limiter
                if (!$this->forgotEmail->create('e:' . $email)->consume(1)->isAccepted()) {
                    return $this->json(['message' => 'If that email exists, a reset link has been sent.']);
                }

                $plain = $this->passwordResetManager->issue($user, $ip);
                $this->mailer->send($user, $plain);
            }
        }

        return $this->json(['message' => 'If that email exists, a reset link has been sent.']);
    }

    /**
     * Reset a password using a token.
     *
     * Body: { "uid": 123, "token": "plainToken", "password": "NewP@ss" }
     * @throws \JsonException
     */
    #[Route('/password/reset', name: 'api_password_reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR) ?? [];
        $uid     = (int)($data['uid'] ?? 0);
        $token   = (string)($data['token'] ?? '');
        $newPass = (string)($data['password'] ?? '');

        if ($uid <= 0 || $token === '' || $newPass === '') {
            return $this->json(['error' => 'uid, token and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->users->find($uid);
        if (!$user instanceof User) {
            // Don’t leak existence
            return $this->json(['error' => 'Invalid token.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->passwordResetManager->consume($user, $token, $newPass);
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid or expired token.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Password updated.']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/dev/test-mail', methods: ['POST'])]
    public function testMail(MailerInterface $mailer): JsonResponse
    {
        $email = (new Email())
            ->from('no-reply@businessfirstacademy.net')
            ->to('test@example.com')
            ->subject('Mailpit smoke test')
            ->text('Hello from Symfony Mailer ✅');

        $mailer->send($email);

        return $this->json(['ok' => true]);
    }

}
