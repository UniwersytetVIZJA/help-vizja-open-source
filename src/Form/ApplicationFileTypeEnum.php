<?php

namespace App\Form;

enum ApplicationFileTypeEnum: string
{
    case APPLICATION_STUDENT_ATTACHMENT = 'application_student_attachment';
    case APPLICATION_EMPLOYEE_ATTACHMENT = 'application_employee_attachment';
}
