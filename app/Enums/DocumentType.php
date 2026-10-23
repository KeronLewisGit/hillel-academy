<?php

namespace App\Enums;

enum DocumentType: string
{
    case BirthCertificate = 'birth_certificate';
    case SchoolReports = 'school_reports';
    case Immunisation = 'immunisation';
    case Passport = 'passport';

    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Birth certificate',
            self::SchoolReports => 'Latest school reports',
            self::Immunisation => 'Immunisation record',
            self::Passport => 'Passport (photo page)',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::BirthCertificate => 'A clear scan or photo of the full certificate.',
            self::SchoolReports => 'The two most recent reports from the current school.',
            self::Immunisation => 'The child’s immunisation card or a doctor’s record.',
            self::Passport => 'Needed for students who are not Jamaican nationals.',
        };
    }

    /** Required documents depend on the grade: the youngest children have no reports yet. */
    public function requiredFor(string $grade): bool
    {
        return match ($this) {
            self::BirthCertificate, self::Immunisation => true,
            self::SchoolReports => ! in_array($grade, (array) config('hillel.no_reports_grades'), true),
            self::Passport => false,
        };
    }

    /** @return list<self> */
    public static function requiredForGrade(string $grade): array
    {
        return array_values(array_filter(self::cases(), fn (self $t) => $t->requiredFor($grade)));
    }
}
