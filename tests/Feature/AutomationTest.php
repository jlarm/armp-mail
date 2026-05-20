<?php

use App\Enums\AutomationStatus;
use App\Models\Automation;
use App\Models\AutomationActionSubscriber;
use App\Models\AutomationStep;
use App\Models\EmailList;

test('the factory creates a persistable automation', function () {
    $automation = Automation::factory()->create();

    $this->assertDatabaseHas('automations', [
        'id' => $automation->id,
        'email_list_id' => $automation->email_list_id,
        'name' => $automation->name,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $emailList = EmailList::factory()->create();

    $automation = Automation::create([
        'email_list_id' => $emailList->id,
        'name' => 'Welcome Series',
        'status' => AutomationStatus::ACTIVE,
        'triggers' => [['type' => 'subscribed', 'config' => []]],
    ]);

    expect($automation->name)->toBe('Welcome Series')
        ->and($automation->status)->toBe(AutomationStatus::ACTIVE)
        ->and($automation->triggers)->toBe([['type' => 'subscribed', 'config' => []]]);
});

test('status is cast to the AutomationStatus enum and defaults to paused', function () {
    $automation = Automation::factory()->create()->fresh();

    expect($automation->status)->toBeInstanceOf(AutomationStatus::class)
        ->and($automation->status)->toBe(AutomationStatus::PAUSED);
});

test('triggers is cast to and from an array', function () {
    $automation = Automation::factory()->create()->fresh();

    expect($automation->triggers)->toBeArray();
});

test('triggers is nullable', function () {
    $automation = Automation::factory()->create(['triggers' => null])->fresh();

    expect($automation->triggers)->toBeNull();
});

test('timestamps are cast to date instances', function () {
    $automation = Automation::factory()->create()->fresh();

    expect($automation->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($automation->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('it belongs to an email list', function () {
    $automation = Automation::factory()->create();

    expect($automation->emailList)->toBeInstanceOf(EmailList::class)
        ->and($automation->emailList->id)->toBe($automation->email_list_id);
});

test('it has many steps', function () {
    $automation = Automation::factory()->create();
    AutomationStep::factory()->count(3)->for($automation)->create();

    expect($automation->steps)->toHaveCount(3)
        ->each->toBeInstanceOf(AutomationStep::class);
});

test('it has many action subscribers', function () {
    $automation = Automation::factory()->create();
    AutomationActionSubscriber::factory()->count(2)->for($automation)->create();

    expect($automation->actionSubscribers)->toHaveCount(2)
        ->each->toBeInstanceOf(AutomationActionSubscriber::class);
});

test('the active state marks the automation as active and run', function () {
    $automation = Automation::factory()->active()->create();

    expect($automation->status)->toBe(AutomationStatus::ACTIVE)
        ->and($automation->last_ran_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('force deleting an email list cascades to its automations', function () {
    $automation = Automation::factory()->create();

    $automation->emailList->forceDelete();

    $this->assertDatabaseMissing('automations', ['id' => $automation->id]);
});
