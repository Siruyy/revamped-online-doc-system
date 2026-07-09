<?php

namespace Tests\Unit\Support;

use App\Support\FileUploadLimits;
use PHPUnit\Framework\TestCase;

class FileUploadLimitsTest extends TestCase
{
    public function test_it_parses_php_ini_size_values_to_kilobytes(): void
    {
        $this->assertSame(5120, FileUploadLimits::iniSizeToKilobytes('5M'));
        $this->assertSame(8192, FileUploadLimits::iniSizeToKilobytes('8m'));
        $this->assertSame(1048576, FileUploadLimits::iniSizeToKilobytes('1G'));
        $this->assertSame(512, FileUploadLimits::iniSizeToKilobytes('512K'));
        $this->assertSame(1, FileUploadLimits::iniSizeToKilobytes('100'));
        $this->assertNull(FileUploadLimits::iniSizeToKilobytes('-1'));
        $this->assertNull(FileUploadLimits::iniSizeToKilobytes(false));
    }
}
