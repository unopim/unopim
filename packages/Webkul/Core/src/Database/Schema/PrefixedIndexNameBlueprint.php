<?php

namespace Webkul\Core\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrefixedIndexNameBlueprint extends Blueprint
{
    public const MAX_INDEX_NAME_LENGTH = 63;

    public function renameIndex($from, $to)
    {
        return parent::renameIndex($this->existingIndexName($from, 'index'), $this->prefixIndexName($to));
    }

    public function prefixIndexName(string $index): string
    {
        return static::nameFor(static::indexPrefix($this->connection), $index);
    }

    /**
     * The prefix explicit index names get: the table prefix, unless the connection turns
     * `prefix_indexes` off, the same rule Laravel applies to the names it generates.
     */
    public static function indexPrefix(?Connection $connection = null): string
    {
        $connection ??= Schema::getConnection();

        return $connection->getConfig('prefix_indexes') ? $connection->getTablePrefix() : '';
    }

    /**
     * A name that already starts with the prefix is kept as is, so packages that still
     * prefix their names by hand do not end up with the prefix twice.
     */
    public static function nameFor(string $prefix, string $index): string
    {
        if ($prefix === '' || str_starts_with($index, $prefix)) {
            return $index;
        }

        $name = $prefix.$index;

        if (strlen($name) <= self::MAX_INDEX_NAME_LENGTH) {
            return $name;
        }

        return substr($name, 0, self::MAX_INDEX_NAME_LENGTH - 9).'_'.substr(md5($index), 0, 8);
    }

    public static function indexExists(string $table, string $index): bool
    {
        $existing = array_map(strtolower(...), array_column(Schema::getIndexes($table), 'name'));
        $candidates = [static::nameFor(static::indexPrefix(), $index), $index];

        return array_intersect(array_map(strtolower(...), $candidates), $existing) !== [];
    }

    public static function realCaseName(string $found, ?string $name): string
    {
        if ($name === null) {
            return $found;
        }

        $prefixed = static::nameFor(static::indexPrefix(), $name);

        return match ($found) {
            strtolower($prefixed) => $prefixed,
            strtolower($name)     => $name,
            default               => $found,
        };
    }

    protected function indexCommand($type, $columns, $index, $algorithm = null, $operatorClass = null)
    {
        return parent::indexCommand($type, $columns, $index ? $this->prefixIndexName($index) : $index, $algorithm, $operatorClass);
    }

    protected function dropIndexCommand($command, $type, $index)
    {
        if (is_array($index)) {
            return parent::indexCommand($command, $index, $this->createIndexName($type, $index));
        }

        return parent::indexCommand($command, [], $index ? $this->existingIndexName($index, $type) : $index);
    }

    protected function existingIndexName(string $index, string $type): string
    {
        $prefixed = $this->prefixIndexName($index);

        if ($prefixed === $index) {
            return $index;
        }

        $existing = array_map(strtolower(...), $this->existingIndexNames($type));

        return in_array(strtolower($index), $existing, true) && ! in_array(strtolower($prefixed), $existing, true)
            ? $index
            : $prefixed;
    }

    /**
     * @return array<int, string>
     */
    protected function existingIndexNames(string $type): array
    {
        try {
            $schema = $this->connection->getSchemaBuilder();

            $definitions = $type === 'foreign'
                ? $schema->getForeignKeys($this->table)
                : $schema->getIndexes($this->table);

            return array_column($definitions, 'name');
        } catch (\Throwable) {
            return [];
        }
    }
}
