<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HandbookController extends Controller
{
    // ── Section extraction ────────────────────────────────────────────────

    private function loadMarkdown(string $lang): string
    {
        $file = $lang === 'en'
            ? base_path('HANDBOOK_EN.md')
            : base_path('HANDBOOK_TR.md');

        if (!file_exists($file)) {
            return '';
        }

        $raw = file_get_contents($file);

        // White-label: marka adını config'ten okuyup tüm "MentorDE" referanslarını
        // dinamik hale getir. Yeni handbook'larda {brand} placeholder kullanılabilir.
        $brand     = (string) config('brand.name', 'MentorDE');
        $brandLow  = strtolower($brand);
        $brandSlug = preg_replace('/[^a-z0-9]/', '', $brandLow) ?: 'app';

        // 1. Önce placeholder'ları replace et (yeni handbook'lar için)
        $out = strtr($raw, [
            '{brand}'      => $brand,
            '{{brand}}'    => $brand,
            '{brand_low}'  => $brandLow,
            '{brand_slug}' => $brandSlug,
        ]);

        // 2. Hardcoded eski referansları case-sensitive olarak değiştir
        //    Sıralama önemli: önce uzun olanlar, sonra kısa olanlar
        $out = strtr($out, [
            'MentorDE' => $brand,    // büyük marka
            'MENTORDE' => strtoupper($brand),
            'mentorde' => $brandSlug, // küçük (slug, klasör adı, env değeri)
        ]);

        return $out;
    }

    /**
     * Split markdown into [header, [heading => content], ...] pairs
     * and return only the sections relevant to $role.
     */
    private function extractForRole(string $markdown, string $role): string
    {
        // Split on top-level ## headings, keeping the delimiter
        $parts = preg_split('/^(## .+)$/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = $parts[0] ?? ''; // doc title + intro

        $count = count($parts);
        for ($i = 1; $i < $count; $i += 2) {
            $heading = $parts[$i] ?? '';
            $body    = $parts[$i + 1] ?? '';

            $chunk = $this->filterSection($heading, $body, $role);
            if ($chunk !== false) {
                $result .= $heading . "\n" . $chunk;
            }
        }

        return $result;
    }

    /**
     * Return filtered content for a top-level section, or false to skip it.
     */
    private function filterSection(string $heading, string $body, string $role): string|false
    {
        $endUsers   = ['guest', 'student', 'dealer'];
        $staffRoles = ['manager', 'senior', 'marketing'];

        // Section 1 — System overview
        if (preg_match('/## 1[\. ]/u', $heading)) {
            if (in_array($role, $endUsers)) {
                // Only the first 600 chars (what/why)
                return mb_substr($body, 0, 600) . "\n\n---\n\n";
            }
            return $body;
        }

        // Section 2 — Roles table
        if (preg_match('/## 2[\. ]/u', $heading)) {
            return in_array($role, $staffRoles) ? $body : false;
        }

        // Section 3 — Portal guides (extract relevant subsection)
        if (preg_match('/## 3[\. ]/u', $heading)) {
            return $this->extractPortalSubsection($body, $role);
        }

        // Section 4 — Module docs
        if (preg_match('/## 4[\. ]/u', $heading)) {
            return in_array($role, $staffRoles) ? $body : false;
        }

        // Section 5 — Integrations
        if (preg_match('/## 5[\. ]/u', $heading)) {
            return $role === 'manager' ? $body : false;
        }

        // Section 6 — Admin management
        if (preg_match('/## 6[\. ]/u', $heading)) {
            return $role === 'manager' ? $body : false;
        }

        // Section 7 — Security & GDPR
        if (preg_match('/## 7[\. ]/u', $heading)) {
            return in_array($role, ['manager', 'senior']) ? $body : false;
        }

        // Section 8 — FAQ (everyone)
        if (preg_match('/## 8[\. ]/u', $heading)) {
            return $body;
        }

        // Section 9+ — Son Değişiklikler / Changelog → sadece staff
        if (preg_match('/## 9[\. ]/u', $heading)) {
            return in_array($role, $staffRoles) ? $body : false;
        }

        // Bilinmeyen section'lar → sadece staff görsün (güvenli default)
        return in_array($role, $staffRoles) ? $body : false;
    }

    /**
     * From the "## 3. Portal Guides" body, extract only the ### subsection
     * that belongs to $role. Manager gets all.
     */
    private function extractPortalSubsection(string $body, string $role): string
    {
        if ($role === 'manager') {
            return $body;
        }

        $targetMap = [
            'senior'    => '3.2',
            'guest'     => '3.3',
            'student'   => '3.4',
            'dealer'    => '3.5',
            'marketing' => '3.6',
        ];

        $target = $targetMap[$role] ?? null;
        if (!$target) {
            return $body;
        }

        // Split on ### subsections
        $subs  = preg_split('/^(### .+)$/m', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $intro = $subs[0] ?? '';
        $result = $intro;

        $count = count($subs);
        for ($i = 1; $i < $count; $i += 2) {
            $subHeading = $subs[$i] ?? '';
            $subBody    = $subs[$i + 1] ?? '';

            if (str_contains($subHeading, $target)) {
                $result .= $subHeading . "\n" . $subBody;
            }
        }

        return $result;
    }

    // ── Render helper ─────────────────────────────────────────────────────

    private function render(Request $request, string $role, string $view): \Illuminate\View\View
    {
        $lang    = in_array($request->get('lang'), ['tr', 'en']) ? $request->get('lang') : 'tr';
        $raw     = $this->loadMarkdown($lang);
        $content = $this->extractForRole($raw, $role);
        $html    = Str::markdown($content, ['html_input' => 'allow', 'allow_unsafe_links' => false]);

        return view($view, compact('html', 'lang', 'role'));
    }

    // ── Portal endpoints ──────────────────────────────────────────────────

    public function manager(Request $request)
    {
        return $this->render($request, 'manager', 'handbook.manager');
    }

    public function senior(Request $request)
    {
        return $this->render($request, 'senior', 'handbook.senior');
    }

    public function guest(Request $request)
    {
        return $this->render($request, 'guest', 'handbook.guest');
    }

    public function student(Request $request)
    {
        return $this->render($request, 'student', 'handbook.student');
    }

    public function dealer(Request $request)
    {
        return $this->render($request, 'dealer', 'handbook.dealer');
    }

    public function marketing(Request $request)
    {
        return $this->render($request, 'marketing', 'handbook.marketing');
    }

    // ── HTML download ─────────────────────────────────────────────────────

    public function download(Request $request, string $role = 'all')
    {
        $lang    = in_array($request->get('lang'), ['tr', 'en']) ? $request->get('lang') : 'tr';
        $raw     = $this->loadMarkdown($lang);
        $validRoles = ['manager', 'senior', 'guest', 'student', 'dealer', 'marketing'];
        $filteredRole = in_array($role, $validRoles) ? $role : 'manager';
        $content = $this->extractForRole($raw, $filteredRole);
        $html    = Str::markdown($content, ['html_input' => 'allow', 'allow_unsafe_links' => false]);

        $brand   = (string) config('brand.name', 'MentorDE');
        $title   = $lang === 'en' ? "{$brand} Handbook" : "{$brand} Kullanıcı Kılavuzu";
        $fullHtml = view('handbook.export', compact('html', 'lang', 'role', 'title'))->render();

        $fileSlug = Str::slug($brand) ?: 'handbook';
        return response($fullHtml, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileSlug . '-handbook-' . $lang . '.html"',
        ]);
    }
}
