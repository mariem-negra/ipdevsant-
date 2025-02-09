<?php

namespace App\Enum;

enum UserRole: string
{
    case MEDECIN = 'Médecin';
    case PATIENT = 'Patient';
    case ADMIN = 'Admin';
}

