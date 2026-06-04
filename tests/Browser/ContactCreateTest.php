<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('creates a contact from the contacts UI', function () {
    $user = User::factory()->create();
    DavAddressBook::factory()->for($user)->create(['display_name' => 'Personal']);

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertNoJavaScriptErrors()
        ->click('New contact')
        ->fill('full_name', 'Katherine Johnson')
        ->fill('email', 'katherine@example.com')
        ->fill('phone', '+1 555 0100')
        ->click('Add phone')
        ->fill('#create-contact-phone-1', '+1 555 0101')
        ->fill('#create-contact-address-0-street', '100 Orbit Way')
        ->fill('#create-contact-address-0-city', 'Hampton')
        ->fill('#create-contact-address-0-region', 'VA')
        ->fill('#create-contact-address-0-postal-code', '23666')
        ->fill('#create-contact-address-0-country', 'USA')
        ->click('Create contact')
        ->assertSee('Katherine Johnson');

    $card = DavCard::query()->where('full_name', 'Katherine Johnson')->firstOrFail();

    expect($card->emails)->toBe(['katherine@example.com'])
        ->and($card->phones)->toBe(['+1 555 0100', '+1 555 0101'])
        ->and($card->phone_numbers)->toHaveCount(2)
        ->and($card->addresses)->toHaveCount(1)
        ->and($card->addresses->first()->street)->toBe('100 Orbit Way');
});

it('creates an address book from the contacts UI', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertNoJavaScriptErrors()
        ->click('[data-new-address-book]')
        ->fill('display_name', 'Family')
        ->click('Create address book')
        ->assertSee('Family');

    expect(DavAddressBook::query()->where('user_id', $user->id)->where('display_name', 'Family')->exists())->toBeTrue();
});
