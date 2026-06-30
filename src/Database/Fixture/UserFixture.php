<?php

declare(strict_types=1);

namespace App\Database\Fixture;

use App\Database\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserFixture
 * @package App\Database\Fixture
 */
class UserFixture extends Fixture implements OrderedFixtureInterface
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
                'email' => 'email@example.pl',
                'first_name' => 'Janusz',
                'is_active' => true,
                'last_name' => 'Kowalski',
                'roles' => [
                    'ROLE_ADMIN',
                ],
            ],
        ];

        foreach ($data as $user) {
            $userEntity = new User();
            $userEntity->email = $user['email'];
            $userEntity->firstName = $user['first_name'];
            $userEntity->isActive = $user['is_active'];
            $userEntity->lastName = $user['last_name'];
            $userEntity->roles = $user['roles'];

            $manager->persist($userEntity);
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return 100;
    }
}
