<?php
declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\GoogleIdTokenVerifier;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly GoogleIdTokenVerifier $verifier,
        private readonly UserRepository $users,
        private readonly JWTTokenManagerInterface $jwt,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $idToken = (string)((json_decode($request->getContent() ?: '[]', true)['idToken'] ?? ''));

        if ($idToken === '') {
            return $this->json(['error' => [
                'code' => 'VALIDATION_FAILED',
                'details' => ['idToken' => 'Missing'],
            ]], 422);
        }

        $payload = $this->verifier->verify($idToken);
        if (!$payload) {
            return $this->json(['error' => [
                'code' => 'VALIDATION_FAILED',
                'details' => ['idToken' => 'Invalid'],
            ]], 422);
        }

        // Find or auto-provision
        $user = $this->users->findOneBy(['email' => $payload['email']]);

        if (!$user) {
            $email  = $payload['email'];
            $given  = (string)($payload['given_name'] ?? '');
            $family = (string)($payload['family_name'] ?? '');
            $full   = (string)($payload['name'] ?? '');

            if ($given === '' && $full !== '') {
                [$given, $family] = array_pad(preg_split('/\s+/', $full, 2) ?: [], 2, '');
            }
            if ($given === '') {
                $given = ucfirst(strtok($email, '@'));
            }

            $user = (new User())
                ->setEmail($email)
                ->setUserName($email)
                ->setFirstName($given)
                ->setLastName($family)
                ->setRole(UserRoleEnum::STUDENT);

            // 👇 set an unusable random password to satisfy NOT NULL
            $random = bin2hex(random_bytes(24)); // 48 chars
            $user->setPassword($this->hasher->hashPassword($user, $random));

            // (optional) store linkage
            if (method_exists($user, 'setOauthProvider')) $user->setOauthProvider('google');
            if (method_exists($user, 'setOauthSubject'))  $user->setOauthSubject((string)($payload['id'] ?? $payload['sub'] ?? ''));

            // persist (use your repo helper or the EM)
            // $this->users->add($user, true);
            $this->em->persist($user); // if you injected the EM, use that
            $this->em->flush();
        }

        return $this->json(['token' => $this->jwt->create($user)], 200);
    }
}
