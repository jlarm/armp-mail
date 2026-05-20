<?php

use App\Models\Automation;
use App\Models\AutomationActionSubscriber;
use App\Models\AutomationStep;
use App\Models\Subscriber;

test('the factory creates a persistable action subscriber', function () {
    $action = AutomationActionSubscriber::factory()->create();

    $this->assertDatabaseHas('automation_action_subscribers', [
        'id' => $action->id,
        'automation_id' => $action->automation_id,
        'automation_step_id' => $action->automation_step_id,
        'subscriber_id' => $action->subscriber_id,
    ]);
});

test('the factory creates a step belonging to the same automation', function () {
    $action = AutomationActionSubscriber::factory()->create();

    expect($action->automationStep->automation_id)->toBe($action->automation_id);
});

test('it mass assigns fillable attributes', function () {
    $automation = Automation::factory()->create();
    $step = AutomationStep::factory()->for($automation)->create();
    $subscriber = Subscriber::factory()->create();

    $action = AutomationActionSubscriber::create([
        'automation_id' => $automation->id,
        'automation_step_id' => $step->id,
        'subscriber_id' => $subscriber->id,
        'run_at' => now(),
    ]);

    expect($action->automation_id)->toBe($automation->id)
        ->and($action->automation_step_id)->toBe($step->id)
        ->and($action->subscriber_id)->toBe($subscriber->id);
});

test('timestamps are cast to date instances', function () {
    $action = AutomationActionSubscriber::factory()->create()->fresh();

    expect($action->run_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($action->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($action->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('completed_at and halted_at are nullable', function () {
    $action = AutomationActionSubscriber::factory()->create()->fresh();

    expect($action->completed_at)->toBeNull()
        ->and($action->halted_at)->toBeNull();
});

test('it belongs to an automation', function () {
    $action = AutomationActionSubscriber::factory()->create();

    expect($action->automation)->toBeInstanceOf(Automation::class)
        ->and($action->automation->id)->toBe($action->automation_id);
});

test('it belongs to an automation step', function () {
    $action = AutomationActionSubscriber::factory()->create();

    expect($action->automationStep)->toBeInstanceOf(AutomationStep::class)
        ->and($action->automationStep->id)->toBe($action->automation_step_id);
});

test('it belongs to a subscriber', function () {
    $action = AutomationActionSubscriber::factory()->create();

    expect($action->subscriber)->toBeInstanceOf(Subscriber::class)
        ->and($action->subscriber->id)->toBe($action->subscriber_id);
});

test('the completed state marks the action as completed', function () {
    $action = AutomationActionSubscriber::factory()->completed()->create();

    expect($action->completed_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('the halted state marks the action as halted', function () {
    $action = AutomationActionSubscriber::factory()->halted()->create();

    expect($action->halted_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('deleting an automation cascades to its action subscribers', function () {
    $action = AutomationActionSubscriber::factory()->create();

    $action->automation->delete();

    $this->assertDatabaseMissing('automation_action_subscribers', ['id' => $action->id]);
});

test('deleting a subscriber cascades to its action subscribers', function () {
    $action = AutomationActionSubscriber::factory()->create();

    $action->subscriber->delete();

    $this->assertDatabaseMissing('automation_action_subscribers', ['id' => $action->id]);
});
