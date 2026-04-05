<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasTable() ve Schema::hasColumn() sonuçlarını request bazında önbelleğe alır.
 * Migration tamamlandıktan sonra bu kontroller her seferinde true döner;
 * static cache ile her kontrol sadece ilk sorguda DB'ye gider.
 */
class SchemaCache
{
    private static array $tables  = [];
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::$columns["{$table}.{$column}"] ??= Schema::hasColumn($table, $column);
    }
}
