<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Bir kullanıcıyı VIP Ortak rolüne yükseltir (owner ile premium arası üst yetkili).
 *
 *   php artisan mentorde:make-vip ahmet@example.com
 *   php artisan mentorde:make-vip ahmet@example.com --revert   (manager'a geri al)
 */
class MakeVip extends Command
{
    protected $signature = 'mentorde:make-vip {email : Hedef kullanıcının e-postası} {--revert : Rolü manager\'a geri al}';

    protected $description = 'Kullanıcıyı VIP Ortak rolüne yükseltir (veya --revert ile manager\'a geri alır)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user  = User::query()->where('email', $email)->first();

        if (!$user) {
            $this->error("Kullanıcı bulunamadı: {$email}");
            return self::FAILURE;
        }

        $target  = $this->option('revert') ? User::ROLE_MANAGER : User::ROLE_VIP;
        $oldRole = (string) $user->role;

        if ($oldRole === $target) {
            $this->info("Zaten '{$target}' rolünde: {$email}");
            return self::SUCCESS;
        }

        $user->forceFill(['role' => $target])->save();

        $this->info("✓ {$email}: '{$oldRole}' → '{$target}'");
        $this->line('Kullanıcı çıkış yapıp tekrar girmeli (oturum rolü yenilensin).');

        return self::SUCCESS;
    }
}
