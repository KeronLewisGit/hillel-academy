<?php

namespace App\Enums;

enum EnquiryKind: string
{
    case Admissions = 'admissions';
    case Tour = 'tour';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Admissions => 'Admissions enquiry',
            self::Tour => 'Campus visit',
            self::General => 'General message',
        };
    }
}
