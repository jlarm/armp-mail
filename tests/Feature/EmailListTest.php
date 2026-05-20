<?php

use App\Models\CustomField;
use App\Models\EmailList;
use App\Models\Segment;

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

test('it has many custom fields', function () {
    $list = EmailList::factory()->create();
    $fields = CustomField::factory()->count(3)->create(['email_list_id' => $list->id]);

    expect($list->customFields)->toHaveCount(3)
        ->and($list->customFields->pluck('id')->all())
        ->toEqualCanonicalizing($fields->pluck('id')->all());
});

test('it has many segments', function () {
    $list = EmailList::factory()->create();
    $segments = Segment::factory()->count(3)->create(['email_list_id' => $list->id]);

    expect($list->segments)->toHaveCount(3)
        ->and($list->segments->pluck('id')->all())
        ->toEqualCanonicalizing($segments->pluck('id')->all());
});
