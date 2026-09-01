<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;

class UploadRules
{
    /**
     * Extension-based image rules. Avoid Laravel `image`/`mimes` (ext-fileinfo).
     *
     * @return list<mixed>
     */
    public static function image(int $maxKb = 5120, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:'.$maxKb,
            self::extensionRule(['jpg', 'jpeg', 'png', 'webp', 'gif'], true),
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function document(int $maxKb = 12288, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:'.$maxKb,
            self::extensionRule(['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'xls', 'xlsx'], false),
        ];
    }

    /**
     * @param  list<string>  $extensions
     */
    private static function extensionRule(array $extensions, bool $allowImageMagic): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($extensions, $allowImageMagic): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $ext = strtolower($value->getClientOriginalExtension()
                ?: pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION)
                ?: '');

            if (in_array($ext, $extensions, true)) {
                return;
            }

            if ($allowImageMagic) {
                $head = (string) @file_get_contents($value->getRealPath() ?: $value->getPathname(), false, null, 0, 16);
                $looksLikeImage = str_starts_with($head, "\xFF\xD8\xFF")
                    || str_starts_with($head, "\x89PNG")
                    || str_starts_with($head, 'GIF8')
                    || str_starts_with($head, 'RIFF');

                if ($looksLikeImage) {
                    return;
                }

                $fail('The file must be a jpg, jpeg, png, webp or gif image.');

                return;
            }

            $fail('The file type is not allowed.');
        };
    }
}
