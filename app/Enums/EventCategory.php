<?php

namespace App\Enums;

enum EventCategory: string
{
    case Admissions = 'admissions';
    case Academic = 'academic';
    case Sports = 'sports';
    case Arts = 'arts';
    case Community = 'community';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Admissions => 'Admissions',
            self::Academic => 'Academic',
            self::Sports => 'Sport',
            self::Arts => 'Arts',
            self::Community => 'Community',
            self::Holiday => 'School closed',
        };
    }
}
