<?php

namespace App\Database\Repository;

use App\Database\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepository extends ServiceEntityRepository
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param User $user
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @return User
     */
    function userUpdate(User $user, Request $request, UserPasswordHasherInterface $passwordHasher): User
    {
        $user->firstName = $request->request->get('firstName', $user->firstName);
        $user->lastName = $request->request->get('lastName', $user->lastName);
        $user->email = $request->request->get('email', $user->email);
        $user->isActive = $request->request->get('isActive') === '1';

        if ($request->request->get('password')) {
            $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));
            $user->password = $hashedPassword;
        }

        $roles = $request->request->all('roles');
        $user->roles = $roles ?? [];

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @param string $role
     * @return QueryBuilder
     */
    public function findByRole(string $role): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :pattern')
            ->setParameter('pattern', '%"' . $role . '"%')
            ->orderBy('u.lastName', 'ASC');
    }

}
