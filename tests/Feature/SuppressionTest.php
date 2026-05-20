<?php

use App\Enums\SuppressionReason;
use App\Models\Suppression;
use Illuminate\Database\UniqueConstraintViolationException;

test('the factory creates a persistable suppression', function () {
    $suppression = Suppression::factory()->create();

    $this->assertDatabaseHas('suppressions', [
        'id' => $suppression->id,
        'email' => $suppression->email,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $suppression = Suppression::create([
        'email' => 'blocked@example.com',
        'reason' => SuppressionReason::MANUAL,
        'notes' => 'Requested removal by phone',
        'suppressed_at' => now(),
    ]);

    expect($suppression->email)->toBe('blocked@example.com')
        ->and($suppression->reason)->toBe(SuppressionReason::MANUAL)
        ->and($suppression->notes)->toBe('Requested removal by phone');
});

test('reason is cast to the SuppressionReason enum', function () {
    $suppression = Suppression::factory()->create()->fresh();

    expect($suppression->reason)->toBeInstanceOf(SuppressionReason::class);
});

test('suppressed_at is cast to a date instance', function () {
    $suppression = Suppression::factory()->create()->fresh();

    expect($suppression->suppressed_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('notes is nullable', function () {
    $suppression = Suppression::factory()->create(['notes' => null])->fresh();

    expect($suppression->notes)->toBeNull();
});

test('an email can only be suppressed once', function () {
    $suppression = Suppression::factory()->create();

    expect(fn () => Suppression::factory()->create(['email' => $suppression->email]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the manual state records a reason and note', function () {
    $suppression = Suppression::factory()->manual()->create();

    expect($suppression->reason)->toBe(SuppressionReason::MANUAL)
        ->and($suppression->notes)->toBeString();
});
