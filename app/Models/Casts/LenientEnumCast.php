<?php

namespace App\Models\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Lenient BackedEnum cast.
 *
 * Laravel's built-in enum cast calls `Enum::from($value)` on read, which
 * throws a hard `ValueError` when the stored value is not part of the enum.
 * That scenario is realistic here: owners occasionally hand-edit a user's
 * `role` in the database (e.g. to 'staff') or MySQL non-strict mode silently
 * stores '' for an unknown enum value.
 *
 * This cast returns `null` for unknown stored values instead of failing, so
 * every caller (`isAdmin()`, the role gates, the `role:` middleware and the
 * login redirect) treats such a user as a non-privileged regular staff member
 * instead of crashing the whole request.
 */
class LenientEnumCast implements CastsAttributes
{
    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public function __construct(private readonly string $enum) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?BackedEnum
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ($this->enum)::from($value);
        } catch (\ValueError) {
            return null;
        }
    }

    /**
     * @param  mixed  $value  A BackedEnum, enum string value, or null.
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_string($value) || is_int($value)) {
            try {
                return ($this->enum)::from($value)->value;
            } catch (\ValueError) {
                throw new InvalidArgumentException(
                    sprintf('"%s" is not a valid backing value for enum %s.', (string) $value, $this->enum)
                );
            }
        }

        throw new InvalidArgumentException('Invalid enum value.');
    }
}