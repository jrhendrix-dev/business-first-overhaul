<?php
// src/Controller/Auth/RegisterController.php
declare(strict_types=1);

namespace App\Controller\Auth;

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

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $details = [];
            foreach ($errors as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        $user = $this->users->createUser(
            $dto->firstName, $dto->lastName, $dto->email, $dto->userName, $dto->password,
            UserRoleEnum::STUDENT
        );

        return $this->json($this->toResponse->toResponse($user), Response::HTTP_CREATED);
    }
}
