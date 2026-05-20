<?php

use App\Enums\Type;
use App\Models\CustomField;
use App\Models\EmailList;
use Illuminate\Database\UniqueConstraintViolationException;

test('the factory creates a persistable custom field', function () {
    $field = CustomField::factory()->create();

    $this->assertDatabaseHas('custom_fields', [
        'id' => $field->id,
        'email_list_id' => $field->email_list_id,
        'name' => $field->name,
        'slug' => $field->slug,
    ]);
});

test('the type is cast to the Type enum', function () {
    $field = CustomField::factory()->create([
        'type' => Type::NUMBER,
    ])->fresh();

    expect($field->type)->toBe(Type::NUMBER);
});

test('timestamps are cast to date instances', function () {
    $field = CustomField::factory()->create()->fresh();

    expect($field->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($field->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('it belongs to an email list', function () {
    $field = CustomField::factory()->create();

    expect($field->emailList)->toBeInstanceOf(EmailList::class)
        ->and($field->emailList->id)->toBe($field->email_list_id);
});

test('the slug must be unique within an email list', function () {
    $existing = CustomField::factory()->create();

    CustomField::factory()->create([
        'email_list_id' => $existing->email_list_id,
        'slug' => $existing->slug,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('the same slug may be reused across email lists', function () {
    $existing = CustomField::factory()->create();

    $field = CustomField::factory()->create([
        'slug' => $existing->slug,
    ]);

    expect($field->slug)->toBe($existing->slug)
        ->and($field->email_list_id)->not->toBe($existing->email_list_id);
});
