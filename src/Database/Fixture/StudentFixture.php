<?php

declare(strict_types=1);

namespace App\Database\Fixture;

use App\Database\Entity\Student;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserFixture
 * @package App\Database\Fixture
 */
class StudentFixture extends Fixture implements OrderedFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * UserFixture constructor
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'email' => 'test@example.pl',
                'first_name' => 'Test',
                'is_active' => true,
                'last_name' => 'Test',
                'password' => 'test',
                'roles' => [
                    'ROLE_STUDENT',
                ],
            ],
            [
                'email' => 'test2@example.pl',
                'first_name' => 'Test_2',
                'is_active' => true,
                'last_name' => 'Test_2',
                'password' => 'test',
                'roles' => [
                    'ROLE_GOSC',
                ],
            ],
        ];

        foreach ($data as $user) {
            $userEntity = new Student();
            $userEntity->email = $user['email'];
            $userEntity->firstName = $user['first_name'];
            $userEntity->isActive = $user['is_active'];
            $userEntity->lastName = $user['last_name'];
            $userEntity->roles = $user['roles'];

            $password = $this->passwordHasher->hashPassword($userEntity, $user['password']);
            $userEntity->password = $password;

            $manager->persist($userEntity);
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return 90;
    }
}
