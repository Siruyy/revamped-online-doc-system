<?php

namespace App\Support;

class FileUploadLimits
{
    public const PUBLIC_INTAKE_MAX_FILE_KB = 5120;

    public static function publicIntakeMaxFileKilobytes(): int
    {
        $serverMax = self::iniSizeToKilobytes(ini_get('upload_max_filesize'));

        if ($serverMax === null) {
            return self::PUBLIC_INTAKE_MAX_FILE_KB;
        }

        return min(self::PUBLIC_INTAKE_MAX_FILE_KB, $serverMax);
    }

    public static function publicIntakeMaxPostKilobytes(): ?int
    {
        return self::iniSizeToKilobytes(ini_get('post_max_size'));
    }

    /**
     * @return array{max_file_kb: int, max_file_bytes: int, max_total_kb: int|null, max_total_bytes: int|null}
     */
    public static function publicIntakePayload(): array
    {
        $maxFileKb = self::publicIntakeMaxFileKilobytes();
        $maxTotalKb = self::publicIntakeMaxPostKilobytes();

        return [
            'max_file_kb' => $maxFileKb,
            'max_file_bytes' => $maxFileKb * 1024,
            'max_total_kb' => $maxTotalKb,
            'max_total_bytes' => $maxTotalKb ? $maxTotalKb * 1024 : null,
        ];
    }

    public static function iniSizeToKilobytes(string|false $value): ?int
    {
        if ($value === false) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $amount = (float) $value;

        return (int) ceil(match ($unit) {
            'g' => $amount * 1024 * 1024,
            'm' => $amount * 1024,
            'k' => $amount,
            default => $amount / 1024,
        });
    }
}
