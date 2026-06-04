<?php

use Illuminate\Testing\TestResponse;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VCard;
use Tests\TestCase;

function calDavAuthHeader(string $username, string $password): string
{
    return 'Basic '.base64_encode($username.':'.$password);
}

function cardDavAuthHeader(string $username, string $password): string
{
    return calDavAuthHeader($username, $password);
}

function davBasicAuthHeader(string $username, string $password): string
{
    return calDavAuthHeader($username, $password);
}

/**
 * @return array<string, string>
 */
function davBasicAuth(string $username, string $password): array
{
    return [
        'HTTP_AUTHORIZATION' => davBasicAuthHeader($username, $password),
    ];
}

function calDavPut(TestCase $test, string $path, string $authHeader, string $payload): TestResponse
{
    return $test->call('PUT', $path, [], [], [], [
        'CONTENT_TYPE' => 'text/calendar',
        'HTTP_AUTHORIZATION' => $authHeader,
    ], $payload);
}

function cardDavPut(TestCase $test, string $path, string $authHeader, string $payload): TestResponse
{
    return $test->call('PUT', $path, [], [], [], [
        'CONTENT_TYPE' => 'text/vcard',
        'HTTP_AUTHORIZATION' => $authHeader,
    ], $payload);
}

function davSyncReport(TestCase $test, string $path, string $authHeader, string $syncToken): TestResponse
{
    $payload = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<d:sync-collection xmlns:d="DAV:">
    <d:sync-token>{$syncToken}</d:sync-token>
    <d:sync-level>1</d:sync-level>
    <d:prop>
        <d:getetag />
    </d:prop>
</d:sync-collection>
XML;

    return $test->call('REPORT', $path, [], [], [], [
        'CONTENT_TYPE' => 'application/xml',
        'HTTP_AUTHORIZATION' => $authHeader,
    ], $payload);
}

function davCurrentUserPrincipalPropfind(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:">
    <d:prop>
        <d:current-user-principal />
    </d:prop>
</d:propfind>
XML;
}

/**
 * @param  array<string, string|array{value: string, parameters?: array<string, string>}>  $properties
 */
function calDavPayload(string $componentName, array $properties): string
{
    $calendar = new VCalendar([], false);
    $calendar->add('VERSION', '2.0');
    $calendar->add('PRODID', '-//Almanac//Tests//EN');
    $component = $calendar->createComponent($componentName, [], false);

    foreach ($properties as $name => $value) {
        if (is_array($value)) {
            $component->add($name, $value['value'], $value['parameters'] ?? []);

            continue;
        }

        $component->add($name, $value);
    }

    $calendar->add($component);

    return $calendar->serialize();
}

/**
 * @param  array<string, string|array{value: string, parameters?: array<string, string>}>  $properties
 */
function calendarObjectPayload(string $componentName, array $properties): string
{
    return calDavPayload($componentName, $properties);
}

/**
 * @param  array<string, string|array{value: string|array<int, string>, parameters?: array<string, string>}|array<int, array{value: string, parameters?: array<string, string>}>>  $properties
 */
function cardDavPayload(array $properties): string
{
    $card = new VCard([], false);
    $card->add('VERSION', '3.0');
    $card->add('PRODID', '-//Almanac//Tests//EN');

    foreach ($properties as $name => $value) {
        if (is_array($value) && array_is_list($value)) {
            foreach ($value as $property) {
                $card->add($name, $property['value'], $property['parameters'] ?? []);
            }

            continue;
        }

        if (is_array($value)) {
            $card->add($name, $value['value'], $value['parameters'] ?? []);

            continue;
        }

        $card->add($name, $value);
    }

    return $card->serialize();
}

/**
 * @param  array<string, string|array{value: string|array<int, string>, parameters?: array<string, string>}|array<int, array{value: string, parameters?: array<string, string>}>>  $properties
 */
function contactCardPayload(array $properties): string
{
    return cardDavPayload($properties);
}
