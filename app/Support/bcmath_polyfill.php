<?php

/*
|--------------------------------------------------------------------------
| BCMath polyfill
|--------------------------------------------------------------------------
| Some hosting servers do not ship PHP's ext-bcmath, which made bcsub(),
| bccomp(), bcdiv() etc. fatal. This file is loaded on every request via
| composer's "files" autoloader and only defines the functions when the
| extension is missing — when ext-bcmath IS present, PHP's native (C)
| implementations win and nothing is touched.
*/

if (! extension_loaded('bcmath')) {
    if (! function_exists('bcadd')) {
        function bcadd(string $num1, string $num2, ?int $scale = null): string
        {
            return \App\Support\BcMathFallback::add($num1, $num2, $scale);
        }
    }

    if (! function_exists('bcsub')) {
        function bcsub(string $num1, string $num2, ?int $scale = null): string
        {
            return \App\Support\BcMathFallback::sub($num1, $num2, $scale);
        }
    }

    if (! function_exists('bcmul')) {
        function bcmul(string $num1, string $num2, ?int $scale = null): string
        {
            return \App\Support\BcMathFallback::mul($num1, $num2, $scale);
        }
    }

    if (! function_exists('bcdiv')) {
        function bcdiv(string $num1, string $num2, ?int $scale = null): string
        {
            return \App\Support\BcMathFallback::div($num1, $num2, $scale);
        }
    }

    if (! function_exists('bccomp')) {
        function bccomp(string $num1, string $num2, ?int $scale = null): int
        {
            return \App\Support\BcMathFallback::comp($num1, $num2, $scale);
        }
    }

    if (! function_exists('bcmod')) {
        function bcmod(string $num1, string $num2, ?int $scale = null): string
        {
            return \App\Support\BcMathFallback::mod($num1, $num2, $scale);
        }
    }

    if (! function_exists('bcscale')) {
        function bcscale(int $scale): int
        {
            static $current = 0;
            $previous = $current;
            $current = $scale;

            return $previous;
        }
    }
}
