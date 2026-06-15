<?php

use App\Enums\CampaignFrequency;
use App\Enums\CampaignStatus;
use App\Enums\Status;
use App\Models\Campaign;
use App\Models\CampaignDispatch;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Send;
use App\Models\Subscriber;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

test('guests are redirected from campaigns', function () {
    $this->get(route('campaigns.index'))->assertRedirect(route('login'));
});

test('the campaigns index lists campaigns', function () {
    $this->actingAs(User::factory()->create());

    Campaign::factory()->create(['name' => 'June blast']);

    $this->get(route('campaigns.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Campaigns/Index')
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.name', 'June blast')
                ->has('statuses')
        );
});

test('the campaigns index filters by status', function () {
    $this->actingAs(User::factory()->create());

    Campaign::factory()->create(['name' => 'Draft one']);
    Campaign::factory()->sent()->create(['name' => 'Old one']);

    $this->get(route('campaigns.index', ['status' => 'sent']))
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.status', 'sent')
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.name', 'Old one')
        );
});

test('the create page renders with options', function () {
    $this->actingAs(User::factory()->create());

    EmailList::factory()->create();
    Template::factory()->create();

    $this->get(route('campaigns.create'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Campaigns/Create')
                ->has('lists', 1)
                ->has('templates', 1)
        );
});

test('a draft campaign can be created from a template', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create([
        'default_from_email' => 'desk@example.com',
        'default_from_name' => 'Desk',
    ]);
    $template = Template::factory()->create(['html' => '<p>Hi</p>']);

    $this->post(route('campaigns.store'), [
        'email_list_id' => $list->id,
        'name' => 'Launch',
        'subject' => 'Big news',
        'template_id' => $template->id,
    ])->assertRedirect(route('campaigns.edit', Campaign::latest('id')->first()));

    $campaign = Campaign::sole();
    expect($campaign->status)->toBe(CampaignStatus::DRAFT);
    expect($campaign->from_email)->toBe('desk@example.com');
    expect($campaign->html)->toBe('<p>Hi</p>');
    expect($campaign->template_id)->toBe($template->id);
});

test('creating a campaign requires a list and name', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('campaigns.store'), [])
        ->assertSessionHasErrors(['email_list_id', 'name']);
});

test('a draft campaign can be edited', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create(['name' => 'Old']);

    $this->put(route('campaigns.update', $campaign), [
        'name' => 'New name',
        'subject' => 'Updated',
        'html' => '<p>Body</p>',
        'track_opens' => true,
        'track_clicks' => false,
    ])->assertRedirect(route('campaigns.edit', $campaign));

    $campaign->refresh();
    expect($campaign->name)->toBe('New name');
    expect($campaign->html)->toBe('<p>Body</p>');
    expect($campaign->track_clicks)->toBeFalse();
});

test('campaign content blocks are saved', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create();

    $this->put(route('campaigns.update', $campaign), [
        'name' => $campaign->name,
        'content' => [
            ['id' => 'a', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'Hello']],
            ['id' => 'b', 'type' => 'text', 'data' => ['text' => 'Body copy']],
        ],
        'html' => '<h1>Hello</h1><p>Body copy</p>',
    ])->assertRedirect(route('campaigns.edit', $campaign));

    $campaign->refresh();
    expect($campaign->content_json)->toHaveCount(2);
    expect($campaign->content_json[0]['type'])->toBe('heading');
    expect($campaign->html)->toContain('Hello');
});

test('a sent campaign cannot be edited', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->sent()->create();

    $this->put(route('campaigns.update', $campaign), [
        'name' => 'Nope',
    ])->assertForbidden();
});

test('a segment must belong to the campaign list', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create();
    $foreignSegment = Segment::factory()->create();

    $this->put(route('campaigns.update', $campaign), [
        'name' => $campaign->name,
        'segment_id' => $foreignSegment->id,
    ])->assertSessionHasErrors('segment_id');
});

test('saving a recurring schedule sets the next run', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create();
    $runAt = now()->addDay()->startOfMinute();

    $this->put(route('campaigns.update', $campaign), [
        'name' => $campaign->name,
        'frequency' => 'weekly',
        'scheduled_at' => $runAt->format('Y-m-d\TH:i'),
    ])->assertRedirect(route('campaigns.edit', $campaign));

    $campaign->refresh();
    expect($campaign->frequency)->toBe(CampaignFrequency::WEEKLY);
    expect($campaign->next_run_at->format('Y-m-d H:i'))->toBe($runAt->format('Y-m-d H:i'));
});

test('the scheduler dispatches a due once campaign and marks it sent', function () {
    $list = EmailList::factory()->create();
    $list->subscribers()->attach(
        Subscriber::factory()->count(3)->create(),
        ['status' => Status::SUBSCRIBED->value],
    );

    $campaign = Campaign::factory()->create([
        'email_list_id' => $list->id,
        'frequency' => CampaignFrequency::ONCE,
        'next_run_at' => now()->subMinute(),
        'status' => CampaignStatus::DRAFT,
    ]);

    $this->artisan('campaigns:dispatch-due')->assertSuccessful();

    $campaign->refresh();
    expect($campaign->dispatches)->toHaveCount(1);
    expect($campaign->dispatches->first()->sent_to_count)->toBe(3);
    expect($campaign->next_run_at)->toBeNull();
    expect($campaign->status)->toBe(CampaignStatus::SENT);
});

test('the scheduler advances a recurring campaign to its next run', function () {
    $campaign = Campaign::factory()->create([
        'frequency' => CampaignFrequency::WEEKLY,
        'next_run_at' => now()->subMinute(),
        'status' => CampaignStatus::DRAFT,
    ]);

    $this->artisan('campaigns:dispatch-due')->assertSuccessful();

    $campaign->refresh();
    expect($campaign->dispatches)->toHaveCount(1);
    expect($campaign->next_run_at)->not->toBeNull();
    expect($campaign->next_run_at->isFuture())->toBeTrue();
    expect($campaign->status)->toBe(CampaignStatus::SENDING);
});

test('the scheduler ignores cancelled and not-due campaigns', function () {
    Campaign::factory()->create([
        'frequency' => CampaignFrequency::WEEKLY,
        'next_run_at' => now()->subMinute(),
        'status' => CampaignStatus::CANCELLED,
    ]);
    Campaign::factory()->create([
        'frequency' => CampaignFrequency::WEEKLY,
        'next_run_at' => now()->addDay(),
        'status' => CampaignStatus::DRAFT,
    ]);

    $this->artisan('campaigns:dispatch-due')->assertSuccessful();

    expect(CampaignDispatch::count())->toBe(0);
});

test('dispatching sends an email per subscriber with tracking', function () {
    $list = EmailList::factory()->create();
    $list->subscribers()->attach(
        Subscriber::factory()->count(2)->create(),
        ['status' => Status::SUBSCRIBED->value],
    );

    $campaign = Campaign::factory()->create([
        'email_list_id' => $list->id,
        'frequency' => CampaignFrequency::ONCE,
        'next_run_at' => now()->subMinute(),
        'status' => CampaignStatus::DRAFT,
        'html' => '<html><body><a href="https://example.com">Go</a></body></html>',
        'structured_html' => '<html><body><a href="https://example.com">Go</a></body></html>',
        'track_opens' => true,
        'track_clicks' => true,
    ]);

    $this->artisan('campaigns:dispatch-due')->assertSuccessful();

    $dispatch = $campaign->dispatches()->sole();
    expect($dispatch->status)->toBe('sent');
    expect($dispatch->sent_to_count)->toBe(2);
    expect(Send::where('sendable_id', $dispatch->id)->count())->toBe(2);
});

test('opening the pixel records an open and rolls up the dispatch', function () {
    $campaign = Campaign::factory()->create();
    $dispatch = $campaign->dispatches()->create([
        'status' => 'sent',
        'scheduled_at' => now(),
        'sent_at' => now(),
    ]);

    $send = new Send;
    $send->uuid = (string) Str::ulid();
    $send->subscriber_id = Subscriber::factory()->create()->id;
    $send->sent_at = now();
    $send->sendable()->associate($dispatch);
    $send->save();

    $this->get(route('campaigns.track.open', $send->uuid))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');

    expect($send->fresh()->opened_at)->not->toBeNull();
    $dispatch->refresh();
    expect($dispatch->open_count)->toBe(1);
    expect($dispatch->unique_open_count)->toBe(1);

    // A second open bumps total but not unique.
    $this->get(route('campaigns.track.open', $send->uuid))->assertOk();
    $dispatch->refresh();
    expect($dispatch->open_count)->toBe(2);
    expect($dispatch->unique_open_count)->toBe(1);
});

test('clicking records a click and redirects to the original url', function () {
    $campaign = Campaign::factory()->create();
    $dispatch = $campaign->dispatches()->create([
        'status' => 'sent',
        'scheduled_at' => now(),
        'sent_at' => now(),
    ]);

    $send = new Send;
    $send->uuid = (string) Str::ulid();
    $send->subscriber_id = Subscriber::factory()->create()->id;
    $send->sent_at = now();
    $send->sendable()->associate($dispatch);
    $send->save();

    $this->get(route('campaigns.track.click', ['send' => $send->uuid, 'u' => 'https://example.com/page']))
        ->assertRedirect('https://example.com/page');

    expect($send->fresh()->clicked_at)->not->toBeNull();
    expect($dispatch->refresh()->unique_click_count)->toBe(1);
});

test('clicking without a valid url 404s', function () {
    $campaign = Campaign::factory()->create();
    $dispatch = $campaign->dispatches()->create(['status' => 'sent', 'scheduled_at' => now()]);

    $send = new Send;
    $send->uuid = (string) Str::ulid();
    $send->subscriber_id = Subscriber::factory()->create()->id;
    $send->sendable()->associate($dispatch);
    $send->save();

    $this->get(route('campaigns.track.click', ['send' => $send->uuid, 'u' => 'javascript:alert(1)']))
        ->assertNotFound();
});

test('the campaign frequency enum computes the next run', function () {
    $from = Carbon::parse('2026-01-01 09:00');

    expect(CampaignFrequency::ONCE->nextRunAfter($from))->toBeNull();
    expect(CampaignFrequency::WEEKLY->nextRunAfter($from)->toDateString())->toBe('2026-01-08');
    expect(CampaignFrequency::BIWEEKLY->nextRunAfter($from)->toDateString())->toBe('2026-01-15');
    expect(CampaignFrequency::MONTHLY->nextRunAfter($from)->toDateString())->toBe('2026-02-01');
});

test('a campaign can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $campaign = Campaign::factory()->create();

    $this->delete(route('campaigns.destroy', $campaign))
        ->assertRedirect(route('campaigns.index'));

    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});
