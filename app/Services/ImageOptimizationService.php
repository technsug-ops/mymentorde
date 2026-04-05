<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Profile photo upload için GD tabanlı WebP dönüşümü ve yeniden boyutlandırma.
 * GD yüklü değilse orijinal dosyayı olduğu gibi kaydeder (fallback).
 */
class ImageOptimizationService
{
    /**
     * Profil fotoğrafını WebP'ye dönüştür, MAX_DIM'e küçült, disk'e kaydet.
     *
     * @param  UploadedFile  $file
     * @param  string        $directory   Disk içindeki hedef klasör (ör. "guest-profile/5")
     * @param  string        $baseName    Uzantısız dosya adı (ör. "profile_20260325_120000")
     * @param  string        $disk        Filesystem diski (default: public)
     * @param  int           $maxDim      Maksimum genişlik/yükseklik (px)
     * @param  int           $quality     WebP kalitesi (0-100)
     * @return string  Storage disk içindeki tam yol
     */
    public function optimizeProfilePhoto(
        UploadedFile $file,
        string $directory,
        string $baseName,
        string $disk = 'public',
        int $maxDim = 400,
        int $quality = 82
    ): string {
        if (!extension_loaded('gd')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            return (string) $file->storeAs($directory, $baseName . '.' . $ext, $disk);
        }

        $img = $this->createGdImage($file);

        if (!$img) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            return (string) $file->storeAs($directory, $baseName . '.' . $ext, $disk);
        }

        $img = $this->resizeIfNeeded($img, $maxDim);

        $storagePath = $directory . '/' . $baseName . '.webp';
        $this->saveWebp($img, $storagePath, $disk, $quality);

        // Thumbnail (200px) — srcset için
        $imgThumb    = $this->createGdImage($file);
        if ($imgThumb) {
            $imgThumb     = $this->resizeIfNeeded($imgThumb, 200);
            $thumbPath    = $directory . '/' . $baseName . '_thumb.webp';
            $this->saveWebp($imgThumb, $thumbPath, $disk, $quality);
        }

        imagedestroy($img);

        return $storagePath;
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function saveWebp(\GdImage $img, string $storagePath, string $disk, int $quality): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'webp_');
        imagewebp($img, $tempFile, $quality);
        Storage::disk($disk)->put($storagePath, file_get_contents($tempFile));
        @unlink($tempFile);
    }

    /** @return \GdImage|false */
    private function createGdImage(UploadedFile $file)
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType() ?? '';

        return match (true) {
            str_contains($mime, 'jpeg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'png')  => @imagecreatefrompng($path),
            str_contains($mime, 'webp') => @imagecreatefromwebp($path),
            default                     => @imagecreatefromjpeg($path),
        };
    }

    /** Orantılı küçültme; zaten küçükse dokunmaz. */
    private function resizeIfNeeded(\GdImage $img, int $maxDim): \GdImage
    {
        $origW = imagesx($img);
        $origH = imagesy($img);

        if ($origW <= $maxDim && $origH <= $maxDim) {
            return $img;
        }

        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW  = (int) round($origW * $ratio);
        $newH  = (int) round($origH * $ratio);

        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($img);

        return $resized;
    }
}
