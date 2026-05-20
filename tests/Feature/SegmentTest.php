<?php

use App\Models\EmailList;
use App\Models\Segment;

test('the factory creates a persistable segment', function () {
    $segment = Segment::factory()->create();

    $this->assertDatabaseHas('segments', [
        'id' => $segment->id,
        'email_list_id' => $segment->email_list_id,
        'name' => $segment->name,
    ]);
});

test('the rules are cast to an array', function () {
    $rules = [
        ['field' => 'email', 'operator' => 'contains', 'value' => 'laravel.com'],
    ];

    $segment = Segment::factory()->create([
        'rules' => $rules,
    ])->fresh();

    expect($segment->rules)->toBe($rules);
});

test('timestamps are cast to date instances', function () {
    $segment = Segment::factory()->create()->fresh();

    expect($segment->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($segment->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('it belongs to an email list', function () {
    $segment = Segment::factory()->create();

    expect($segment->emailList)->toBeInstanceOf(EmailList::class)
        ->and($segment->emailList->id)->toBe($segment->email_list_id);
});
