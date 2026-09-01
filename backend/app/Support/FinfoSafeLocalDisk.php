<?php

namespace App\Support;

use Illuminate\Filesystem\LocalFilesystemAdapter as IlluminateLocalAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as FlysystemLocal;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

class FinfoSafeLocalDisk
{
    public static function create(array $config, string $name = 'local'): IlluminateLocalAdapter
    {
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
        );

        $links = ($config['links'] ?? null) === 'skip'
            ? FlysystemLocal::SKIP_LINKS
            : FlysystemLocal::DISALLOW_LINKS;

        $detector = extension_loaded('fileinfo') ? null : new ExtensionOnlyMimeTypeDetector;

        $adapter = new FlysystemLocal(
            $config['root'],
            $visibility,
            $config['lock'] ?? LOCK_EX,
            $links,
            $detector,
        );

        $filesystem = new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'retain_visibility',
            'temporary_url',
            'url',
            'visibility',
        ]));

        return (new IlluminateLocalAdapter($filesystem, $adapter, $config))
            ->diskName($name)
            ->shouldServeSignedUrls(
                $config['serve'] ?? false,
                fn () => app('url'),
            );
    }
}
