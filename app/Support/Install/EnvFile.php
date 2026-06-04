<?php

namespace App\Support\Install;

use Illuminate\Filesystem\Filesystem;

readonly class EnvFile
{
    public function __construct(
        private Filesystem $files,
        private string $path,
    ) {}

    /**
     * @param  array<string, string>  $values
     */
    public function write(array $values): void
    {
        $contents = $this->files->exists($this->path) ? $this->files->get($this->path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatValue($value);

            $contents = $this->findCurrentValue($contents, $key) === null
                ? $this->appendLine($contents, $line)
                : $this->replaceLine($contents, $key, $line);
        }

        $this->files->put($this->path, $this->ensureTrailingNewLine($contents));
    }

    private function formatValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s"\'#=$]/', $value) === 1) {
            return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
        }

        return $value;
    }

    private function findCurrentValue(string $contents, string $key): ?string
    {
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (! str_starts_with($line, $key.'=')) {
                continue;
            }

            return substr($line, strlen($key) + 1);
        }

        return null;
    }

    private function replaceLine(string $contents, string $key, string $line): string
    {
        return (string) preg_replace_callback(
            '/^'.preg_quote($key, '/').'=.*/m',
            fn (): string => $line,
            $contents,
            1,
        );
    }

    private function appendLine(string $contents, string $line): string
    {
        $contents = rtrim($contents, "\n");

        return ($contents === '' ? '' : $contents."\n").$line;
    }

    private function ensureTrailingNewLine(string $contents): string
    {
        return str_ends_with($contents, "\n") ? $contents : $contents."\n";
    }
}
