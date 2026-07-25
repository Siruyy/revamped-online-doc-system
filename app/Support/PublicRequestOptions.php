<?php

namespace App\Support;

class PublicRequestOptions
{
    public const DIVISIONS = [
        'college' => 'College / Graduate School',
        'basic_education' => 'Basic Education Campus (BEC)',
    ];

    public const BASIC_EDUCATION_LEVELS = [
        'elementary' => ['code' => 'Elementary', 'label' => 'Elementary'],
        'junior_high' => ['code' => 'JHS', 'label' => 'Junior High School'],
        'senior_high' => ['code' => 'SHS', 'label' => 'Senior High School'],
    ];

    public const TERMS = [
        'first_semester' => 'First Semester',
        'second_semester' => 'Second Semester',
        'summer' => 'Summer',
    ];

    public const PURPOSES = [
        'Employment',
        'Board examination',
        'Further studies',
        'Transfer',
        'Passport or visa application',
        'Record evaluation',
        'Personal copy',
        'Other official purpose',
    ];

    public const PAYMENT_METHODS = [
        'gcash' => 'GCash',
        'bank_deposit' => 'Bank deposit / transfer',
    ];

    /**
     * @return list<string>
     */
    public static function academicYears(int $count = 80): array
    {
        $endingYear = (int) now()->format('Y') + 1;

        return collect(range($endingYear, $endingYear - $count + 1))
            ->map(fn (int $year): string => ($year - 1).'-'.$year)
            ->values()
            ->all();
    }

    public static function attendanceLabel(string $term, string $year): string
    {
        return (self::TERMS[$term] ?? str($term)->replace('_', ' ')->title()->toString()).' '.$year;
    }
}
