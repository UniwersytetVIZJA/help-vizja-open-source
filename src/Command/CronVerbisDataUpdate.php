<?php

namespace App\Command;

use App\Core\Student\StudentManager;
use App\Core\Student\StudentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function count;
use function sprintf;

#[AsCommand(
    name: 'app:update-data-verbis',
    description: 'Aktualizuje dane studentów w encji przez Verbisa',
)]
class CronVerbisDataUpdate extends Command
{

    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly StudentManager $studentManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $students = $this->studentRepository->createQueryBuilder('s')
            ->andWhere('s.roles LIKE :role')
            ->setParameter('role', '%ROLE_STUDENT%')
            ->getQuery()
            ->getResult();

        $io->section('Pobieranie studentów');

        foreach ($students as $student) {
            $this->studentManager->updateWydzial($student);
        }

        $count = count($students);

        $io->success(sprintf('Zaktualizowano %d studentów.', $count));

        return Command::SUCCESS;
    }
}
