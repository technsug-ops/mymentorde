<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAssetFolder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class DigitalAssetFolderService
{
    public const MAX_DEPTH = 10;

    public function createFolder(string $name, ?int $parentId, User $user, array $extra = []): DigitalAssetFolder
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Klasör adı boş olamaz.');
        }

        $parent = null;
        if ($parentId) {
            $parent = DigitalAssetFolder::query()->findOrFail($parentId);
        }

        $depth = $parent ? ((int) $parent->depth + 1) : 0;
        if ($depth >= self::MAX_DEPTH) {
            throw new RuntimeException('Maksimum klasör derinliği aşıldı (' . self::MAX_DEPTH . ').');
        }

        $slug = $this->uniqueSlug($name, $parentId);
        $path = $parent ? rtrim($parent->path, '/') . '/' . $slug : '/' . $slug;

        return DigitalAssetFolder::query()->create([
            'parent_id'     => $parentId,
            'name'          => $name,
            'slug'          => $slug,
            'path'          => $path,
            'depth'         => $depth,
            'description'   => $extra['description'] ?? null,
            'color'         => $extra['color'] ?? null,
            'icon'          => $extra['icon'] ?? null,
            'is_system'     => (bool) ($extra['is_system'] ?? false),
            'allowed_roles' => $this->normalizeRoles($extra['allowed_roles'] ?? null),
            'created_by'    => $user->id,
        ]);
    }

    /**
     * Rol array'ini normalize et — boş/null ise null döner (= herkese açık).
     */
    private function normalizeRoles($roles): ?array
    {
        if (empty($roles)) {
            return null;
        }
        $clean = array_values(array_filter(array_map('trim', (array) $roles)));
        return empty($clean) ? null : $clean;
    }

    public function rename(DigitalAssetFolder $folder, string $newName): DigitalAssetFolder
    {
        if ($folder->is_system) {
            throw new RuntimeException('Sistem klasörü yeniden adlandırılamaz.');
        }

        $newName = trim($newName);
        if ($newName === '') {
            throw new RuntimeException('Klasör adı boş olamaz.');
        }

        $newSlug = $this->uniqueSlug($newName, $folder->parent_id, $folder->id);
        $oldPath = $folder->path;
        $parent  = $folder->parent;
        $newPath = $parent ? rtrim($parent->path, '/') . '/' . $newSlug : '/' . $newSlug;

        $folder->update([
            'name' => $newName,
            'slug' => $newSlug,
            'path' => $newPath,
        ]);

        // Alt klasörlerin path'lerini güncelle
        $this->rewriteDescendantPaths($folder, $oldPath, $newPath);

        return $folder->refresh();
    }

    public function delete(DigitalAssetFolder $folder): void
    {
        if ($folder->is_system) {
            throw new RuntimeException('Sistem klasörü silinemez.');
        }
        if ($folder->children()->exists() || $folder->assets()->exists()) {
            throw new RuntimeException('Klasör boş değil; önce içeriğini taşıyın veya silin.');
        }
        $folder->delete();
    }

    /**
     * Klasörü başka bir parent'a taşı. path/depth yeniden hesaplanır ve descendants cascade güncellenir.
     * null newParentId → root'a taşı.
     */
    public function move(DigitalAssetFolder $folder, ?int $newParentId): DigitalAssetFolder
    {
        if ($folder->is_system) {
            throw new RuntimeException('Sistem klasörü taşınamaz.');
        }

        // Aynı yere taşıma no-op
        if ((int) ($folder->parent_id ?? 0) === (int) ($newParentId ?? 0)) {
            return $folder;
        }

        $newParent = null;
        if ($newParentId) {
            $newParent = DigitalAssetFolder::query()->findOrFail($newParentId);

            // Kendi kendini veya descendant'ına taşıma yasak — cycle engellenir
            if ($newParent->id === $folder->id) {
                throw new RuntimeException('Klasör kendi içine taşınamaz.');
            }
            if (str_starts_with((string) $newParent->path, $folder->path . '/')) {
                throw new RuntimeException('Klasör alt klasörüne taşınamaz.');
            }
        }

        $newDepth = $newParent ? ((int) $newParent->depth + 1) : 0;

        // Descendants derinliğini hesaba kat — en derin child + move = max_depth'i aşmasın
        $maxDescendantDepth = (int) DigitalAssetFolder::query()
            ->where('path', 'like', $folder->path . '/%')
            ->max('depth');
        $currentMaxRelative = $maxDescendantDepth > 0 ? ($maxDescendantDepth - (int) $folder->depth) : 0;
        if (($newDepth + $currentMaxRelative) >= self::MAX_DEPTH) {
            throw new RuntimeException('Hedef konum çok derin, maksimum seviye aşılır (' . self::MAX_DEPTH . ').');
        }

        // Yeni slug'ı parent bağlamında unique yap (aynı isim olabilir başka yerde)
        $newSlug = $this->uniqueSlug($folder->name, $newParentId, $folder->id);
        $oldPath = $folder->path;
        $newPath = $newParent ? rtrim($newParent->path, '/') . '/' . $newSlug : '/' . $newSlug;
        $depthDiff = $newDepth - (int) $folder->depth;

        $folder->update([
            'parent_id' => $newParentId,
            'slug'      => $newSlug,
            'path'      => $newPath,
            'depth'     => $newDepth,
        ]);

        // Descendant path ve depth güncelleme
        DigitalAssetFolder::query()
            ->where('path', 'like', $oldPath . '/%')
            ->get()
            ->each(function (DigitalAssetFolder $child) use ($oldPath, $newPath, $depthDiff) {
                $child->path  = $newPath . substr($child->path, strlen($oldPath));
                $child->depth = (int) $child->depth + $depthDiff;
                $child->save();
            });

        return $folder->refresh();
    }

    public function tree(): Collection
    {
        return $this->treeForRole(null);
    }

    /**
     * Role göre filtrelenmiş klasör ağacı. null rol = tümü.
     * allowed_roles null/boş olan klasörler herkese açıktır.
     */
    public function treeForRole(?string $role): Collection
    {
        $all = DigitalAssetFolder::query()
            ->orderBy('depth')
            ->orderBy('name')
            ->get();

        // Role erişim filtresi
        if ($role !== null) {
            $all = $all->filter(fn ($f) => $f->isAccessibleByRole($role))->values();
        }

        $byParent = $all->groupBy(fn ($f) => (int) ($f->parent_id ?? 0));

        $build = function ($parentId) use (&$build, $byParent) {
            return ($byParent[$parentId] ?? collect())->map(function ($f) use (&$build) {
                return [
                    'id'            => (int) $f->id,
                    'name'          => $f->name,
                    'slug'          => $f->slug,
                    'path'          => $f->path,
                    'icon'          => $f->icon,
                    'color'         => $f->color,
                    'is_system'     => (bool) $f->is_system,
                    'is_restricted' => !empty($f->allowed_roles),
                    'description'   => $f->description,
                    'allowed_roles' => is_array($f->allowed_roles) ? $f->allowed_roles : [],
                    'children'      => $build((int) $f->id),
                ];
            })->values();
        };

        return $build(0);
    }

    private function uniqueSlug(string $name, ?int $parentId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'klasor';
        $slug = $base;
        $i    = 2;

        while (
            DigitalAssetFolder::query()
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function rewriteDescendantPaths(DigitalAssetFolder $folder, string $oldPath, string $newPath): void
    {
        DigitalAssetFolder::query()
            ->where('path', 'like', $oldPath . '/%')
            ->get()
            ->each(function (DigitalAssetFolder $child) use ($oldPath, $newPath) {
                $child->path = $newPath . substr($child->path, strlen($oldPath));
                $child->save();
            });
    }
}
