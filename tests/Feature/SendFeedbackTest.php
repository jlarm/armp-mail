<?php

use App\Enums\SendFeedbackType;
use App\Models\Send;
use App\Models\SendFeedback;

test('the factory creates a persistable send feedback event', function () {
    $feedback = SendFeedback::factory()->create();

    $this->assertDatabaseHas('send_feedback', [
        'id' => $feedback->id,
        'send_id' => $feedback->send_id,
        'type' => $feedback->type->value,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $send = Send::factory()->create();

    $feedback = SendFeedback::create([
        'send_id' => $send->id,
        'type' => SendFeedbackType::CLICK,
        'url' => 'https://example.com/promo',
        'user_agent' => 'Mozilla/5.0',
        'ip_address' => '2001:db8::1',
        'payload' => ['raw' => true],
        'happened_at' => now(),
    ]);

    expect($feedback->type)->toBe(SendFeedbackType::CLICK)
        ->and($feedback->url)->toBe('https://example.com/promo')
        ->and($feedback->ip_address)->toBe('2001:db8::1')
        ->and($feedback->payload)->toBe(['raw' => true]);
});

test('type is cast to the SendFeedbackType enum', function () {
    $feedback = SendFeedback::factory()->create()->fresh();

    expect($feedback->type)->toBeInstanceOf(SendFeedbackType::class);
});

test('payload is cast to and from an array', function () {
    $feedback = SendFeedback::factory()->bounce()->create()->fresh();

    expect($feedback->payload)->toBeArray();
});

test('happened_at is cast to a date instance', function () {
    $feedback = SendFeedback::factory()->create()->fresh();

    expect($feedback->happened_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('url, user_agent, ip_address and payload are nullable', function () {
    $feedback = SendFeedback::factory()->create([
        'url' => null,
        'user_agent' => null,
        'ip_address' => null,
        'payload' => null,
    ])->fresh();

    expect($feedback->url)->toBeNull()
        ->and($feedback->user_agent)->toBeNull()
        ->and($feedback->ip_address)->toBeNull()
        ->and($feedback->payload)->toBeNull();
});

test('it belongs to a send', function () {
    $feedback = SendFeedback::factory()->create();

    expect($feedback->send)->toBeInstanceOf(Send::class)
        ->and($feedback->send->id)->toBe($feedback->send_id);
});

test('the click state records the clicked url', function () {
    $feedback = SendFeedback::factory()->click()->create();

    expect($feedback->type)->toBe(SendFeedbackType::CLICK)
        ->and($feedback->url)->not->toBeNull();
});

test('the bounce state records the raw webhook payload', function () {
    $feedback = SendFeedback::factory()->bounce()->create();

    expect($feedback->type)->toBe(SendFeedbackType::BOUNCE)
        ->and($feedback->payload)->toHaveKey('bounceType');
});

test('deleting a send cascades to its feedback events', function () {
    $feedback = SendFeedback::factory()->create();

    $feedback->send->delete();

    $this->assertDatabaseMissing('send_feedback', ['id' => $feedback->id]);
});
