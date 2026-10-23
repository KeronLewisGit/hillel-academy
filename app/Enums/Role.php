<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Admissions = 'admissions';
    case Communications = 'communications';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Admissions => 'Admissions',
            self::Communications => 'Communications',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts, settings, fees, capacity and teacher recruitment.',
            self::Admissions => 'Enquiries, tours, applications, documents, assessments, decisions and email templates.',
            self::Communications => 'News and the events calendar only.',
        };
    }
}
