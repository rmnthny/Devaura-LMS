<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS2D;

class QrCodeService
{
    public function generateMemberQrPng(string $value): string
    {
        $pngBinary = base64_decode($this->getQrPngBase64($value), true);
        $path = "qrcodes/members/{$value}.png";

        if ($pngBinary !== false && $pngBinary !== '') {
            // Member cards stay outside the public storage link.
            Storage::disk('local')->put($path, $pngBinary);
        }

        return $path;
    }

    public function getQrPngBase64(string $value, int $scale = 6): string
    {
        $dns2d = new DNS2D();

        return (string) $dns2d->getBarcodePNG($value, 'QRCODE', $scale, $scale);
    }

    public function getQrSvg(string $value, int $scale = 5): string
    {
        $dns2d = new DNS2D();

        return (string) $dns2d->getBarcodeSVG($value, 'QRCODE', $scale, $scale);
    }
}
