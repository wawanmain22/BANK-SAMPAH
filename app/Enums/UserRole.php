<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Nasabah = 'nasabah';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Owner => 'Owner',
            self::Nasabah => 'Nasabah',
        };
    }
}
