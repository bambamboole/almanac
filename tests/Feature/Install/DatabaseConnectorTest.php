<?php

use App\Support\Install\DatabaseConnector;
use Illuminate\Support\Facades\DB;

afterEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');
});

it('creates the sqlite file and applies it as the default connection', function () {
    $path = sys_get_temp_dir().'/almanac-connector-'.uniqid().'.sqlite';

    $env = (new DatabaseConnector)->configureSqlite($path);

    expect(file_exists($path))->toBeTrue()
        ->and($env)->toBe(['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $path])
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe($path);

    DB::connection('sqlite')->getPdo();

    unlink($path);
});

it('leaves an existing sqlite file in place', function () {
    $path = sys_get_temp_dir().'/almanac-connector-'.uniqid().'.sqlite';
    file_put_contents($path, '');

    (new DatabaseConnector)->configureSqlite($path);

    expect(file_exists($path))->toBeTrue();

    unlink($path);
});
