<?php

namespace App\Domain\Enums;

enum UserRole: string
{
    case Dispatcher = 'dispatcher';
    case Master = 'master';
}

