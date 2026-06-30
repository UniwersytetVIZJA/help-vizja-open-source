<?php

namespace App\Service;

use App\Database\Entity\Registration;

final class CapacityService
{
    /**
     * @param Registration $registration
     * @return bool
     */
    public function isFull(Registration $registration): bool
    {
        $capacity = (int)($registration->capacity ?? 0);
        $registered = (int)($registration->registeredStudents->count() ?? 0);

        return $capacity > 0 && $registered >= $capacity;
    }

    /**
     * @param Registration $registration
     * @return string
     */
    public function labelFor(Registration $registration): string
    {
        return $this->isFull($registration)
            ? 'Zapisy zamknięte'
            : 'Zapisz się';
    }

}
