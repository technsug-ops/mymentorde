<?php

namespace App\Services;

use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Public landing envanteri:
 *  - config/public_landings.php manuel registry (kuratörlü açıklama + edit pointer)
 *  - Routes'a kayıtlı public route'larla karşılaştırır
 *  - Eksik (registry'de YOK ama route'da VAR) ve ölü (registry'de VAR
 *    ama route'da YOK) entry'leri tespit eder
 *
 * Manager bu sayede her yeni public landing eklediğinde
 * /manager/landing-inventory sayfasında hatırlatma görür.
 */
class LandingInventoryService
{
    /** @return array<int,array<string,mixed>> */
    public function registry(): array
    {
        return (array) config('public_landings', []);
    }

    /**
     * Tüm public-erişimli GET route'ları topla (auth/role gerektirmeyenler).
     * Sadece bilinen public prefix'leri dahil et — sistem rotaları (api, _ignition vs.) hariç.
     *
     * @return array<int,string>
     */
    public function publicRoutes(): array
    {
        $known = [];
        foreach (RouteFacade::getRoutes() as $route) {
            /** @var RouteInstance $route */
            if (! in_array('GET', $route->methods(), true)) continue;

            $uri = '/' . ltrim($route->uri(), '/');
            if ($this->shouldSkip($uri, $route)) continue;
            if (! $this->isLikelyPublicLanding($route)) continue;

            $known[] = $uri;
        }

        return array_values(array_unique($known));
    }

    /**
     * Karşılaştırma:
     *  - missing: route'ta var ama registry'de yok (eksik)
     *  - dead: registry'de var ama route'ta yok (ölü)
     *  - matched: ikisinde de var (sağlıklı)
     *
     * @return array{matched:array<int,array>, missing:array<int,string>, dead:array<int,array>}
     */
    public function diff(): array
    {
        $registry = $this->registry();
        $routes   = $this->publicRoutes();

        // Registry path'lerini normalize (param placeholder'larını {x} olarak)
        $registryPaths = array_map(fn ($e) => $this->normalizePath((string) ($e['path'] ?? '')), $registry);

        $matched = [];
        $missing = [];
        foreach ($routes as $r) {
            $rNorm = $this->normalizePath($r);
            $idx = array_search($rNorm, $registryPaths, true);
            if ($idx !== false) {
                $matched[] = $registry[$idx];
            } else {
                $missing[] = $r;
            }
        }

        $dead = [];
        $matchedPaths = array_map(fn ($e) => $this->normalizePath((string) $e['path']), $matched);
        foreach ($registry as $entry) {
            if (! in_array($this->normalizePath((string) $entry['path']), $matchedPaths, true)) {
                $dead[] = $entry;
            }
        }

        return ['matched' => $matched, 'missing' => $missing, 'dead' => $dead];
    }

    /**
     * Type'a göre grupla (marketing / form / widget / legal / utility).
     *
     * @return array<string,array<int,array>>
     */
    public function grouped(): array
    {
        $groups = ['marketing' => [], 'form' => [], 'widget' => [], 'legal' => [], 'utility' => []];
        foreach ($this->registry() as $entry) {
            $type = (string) ($entry['type'] ?? 'utility');
            $groups[$type][] = $entry;
            if (! isset($groups[$type])) $groups[$type] = [$entry];
        }
        return $groups;
    }

    private function normalizePath(string $path): string
    {
        $p = '/' . ltrim($path, '/');
        // {anything} → {*}
        return preg_replace('/\{[^}]+\}/', '{*}', $p);
    }

    private function shouldSkip(string $uri, RouteInstance $route): bool
    {
        // Sistem / debug / api / livewire / sanctum / _ignition rotalarını atla
        $skipPrefixes = ['api/', '_ignition', 'horizon', 'telescope', 'sanctum/', 'livewire/'];
        foreach ($skipPrefixes as $p) {
            if (str_starts_with($uri, '/' . $p) || str_starts_with(ltrim($uri, '/'), $p)) return true;
        }
        // Closure-only auth/role middleware ile başlayan rotalar atlanmaz, public landing olabilir
        return false;
    }

    private function isLikelyPublicLanding(RouteInstance $route): bool
    {
        $middleware = $route->gatherMiddleware();
        // auth, manager.role, senior.role, dealer.role gibi role guard varsa public değil
        $authGuards = ['auth', 'manager.role', 'senior.role', 'dealer.role', 'student.role', 'marketing.access', 'guest.access'];
        foreach ($authGuards as $guard) {
            foreach ($middleware as $mw) {
                if ($mw === $guard || str_starts_with((string) $mw, $guard . ':')) return false;
            }
        }
        return true;
    }
}
