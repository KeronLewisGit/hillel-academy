<?php

namespace App\Enums;

enum TeacherApplicationStatus: string
{
    case New = 'new';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Offered => 'Offer made',
            self::Hired => 'Hired',
            self::Declined => 'Declined',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Hired => 'good',
            self::Declined => 'muted',
            default => 'neutral',
        };
    }
}
