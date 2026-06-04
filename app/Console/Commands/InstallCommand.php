<?php

namespace App\Console\Commands;

use App\Actions\Dav\CreateDavCredential;
use App\Actions\Install\SeedDemoData;
use App\Actions\Users\CreateUser;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Support\Install\DatabaseConnector;
use App\Support\Install\EnvFile;
use Bambamboole\LaravelDav\Models\DavCredential;
use Database\Seeders\RoleSeeder;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Description('Interactively install Almanac from the command line')]
#[Signature('almanac:install {--force : Run even if the app looks installed}')]
class InstallCommand extends Command
{
    private const LocalSite = 'almanac';

    private const LocalUrl = 'https://almanac.test';

    private const DefaultAdminName = 'admin';

    private const DefaultAdminEmail = 'demo@example.com';

    private const DefaultAdminPassword = 'password';

    private const DefaultDavUsername = 'demo';

    private const DefaultDavPassword = 'password';

    public function handle(
        EnvFile $env,
        DatabaseConnector $database,
        CreateUser $createUser,
        SeedDemoData $seedDemoData,
        CreateDavCredential $createDavCredential,
    ): int {
        if (! $this->option('force') && $this->isInstalled()) {
            $this->error('Almanac already installed (an admin user exists). Re-run with --force to override.');

            return self::FAILURE;
        }

        $this->generateAppKey();
        $this->configureTimezone($env);
        $this->configureLocalUrl($env);
        $this->configureDatabase($env, $database);
        $adminDetails = $this->askAdminDetails();
        $davDetails = $this->askDavDetails();
        $shouldSeedDemoData = $this->shouldSeedDemoData();

        $this->migrate();

        $admin = $this->createAdmin($createUser, $adminDetails);

        if ($shouldSeedDemoData) {
            $seedDemoData->handle($admin);
            $this->info('Demo data added.');
        }

        $davCredential = $createDavCredential->handle(
            user: $admin,
            name: 'Default',
            username: $davDetails['username'],
            plainSecret: $davDetails['password'],
        );

        $this->printDavCredential($davCredential);
        $this->configureHerd();
        $this->verifyDavEndpoint($davCredential);

        $this->components->info('Almanac is installed.');

        return self::SUCCESS;
    }

    private function isInstalled(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return false;
        }

        return Role::query()->where('name', UserRole::Admin->value)
            ->has('users')
            ->exists();
    }

    private function generateAppKey(): void
    {
        if (config('app.key')) {
            $this->line('APP_KEY already set, skipping.');

            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
        $this->info('Generated APP_KEY.');
    }

    private function configureTimezone(EnvFile $env): void
    {
        $timezones = DateTimeZone::listIdentifiers();

        $timezone = search(
            label: 'Select your timezone',
            options: fn (string $value) => $value === ''
                ? array_slice($timezones, 0, 20)
                : array_values(array_filter($timezones, fn (string $tz) => str_contains(strtolower($tz), strtolower($value)))),
            placeholder: 'Europe/Berlin',
        );

        $env->write(['APP_TIMEZONE' => $timezone]);
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        $this->info("Timezone set to {$timezone}.");
    }

    private function configureLocalUrl(EnvFile $env): void
    {
        $env->write(['APP_URL' => self::LocalUrl]);
        config(['app.url' => self::LocalUrl]);

        $this->info('Local URL set to '.self::LocalUrl.'.');
    }

    private function configureDatabase(EnvFile $env, DatabaseConnector $database): void
    {
        $driver = select(
            label: 'Which database do you want to use?',
            options: ['sqlite' => 'SQLite', 'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'],
            default: 'sqlite',
        );

        $values = $driver === 'sqlite'
            ? $this->configureSqlite($database)
            : $this->configureServerDatabase($driver, $database);

        $env->write($values);
    }

    /**
     * @return array<string, string>
     */
    private function configureSqlite(DatabaseConnector $database): array
    {
        $path = text(
            label: 'SQLite database path',
            default: database_path('database.sqlite'),
            required: true,
        );

        return $database->configureSqlite($path);
    }

    /**
     * @return array<string, string>
     */
    private function configureServerDatabase(string $driver, DatabaseConnector $database): array
    {
        $defaultPort = $driver === 'pgsql' ? '5432' : '3306';
        $defaultUser = $driver === 'pgsql' ? 'postgres' : 'root';

        while (true) {
            $params = [
                'host' => text('Database host', default: '127.0.0.1', required: true),
                'port' => text('Database port', default: $defaultPort, required: true),
                'database' => text('Database name', default: 'almanac', required: true),
                'username' => text('Database username', default: $defaultUser, required: true),
                'password' => password('Database password'),
            ];

            try {
                $database->ensureConnection($driver, $params);

                return [
                    'DB_CONNECTION' => $driver,
                    'DB_HOST' => $params['host'],
                    'DB_PORT' => $params['port'],
                    'DB_DATABASE' => $params['database'],
                    'DB_USERNAME' => $params['username'],
                    'DB_PASSWORD' => $params['password'],
                ];
            } catch (Throwable $e) {
                $this->error('Could not connect: '.$e->getMessage());
                $this->line('Please check the details and try again.');
            }
        }
    }

    private function migrate(): void
    {
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);
    }

    /**
     * @return array{name: string, email: string, password: string}
     */
    private function askAdminDetails(): array
    {
        return [
            'name' => text('Admin name', default: self::DefaultAdminName, required: true),
            'email' => text('Admin email', default: self::DefaultAdminEmail, required: true, validate: ['email']),
            'password' => text('Admin password', default: self::DefaultAdminPassword, required: true, validate: ['min:8']),
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string}  $adminDetails
     */
    private function createAdmin(CreateUser $createUser, array $adminDetails): User
    {
        $this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        $existing = User::query()->where('email', $adminDetails['email'])->first();

        if ($existing) {
            $existing->forceFill([
                'name' => $adminDetails['name'],
                'password' => $adminDetails['password'],
                'role_id' => Role::forName(UserRole::Admin)->id,
                'email_verified_at' => now(),
            ])->save();

            $this->components->info("Updated admin {$adminDetails['email']}.");

            return $existing->refresh();
        }

        $admin = $createUser->handle(
            name: $adminDetails['name'],
            email: $adminDetails['email'],
            password: $adminDetails['password'],
            role: UserRole::Admin,
            emailVerified: true,
            createDefaultDavCollections: false,
        );
        $this->components->info("Created admin {$adminDetails['email']}.");

        return $admin;
    }

    private function shouldSeedDemoData(): bool
    {
        return confirm('Add demo data (sample calendar & contacts) to the admin account?', default: false);
    }

    /**
     * @return array{username: string, password: string}
     */
    private function askDavDetails(): array
    {
        return [
            'username' => text('DAV username', default: self::DefaultDavUsername, required: true),
            'password' => text('DAV password', default: self::DefaultDavPassword, required: true, validate: ['min:8']),
        ];
    }

    /**
     * @param  array{credential: DavCredential, username: string, plainSecret: string}  $davCredential
     */
    private function printDavCredential(array $davCredential): void
    {
        $this->newLine();
        $this->components->info('DAV credential created — connect your calendar/contacts client with:');
        $this->line('  Server:   '.$this->davServerUrl());
        $this->line('  Username: '.$davCredential['username']);
        $this->line('  Password: '.$davCredential['plainSecret']);
        $this->newLine();
    }

    private function configureHerd(): void
    {
        $this->runHerdCommand('herd link '.self::LocalSite, 'Linked Herd site '.self::LocalSite.'.');
        $this->runHerdCommand('herd secure '.self::LocalSite, 'Secured Herd site '.self::LocalSite.'.');
    }

    private function runHerdCommand(string $command, string $successMessage): void
    {
        $result = Process::timeout(30)->run($command);

        if ($result->successful()) {
            $this->components->info($successMessage);

            return;
        }

        $this->components->warn('Could not run `'.$command.'`. Run it manually before syncing DAV clients.');
    }

    /**
     * @param  array{credential: DavCredential, username: string, plainSecret: string}  $davCredential
     */
    private function verifyDavEndpoint(array $davCredential): void
    {
        $response = null;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withBasicAuth($davCredential['username'], $davCredential['plainSecret'])
                    ->send('PROPFIND', $this->davServerUrl(), [
                        'headers' => ['Depth' => '0'],
                    ]);

                break;
            } catch (ConnectionException) {
                if ($attempt < 5) {
                    sleep(1);
                }
            }
        }

        if ($response === null) {
            $this->components->warn('Could not reach '.$this->davServerUrl().' over HTTPS. Run `herd link almanac`, `herd secure almanac`, and `composer dev`, then try the DAV URL again.');

            return;
        }

        if ($response->serverError()) {
            $this->components->warn('DAV endpoint responded with HTTP '.$response->status().'. Check Herd, Reverb, and the application logs.');

            return;
        }

        $this->components->info('DAV endpoint verified at '.$this->davServerUrl().' (HTTP '.$response->status().').');
    }

    private function davServerUrl(): string
    {
        return rtrim((string) config('app.url'), '/').rtrim((string) config('dav.base_uri'), '/').'/';
    }
}
