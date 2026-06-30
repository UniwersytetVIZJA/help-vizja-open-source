<?php

namespace App\Mailer;

use App\Database\Entity\Student;
use App\Database\Entity\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment as Twig;

final readonly class MailerService
{
    /**
     * @param Twig $twig
     */
    public function __construct(
        private Twig $twig,
    ) {}

    /**
     * @param Student $student
     * @param MailContentInterface $mailContent
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendEmailToStudent(Student $student, MailContentInterface $mailContent): void
    {
        $noreplyEmail = 'noreply@example.com';

        $studentEmail = $student->email;

        $html = $this->twig->render($mailContent->getTemplate(), $mailContent->getContext());

        $email = new Email()
            ->from(new Address($noreplyEmail, 'Centrum Wsparcia'))
            ->to($studentEmail)
            ->subject($mailContent->getSubject())
            ->html($html);

        $transport = Transport::fromDsn($_ENV['MAILER_URL']);
        $mailer = new Mailer($transport);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new \Exception('Nie udało się wysłać maila: ' . $e->getMessage());
        }
    }

    /**
     * @param User $user
     * @param MailContentInterface $mailContent
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendEmailToEmployee(User $user, MailContentInterface $mailContent): void
    {
        $noreplyEmail = 'noreply@example.com';
        $userEmail = $user->email;

        $html = $this->twig->render($mailContent->getTemplate(), $mailContent->getContext());

        $email = new Email()
            ->from($noreplyEmail)
            ->to($userEmail)
            ->subject($mailContent->getSubject())
            ->html($html);

        $transport = Transport::fromDsn($_ENV['MAILER_URL']);
        $mailer = new Mailer($transport);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new \Exception('Nie udało się wysłać maila: ' . $e->getMessage());
        }
    }
}
