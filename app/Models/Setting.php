<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Retrieve a setting value by key, applying type-aware casting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();

        if (!$row) {
            return $default;
        }

        return static::castValueOut($row->value, $row->type);
    }

    /**
     * Persist a setting value by key. Creates the row if it does not exist.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): self
    {
        $stored = static::castValueIn($value, $type);

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type]
        );
    }

    /**
     * Convert a stored string value back into its native PHP type.
     */
    protected static function castValueOut(?string $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $raw,
            'json', 'array' => json_decode($raw, true),
            default => $raw,
        };
    }

    /**
     * Convert a native PHP value into the string form stored in the DB.
     */
    protected static function castValueIn(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'integer', 'int' => (string) (int) $value,
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }
}
