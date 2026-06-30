<?php

namespace App\Command;

use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Database\Repository\OfficeRegistrationRepository;
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
    name: 'app:clean-null-office-registed-students',
    description: 'Usuwa niedokończone wizyty w BON',
)]
class CronCleanNullOfficeRegisteredStudents extends Command
{
    /**
     * @param OfficeRegistrationRepository $officeRegistrationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private OfficeRegistrationRepository $officeRegistrationRepository,
        private EntityManagerInterface $entityManager, private readonly OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoff = new \DateTimeImmutable()->modify('-1 day');
        $io->section('Usuwanie starych pustych zapisów do BON');
        $io->text('Data graniczna: ' . $cutoff->format('Y-m-d H:i:s'));

        $oldApps = $this->officeRegistrationRegisteredStudentRepository->findInactiveMeetings();

        $count = count($oldApps);

        foreach ($oldApps as $app) {
            $this->entityManager->remove($app);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Usunięto %d pustych zapisów.', $count));

        return Command::SUCCESS;
    }
}
