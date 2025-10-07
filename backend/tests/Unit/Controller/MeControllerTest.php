<?php
// tests/Unit/Controller/MeControllerTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Api\MeController;
use App\Dto\User\Password\ChangePasswordDto;
use App\Dto\User\UpdateUserDto;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\MeChangePasswordRequestMapper;
use App\Mapper\Request\UserUpdateRequestMapper;
use App\Mapper\Response\GradeResponseMapper;
use App\Mapper\Response\MeResponseMapper;
use App\Mapper\Response\UserResponseMapper;
use App\Service\GradeManager;
use App\Service\UserManager;
use App\Tests\Support\EntityIdHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GradeRepository;
use App\Repository\ClassroomRepository;
use App\Service\Contracts\EnrollmentPort;

final class MeControllerTest extends TestCase
{
    #[Test]
    public function get_self_returns_authenticated_user_payload(): void
    {
        $user = (new User())
            ->setUserName('me')
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail('me@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);
        EntityIdHelper::setId($user, 99);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $users   = $this->createMock(UserManager::class);
        $validator = $this->createMock(ValidatorInterface::class);
        // ✅ real GradeManager with stubbed deps (no mocking of a final class)
        $grades = new GradeManager(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(GradeRepository::class),
            $this->createStub(EnrollmentPort::class),
            $this->createStub(ClassroomRepository::class),
        );


        // use real mappers (they're final)
        $userResp   = new UserResponseMapper();
        $meResp     = new MeResponseMapper();
        $gradeResp  = new GradeResponseMapper();
        $updateReq  = new UserUpdateRequestMapper();
        $pwdReq     = new MeChangePasswordRequestMapper();

        $controller = new MeController(
            security:            $security,
            users:               $users,
            toResponse:          $userResp,
            toMeResponse:        $meResp,
            manager:             $users,     // same service injected twice as in controller
            grades:              $grades,
            gradeResponseMapper: $gradeResp,
            updateMapper:        $updateReq,
            pwdMapper:           $pwdReq,
            validator:           $validator,
        );
        $controller->setContainer(new SymfonyContainer());

        $response = $controller->getSelf();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['id' => 99, 'email' => 'me@example.com', 'role' => 'ROLE_STUDENT'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function update_nullifies_restricted_fields_before_validation(): void
    {
        $user = (new User())
            ->setUserName('me')
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail('me@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $users = $this->createMock(UserManager::class);
        $users->expects($this->once())->method('updateUser')->with(
            $user,
            self::callback(static function (UpdateUserDto $dto): bool {
                // /me must ignore these three fields
                return $dto->email === null && $dto->password === null && $dto->role === null
                    // and still pass through editable fields
                    && $dto->firstName === 'Updated';
            })
        )->willReturn($user);

        $grades = new GradeManager(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(GradeRepository::class),
            $this->createStub(EnrollmentPort::class),
            $this->createStub(ClassroomRepository::class),
        );
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new MeController(
            security:            $security,
            users:               $users,
            toResponse:          new UserResponseMapper(),
            toMeResponse:        new MeResponseMapper(),
            manager:             $users,
            grades:              $grades,
            gradeResponseMapper: new GradeResponseMapper(),
            updateMapper:        new UserUpdateRequestMapper(),      // real
            pwdMapper:           new MeChangePasswordRequestMapper(),// real
            validator:           $validator,
        );
        $controller->setContainer(new SymfonyContainer());

        $request  = new Request(content: json_encode(['firstName' => 'Updated'], JSON_THROW_ON_ERROR));
        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function change_password_throws_when_confirmation_does_not_match(): void
    {
        // must be our concrete User (requireAuthenticatedUserEntity)
        $user = (new User())->setEmail('me@example.com')->setRole(UserRoleEnum::STUDENT);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $users     = $this->createMock(UserManager::class);
        $grades = new GradeManager(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(GradeRepository::class),
            $this->createStub(EnrollmentPort::class),
            $this->createStub(ClassroomRepository::class),
        );
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $controller = new MeController(
            security:            $security,
            users:               $users,
            toResponse:          new UserResponseMapper(),
            toMeResponse:        new MeResponseMapper(),
            manager:             $users,
            grades:              $grades,
            gradeResponseMapper: new GradeResponseMapper(),
            updateMapper:        new UserUpdateRequestMapper(),
            pwdMapper:           new MeChangePasswordRequestMapper(), // real mapper; drive via request JSON
            validator:           $validator,
        );
        $controller->setContainer(new SymfonyContainer());

        $badBody = json_encode([
            'currentPassword' => 'old',
            'newPassword'     => 'NewPassword123!',
            'confirmPassword' => 'Different!',
        ], JSON_THROW_ON_ERROR);

        $this->expectException(ValidationException::class);

        $controller->changePassword(new Request(content: $badBody));
    }
}
