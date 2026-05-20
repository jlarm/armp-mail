<?php

use App\Enums\Status;
use App\Models\EmailList;
use App\Models\EmailListSubscriber;
use App\Models\Subscriber;
use Illuminate\Database\UniqueConstraintViolationException;

test('a subscriber can be attached to an email list with pivot data', function () {
    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();

    $list->subscribers()->attach($subscriber, [
        'status' => Status::SUBSCRIBED->value,
        'subscribed_at' => now(),
        'subscribe_source' => 'website',
    ]);

    expect($list->subscribers)->toHaveCount(1)
        ->and($subscriber->emailLists)->toHaveCount(1);

    $this->assertDatabaseHas('email_list_subscribers', [
        'email_list_id' => $list->id,
        'subscriber_id' => $subscriber->id,
        'subscribe_source' => 'website',
    ]);
});

test('the pivot uses the EmailListSubscriber model and casts its attributes', function () {
    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();

    $list->subscribers()->attach($subscriber, [
        'status' => Status::SUBSCRIBED->value,
        'subscribed_at' => now(),
    ]);

    $pivot = $list->subscribers()->first()->pivot;

    expect($pivot)->toBeInstanceOf(EmailListSubscriber::class)
        ->and($pivot->status)->toBe(Status::SUBSCRIBED)
        ->and($pivot->subscribed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($pivot->created_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('a subscriber may not be attached to the same list twice', function () {
    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();

    $list->subscribers()->attach($subscriber);
    $list->subscribers()->attach($subscriber);
})->throws(UniqueConstraintViolationException::class);
