<?php
// src/Controller/Api/RegisterController.php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\User\CreateUserDto;
use App\Enum\UserRoleEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\UserCreateRequestMapper;
use App\Mapper\Response\UserResponseMapper;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


#[Route('/auth')]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly UserManager $users,
        private readonly ValidatorInterface $validator,
        private readonly UserCreateRequestMapper $mapper,
        private readonly UserResponseMapper $toResponse,
    ) {}

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        /** @var CreateUserDto $dto */
        $dto = $this->mapper->fromRequest($request);

        // DTO validation → multiple field errors supported
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) {
                // If you want to preserve multiple messages per field, make value an array;
                // Frontend already supports both string or string[].
                $details[$v->getPropertyPath()] = $v->getMessage();
            }
            // Keep a machine token too (frontend uses details.message to branch)
            $details['message'] = 'VALIDATION_FAILED';
            throw new ValidationException($details);
        }

        try {
            $user = $this->users->createUser(
                $dto->firstName,
                $dto->lastName,
                $dto->email,
                $dto->userName,
                $dto->password,
                UserRoleEnum::STUDENT
            );
        } catch (\DomainException $e) {
            // Map domain codes to per-field messages, **and** include a details.message token.
            $code = \strtolower($e->getMessage());
            $details = ['message' => $code];

            // Add as many fields as needed; both can show at once → multiple toasts
            if ($code === 'email_taken') {
                $details['email'] = 'Este email ya está en uso.';
            }
            if ($code === 'username_taken') {
                $details['userName'] = 'Este nombre de usuario ya existe.';
            }

            // Any other domain code → generic message (still 422)
            if (\count($details) === 1) { // only 'message' set
                $details['email'] ??= 'Valor inválido.';
            }

            throw new ValidationException($details);
        }

        return $this->json($this->toResponse->toResponse($user), Response::HTTP_CREATED);
    }
}
