<?php

namespace App\Command;

use App\Database\Repository\OfficeRegistrationRepository;
use App\Database\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package App\Command
 */
#[AsCommand(
    name: 'app:clean-old-registration',
    description: 'Usuwa stare puste rejestracje na konsultacje',
)]
class CronCleanOldRegistration extends Command
{
    /**
     * @param OfficeRegistrationRepository $officeRegistrationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly RegistrationRepository $registrationRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoff = new \DateTimeImmutable()->modify('-1 day');
        $io->section('Usuwanie starych pustych rejestracji na konsultacje');
        $io->text('Data graniczna: ' . $cutoff->format('Y-m-d H:i:s'));

        $oldApps = $this->registrationRepository->createQueryBuilder('a')
            ->leftJoin('a.registeredStudents', 'rs')
            ->andWhere('rs.id IS NULL')
            ->andWhere('a.startsAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();

        $count = count($oldApps);

        foreach ($oldApps as $app) {
            $this->entityManager->remove($app);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Usunięto %d pustych rejestracji na konsultacje.', $count));

        return Command::SUCCESS;
    }
}
