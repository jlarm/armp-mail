<?php

use App\Enums\AutomationStepType;
use App\Models\Automation;
use App\Models\AutomationStep;

test('the factory creates a persistable automation step', function () {
    $step = AutomationStep::factory()->create();

    $this->assertDatabaseHas('automation_steps', [
        'id' => $step->id,
        'automation_id' => $step->automation_id,
        'type' => $step->type->value,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $automation = Automation::factory()->create();

    $step = AutomationStep::create([
        'automation_id' => $automation->id,
        'type' => AutomationStepType::SEND_MAIL,
        'order' => 1,
        'config' => ['subject' => 'Hello'],
    ]);

    expect($step->type)->toBe(AutomationStepType::SEND_MAIL)
        ->and($step->order)->toBe(1)
        ->and($step->config)->toBe(['subject' => 'Hello']);
});

test('type is cast to the AutomationStepType enum', function () {
    $step = AutomationStep::factory()->create()->fresh();

    expect($step->type)->toBeInstanceOf(AutomationStepType::class);
});

test('config is cast to and from an array', function () {
    $step = AutomationStep::factory()->create()->fresh();

    expect($step->config)->toBeArray();
});

test('timestamps are cast to date instances', function () {
    $step = AutomationStep::factory()->create()->fresh();

    expect($step->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($step->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('it belongs to an automation', function () {
    $step = AutomationStep::factory()->create();

    expect($step->automation)->toBeInstanceOf(Automation::class)
        ->and($step->automation->id)->toBe($step->automation_id);
});

test('it optionally belongs to a parent step', function () {
    $parent = AutomationStep::factory()->create();
    $child = AutomationStep::factory()->create(['parent_step_id' => $parent->id]);

    expect($child->parent)->toBeInstanceOf(AutomationStep::class)
        ->and($child->parent->id)->toBe($parent->id)
        ->and(AutomationStep::factory()->create()->parent)->toBeNull();
});

test('it has many child steps', function () {
    $parent = AutomationStep::factory()->create();
    AutomationStep::factory()->count(2)->create(['parent_step_id' => $parent->id]);

    expect($parent->children)->toHaveCount(2)
        ->each->toBeInstanceOf(AutomationStep::class);
});

test('deleting an automation cascades to its steps', function () {
    $step = AutomationStep::factory()->create();

    $step->automation->delete();

    $this->assertDatabaseMissing('automation_steps', ['id' => $step->id]);
});

test('deleting a parent step nulls the child parent_step_id', function () {
    $parent = AutomationStep::factory()->create();
    $child = AutomationStep::factory()->create(['parent_step_id' => $parent->id]);

    $parent->delete();

    expect($child->fresh()->parent_step_id)->toBeNull();
});

test('the sendMail state configures a mail step', function () {
    $step = AutomationStep::factory()->sendMail()->create();

    expect($step->type)->toBe(AutomationStepType::SEND_MAIL)
        ->and($step->config)->toHaveKeys(['subject', 'html']);
});

test('the wait state configures a delay step', function () {
    $step = AutomationStep::factory()->wait()->create();

    expect($step->type)->toBe(AutomationStepType::WAIT)
        ->and($step->config)->toHaveKeys(['duration', 'unit']);
});
