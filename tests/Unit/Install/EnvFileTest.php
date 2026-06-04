<?php

use App\Support\Install\EnvFile;
use Illuminate\Filesystem\Filesystem;

it('writes environment values', function (?string $current, array $values, string $expected) {
    $path = '/app/.env';
    $files = Mockery::mock(Filesystem::class);

    $files->shouldReceive('exists')
        ->once()
        ->with($path)
        ->andReturn($current !== null);

    if ($current !== null) {
        $files->shouldReceive('get')
            ->once()
            ->with($path)
            ->andReturn($current);
    }

    $files->shouldReceive('put')
        ->once()
        ->with($path, $expected);

    (new EnvFile($files, $path))->write($values);
})->with([
    'replaces an existing key in place' => [
        "APP_NAME=Almanac\nAPP_KEY=\nDB_CONNECTION=sqlite\n",
        ['DB_CONNECTION' => 'mysql'],
        "APP_NAME=Almanac\nAPP_KEY=\nDB_CONNECTION=mysql\n",
    ],
    'appends a key that does not exist' => [
        "APP_NAME=Almanac\n",
        ['APP_TIMEZONE' => 'Europe/Berlin'],
        "APP_NAME=Almanac\nAPP_TIMEZONE=Europe/Berlin\n",
    ],
    'quotes and escapes values that need it' => [
        "DB_PASSWORD=\n",
        ['DB_PASSWORD' => 'p a"s$word'],
        "DB_PASSWORD=\"p a\\\"s\\\$word\"\n",
    ],
    'writes multiple values in one call' => [
        "DB_CONNECTION=sqlite\n",
        [
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
        ],
        "DB_CONNECTION=pgsql\nDB_HOST=127.0.0.1\n",
    ],
    'preserves unrelated comments and blank lines' => [
        "# Application\nAPP_NAME=\"Almanac\"\n\nDB_CONNECTION=sqlite\n",
        ['DB_CONNECTION' => 'mysql'],
        "# Application\nAPP_NAME=\"Almanac\"\n\nDB_CONNECTION=mysql\n",
    ],
    'creates the file when it does not exist' => [
        null,
        ['APP_KEY' => 'base64:abc'],
        "APP_KEY=base64:abc\n",
    ],
]);
