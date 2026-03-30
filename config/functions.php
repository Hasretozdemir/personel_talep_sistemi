<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function durum_text(int $durum): string
{
    return match ($durum) {
        1 => 'Onaylandi',
        2 => 'Reddedildi',
        default => 'Beklemede',
    };
}
