<?php

namespace App\Providers;

use App\Listeners\RebroadcastDavCollectionChanged;
use App\Policies\DavAddressBookPolicy;
use App\Policies\DavCalendarObjectPolicy;
use App\Policies\DavCalendarPolicy;
use App\Policies\DavCardPolicy;
use App\Support\Install\EnvFile;
use Bambamboole\LaravelDav\Events\DavCollectionChanged;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EnvFile::class, fn () => new EnvFile(
            new Filesystem,
            $this->app->environmentFilePath(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::policy(DavCalendar::class, DavCalendarPolicy::class);
        Gate::policy(DavCalendarObject::class, DavCalendarObjectPolicy::class);
        Gate::policy(DavAddressBook::class, DavAddressBookPolicy::class);
        Gate::policy(DavCard::class, DavCardPolicy::class);

        Event::listen(DavCollectionChanged::class, RebroadcastDavCollectionChanged::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
