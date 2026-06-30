<?php

namespace App\Command;

use App\Core\Application\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CronCleanOldApplication
 * @package App\Command
 */
#[AsCommand(
    name: 'app:clean-old-applications',
    description: 'Usuwa wnioski starsze niż 30 minut od updatedAt',
)]
class CronCleanOldApplication extends Command
{
    /**
     * CronCleanOldApplication constructor
     * @param ApplicationRepository $applicationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private ApplicationRepository $applicationRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    /**
     * Usuwa z systemu wnioski z nieuzupełnionymi wymaganymi danymi.
     *
     * Wyszukuje wnioski z brakującymi polami (np. tryb studiów, wydział,
     * rok, kierunek, telefon lub numer albumu) i trwale usuwa je z bazy danych
     * wraz z powiązanymi encjami.
     *
     * @param InputInterface $input Dane wejściowe komendy
     * @param OutputInterface $output Wyjście konsoli
     *
     * @return int Kod zakończenia komendy
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoff = new \DateTimeImmutable('-30 minutes');
        $io->section('Usuwanie wniosków z nieuzupełnionymi danymi…');
        $io->text('Data graniczna: ' . $cutoff->format('Y-m-d H:i:s'));

        $oldApps = $this->applicationRepository->createQueryBuilder('a')
            ->orWhere('a.studyMode IS NULL')
            ->orWhere('a.faculty IS NULL')
            ->orWhere('a.year IS NULL')
            ->orWhere('a.department IS NULL')
            ->orWhere('a.phone IS NULL')
            ->orWhere('a.albumNumber IS NULL')
            ->getQuery()
            ->getResult();

        $count = count($oldApps);

        foreach ($oldApps as $app) {
            $this->entityManager->remove($app);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Usunięto %d wniosków wraz z powiązanymi plikami.', $count));

        return Command::SUCCESS;
    }
}
