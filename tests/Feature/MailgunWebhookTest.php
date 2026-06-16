<?php

use App\Enums\Status;
use App\Models\Campaign;
use App\Models\CampaignDispatch;
use App\Models\EmailList;
use App\Models\Send;
use App\Models\Subscriber;
use Illuminate\Support\Str;

function webhookPayload(string $event, string $sendUuid, string $secret): array
{
    $timestamp = (string) now()->timestamp;
    $token = Str::random(50);
    $signature = hash_hmac('sha256', $timestamp.$token, $secret);

    return [
        'signature' => [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
        ],
        'event-data' => [
            'event' => $event,
            'recipient' => 'test@example.com',
            'user-variables' => ['send_uuid' => $sendUuid],
        ],
    ];
}

beforeEach(function (): void {
    config(['services.mailgun.webhook_secret' => 'test-secret']);
});

test('webhook is rejected without a valid signature', function () {
    $this->postJson(route('webhooks.mailgun'), [
        'signature' => ['timestamp' => '1', 'token' => 'bad', 'signature' => 'wrong'],
        'event-data' => ['event' => 'permanent_fail'],
    ])->assertForbidden();
});

test('permanent_fail increments bounce_count and records bounced_at', function () {
    $send = Send::factory()->create(['uuid' => (string) Str::ulid()]);
    $dispatch = CampaignDispatch::factory()->create(['bounce_count' => 0]);
    $send->sendable()->associate($dispatch)->save();

    $this->postJson(route('webhooks.mailgun'), webhookPayload('permanent_fail', $send->uuid, 'test-secret'))
        ->assertOk();

    expect($send->fresh()->bounced_at)->not->toBeNull();
    expect($dispatch->fresh()->bounce_count)->toBe(1);
});

test('permanent_fail is idempotent — duplicate events do not double-count', function () {
    $send = Send::factory()->create(['uuid' => (string) Str::ulid(), 'bounced_at' => now()]);
    $dispatch = CampaignDispatch::factory()->create(['bounce_count' => 1]);
    $send->sendable()->associate($dispatch)->save();

    $this->postJson(route('webhooks.mailgun'), webhookPayload('permanent_fail', $send->uuid, 'test-secret'))
        ->assertOk();

    expect($dispatch->fresh()->bounce_count)->toBe(1);
});

test('complained unsubscribes the subscriber from the list', function () {
    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();
    $list->subscribers()->attach($subscriber, ['status' => Status::SUBSCRIBED->value, 'subscribed_at' => now()]);

    $dispatch = CampaignDispatch::factory()
        ->for(Campaign::factory()->for($list, 'emailList'), 'campaign')
        ->create(['unsubscribe_count' => 0]);

    $send = Send::factory()->create(['uuid' => (string) Str::ulid(), 'subscriber_id' => $subscriber->id]);
    $send->sendable()->associate($dispatch)->save();

    $this->postJson(route('webhooks.mailgun'), webhookPayload('complained', $send->uuid, 'test-secret'))
        ->assertOk();

    expect($send->fresh()->complained_at)->not->toBeNull();
    expect($dispatch->fresh()->unsubscribe_count)->toBe(1);

    $pivot = $list->subscribers()->where('subscriber_id', $subscriber->id)->first()?->pivot;
    expect($pivot?->status->value)->toBe(Status::UNSUBSCRIBED->value);
});
