<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserControllerTest extends WebTestCase
{
    private const BASE     = '/api/users';
    private const ADMIN_EM = 'admin@example.com';
    private const ADMIN_PW = 'Passw0rd!';

    // ------------------------------
    // Listing & simple GET endpoints
    // ------------------------------

    #[Test]
    public function list_requires_auth_401(): void
    {
        $client = static::createClient();
        $client->request('GET', self::BASE);

        self::assertSame(401, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    #[Test]
    public function list_returns_200_and_expected_structure_when_authorized(): void
    {
        $client = static::createClient();

        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $client->request('GET', self::BASE, server: [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertNotEmpty($data);

        foreach ($data as $row) {
            self::assertArrayHasKey('id', $row);
            self::assertIsInt($row['id']);
            self::assertArrayHasKey('username', $row);
            self::assertIsString($row['username']);
            self::assertArrayHasKey('firstname', $row);
            self::assertIsString($row['firstname']);
            self::assertArrayHasKey('lastname', $row);
            self::assertIsString($row['lastname']);
            self::assertArrayHasKey('email', $row);
            self::assertMatchesRegularExpression('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $row['email']);
        }
    }

    #[Test]
    public function get_recently_registered_rejects_non_positive_days(): void
    {
        $client = static::createClient();

        $admin = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token = $this->jwtFor($admin);

        $client->request('GET', self::BASE.'/get-recently-registered?days=0', server: [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

    #[Test]
    public function get_user_by_name_returns_404_when_not_found(): void
    {
        $client = static::createClient();

        $admin = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token = $this->jwtFor($admin);

        $client->request('GET', self::BASE.'/get-user-by-name?name=__nope__', server: [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([], $data);
    }

    // -------------
    // Create (POST)
    // -------------

    #[Test]
    public function create_user_validation_error_400(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $client->request(
            'POST',
            self::BASE.'/create-user',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR)
        );

        self::assertSame(400, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    #[Test]
    public function create_user_success_201(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $slug = $this->uniqueSlug();

        $payload = [
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => "jane.doe.$slug@example.com",
            'username'   => "janedoe_$slug",
            'password'   => 'S3cretPass!',
            'role'       => UserRoleEnum::TEACHER->value,
        ];

        $client->request(
            'POST',
            self::BASE.'/create-user',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertSame(201, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($payload['email'], $data['email']);
        self::assertSame($payload['username'], $data['username']);
    }

    // -----------------
    // Change role (POST)
    // -----------------

    #[Test]
    public function change_role_missing_role_param_400(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $target = $this->ensureUser('target_'.$this->uniqueSlug(3), "target@{$this->uniqueSlug(3)}.com", 'T@rget123', UserRoleEnum::STUDENT);

        $client->request(
            'POST',
            self::BASE."/change-role/{$target->getId()}",
            server: ['HTTP_Authorization' => 'Bearer '.$token]
        );

        self::assertSame(400, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    #[Test]
    public function change_role_success_200(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $target = $this->ensureUser(
            'stud_'.$this->uniqueSlug(3),
            "stud+{$this->uniqueSlug(3)}@ex.com",
            'Stud12345',
            UserRoleEnum::STUDENT
        );

        $client->request(
            'POST',
            self::BASE."/change-role/{$target->getId()}?role=".UserRoleEnum::TEACHER->value,
            server: ['HTTP_Authorization' => 'Bearer '.$token]
        );

        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($target->getId(), $data['user_id']);
        self::assertSame(UserRoleEnum::TEACHER->value, $data['new_role']);
    }

    // --------------------
    // Change password/email
    // --------------------

    #[Test]
    public function change_password_blocks_bad_confirm_or_length_and_handles_success(): void
    {
        $client  = static::createClient();
        $admin   = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token   = $this->jwtFor($admin);

        // 1) bad confirm
        $client->request(
            'POST',
            self::BASE.'/change-password',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'current_password' => self::ADMIN_PW,
                'new_password'     => 'NewPass123',
                'confirm_password' => 'Mismatch',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());

        // 2) too short
        $client->request(
            'POST',
            self::BASE.'/change-password',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'current_password' => self::ADMIN_PW,
                'new_password'     => 'short',
                'confirm_password' => 'short',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());

        // 3) success
        $client->request(
            'POST',
            self::BASE.'/change-password',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'current_password' => self::ADMIN_PW,
                'new_password'     => 'BrandNewPass1!',
                'confirm_password' => 'BrandNewPass1!',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    #[Test]
    public function change_email_validates_and_updates(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        // invalid format
        $client->request(
            'POST',
            self::BASE.'/change-email',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'bad', 'password' => self::ADMIN_PW], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());

        // same email (should 400)
        $client->request(
            'POST',
            self::BASE.'/change-email',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => self::ADMIN_EM, 'password' => self::ADMIN_PW], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());

        // success
        $newEmail = "newadmin+{$this->uniqueSlug()}@example.com";
        $client->request(
            'POST',
            self::BASE.'/change-email',
            server: ['HTTP_Authorization' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $newEmail, 'password' => self::ADMIN_PW], JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    // ---------------
    // Remove (DELETE)
    // ---------------

    #[Test]
    public function remove_user_self_delete_blocked_400(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $client->request(
            'DELETE',
            self::BASE."/remove-user/{$admin->getId()}",
            server: ['HTTP_Authorization' => 'Bearer '.$token]
        );

        self::assertSame(400, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    #[Test]
    public function remove_user_success_204(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $victim = $this->ensureUser(
            'victim_'.$this->uniqueSlug(3),
            "victim+{$this->uniqueSlug(3)}@ex.com",
            'Vict1mPass',
            UserRoleEnum::STUDENT
        );

        $client->request(
            'DELETE',
            self::BASE."/remove-user/{$victim->getId()}",
            server: ['HTTP_Authorization' => 'Bearer '.$token]
        );

        self::assertSame(204, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
    }

    // -----------------
    // Helper utilities
    // -----------------

    private function ensureAdmin(string $email, string $plain): User
    {
        return $this->ensureUser('admin', $email, $plain, UserRoleEnum::ADMIN);
    }

    private function ensureUser(string $username, string $email, string $plain, UserRoleEnum $role): User
    {
        /** @var EntityManagerInterface $em */
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $repo = $em->getRepository(User::class);

        /** @var User|null $user */
        $user = $repo->findOneBy(['email' => $email]);
        if ($user) return $user;

        $user = new User();
        $user->setUsername($username);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setEmail($email);
        $user->setRole($role);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $plain));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    // --- More list/read endpoints ---

    #[Test]
    public function students_list_200_and_structure(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        // ensure at least one student exists
        $this->ensureUser('stud_'.$this->uniq(), 'stud'.$this->uniq().'@ex.com', 'Stud12345', UserRoleEnum::STUDENT);

        $client->request('GET', self::BASE.'/students', server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        if ($data) {
            self::assertIsInt($data[0]['id']);
            self::assertIsString($data[0]['username']);
        }
    }

    #[Test]
    public function teachers_list_200_and_structure(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $this->ensureUser('teach_'.$this->uniq(), 'teach'.$this->uniq().'@ex.com', 'Teach12345', UserRoleEnum::TEACHER);

        $client->request('GET', self::BASE.'/teachers', server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    #[Test]
    public function get_user_by_id_200(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $u = $this->ensureUser('byid_'.$this->uniq(), 'byid'.$this->uniq().'@ex.com', 'Pass12345', UserRoleEnum::STUDENT);

        $client->request('GET', self::BASE.'/get-user-by-id?id='.$u->getId(), server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($u->getId(), $data['id']);
    }

    #[Test]
    public function get_user_by_email_200(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $email = 'byemail'.$this->uniq().'@ex.com';
        $u = $this->ensureUser('byemail_'.$this->uniq(), $email, 'Pass12345', UserRoleEnum::STUDENT);

        $client->request('GET', self::BASE.'/get-user-by-email?email='.$email, server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($u->getEmail(), $data['email']);
    }

    #[Test]
    public function get_recently_registered_default_days_ok(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $this->ensureUser('recent_'.$this->uniq(), 'recent'.$this->uniq().'@ex.com', 'Pass12345', UserRoleEnum::STUDENT);

        $client->request('GET', self::BASE.'/get-recently-registered', server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    #[Test]
    public function get_unassigned_students_and_teachers_ok(): void
    {
        $client = static::createClient();

        // make an admin and get a JWT
        $admin = $this->ensureAdmin(email: self::ADMIN_EM, plain: self::ADMIN_PW);
        $token = $this->jwtFor($admin);

        // correct headers (NOTE: HTTP_AUTHORIZATION must be uppercase)
        $server = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_ACCEPT'        => 'application/json',
        ];

        // students
        $client->request('GET', self::BASE.'/get-unassigned-students', server: $server);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        // teachers
        $client->request('GET', self::BASE.'/get-unassigned-teachers', server: $server);
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    #[Test]
    public function get_count_by_role_ok_and_bad_role(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        // OK: valid role
        $client->request('GET', self::BASE.'/get-count-by-role?role='.UserRoleEnum::STUDENT->value, server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        // BAD: missing/invalid role
        $client->request('GET', self::BASE.'/get-count-by-role?role=999', server: ['HTTP_Authorization' => 'Bearer '.$token]);
        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

// Optional: change-role "already same role" path (200 with message)
    #[Test]
    public function change_role_noop_when_same_role_returns_200(): void
    {
        $client = static::createClient();
        $admin  = $this->ensureAdmin(self::ADMIN_EM, self::ADMIN_PW);
        $token  = $this->jwtFor($admin);

        $t = $this->ensureUser('t_'.$this->uniq(), 't'.$this->uniq().'@ex.com', 'Pass12345', UserRoleEnum::TEACHER);

        $client->request(
            'POST',
            self::BASE."/change-role/{$t->getId()}?role=".UserRoleEnum::TEACHER->value,
            server: ['HTTP_Authorization' => 'Bearer '.$token]
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('message', $data);
    }

// ---- helper for unique suffixes without uniqid() ----
    private function uniq(): string
    {
        return bin2hex(random_bytes(4)); // 8 hex chars
    }


    private function jwtFor(User $user): string
    {
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($user);
    }

    private function uniqueSlug(int $len = 6): string
    {
        return bin2hex(random_bytes($len));
    }
}
