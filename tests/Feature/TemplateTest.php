<?php

use App\Models\Template;

test('the factory creates a persistable template', function () {
    $template = Template::factory()->create();

    $this->assertDatabaseHas('templates', [
        'id' => $template->id,
        'name' => $template->name,
        'html' => $template->html,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $template = Template::create([
        'name' => 'Welcome Email',
        'html' => '<h1>Welcome</h1>',
        'content_json' => ['pages' => [], 'styles' => []],
        'structured_html' => '<h1>Welcome</h1>',
    ]);

    expect($template->name)->toBe('Welcome Email')
        ->and($template->html)->toBe('<h1>Welcome</h1>')
        ->and($template->content_json)->toBe(['pages' => [], 'styles' => []])
        ->and($template->structured_html)->toBe('<h1>Welcome</h1>');
});

test('content_json is cast to and from an array', function () {
    $template = Template::factory()->create()->fresh();

    expect($template->content_json)->toBeArray();
});

test('content_json and structured_html are nullable', function () {
    $template = Template::factory()->create([
        'content_json' => null,
        'structured_html' => null,
    ])->fresh();

    expect($template->content_json)->toBeNull()
        ->and($template->structured_html)->toBeNull();
});

test('timestamps are cast to date instances', function () {
    $template = Template::factory()->create()->fresh();

    expect($template->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($template->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});
