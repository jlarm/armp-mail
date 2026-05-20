<?php

use App\Models\Subscriber;
use Illuminate\Database\UniqueConstraintViolationException;

test('the factory creates a persistable subscriber', function () {
    $subscriber = Subscriber::factory()->create();

    $this->assertDatabaseHas('subscribers', [
        'id' => $subscriber->id,
        'email' => $subscriber->email,
        'uuid' => $subscriber->uuid,
    ]);
});

test('extra attributes are cast to and from json', function () {
    $subscriber = Subscriber::factory()->create([
        'extra_attributes' => ['plan' => 'pro', 'tags' => ['vip', 'beta']],
    ])->fresh();

    expect($subscriber->extra_attributes)->toBe([
        'plan' => 'pro',
        'tags' => ['vip', 'beta'],
    ]);
});

test('timestamps are cast to date instances', function () {
    $subscriber = Subscriber::factory()->create()->fresh();

    expect($subscriber->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($subscriber->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('the email must be unique', function () {
    $existing = Subscriber::factory()->create();

    Subscriber::factory()->create(['email' => $existing->email]);
})->throws(UniqueConstraintViolationException::class);

test('the uuid must be unique', function () {
    $existing = Subscriber::factory()->create();

    Subscriber::factory()->create(['uuid' => $existing->uuid]);
})->throws(UniqueConstraintViolationException::class);
