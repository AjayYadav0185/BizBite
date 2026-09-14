<?php

namespace App\Support;

/**
 * Pure-PHP BCMath replacement for hosts without the ext-bcmath extension.
 *
 * Mirrors real BCMath semantics closely enough for money math:
 *  - all operands/values are decimal strings ("123.45");
 *  - `scale` controls the number of fractional digits kept;
 *  - bcmul/bcdiv TRUNCATE toward zero (never round), like real bcmath;
 *  - bcdiv by zero throws DivisionByZeroError, like real bcmath;
 *  - operands are truncated (not rounded) to `scale`, like real bcmath.
 *
 * Arithmetic runs on 64-bit integers scaled by 10^scale, which is exact for
 * every amount this app handles (safe up to ~92,23,37,20,36,854.77 rupees).
 */
final class BcMathFallback
{
    /** Parse a decimal string into an integer scaled by 10^$scale (truncating). */
    public static function toScaled(string $operand, int $scale): int
    {
        $operand = trim($operand);

        if ($operand === '' || ! is_numeric($operand)) {
            $operand = '0';
        }

        // Handle scientific notation by normalising through float first.
        if (stripos($operand, 'e') !== false) {
            $operand = number_format((float) $operand, max($scale, 10), '.', '');
        }

        $negative = $operand[0] === '-' || $operand[0] === '+';
        $operand = ltrim($operand, '+-');

        [$int, $frac] = array_pad(explode('.', $operand, 2), 2, '0');
        $frac = str_pad(substr($frac, 0, $scale), $scale, '0');

        $scaled = (int) ($int.$frac);

        return $negative ? -$scaled : $scaled;
    }

    /** Format an integer scaled by 10^$scale back into a decimal string. */
    public static function fromScaled(int $scaled, int $scale): string
    {
        if ($scale === 0) {
            return (string) $scaled;
        }

        $negative = $scaled < 0;
        $scaled = abs($scaled);
        $divisor = 10 ** $scale;

        $int = intdiv($scaled, $divisor);
        $frac = str_pad((string) ($scaled % $divisor), $scale, '0', STR_PAD_LEFT);

        return ($negative && ($int > 0 || $frac !== str_repeat('0', $scale)) ? '-' : '')
            .$int.'.'.$frac;
    }

    public static function add(string $a, string $b, ?int $scale = null): string
    {
        $scale ??= 0;

        return self::fromScaled(self::toScaled($a, $scale) + self::toScaled($b, $scale), $scale);
    }

    public static function sub(string $a, string $b, ?int $scale = null): string
    {
        $scale ??= 0;

        return self::fromScaled(self::toScaled($a, $scale) - self::toScaled($b, $scale), $scale);
    }

    public static function mul(string $a, string $b, ?int $scale = null): string
    {
        $scale ??= 0;
        $divisor = 10 ** $scale;

        // Product is scaled 2*$scale; truncate back down toward zero.
        return self::fromScaled(intdiv(self::toScaled($a, $scale) * self::toScaled($b, $scale), $divisor), $scale);
    }

    public static function div(string $a, string $b, ?int $scale = null): string
    {
        $scale ??= 0;

        if (self::toScaled($b, $scale) === 0) {
            throw new \DivisionByZeroError('Division by zero');
        }

        $divisor = self::toScaled($b, $scale);
        $scaledA = self::toScaled($a, $scale);

        // Scale the numerator up so the quotient keeps $scale decimals.
        $quotient = intdiv($scaledA * (10 ** $scale), $divisor);

        return self::fromScaled($quotient, $scale);
    }

    /** Returns -1, 0 or 1 — identical contract to bccomp(). */
    public static function comp(string $a, string $b, ?int $scale = null): int
    {
        $scale ??= 0;

        $left = self::toScaled($a, $scale);
        $right = self::toScaled($b, $scale);

        return $left <=> $right;
    }

    public static function mod(string $a, string $b, ?int $scale = null): string
    {
        $scale ??= 0;
        $divisor = self::toScaled($b, $scale);

        if ($divisor === 0) {
            throw new \DivisionByZeroError('Modulo by zero');
        }

        $remainder = self::toScaled($a, $scale) % $divisor;

        return self::fromScaled($remainder, $scale);
    }
}
