<?php

namespace Webkul\Core\Helpers\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Contracts\Database\Grammar;
use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

class GrammarQueryManager
{
    protected static array $instances = [];

    public static function getGrammar(?string $driver = null): Grammar
    {
        $driver = $driver ?? DB::getDriverName();

        if (isset(static::$instances[$driver])) {
            return static::$instances[$driver];
        }

        static::$instances[$driver] = match ($driver) {
            'pgsql' => new PostgresGrammar,
            'mysql' => new MySQLGrammar,
            default => throw new \RuntimeException("Unsupported DB driver: {$driver}")
        };

        return static::$instances[$driver];
    }
}
