<?php

namespace App\TwigHelper\GlobalExtension;

use App\Core\Application\ApplicationRepository;
use App\Database\Entity\Student;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NewEmployeeComment extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ApplicationRepository $applicationRepository,
    ) {}

    public function getGlobals(): array
    {
        $student = $this->security->getUser();

        if (!$student instanceof Student) {
            return ['hasNewEmployeeComment' => false];
        }

        return [
            'hasNewEmployeeComment' => $this->applicationRepository->hasUnreadComments($student),
        ];
    }
}
