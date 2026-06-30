<?php

declare(strict_types=1);

namespace App\Core\User;

use App\Core\BaseManager;
use App\Database\Entity\User;
use App\Graph\GraphCreateUserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserManager
 * @package App\Core\User
 */
final class UserManager extends BaseManager
{
    /**
     * UserManager constructor
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly UserRepository $userRepository, private readonly EntityManagerInterface $entityManager, private readonly GraphCreateUserService $graphCreateUserService
    ) {}

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->userRepository->getAll();
    }

    /**
     * @return QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        return $this->userRepository->getAllQueryBuilder();
    }

    /**
     * @param string $email
     * @return User
     */
    public function getOneByEmail(string $email): User
    {
        $user = $this->userRepository->getOneByEmail($email);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Nie znaleziono użytkownika');
        }

        return $user;
    }

    /**
     * @param string $userId
     * @return User
     */
    public function getOneById(string $userId): User
    {
        $user = $this->userRepository->getOneById($userId);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Nie znaleziono użytkownika');
        }

        return $user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function createUser(User $user): void
    {
        $this->basePersister->create($user, true);

        $this->graphCreateUserService->createUser($user->email);
    }

    /**
     * @param User $user
     * @return void
     */
    public function updateUser(User $user): void
    {
        $this->basePersister->update($user);
    }
}
