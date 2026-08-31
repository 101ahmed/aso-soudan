<?php

namespace App\Support;

/**
 * Shapes Arabic into Unicode presentation forms and visual order so Dompdf
 * (LTR, no OpenType shaping) can render readable Arabic.
 */
class ArabicPdfGlyphs
{
    /**
     * Isolated, final, initial, medial presentation forms (Arabic Presentation Forms-B).
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    private static array $forms = [
        'ء' => ["\u{FE80}", "\u{FE80}", "\u{FE80}", "\u{FE80}"],
        'آ' => ["\u{FE81}", "\u{FE82}", "\u{FE81}", "\u{FE82}"],
        'أ' => ["\u{FE83}", "\u{FE84}", "\u{FE83}", "\u{FE84}"],
        'ؤ' => ["\u{FE85}", "\u{FE86}", "\u{FE85}", "\u{FE86}"],
        'إ' => ["\u{FE87}", "\u{FE88}", "\u{FE87}", "\u{FE88}"],
        'ئ' => ["\u{FE89}", "\u{FE8A}", "\u{FE8B}", "\u{FE8C}"],
        'ا' => ["\u{FE8D}", "\u{FE8E}", "\u{FE8D}", "\u{FE8E}"],
        'ب' => ["\u{FE8F}", "\u{FE90}", "\u{FE91}", "\u{FE92}"],
        'ة' => ["\u{FE93}", "\u{FE94}", "\u{FE93}", "\u{FE94}"],
        'ت' => ["\u{FE95}", "\u{FE96}", "\u{FE97}", "\u{FE98}"],
        'ث' => ["\u{FE99}", "\u{FE9A}", "\u{FE9B}", "\u{FE9C}"],
        'ج' => ["\u{FE9D}", "\u{FE9E}", "\u{FE9F}", "\u{FEA0}"],
        'ح' => ["\u{FEA1}", "\u{FEA2}", "\u{FEA3}", "\u{FEA4}"],
        'خ' => ["\u{FEA5}", "\u{FEA6}", "\u{FEA7}", "\u{FEA8}"],
        'د' => ["\u{FEA9}", "\u{FEAA}", "\u{FEA9}", "\u{FEAA}"],
        'ذ' => ["\u{FEAB}", "\u{FEAC}", "\u{FEAB}", "\u{FEAC}"],
        'ر' => ["\u{FEAD}", "\u{FEAE}", "\u{FEAD}", "\u{FEAE}"],
        'ز' => ["\u{FEAF}", "\u{FEB0}", "\u{FEAF}", "\u{FEB0}"],
        'س' => ["\u{FEB1}", "\u{FEB2}", "\u{FEB3}", "\u{FEB4}"],
        'ش' => ["\u{FEB5}", "\u{FEB6}", "\u{FEB7}", "\u{FEB8}"],
        'ص' => ["\u{FEB9}", "\u{FEBA}", "\u{FEBB}", "\u{FEBC}"],
        'ض' => ["\u{FEBD}", "\u{FEBE}", "\u{FEBF}", "\u{FEC0}"],
        'ط' => ["\u{FEC1}", "\u{FEC2}", "\u{FEC3}", "\u{FEC4}"],
        'ظ' => ["\u{FEC5}", "\u{FEC6}", "\u{FEC7}", "\u{FEC8}"],
        'ع' => ["\u{FEC9}", "\u{FECA}", "\u{FECB}", "\u{FECC}"],
        'غ' => ["\u{FECD}", "\u{FECE}", "\u{FECF}", "\u{FED0}"],
        'ف' => ["\u{FED1}", "\u{FED2}", "\u{FED3}", "\u{FED4}"],
        'ق' => ["\u{FED5}", "\u{FED6}", "\u{FED7}", "\u{FED8}"],
        'ك' => ["\u{FED9}", "\u{FEDA}", "\u{FEDB}", "\u{FEDC}"],
        'ل' => ["\u{FEDD}", "\u{FEDE}", "\u{FEDF}", "\u{FEE0}"],
        'م' => ["\u{FEE1}", "\u{FEE2}", "\u{FEE3}", "\u{FEE4}"],
        'ن' => ["\u{FEE5}", "\u{FEE6}", "\u{FEE7}", "\u{FEE8}"],
        'ه' => ["\u{FEE9}", "\u{FEEA}", "\u{FEEB}", "\u{FEEC}"],
        'و' => ["\u{FEED}", "\u{FEEE}", "\u{FEED}", "\u{FEEE}"],
        'ى' => ["\u{FEEF}", "\u{FEF0}", "\u{FEEF}", "\u{FEF0}"],
        'ي' => ["\u{FEF1}", "\u{FEF2}", "\u{FEF3}", "\u{FEF4}"],
        'ـ' => ['ـ', 'ـ', 'ـ', 'ـ'],
        "\u{FEFB}" => ["\u{FEFB}", "\u{FEFC}", "\u{FEFB}", "\u{FEFC}"],
        "\u{FEF7}" => ["\u{FEF7}", "\u{FEF8}", "\u{FEF7}", "\u{FEF8}"],
        "\u{FEF5}" => ["\u{FEF5}", "\u{FEF6}", "\u{FEF5}", "\u{FEF6}"],
        "\u{FEF9}" => ["\u{FEF9}", "\u{FEFA}", "\u{FEF9}", "\u{FEFA}"],
    ];

    private const LAM_ALEF = [
        'ا' => "\u{FEFB}",
        'أ' => "\u{FEF7}",
        'إ' => "\u{FEF5}",
        'آ' => "\u{FEF9}",
    ];

    public static function shapeHtml(string $html): string
    {
        $shaped = preg_replace_callback(
            '/>([^<]*)</u',
            static fn (array $m): string => '>'.self::shape($m[1]).'<',
            $html
        );

        return $shaped ?? $html;
    }

    public static function shape(string $text): string
    {
        if ($text === '' || ! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        $lines = preg_split("/\r\n|\r|\n/u", $text) ?: [$text];
        $sep = str_contains($text, "\r\n") ? "\r\n" : (str_contains($text, "\r") ? "\r" : "\n");

        if (count($lines) === 1) {
            return self::shapeLine($text);
        }

        return implode($sep, array_map([self::class, 'shapeLine'], $lines));
    }

    private static function shapeLine(string $line): string
    {
        if ($line === '' || ! preg_match('/\p{Arabic}/u', $line)) {
            return $line;
        }

        preg_match_all('/\p{Arabic}+|\P{Arabic}+/u', $line, $matches);
        $runs = $matches[0] ?? [];
        $shaped = [];

        foreach ($runs as $run) {
            if (preg_match('/\p{Arabic}/u', $run)) {
                $shaped[] = self::reverseClusters(self::applyForms($run));
            } else {
                $shaped[] = $run;
            }
        }

        return implode('', array_reverse($shaped));
    }

    private static function applyForms(string $arabic): string
    {
        $chars = self::combineLamAlef(self::split($arabic));
        $out = [];

        foreach ($chars as $i => $ch) {
            if (self::isTransparent($ch) || ! isset(self::$forms[$ch])) {
                $out[] = $ch;

                continue;
            }

            $prev = self::adjacentJoiner($chars, $i, -1);
            $next = self::adjacentJoiner($chars, $i, 1);
            $joinPrev = $prev !== null && self::joinsLeft($prev) && self::joinsRight($ch);
            $joinNext = $next !== null && self::joinsLeft($ch) && self::joinsRight($next);
            $forms = self::$forms[$ch];

            if ($joinPrev && $joinNext) {
                $out[] = $forms[3];
            } elseif ($joinPrev) {
                $out[] = $forms[1];
            } elseif ($joinNext) {
                $out[] = $forms[2];
            } else {
                $out[] = $forms[0];
            }
        }

        return implode('', $out);
    }

    /**
     * @param  list<string>  $chars
     * @return list<string>
     */
    private static function combineLamAlef(array $chars): array
    {
        $out = [];
        $n = count($chars);

        for ($i = 0; $i < $n; $i++) {
            if ($chars[$i] === 'ل' && isset($chars[$i + 1], self::LAM_ALEF[$chars[$i + 1]])) {
                $out[] = self::LAM_ALEF[$chars[$i + 1]];
                $i++;

                continue;
            }
            $out[] = $chars[$i];
        }

        return $out;
    }

    /**
     * @param  list<string>  $chars
     */
    private static function adjacentJoiner(array $chars, int $i, int $step): ?string
    {
        for ($j = $i + $step; $j >= 0 && $j < count($chars); $j += $step) {
            if (self::isTransparent($chars[$j])) {
                continue;
            }

            return isset(self::$forms[$chars[$j]]) ? $chars[$j] : null;
        }

        return null;
    }

    private static function joinsLeft(string $ch): bool
    {
        $forms = self::$forms[$ch] ?? null;

        return $forms !== null && $forms[2] !== $forms[0];
    }

    private static function joinsRight(string $ch): bool
    {
        return isset(self::$forms[$ch]) && $ch !== 'ء';
    }

    private static function reverseClusters(string $shaped): string
    {
        $clusters = [];
        $current = '';

        foreach (self::split($shaped) as $ch) {
            if ($current !== '' && self::isTransparent($ch)) {
                $current .= $ch;

                continue;
            }
            if ($current !== '') {
                $clusters[] = $current;
            }
            $current = $ch;
        }
        if ($current !== '') {
            $clusters[] = $current;
        }

        return implode('', array_reverse($clusters));
    }

    private static function isTransparent(string $ch): bool
    {
        return (bool) preg_match('/\p{Mn}/u', $ch);
    }

    /**
     * @return list<string>
     */
    private static function split(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
