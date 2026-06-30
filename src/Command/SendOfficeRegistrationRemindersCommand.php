<?php

namespace App\Command;

use App\Service\OfficeRegistrationReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:office-registration:send-reminders',
    description: 'Wysyła przypomnienia o wizytach w BON.'
)]
class SendOfficeRegistrationRemindersCommand extends Command
{
    public function __construct(
        private readonly OfficeRegistrationReminderService $officeRegistrationReminderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->officeRegistrationReminderService->sendReminders();

        $output->writeln(sprintf(
            'Wysłano %d przypomnień.',
            $count
        ));

        return Command::SUCCESS;
    }
}
