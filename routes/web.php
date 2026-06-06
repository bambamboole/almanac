<?php

use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\CalendarEventController;
use App\Http\Controllers\Calendar\CalendarExportController;
use App\Http\Controllers\Calendar\CalendarManagementController;
use App\Http\Controllers\Contacts\AddressBookManagementController;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Contacts\ContactExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\DavCredentialController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Users\UserManagementController;
use App\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/dav', [DavCredentialController::class, 'index'])->name('settings.dav.edit');
    Route::post('settings/dav/credentials', [DavCredentialController::class, 'store'])->name('settings.dav.credentials.store');
    Route::delete('settings/dav/credentials/{credential}', [DavCredentialController::class, 'destroy'])->name('settings.dav.credentials.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->middleware(RequirePassword::class)->name('security.edit');
    Route::put('settings/password', [SecurityController::class, 'update'])->middleware('throttle:6,1')->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('calendar', CalendarController::class)->name('calendar');
    Route::get('calendar/export', CalendarExportController::class)->name('calendar.export');

    Route::post('/calendar/calendars', [CalendarManagementController::class, 'store'])->name('calendar.calendars.store');
    Route::get('/calendar/calendars/{calendar}/export', [CalendarExportController::class, 'show'])->name('calendar.calendars.export');
    Route::patch('/calendar/calendars/{calendar}', [CalendarManagementController::class, 'update'])->name('calendar.calendars.update');
    Route::delete('/calendar/calendars/{calendar}', [CalendarManagementController::class, 'destroy'])->name('calendar.calendars.destroy');

    Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::put('/calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
    Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');

    Route::get('contacts', [ContactController::class, 'show'])->name('contacts');
    Route::get('contacts/export', ContactExportController::class)->name('contacts.export');

    Route::post('/contacts/address-books', [AddressBookManagementController::class, 'store'])->name('contacts.address-books.store');
    Route::get('/contacts/address-books/{address_book}/export', [ContactExportController::class, 'show'])->name('contacts.address-books.export');
    Route::patch('/contacts/address-books/{address_book}', [AddressBookManagementController::class, 'update'])->name('contacts.address-books.update');
    Route::delete('/contacts/address-books/{address_book}', [AddressBookManagementController::class, 'destroy'])->name('contacts.address-books.destroy');

    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/contacts/{contact}/export', [ContactExportController::class, 'card'])->name('contacts.cards.export');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    Route::get('users', [UserManagementController::class, 'index'])->can('viewAny', User::class)->name('users.index');
    Route::post('users', [UserManagementController::class, 'store'])->can('create', User::class)->name('users.store');
    Route::patch('users/{user}', [UserManagementController::class, 'update'])->can('update', 'user')->name('users.update');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->can('delete', 'user')->name('users.destroy');
    Route::post('users/{user}/dav-credential', [UserManagementController::class, 'resetCredential'])->can('update', 'user')->name('users.dav-credential');
});
