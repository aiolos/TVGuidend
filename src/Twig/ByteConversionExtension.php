<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ByteConversionExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return array(
            new TwigFilter('format_bytes', array($this, 'formatBytes')),
        );
    }

    public function getName(): string
    {
        return 'format_bytes';
    }

    public function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
