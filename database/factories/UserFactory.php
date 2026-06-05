<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => Role::admin()->id,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Give the user a calendar populated with events.
     *
     * Pass an int to generate that many random events, or a list of
     * attribute-override maps to create specific events. Events without an
     * explicit start fall uniformly inside the given period (defaults to the
     * next 30 days).
     *
     * @param  int|array<int, array<string, mixed>>  $events  CalendarObjectData overrides per event.
     * @param  CarbonPeriod|array{0: mixed, 1: mixed}|null  $period
     */
    public function withCalendar(string $name = 'Personal', int|array $events = 0, CarbonPeriod|array|null $period = null): static
    {
        return $this->afterCreating(function (User $user) use ($name, $events, $period): void {
            $calendar = DavCalendar::factory()->create([
                'user_id' => $user->id,
                'display_name' => $name,
                'uri' => Str::slug($name),
            ]);

            [$start, $end] = $this->resolvePeriod($period);

            foreach ($this->normalizeItems($events) as $data) {
                if (! array_key_exists('startsAt', $data)) {
                    $startsAt = $this->randomMomentBetween($start, $end);
                    $data['startsAt'] = $startsAt;
                    $data['endsAt'] ??= $startsAt->copy()->addHour();
                }

                DavCalendarObject::factory()
                    ->state(fn (array $attributes): array => ['data' => [...$attributes['data'], ...$data]])
                    ->create(['dav_calendar_id' => $calendar->id]);
            }
        });
    }

    /**
     * Give the user a personal address book populated with contacts.
     *
     * Pass an int to generate that many random contacts, or a list of
     * attribute-override maps to create specific contacts.
     *
     * @param  int|array<int, array<string, mixed>>  $contacts  ContactData overrides per contact.
     */
    public function withContacts(int|array $contacts = 0): static
    {
        return $this->afterCreating(function (User $user) use ($contacts): void {
            $addressBook = DavAddressBook::factory()->create([
                'user_id' => $user->id,
                'display_name' => 'Personal',
                'uri' => 'personal',
            ]);

            foreach ($this->normalizeItems($contacts) as $data) {
                DavCard::factory()
                    ->state(fn (array $attributes): array => ['data' => [...$attributes['data'], ...$data]])
                    ->create(['dav_address_book_id' => $addressBook->id]);
            }
        });
    }

    /**
     * Normalize an int count or an explicit list into a list of attribute maps.
     *
     * @param  int|array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(int|array $items): array
    {
        return is_int($items) ? array_fill(0, $items, []) : $items;
    }

    /**
     * @param  CarbonPeriod|array{0: mixed, 1: mixed}|null  $period
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(CarbonPeriod|array|null $period): array
    {
        if ($period instanceof CarbonPeriod) {
            return [Carbon::instance($period->getStartDate()), Carbon::instance($period->getEndDate())];
        }

        if (is_array($period)) {
            return [Carbon::parse($period[0]), Carbon::parse($period[1])];
        }

        return [Carbon::now(), Carbon::now()->addDays(30)];
    }

    private function randomMomentBetween(Carbon $start, Carbon $end): Carbon
    {
        $timestamp = fake()->numberBetween($start->getTimestamp(), $end->getTimestamp());

        return Carbon::createFromTimestamp($timestamp, 'UTC')->setSeconds(0);
    }
}
