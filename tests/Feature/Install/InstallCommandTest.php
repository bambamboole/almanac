<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

it('aborts when an admin user already exists', function () {
    User::factory()->admin()->create();

    $this->artisan('almanac:install')
        ->expectsOutputToContain('already installed')
        ->assertExitCode(1);
});

it('installs end-to-end with sqlite and demo data', function () {
    Http::fake([
        'https://almanac.test/dav/' => Http::response('', 200),
    ]);
    Process::fake();

    $envPath = sys_get_temp_dir().'/almanac-install-'.uniqid().'.env';
    file_put_contents($envPath, "APP_TIMEZONE=UTC\nDB_CONNECTION=sqlite\n");
    $this->app->useEnvironmentPath(dirname($envPath));
    $this->app->loadEnvironmentFrom(basename($envPath));

    $dbPath = sys_get_temp_dir().'/almanac-install-'.uniqid().'.sqlite';

    $this->artisan('almanac:install', ['--force' => true])
        ->expectsSearch(
            'Select your timezone',
            search: 'Europe/Berlin',
            answers: ['Europe/Berlin'],
            answer: 'Europe/Berlin',
        )
        ->expectsQuestion('Which database do you want to use?', 'sqlite')
        ->expectsQuestion('SQLite database path', $dbPath)
        ->expectsQuestion('Admin name', 'admin')
        ->expectsQuestion('Admin email', 'demo@example.com')
        ->expectsQuestion('Admin password', 'password')
        ->expectsQuestion('DAV username', 'demo')
        ->expectsQuestion('DAV password', 'password')
        ->expectsConfirmation('Add demo data (sample calendar & contacts) to the admin account?', 'yes')
        ->expectsOutputToContain('DAV credential created')
        ->expectsOutputToContain('Server:   https://almanac.test/dav/')
        ->expectsOutputToContain('Username: demo')
        ->expectsOutputToContain('Password: password')
        ->expectsOutputToContain('DAV endpoint verified')
        ->assertExitCode(0);

    $admin = User::query()->where('email', 'demo@example.com')->firstOrFail();
    $credential = DavCredential::query()->whereBelongsTo($admin)->where('username', 'demo')->firstOrFail();

    expect($admin->role->name)->toBe('admin')
        ->and(DavCalendar::query()->whereBelongsTo($admin)->where('uri', 'personal')->firstOrFail()->objects()->count())->toBe(4)
        ->and(Hash::check('password', $credential->secret_hash))->toBeTrue()
        ->and(file_get_contents($envPath))->toContain('APP_TIMEZONE=Europe/Berlin')
        ->and(file_get_contents($envPath))->toContain('APP_URL=https://almanac.test')
        ->and(file_get_contents($envPath))->toContain('DB_DATABASE='.$dbPath);

    Process::assertRan('herd link almanac');
    Process::assertRan('herd secure almanac');
    Http::assertSent(fn ($request) => $request->method() === 'PROPFIND'
        && $request->url() === 'https://almanac.test/dav/');

    @unlink($envPath);
    @unlink($dbPath);
});

afterEach(function () {
    config(['app.timezone' => 'UTC']);
    date_default_timezone_set('UTC');

    if (config('database.connections.sqlite.database') === ':memory:') {
        return;
    }

    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');
});
