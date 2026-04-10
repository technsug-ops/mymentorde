<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset;
use Illuminate\Support\Facades\Storage;

class DigitalAssetThumbnailService
{
    private const SIZE = 320;

    /**
     * Returns the thumbnail path (relative to disk) or null if not generated.
     */
    public function generate(DigitalAsset $asset): ?string
    {
        if ($asset->category !== 'image') {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            return null; // GD yok
        }

        $disk = Storage::disk($asset->disk);
        if (!$disk->exists($asset->path)) {
            return null;
        }

        $raw = $disk->get($asset->path);
        $src = @imagecreatefromstring($raw);
        if (!$src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = max($w, $h) / self::SIZE;
        $newW  = max(1, (int) round($w / $ratio));
        $newH  = max(1, (int) round($h / $ratio));

        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        imagejpeg($dst, null, 82);
        $jpeg = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        $thumbDir  = dirname($asset->path) . '/thumbs';
        $thumbPath = $thumbDir . '/' . $asset->uuid . '.jpg';
        $disk->put($thumbPath, $jpeg);

        return $thumbPath;
    }
}
