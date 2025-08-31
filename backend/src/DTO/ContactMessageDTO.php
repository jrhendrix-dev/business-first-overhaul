<?php
declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Transport for contact form payload.
 */
final class ContactMessageDTO
{
    /** @var string Full name of the sender */
    #[Assert\NotBlank(message: "Name is required.")]
    #[Assert\Length(max: 120)]
    public string $name;

    /** @var string Reply-to email */
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "Invalid")]
    #[Assert\Length(max: 180)]
    public string $email;

    /** @var string Short subject line */
    #[Assert\NotBlank(message: "Subject is required.")]
    #[Assert\Length(max: 140)]
    public string $subject;

    /** @var string Body content */
    #[Assert\NotBlank(message: "Message is required.")]
    #[Assert\Length(min: 10, max: 4000)]
    public string $message;

    /** @var bool Marketing consent / privacy checkbox */
    #[Assert\NotNull(message: "Consent is required.")]
    #[Assert\Type('bool')]
    public bool $consent = false;

    /**
     * Honeypot field: MUST be empty or missing. Bots fill it.
     * Not mapped/validated beyond emptiness in controller.
     * @var string|null
     */
    public ?string $website = null;

    /**
     * Optional reCAPTCHA v3 token, if you enable verification.
     * @var string|null
     */
    public ?string $captchaToken = null;
}
