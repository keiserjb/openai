<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$prefix = 'BackdropOpenAI';

return [
    'prefix' => $prefix,
    'exclude-files' => [],
    'exclude-namespaces' => [],
    'exclude-classes' => [],
    'exclude-functions' => [],
    'exclude-constants' => [
        '/^BACKDROP_/',
        '/^FILE_/',
        '/^LANGUAGE_/',
        '/^MENU_/',
        '/^WATCHDOG_/',
    ],
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            return $contents;
        },
    ],
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'doc', 
                'test', 
                'test_old', 
                'tests', 
                'Tests', 
                'vendor-bin', 
                'bin',
                // Exclude dev dependencies
                'humbug',
                'fidry',
                'jetbrains',
                'nikic',
                'symfony/console',
                'symfony/event-dispatcher-contracts',
                'symfony/filesystem',
                'symfony/finder',
                'symfony/polyfill-ctype',
                'symfony/polyfill-intl-grapheme',
                'symfony/polyfill-intl-normalizer',
                'symfony/polyfill-php84',
                'symfony/service-contracts',
                'symfony/string',
                'symfony/var-dumper',
                'thecodingmachine',
                'webmozart',
                'psr/container',
                'psr/event-dispatcher',
                'psr/log',
            ])
            ->in('vendor'),
    ],
];
