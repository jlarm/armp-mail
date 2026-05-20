<?php

use App\Models\EmailList;

test('the factory creates a persistable email list', function () {
    $list = EmailList::factory()->create();

    $this->assertDatabaseHas('email_lists', [
        'id' => $list->id,
        'name' => $list->name,
        'slug' => $list->slug,
    ]);
});

test('attributes are cast to their expected types', function () {
    $list = EmailList::factory()->create([
        'requires_confirmation' => 1,
        'campaign_mails_per_minute' => '60',
    ])->fresh();

    expect($list->requires_confirmation)->toBeBool()
        ->and($list->campaign_mails_per_minute)->toBeInt()
        ->and($list->created_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('the list is soft deleted', function () {
    $list = EmailList::factory()->create();

    $list->delete();

    $this->assertSoftDeleted($list);
});
