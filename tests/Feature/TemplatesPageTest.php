<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from templates', function () {
    $this->get(route('templates.index'))->assertRedirect(route('login'));
});

test('the templates index lists templates', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create(['name' => 'Christmas']);

    $this->get(route('templates.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Templates/Index')
                ->has('templates.data', 1)
                ->where('templates.data.0.name', 'Christmas')
        );
});

test('the templates index can search', function () {
    $this->actingAs(User::factory()->create());

    Template::factory()->create(['name' => 'Christmas']);
    Template::factory()->create(['name' => 'Summer Sale']);

    $this->get(route('templates.index', ['search' => 'chris']))
        ->assertInertia(
            fn ($page) => $page
                ->has('templates.data', 1)
                ->where('templates.data.0.name', 'Christmas')
        );
});

test('the create page renders', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('templates.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Templates/Create'));
});

test('a template can be created', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('templates.store'), [
        'name' => 'Christmas',
        'html' => '<html><body>[[[content]]]</body></html>',
    ]);

    $template = Template::sole();

    expect($template->name)->toBe('Christmas');
    expect($template->html)->toContain('[[[content]]]');

    $this->post(route('templates.store'), [
        'name' => 'Another',
        'html' => '<p>Hi</p>',
    ])->assertRedirect(route('templates.edit', Template::latest('id')->first()));
});

test('creating a template requires a name and html', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('templates.store'), [])
        ->assertSessionHasErrors(['name', 'html']);
});

test('the edit page loads the template html', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create(['html' => '<h1>Hello</h1>']);

    $this->get(route('templates.edit', $template))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Templates/Edit')
                ->where('template.html', '<h1>Hello</h1>')
        );
});

test('a template can be updated', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create(['name' => 'Old']);

    $this->put(route('templates.update', $template), [
        'name' => 'New',
        'html' => '<p>Updated</p>',
    ])->assertRedirect(route('templates.edit', $template));

    $template->refresh();
    expect($template->name)->toBe('New');
    expect($template->html)->toBe('<p>Updated</p>');
});

test('a template can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create();

    $this->delete(route('templates.destroy', $template))
        ->assertRedirect(route('templates.index'));

    $this->assertDatabaseMissing('templates', ['id' => $template->id]);
});

test('saving a template inlines its css into structured_html', function () {
    $this->actingAs(User::factory()->create());

    $html = '<html><head><style>p { color: red; }</style></head><body><p>Hi</p></body></html>';

    $this->post(route('templates.store'), ['name' => 'Styled', 'html' => $html]);

    $template = Template::sole();

    expect($template->structured_html)->toContain('style="color: red;"');
});

test('the edit page provides lists for the use-in-campaign action', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create();
    EmailList::factory()->create(['name' => 'Owners']);

    $this->get(route('templates.edit', $template))
        ->assertInertia(fn ($page) => $page->has('lists', 1)->where('lists.0.label', 'Owners'));
});

test('a draft campaign can be created from a template', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create([
        'default_from_email' => 'desk@example.com',
        'default_from_name' => 'Desk',
    ]);
    $template = Template::factory()->create(['html' => '<p>Hello [[[name]]]</p>']);

    $this->post(route('templates.campaign', $template), [
        'email_list_id' => $list->id,
        'name' => 'Spring blast',
        'subject' => 'Hello there',
    ])->assertRedirect(route('lists.show', $list));

    $campaign = Campaign::sole();

    expect($campaign->template_id)->toBe($template->id);
    expect($campaign->email_list_id)->toBe($list->id);
    expect($campaign->name)->toBe('Spring blast');
    expect($campaign->from_email)->toBe('desk@example.com');
    expect($campaign->status)->toBe(CampaignStatus::DRAFT);
});

test('creating a campaign from a template requires a list and name', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create();

    $this->post(route('templates.campaign', $template), [])
        ->assertSessionHasErrors(['email_list_id', 'name']);
});

test('a template can be duplicated', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create(['name' => 'Christmas', 'html' => '<p>Ho ho</p>']);

    $this->post(route('templates.duplicate', $template))
        ->assertRedirect();

    expect(Template::count())->toBe(2);
    $copy = Template::where('name', 'Christmas (copy)')->sole();
    expect($copy->html)->toBe('<p>Ho ho</p>');
});

test('a test email can be sent for a template', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create();

    $this->post(route('templates.test', $template), ['email' => 'me@example.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(1);
});

test('sending a test requires a valid email', function () {
    $this->actingAs(User::factory()->create());

    $template = Template::factory()->create();

    $this->post(route('templates.test', $template), ['email' => 'nope'])
        ->assertSessionHasErrors('email');
});

test('an image can be uploaded and returns a url', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->post(route('templates.images'), [
        'image' => UploadedFile::fake()->image('logo.png'),
    ])
        ->assertOk()
        ->assertJsonStructure(['url']);

    expect(Storage::disk('public')->allFiles('templates'))->toHaveCount(1);
});

test('image upload rejects non-images', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->post(route('templates.images'), [
        'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ])->assertSessionHasErrors('image');
});
