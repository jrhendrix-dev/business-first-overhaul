<?php
// tests/Controller/PasswordResetControllerTest.php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PasswordResetControllerTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        return self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws \JsonException
     */
    public function testForgotAlways200_NoEnumeration(): void
    {
        $client = self::createClient();

        // unknown email
        $client->request(
            'POST',
            '/api/password/forgot',
            server: ['CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json'],
            content: json_encode(['email' => 'nope@nowhere.tld'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(200);

        // existing user
        $em = $this->em();
        $u  = (new User())
            ->setFirstName('A')->setLastName('B')
            ->setEmail('known@example.com')
            ->setUsername('known_'.uniqid('', true))
            ->setPassword('x')->setRole(UserRoleEnum::STUDENT);
        $em->persist($u); $em->flush();

        $client->request(
            'POST',
            '/api/password/forgot',
            server: ['CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json'],
            content: json_encode(['email' => 'known@example.com'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(200);
    }

    /**
     * @throws \JsonException
     */
    public function testForgotAlways200(): void
    {
        $c = self::createClient();
        $c->request('POST', '/api/password/forgot', server: [
            'CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json'
        ], content: json_encode(['email' => 'nobody@example.com'], JSON_THROW_ON_ERROR));
        self::assertResponseStatusCodeSame(200);
    }
}
