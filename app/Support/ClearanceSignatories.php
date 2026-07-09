<?php

namespace App\Support;

class ClearanceSignatories
{
    /**
     * @var array<string, array{label: string, seeded_email: string}>
     */
    public const SIGNATORIES = [
        'dean' => [
            'label' => 'Dean',
            'seeded_email' => 'dean@svci.test',
        ],
        'president' => [
            'label' => 'Office of the President',
            'seeded_email' => 'president@svci.test',
        ],
        'librarian' => [
            'label' => 'Librarian',
            'seeded_email' => 'librarian@svci.test',
        ],
        'student_affairs' => [
            'label' => 'Dean of Student Affairs',
            'seeded_email' => 'student_affairs@svci.test',
        ],
        'alumni' => [
            'label' => 'SVC Alumni Officer',
            'seeded_email' => 'alumni@svci.test',
        ],
        'guidance' => [
            'label' => 'Guidance Counselor',
            'seeded_email' => 'guidance@svci.test',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return array_keys(self::SIGNATORIES);
    }

    public static function isSignatoryRole(?string $role): bool
    {
        return is_string($role) && array_key_exists($role, self::SIGNATORIES);
    }

    public static function label(string $role): string
    {
        return self::SIGNATORIES[$role]['label'] ?? str($role)->replace('_', ' ')->title()->toString();
    }

    /**
     * @return array{status: string, remarks: string, signed_by: string, signed_at: string, signer: string, signer_payload: string, label: string}
     */
    public static function columns(string $role): array
    {
        if (! self::isSignatoryRole($role)) {
            throw new \InvalidArgumentException('Invalid clearance signatory role.');
        }

        $signerRelation = str($role)->camel()->toString().'Signer';

        return [
            'status' => "{$role}_status",
            'remarks' => "{$role}_remarks",
            'signed_by' => "{$role}_signed_by",
            'signed_at' => "{$role}_signed_at",
            'signer' => $signerRelation,
            'signer_payload' => str($signerRelation)->snake()->toString(),
            'label' => self::label($role),
        ];
    }

    /**
     * @return array<int, array{role: string, label: string, status: string, remarks: string, signed_by: string, signed_at: string, signer: string, signer_payload: string}>
     */
    public static function definitions(): array
    {
        return collect(self::roles())
            ->map(fn (string $role): array => [
                'role' => $role,
                ...self::columns($role),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function signerRelations(): array
    {
        return collect(self::roles())
            ->map(fn (string $role): string => self::columns($role)['signer'])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function roleOptions(bool $includeStudentAndSuperAdmin = false): array
    {
        $roles = ['admin', ...self::roles()];

        if ($includeStudentAndSuperAdmin) {
            $roles = ['student', ...$roles, 'superadmin'];
        }

        return $roles;
    }
}
