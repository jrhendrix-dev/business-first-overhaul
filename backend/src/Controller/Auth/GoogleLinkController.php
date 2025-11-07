<?php
declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\GoogleIdTokenVerifier;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Link / Unlink Google to the CURRENT authenticated user.
 */
final class GoogleLinkController extends AbstractController
{
    public function __construct(
        private readonly GoogleIdTokenVerifier $verifier,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Link current account with Google using a fresh ID token.
     * Body: { "idToken": "..." }
     */
    #[Route('/auth/google/link', name: 'api_auth_google_link', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function link(Request $request): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();
        $data   = json_decode($request->getContent() ?: '[]', true);
        $idToken = (string)($data['idToken'] ?? '');

        if ($idToken === '') {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['idToken' => 'Missing']]], 422);
        }

        $payload = $this->verifier->verify($idToken);
        if (!$payload) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['idToken' => 'Invalid']]], 422);
        }

            // ✅ accept either key; verifier already returns 'id'
        $googleSub   = (string)($payload['id'] ?? $payload['sub'] ?? '');
        $googleEmail = (string)($payload['email'] ?? '');

        if ($googleSub === '') {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['idToken' => 'Missing sub']]], 422);
        }

        // Prevent linking to an already-linked Google account
        $other = $this->users->findOneBy(['googleSub' => $googleSub]);
        if ($other && $other->getId() !== $me->getId()) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => 'google_already_linked']]], 422);
        }

        $me->setGoogleSub($googleSub);
        $me->setGoogleLinkedAt(new \DateTimeImmutable());
        // If you do track a provider string, keep; otherwise remove the 2 lines below.
        // if (method_exists($me, 'setOauthProvider')) { $me->setOauthProvider('google'); }

        $this->em->flush();

        return $this->json(['message' => 'linked', 'googleEmail' => $googleEmail]);
    }

    /**
     * Unlink Google from the current account.
     * DELETE /api/auth/google/link
     */
    #[Route('/api/auth/google/link', name: 'api_auth_google_unlink', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unlink(): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();

        $me->setGoogleSub(null);
        $me->setGoogleLinkedAt(null);
        // if (method_exists($me, 'getOauthProvider') && $me->getOauthProvider() === 'google' && method_exists($me, 'setOauthProvider')) {
        //     $me->setOauthProvider(null);
        // }
        $this->em->flush();

        return $this->json(['message' => 'unlinked']);
    }
}
