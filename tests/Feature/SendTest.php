<?php

use App\Models\Campaign;
use App\Models\Send;
use App\Models\Subscriber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

test('the factory creates a persistable send', function () {
    $send = Send::factory()->create();

    $this->assertDatabaseHas('sends', [
        'id' => $send->id,
        'sendable_type' => $send->sendable_type,
        'sendable_id' => $send->sendable_id,
        'subscriber_id' => $send->subscriber_id,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $campaign = Campaign::factory()->create();
    $subscriber = Subscriber::factory()->create();

    $send = new Send([
        'sendable_type' => Campaign::class,
        'sendable_id' => $campaign->id,
        'subscriber_id' => $subscriber->id,
        'transport_message_id' => 'ses-abc-123',
        'failure_reason' => 'Hard bounce',
    ]);
    $send->uuid = (string) Str::ulid();
    $send->save();

    expect($send->sendable_id)->toBe($campaign->id)
        ->and($send->subscriber_id)->toBe($subscriber->id)
        ->and($send->transport_message_id)->toBe('ses-abc-123')
        ->and($send->failure_reason)->toBe('Hard bounce');
});

test('the factory assigns a unique ulid', function () {
    $send = Send::factory()->create()->fresh();

    expect($send->uuid)->toBeString()
        ->and(Str::isUlid($send->uuid))->toBeTrue();
});

test('event timestamps are cast to date instances', function () {
    $send = Send::factory()->sent()->opened()->clicked()->bounced()->complained()->create()->fresh();

    expect($send->sent_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->opened_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->clicked_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->bounced_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->complained_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('event timestamps default to null', function () {
    $send = Send::factory()->create()->fresh();

    expect($send->sent_at)->toBeNull()
        ->and($send->failed_at)->toBeNull()
        ->and($send->opened_at)->toBeNull()
        ->and($send->clicked_at)->toBeNull()
        ->and($send->bounced_at)->toBeNull()
        ->and($send->complained_at)->toBeNull();
});

test('it belongs to a sendable via a morph relationship', function () {
    $campaign = Campaign::factory()->create();
    $send = Send::factory()->for($campaign, 'sendable')->create();

    expect($send->sendable)->toBeInstanceOf(Campaign::class)
        ->and($send->sendable->id)->toBe($campaign->id);
});

test('it belongs to a subscriber', function () {
    $send = Send::factory()->create();

    expect($send->subscriber)->toBeInstanceOf(Subscriber::class)
        ->and($send->subscriber->id)->toBe($send->subscriber_id);
});

test('the sent state records the transport message id and sent time', function () {
    $send = Send::factory()->sent()->create();

    expect($send->sent_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->transport_message_id)->not->toBeNull();
});

test('the failed state records the failure reason', function () {
    $send = Send::factory()->failed()->create();

    expect($send->failed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($send->failure_reason)->toBeString();
});

test('the same sendable cannot be sent to the same subscriber twice', function () {
    $send = Send::factory()->create();

    expect(fn () => Send::factory()->create([
        'sendable_type' => $send->sendable_type,
        'sendable_id' => $send->sendable_id,
        'subscriber_id' => $send->subscriber_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('the same sendable may be sent to different subscribers', function () {
    $send = Send::factory()->create();

    $other = Send::factory()->create([
        'sendable_type' => $send->sendable_type,
        'sendable_id' => $send->sendable_id,
    ]);

    expect($other->subscriber_id)->not->toBe($send->subscriber_id);
});

test('deleting a subscriber cascades to its sends', function () {
    $send = Send::factory()->create();

    $send->subscriber->delete();

    $this->assertDatabaseMissing('sends', ['id' => $send->id]);
});
