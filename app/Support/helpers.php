<?php

use App\Models\Setting;

if (! function_exists('hillel')) {
    /** Static school facts from config/hillel.php. */
    function hillel(string $key, mixed $default = null): mixed
    {
        return config('hillel.'.$key, $default);
    }
}

if (! function_exists('demo_links')) {
    /**
     * Whether sign-in links are also shown on screen (demos without email). Never in
     * production, whatever HILLEL_DEMO_LINKS says: on a live site it would let anyone
     * sign in as any family by typing their email address.
     */
    function demo_links(): bool
    {
        return (bool) config('hillel.demo_links') && ! app()->isProduction();
    }
}

if (! function_exists('setting')) {
    /** Office-editable value from the settings table, falling back to config('hillel.settings'). */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::read($key, $default);
    }
}

if (! function_exists('grades')) {
    /**
     * Every grade the school admits into, in order: key => label.
     *
     * @return array<string, string>
     */
    function grades(): array
    {
        $out = [];
        foreach ((array) config('hillel.grades') as $key => $grade) {
            $out[$key] = $grade['label'];
        }

        return $out;
    }
}

if (! function_exists('grade_label')) {
    function grade_label(?string $key): string
    {
        return (string) (config('hillel.grades.'.$key.'.label') ?? $key);
    }
}

if (! function_exists('entry_years')) {
    /**
     * School years families can apply for, from the "entry_years" setting ("2026/27, 2027/28").
     *
     * @return list<string>
     */
    function entry_years(): array
    {
        $years = preg_split('/[\s,;]+/', (string) setting('entry_years'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($years, fn (string $y) => (bool) preg_match('/^\d{4}\/\d{2}$/', $y)));
    }
}

if (! function_exists('usd')) {
    function usd(int|float $amount): string
    {
        return 'US$'.number_format($amount);
    }
}

if (! function_exists('jmd')) {
    function jmd(int|float $amount): string
    {
        return 'J$'.number_format($amount);
    }
}

if (! function_exists('nanp_digits')) {
    /** E.164 digits for a North American Numbering Plan number (Jamaica is +1 876/658) written any common way, or null. */
    function nanp_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return strlen($digits) === 11 && $digits[0] === '1' ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    function tel_href(?string $number): string
    {
        $digits = nanp_digits($number);

        return $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /** (876) 941-2912 for Jamaican and other +1 numbers, the input unchanged otherwise. */
    function format_phone(?string $number): string
    {
        $digits = nanp_digits($number);
        if (! $digits) {
            return (string) $number;
        }

        return sprintf('(%s) %s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img (WebP at up to 480/960/1920 wide).
     * Small originals were never upscaled, so the srcset only advertises real widths.
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('hillel.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        $w = (int) $meta['w'];
        $url = fn (int $size) => asset("img/{$key}-{$size}.webp");
        $set = [];
        foreach ([480, 960, 1920] as $size) {
            if ($size <= $w) {
                $set[] = $url($size).' '.$size.'w';
            }
        }
        if ($w < 1920) {
            // The largest file holds the original width.
            $set[] = $url(1920).' '.$w.'w';
        }

        return [
            'src' => $url($w >= 960 ? 960 : 1920),
            'srcset' => implode(', ', array_unique($set)),
            'width' => $w,
            'height' => (int) $meta['h'],
        ];
    }
}
