<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\ContactMessageDTO;
use App\Message\ContactMessage;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
/**
 * Accepts contact form submissions and queues an email.
 */
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly ValidatorInterface $validator,
        #[Autowire(service: 'contact_form.limiter')]
        private readonly RateLimiterFactory $contactLimiter,
    ) {}

    /**
     * @throws RandomException
     * @throws \JsonException
     * @throws ExceptionInterface
     */
    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string,mixed> $payload */
        $payload = (array)json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto = new ContactMessageDTO();
        $dto->name         = (string)($payload['name']    ?? '');
        $dto->email        = (string)($payload['email']   ?? '');
        $dto->subject      = (string)($payload['subject'] ?? '');
        $dto->message      = (string)($payload['message'] ?? '');
        $dto->consent      = (bool)  ($payload['consent'] ?? false);
        $dto->website      = isset($payload['website']) ? (string)$payload['website'] : null;
        $dto->captchaToken = isset($payload['captchaToken']) ? (string)$payload['captchaToken'] : null;

        // Honeypot: if filled -> pretend success to avoid tipping off bots.
        if (!empty($dto->website)) {
            return new JsonResponse(['status' => 'QUEUED', 'id' => 'hp_'.bin2hex(random_bytes(6))], 202);
        }

        // Validate
        $violations = $this->validator->validate($dto);
        if ($violations->count() > 0) {
            $details = [];
            foreach ($violations as $v) {
                $details[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => $details]],
                422
            );
        }

        // Rate limit per IP
        $limiter = $this->contactLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return new JsonResponse(
                ['error' => ['code' => 'RATE_LIMITED', 'details' => ['retry_after_seconds' => $limit->getRetryAfter()->getTimestamp() - time()]]],
                429
            );
        }

        // (Optional) reCAPTCHA v3 verification can be added here if enabled.

        $id = 'cmsg_'.date('Ymd').'_'.bin2hex(random_bytes(4));

        $this->bus->dispatch(new ContactMessage(
            id:        $id,
            name:      $dto->name,
            email:     $dto->email,
            subject:   $dto->subject,
            message:      $dto->message,
            consent:   $dto->consent,
            ip:        $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent')
        ));

        return new JsonResponse(['status' => 'QUEUED', 'id' => $id], 202);
    }
}
