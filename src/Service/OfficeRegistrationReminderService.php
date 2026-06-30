<?php

namespace App\Service;

use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Mailer\Mail\OfficeRegistration\Reminder;
use App\Mailer\MailerService;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class OfficeRegistrationReminderService
{
    public function __construct(
        private OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepoisotory, private MailerService $mailerService, private EntityManagerInterface $entityManager
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws ORMException
     */
    public function sendReminders(): int
    {
        $tomorrow = (new \DateTimeImmutable('tomorrow'))->setTime(0, 0);

        $from = $tomorrow;
        $to = $tomorrow->modify('+1 day');

        $registrations = $this->officeRegistrationRegisteredStudentRepoisotory->findForReminder($from, $to);

        $sent = 0;

        foreach ($registrations as $registration) {
            $mailContent = Reminder::fromEntity($registration->student, $registration->registration);
            $this->mailerService->sendEmailToStudent($registration->student, $mailContent);

            $registration->reminderSentAt = new DateTimeImmutable();

            $sent++;
        }

        $this->entityManager->flush();

        return $sent;
    }
}
