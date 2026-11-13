<?php

namespace App\Support;

/**
 * India-specific formatting and validation used across bills and the admin.
 *
 *  - money():  ₹1,23,456.50 (lakh/crore digit grouping, not 123,456)
 *  - words():  "Rupees One Thousand Two Hundred Ten and Fifty Paise Only"
 *  - GSTIN / FSSAI / UPI VPA / mobile validation
 *  - GST state code lookup (first two digits of a GSTIN)
 */
final class India
{
    public const SAC_RESTAURANT = '996331';

    public const GSTIN_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';

    public const FSSAI_REGEX = '/^[0-9]{14}$/';

    public const VPA_REGEX = '/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z][a-zA-Z0-9.\-]{1,64}$/';

    public const MOBILE_REGEX = '/^[6-9][0-9]{9}$/';

    /** GST state codes most relevant to NCR first; full list for GSTIN validation. */
    public const STATES = [
        '07' => 'Delhi', '06' => 'Haryana', '09' => 'Uttar Pradesh', '08' => 'Rajasthan',
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
        '05' => 'Uttarakhand', '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram', '16' => 'Tripura', '17' => 'Meghalaya',
        '18' => 'Assam', '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh', '24' => 'Gujarat', '26' => 'Dadra and Nagar Haveli and Daman and Diu',
        '27' => 'Maharashtra', '29' => 'Karnataka', '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala',
        '33' => 'Tamil Nadu', '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana',
        '37' => 'Andhra Pradesh', '38' => 'Ladakh',
    ];

    /** Indian-grouped amount: 1234567.5 -> "12,34,567.50". */
    public static function number(string|int|float|null $amount, int $decimals = 2): string
    {
        $value = (float) ($amount ?? 0);
        $negative = $value < 0;
        $fixed = number_format(abs($value), $decimals, '.', '');
        [$int, $frac] = array_pad(explode('.', $fixed), 2, '');

        if (strlen($int) > 3) {
            $last3 = substr($int, -3);
            $rest = substr($int, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int = $rest.','.$last3;
        }

        return ($negative ? '-' : '').$int.($decimals > 0 ? '.'.$frac : '');
    }

    /** ₹ prefixed Indian-grouped amount. */
    public static function money(string|int|float|null $amount, int $decimals = 2): string
    {
        $formatted = self::number($amount, $decimals);

        return str_starts_with($formatted, '-') ? '-₹'.substr($formatted, 1) : '₹'.$formatted;
    }

    /** Amount in words, Indian system (lakh / crore), as printed on tax invoices. */
    public static function words(string|int|float|null $amount): string
    {
        $value = round(abs((float) ($amount ?? 0)), 2);
        $rupees = (int) floor($value);
        $paise = (int) round(($value - $rupees) * 100);

        $text = 'Rupees '.($rupees === 0 ? 'Zero' : self::spell($rupees));
        if ($paise > 0) {
            $text .= ' and '.self::spell($paise).' Paise';
        }

        return $text.' Only';
    }

    private static function spell(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $twoDigits = function (int $x) use ($ones, $tens): string {
            if ($x < 20) {
                return $ones[$x];
            }

            return trim($tens[intdiv($x, 10)].' '.$ones[$x % 10]);
        };

        $parts = [];
        foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']] as [$unit, $label]) {
            if ($n >= $unit) {
                $count = intdiv($n, $unit);
                $parts[] = ($unit === 10000000 ? self::spell($count) : $twoDigits($count)).' '.$label;
                $n %= $unit;
            }
        }
        if ($n > 0) {
            $parts[] = $twoDigits($n);
        }

        return implode(' ', $parts);
    }

    /** Normalises + validates a GSTIN (format and state code). */
    public static function isValidGstin(?string $gstin): bool
    {
        $gstin = strtoupper(trim((string) $gstin));

        return (bool) preg_match(self::GSTIN_REGEX, $gstin)
            && array_key_exists(substr($gstin, 0, 2), self::STATES)
            && self::gstinChecksumOk($gstin);
    }

    /** GSTIN check digit (15th char) — base-36 Luhn-style mod algorithm. */
    private static function gstinChecksumOk(string $gstin): bool
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $value = strpos($chars, $gstin[$i]);
            $product = $value * (($i % 2) + 1);
            $sum += intdiv($product, 36) + ($product % 36);
        }
        $check = (36 - ($sum % 36)) % 36;

        return $chars[$check] === $gstin[14];
    }

    public static function stateFromGstin(?string $gstin): ?string
    {
        return self::STATES[substr(strtoupper(trim((string) $gstin)), 0, 2)] ?? null;
    }

    /** Strips +91 / 0 / spaces; returns the bare 10-digit mobile or null. */
    public static function normaliseMobile(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match(self::MOBILE_REGEX, $digits) ? $digits : null;
    }

    /**
     * upi://pay deep link for a dynamic QR (scannable by PhonePe, GPay, Paytm,
     * BHIM…). The amount is fixed so the customer cannot mistype it.
     */
    public static function upiLink(string $vpa, string $payeeName, string|float $amount, ?string $note = null): string
    {
        $params = [
            'pa' => $vpa,
            'pn' => mb_substr($payeeName, 0, 40),
            'am' => number_format((float) $amount, 2, '.', ''),
            'cu' => 'INR',
        ];
        if ($note) {
            $params['tn'] = mb_substr($note, 0, 60);
        }

        // Some UPI apps reject a percent-encoded "@" in the VPA — keep it literal.
        return 'upi://pay?'.str_replace('%40', '@', http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }
}
