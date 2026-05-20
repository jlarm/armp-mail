<?php

use App\Models\TransactionalMail;

test('the factory creates a persistable transactional mail', function () {
    $mail = TransactionalMail::factory()->create();

    $this->assertDatabaseHas('transactional_mails', [
        'id' => $mail->id,
        'name' => $mail->name,
        'subject' => $mail->subject,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $mail = TransactionalMail::create([
        'name' => 'audit-form-submitted',
        'subject' => 'Your audit was received',
        'html' => '<h1>Received</h1>',
        'structured_html' => '<h1>Received</h1>',
        'store_mail' => false,
        'track_opens' => true,
        'track_clicks' => true,
    ]);

    expect($mail->name)->toBe('audit-form-submitted')
        ->and($mail->subject)->toBe('Your audit was received')
        ->and($mail->store_mail)->toBeFalse()
        ->and($mail->track_opens)->toBeTrue()
        ->and($mail->track_clicks)->toBeTrue();
});

test('booleans are cast and default to a transactional-friendly state', function () {
    $mail = TransactionalMail::factory()->create()->fresh();

    expect($mail->store_mail)->toBeTrue()
        ->and($mail->track_opens)->toBeFalse()
        ->and($mail->track_clicks)->toBeFalse();
});

test('name and structured_html are nullable', function () {
    $mail = TransactionalMail::factory()->create([
        'name' => null,
        'structured_html' => null,
    ])->fresh();

    expect($mail->name)->toBeNull()
        ->and($mail->structured_html)->toBeNull();
});

test('timestamps are cast to date instances', function () {
    $mail = TransactionalMail::factory()->create()->fresh();

    expect($mail->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($mail->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('the tracked state enables open and click tracking', function () {
    $mail = TransactionalMail::factory()->tracked()->create();

    expect($mail->track_opens)->toBeTrue()
        ->and($mail->track_clicks)->toBeTrue();
});
