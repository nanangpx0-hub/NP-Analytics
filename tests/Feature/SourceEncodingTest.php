<?php

namespace Tests\Feature;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SourceEncodingTest extends TestCase
{
    public function test_mobile_sources_are_utf8_without_bom(): void
    {
        $roots = array_filter([
            base_path('app'),
            base_path('config'),
            base_path('database'),
            base_path('resources'),
            base_path('routes'),
            base_path('tests'),
            dirname(base_path()).'/packages/np-analytics-core',
        ], 'is_dir');

        $extensions = ['php', 'js', 'css', 'json', 'md'];
        $withBom = [];
        $invalidUtf8 = [];

        foreach ($roots as $root) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                if (str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                if (! in_array(strtolower($file->getExtension()), $extensions, true)) {
                    continue;
                }

                $contents = (string) file_get_contents($path);

                if (str_starts_with($contents, "\xEF\xBB\xBF")) {
                    $withBom[] = $path;
                }

                if (! mb_check_encoding($contents, 'UTF-8')) {
                    $invalidUtf8[] = $path;
                }
            }
        }

        $this->assertSame([], $withBom, 'File ber-BOM: '.implode(', ', $withBom));
        $this->assertSame([], $invalidUtf8, 'File bukan UTF-8: '.implode(', ', $invalidUtf8));
    }
}
