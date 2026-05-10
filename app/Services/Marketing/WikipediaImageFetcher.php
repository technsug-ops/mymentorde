<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Wikipedia/Wikimedia Commons'dan üniversite kapak görseli çek.
 *
 * Akış: pageimages (infobox) → logo ise prop=images ile sayfadaki tüm görseller
 * → ilk non-logo JPG → indir → storage/cms-covers'a kaydet + atıf bilgisi.
 */
class WikipediaImageFetcher
{
    /**
     * @return array{ok:bool,url?:string,path?:string,attribution?:string,page_title?:string,lang?:string,message?:string}
     */
    public function fetch(string $title): array
    {
        $http = Http::withHeaders([
            'User-Agent' => 'MentorDE/1.0 (https://panel.mentorde.com; technsug@gmail.com)',
            'Accept' => 'application/json',
        ])->timeout(15);

        $hit = $this->findImage($http, $title);
        if ($hit === null) {
            return ['ok' => false, 'message' => "Wikipedia'da '{$title}' için uygun görsel bulunamadı."];
        }

        [$artist, $license] = $this->fetchAttribution($http, $hit['lang'], $hit['file']);

        try {
            $imgResp = $http->get($hit['thumb']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Görsel indirilemedi: ' . $e->getMessage()];
        }
        if (!$imgResp->ok()) {
            return ['ok' => false, 'message' => 'Görsel indirilemedi (HTTP ' . $imgResp->status() . ').'];
        }

        $contentType = (string) $imgResp->header('Content-Type');
        $ext = match (true) {
            str_contains($contentType, 'jpeg') => 'jpg',
            str_contains($contentType, 'png')  => 'png',
            str_contains($contentType, 'webp') => 'webp',
            default => 'jpg',
        };
        $slug = Str::slug($hit['page_title']) ?: 'wiki';
        $name = 'cms-cover-wiki-' . $slug . '-' . now()->format('Ymd-His') . '-' . Str::random(4) . '.' . $ext;
        $path = 'cms-covers/' . $name;
        Storage::disk('public')->put($path, $imgResp->body());

        $attribution = trim('Foto: Wikimedia Commons'
            . ($artist !== '' ? ' / ' . $artist : '')
            . ($license !== '' ? ' (' . $license . ')' : ''));

        return [
            'ok' => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
            'attribution' => $attribution,
            'page_title' => $hit['page_title'],
            'lang' => $hit['lang'],
        ];
    }

    /**
     * @return array{lang:string,thumb:string,file:string,page_title:string}|null
     */
    private function findImage(PendingRequest $http, string $title): ?array
    {
        $fallback = null;
        foreach (['de', 'tr', 'en'] as $lang) {
            try {
                $resp = $http->get("https://{$lang}.wikipedia.org/w/api.php", [
                    'action' => 'query',
                    'prop' => 'pageimages|images',
                    'piprop' => 'thumbnail|name',
                    'pithumbsize' => 1200,
                    'imlimit' => 30,
                    'redirects' => 1,
                    'titles' => $title,
                    'format' => 'json',
                    'formatversion' => 2,
                ]);
            } catch (\Throwable $e) {
                continue;
            }
            if (!$resp->ok()) continue;

            foreach ((array) data_get($resp->json(), 'query.pages', []) as $page) {
                $pageTitle = (string) data_get($page, 'title', $title);
                $infoboxFile = (string) data_get($page, 'pageimage', '');
                $infoboxThumb = (string) data_get($page, 'thumbnail.source', '');

                if ($infoboxThumb !== '' && !$this->isLogoLike($infoboxFile)) {
                    return ['lang' => $lang, 'thumb' => $infoboxThumb, 'file' => $infoboxFile, 'page_title' => $pageTitle];
                }

                foreach ((array) data_get($page, 'images', []) as $img) {
                    $imgTitle = (string) data_get($img, 'title', '');
                    if ($imgTitle === '') continue;
                    $bare = preg_replace('/^(File|Datei|Bild|Dosya):/i', '', $imgTitle);
                    if ($this->isLogoLike($bare)) continue;
                    $thumb = $this->fetchCommonsThumbUrl($http, $lang, $imgTitle, 1200);
                    if ($thumb !== '') {
                        return ['lang' => $lang, 'thumb' => $thumb, 'file' => $bare, 'page_title' => $pageTitle];
                    }
                }

                if ($infoboxThumb !== '' && $fallback === null) {
                    $fallback = ['lang' => $lang, 'thumb' => $infoboxThumb, 'file' => $infoboxFile, 'page_title' => $pageTitle];
                }
            }
        }
        return $fallback;
    }

    /**
     * @return array{0:string,1:string} [artist, license]
     */
    private function fetchAttribution(PendingRequest $http, string $lang, string $file): array
    {
        if ($file === '') return ['', ''];
        try {
            $resp = $http->get("https://{$lang}.wikipedia.org/w/api.php", [
                'action' => 'query',
                'prop' => 'imageinfo',
                'iiprop' => 'extmetadata',
                'titles' => 'File:' . $file,
                'format' => 'json',
                'formatversion' => 2,
            ]);
        } catch (\Throwable $e) {
            return ['', ''];
        }
        if (!$resp->ok()) return ['', ''];
        $meta = (array) data_get($resp->json(), 'query.pages.0.imageinfo.0.extmetadata', []);
        $artist = trim(strip_tags((string) data_get($meta, 'Artist.value', '')));
        $license = (string) data_get($meta, 'LicenseShortName.value', '');
        return [$artist, $license];
    }

    private function fetchCommonsThumbUrl(PendingRequest $http, string $lang, string $fileTitle, int $width): string
    {
        try {
            $resp = $http->get("https://{$lang}.wikipedia.org/w/api.php", [
                'action' => 'query',
                'prop' => 'imageinfo',
                'iiprop' => 'url',
                'iiurlwidth' => $width,
                'titles' => $fileTitle,
                'format' => 'json',
                'formatversion' => 2,
            ]);
        } catch (\Throwable $e) {
            return '';
        }
        if (!$resp->ok()) return '';
        return (string) data_get($resp->json(), 'query.pages.0.imageinfo.0.thumburl', '');
    }

    private function isLogoLike(string $filename): bool
    {
        $f = strtolower($filename);
        if ($f === '') return true;
        if (str_ends_with($f, '.svg')) return true;
        foreach (['logo', 'flag', 'flagge', 'wappen', 'coat_of_arms', 'siegel', 'commons-logo', 'karte_', 'map_'] as $bad) {
            if (str_contains($f, $bad)) return true;
        }
        return false;
    }
}
