<?php

namespace Database\Seeders;

use App\Actions\Install\SeedDemoData;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * The fixed DAV credential seeded for connecting a desktop calendar client.
     */
    private const DavUsername = 'demo';

    private const DavPassword = 'password';

    /**
     * Seed the application's database with demo life data.
     *
     * Re-runnable: the demo user and its collections are only created once.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (User::query()->where('email', 'demo@example.com')->exists()) {
            return;
        }

        $user = User::factory()->admin()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        app(SeedDemoData::class)->handle($user);

        $this->seedDavCredential($user);
    }

    /**
     * Seed a stable DAV credential and print its connection details, so a
     * connected calendar/contacts client keeps working across re-seeds.
     */
    private function seedDavCredential(User $user): void
    {
        DavCredential::query()->create([
            'user_id' => $user->id,
            'name' => 'Seeded credential',
            'username' => self::DavUsername,
            'secret_hash' => Hash::make(self::DavPassword),
        ]);

        // CalDAV/CardDAV clients require TLS, and Herd serves the .test site over https.
        $baseUrl = str_replace('http://', 'https://', rtrim((string) config('app.url'), '/'));
        $server = $baseUrl.rtrim((string) config('dav.base_uri'), '/').'/';

        $this->command->newLine();
        $this->command->info('DAV credential seeded — connect your calendar/contacts client with:');
        $this->command->line('  Server:   '.$server);
        $this->command->line('  Username: '.self::DavUsername);
        $this->command->line('  Password: '.self::DavPassword);
        $this->command->newLine();
    }
}
