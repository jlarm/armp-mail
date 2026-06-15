<?php

use App\Enums\Status;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Send;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('guests are redirected to the login page', function () {
    $this->get(route('lists.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the lists page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('lists.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Index')
                ->has('lists')
        );
});

test('the lists page returns email lists with subscriber counts', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->count(3)->create());

    $this->get(route('lists.index'))
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Index')
                ->has('lists', 1)
                ->where('lists.0.name', $list->name)
                ->where('lists.0.subscribers_count', 3)
        );
});

test('authenticated users can create a list', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('lists.store'), [
        'name' => 'Weekly Dispatch',
        'default_from_name' => 'The Dispatch Desk',
        'default_from_email' => 'hello@example.com',
    ])->assertRedirect(route('lists.show', 'weekly-dispatch'));

    $this->assertDatabaseHas('email_lists', [
        'name' => 'Weekly Dispatch',
        'slug' => 'weekly-dispatch',
        'default_from_name' => 'The Dispatch Desk',
        'default_from_email' => 'hello@example.com',
    ]);
});

test('authenticated users can view a list show page', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->count(2)->create());

    $this->get(route('lists.show', $list))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Show')
                ->where('list.name', $list->name)
                ->where('list.subscribers_count', 2)
                ->has('subscribers.data', 2)
                ->where('subscribers.total', 2)
        );
});

test('the show page paginates subscribers', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->count(30)->create());

    $this->get(route('lists.show', $list))
        ->assertInertia(
            fn ($page) => $page
                ->has('subscribers.data', 25)
                ->where('subscribers.total', 30)
                ->where('subscribers.last_page', 2)
        );

    $this->get(route('lists.show', [$list, 'page' => 2]))
        ->assertInertia(
            fn ($page) => $page
                ->has('subscribers.data', 5)
                ->where('subscribers.current_page', 2)
        );
});

test('the show page can search subscribers', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $needle = Subscriber::factory()->create([
        'email' => 'findme@example.com',
        'first_name' => 'Unique',
        'last_name' => 'Person',
    ]);
    $list->subscribers()->attach($needle);
    $list->subscribers()->attach(Subscriber::factory()->count(5)->create());

    $this->get(route('lists.show', [$list, 'search' => 'findme']))
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.search', 'findme')
                ->has('subscribers.data', 1)
                ->where('subscribers.data.0.email', 'findme@example.com')
        );

    $this->get(route('lists.show', [$list, 'search' => 'Unique']))
        ->assertInertia(fn ($page) => $page->has('subscribers.data', 1));
});

test('the show page can filter subscribers by status', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(
        Subscriber::factory()->create()->id,
        ['status' => Status::SUBSCRIBED->value],
    );
    Subscriber::factory()->count(2)->create()->each(
        fn ($subscriber) => $list->subscribers()->attach(
            $subscriber->id,
            ['status' => Status::UNSUBSCRIBED->value],
        ),
    );

    $this->get(route('lists.show', [$list, 'status' => 'unsubscribed']))
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.status', 'unsubscribed')
                ->has('subscribers.data', 2)
                ->where('subscribers.total', 2)
                ->has('statuses')
        );

    $this->get(route('lists.show', [$list, 'status' => 'subscribed']))
        ->assertInertia(fn ($page) => $page->has('subscribers.data', 1));
});

test('an invalid status filter is ignored', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->count(3)->create());

    $this->get(route('lists.show', [$list, 'status' => 'bogus']))
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.status', null)
                ->has('subscribers.data', 3)
        );
});

test('creating a list generates a unique slug when names collide', function () {
    $this->actingAs(User::factory()->create());

    EmailList::factory()->create(['name' => 'Weekly Dispatch', 'slug' => 'weekly-dispatch']);

    $this->post(route('lists.store'), [
        'name' => 'Weekly Dispatch',
        'default_from_name' => 'The Dispatch Desk',
        'default_from_email' => 'hello@example.com',
    ]);

    $this->assertDatabaseHas('email_lists', ['slug' => 'weekly-dispatch-2']);
});

test('creating a list requires name and sender details', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('lists.store'), [])
        ->assertSessionHasErrors(['name', 'default_from_name', 'default_from_email']);
});

test('a subscriber can be added to a list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create(['requires_confirmation' => false]);

    $this->post(route('lists.subscribers.store', $list), [
        'email' => 'new@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ])->assertRedirect(route('lists.show', $list));

    $this->assertDatabaseHas('subscribers', [
        'email' => 'new@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    $subscriber = Subscriber::where('email', 'new@example.com')->sole();

    expect($list->fresh()->subscribers()->whereKey($subscriber->id)->exists())->toBeTrue();
});

test('adding an existing email reuses the subscriber', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create(['email' => 'reuse@example.com']);

    $this->post(route('lists.subscribers.store', $list), [
        'email' => 'reuse@example.com',
    ]);

    expect(Subscriber::where('email', 'reuse@example.com')->count())->toBe(1);
    expect($list->fresh()->subscribers()->whereKey($subscriber->id)->exists())->toBeTrue();
});

test('a subscriber cannot be added to the same list twice', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create(['email' => 'dupe@example.com']);
    $list->subscribers()->attach($subscriber);

    $this->post(route('lists.subscribers.store', $list), [
        'email' => 'dupe@example.com',
    ])->assertSessionHasErrors('email');
});

test('adding a subscriber requires a valid email', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->post(route('lists.subscribers.store', $list), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('subscribers can be imported from a mailcoach csv export', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create(['requires_confirmation' => false]);

    // Header is intentionally misaligned with the data, mirroring the real export.
    $csv = <<<'CSV'
    email,first_name,last_name,tags,subscribed_at,unsubscribed_at,extra_attributes
    "{""mailcoach_tags"":""campaign-1-opened""}","2025-01-27 16:04:23",,active@example.com,"Jane","Doe","Sales;Acme"
    "{""mailcoach_tags"":""""}","2025-01-27 16:04:23","2025-01-28 15:05:04",gone@example.com,,,
    "{""mailcoach_tags"":""""}","2025-01-27 16:04:23",,not-an-email,,,
    CSV;

    $file = UploadedFile::fake()->createWithContent('export.csv', $csv);

    $this->post(route('lists.subscribers.import', $list), ['file' => $file])
        ->assertRedirect(route('lists.show', $list));

    // Two valid rows imported, one invalid email skipped.
    expect(Subscriber::count())->toBe(2);

    $active = Subscriber::where('email', 'active@example.com')->sole();
    expect($active->first_name)->toBe('Jane');
    expect($active->last_name)->toBe('Doe');
    expect($active->extra_attributes)->toMatchArray(['tags' => ['Sales', 'Acme']]);

    expect($list->subscribers()->count())->toBe(2);

    expect(
        $list->subscribers()->where('email', 'active@example.com')->first()->pivot->status
    )->toBe(Status::SUBSCRIBED);

    // A row with an unsubscribed_at date lands as unsubscribed.
    expect(
        $list->subscribers()->where('email', 'gone@example.com')->first()->pivot->status
    )->toBe(Status::UNSUBSCRIBED);
});

test('importing the same export twice does not duplicate subscribers', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $csv = <<<'CSV'
    email,first_name,last_name,tags,subscribed_at,unsubscribed_at,extra_attributes
    "{""mailcoach_tags"":""""}","2025-01-27 16:04:23",,repeat@example.com,"Sam",,
    CSV;

    $payload = fn () => ['file' => UploadedFile::fake()->createWithContent('export.csv', $csv)];

    $this->post(route('lists.subscribers.import', $list), $payload());
    $this->post(route('lists.subscribers.import', $list), $payload());

    expect(Subscriber::where('email', 'repeat@example.com')->count())->toBe(1);
    expect($list->subscribers()->count())->toBe(1);
});

test('importing requires a csv file', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->post(route('lists.subscribers.import', $list), [])
        ->assertSessionHasErrors('file');
});

test('the list settings page can be viewed', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->get(route('lists.edit', $list))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Edit')
                ->where('list.slug', $list->slug)
                ->where('list.name', $list->name)
        );
});

test('a list can be updated', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create(['slug' => 'old-slug']);

    $this->put(route('lists.update', $list), [
        'name' => 'Updated List',
        'slug' => 'updated-list',
        'description' => 'A renamed list',
        'default_from_name' => 'Desk',
        'default_from_email' => 'desk@example.com',
        'default_reply_to_email' => 'reply@example.com',
        'requires_confirmation' => true,
        'redirect_after_subscribed' => 'https://example.com/welcome',
        'redirect_after_unsubscribed' => 'https://example.com/bye',
        'campaign_mails_per_minute' => 60,
    ])->assertRedirect(route('lists.edit', 'updated-list'));

    $this->assertDatabaseHas('email_lists', [
        'id' => $list->id,
        'name' => 'Updated List',
        'slug' => 'updated-list',
        'default_from_email' => 'desk@example.com',
        'requires_confirmation' => true,
        'campaign_mails_per_minute' => 60,
    ]);
});

test('updating a list requires sender details and a unique slug', function () {
    $this->actingAs(User::factory()->create());

    $other = EmailList::factory()->create(['slug' => 'taken']);
    $list = EmailList::factory()->create();

    $this->put(route('lists.update', $list), [
        'name' => '',
        'slug' => 'taken',
        'default_from_name' => '',
        'default_from_email' => 'not-an-email',
    ])->assertSessionHasErrors(['name', 'slug', 'default_from_name', 'default_from_email']);
});

test('a list keeps its own slug on update', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create(['slug' => 'keep-me']);

    $this->put(route('lists.update', $list), [
        'name' => $list->name,
        'slug' => 'keep-me',
        'default_from_name' => $list->default_from_name,
        'default_from_email' => $list->default_from_email,
    ])->assertSessionHasNoErrors();
});

test('a subscriber edit page can be viewed', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create([
        'email' => 'edit@example.com',
        'extra_attributes' => ['tags' => ['VIP'], 'mailcoach_tags' => 'x'],
    ]);
    $list->subscribers()->attach($subscriber);

    $this->get(route('lists.subscribers.edit', [$list, $subscriber]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Subscribers/Edit')
                ->where('subscriber.email', 'edit@example.com')
                ->where('subscriber.tags', ['VIP'])
                ->where('subscriber.attributes', [['key' => 'mailcoach_tags', 'value' => 'x']])
        );
});

test('a subscriber on another list cannot be edited via this list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $otherList = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();
    $otherList->subscribers()->attach($subscriber);

    $this->get(route('lists.subscribers.edit', [$list, $subscriber]))
        ->assertNotFound();
});

test('a subscriber can be updated with tags and attributes', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();
    $list->subscribers()->attach($subscriber);

    $this->put(route('lists.subscribers.update', [$list, $subscriber]), [
        'email' => 'updated@example.com',
        'first_name' => 'Joe',
        'last_name' => 'Lohr',
        'tags' => ['Joe Lohr', 'Test Dealership'],
        'attributes' => [
            ['key' => 'role', 'value' => 'Owner'],
            ['key' => '', 'value' => 'ignored'],
        ],
    ])->assertRedirect(route('lists.subscribers.edit', [$list, $subscriber]));

    $subscriber->refresh();

    expect($subscriber->email)->toBe('updated@example.com');
    expect($subscriber->first_name)->toBe('Joe');
    expect($subscriber->extra_attributes)->toBe([
        'role' => 'Owner',
        'tags' => ['Joe Lohr', 'Test Dealership'],
    ]);
});

test('updating to an email used by another subscriber fails', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    Subscriber::factory()->create(['email' => 'taken@example.com']);
    $subscriber = Subscriber::factory()->create(['email' => 'mine@example.com']);
    $list->subscribers()->attach($subscriber);

    $this->put(route('lists.subscribers.update', [$list, $subscriber]), [
        'email' => 'taken@example.com',
    ])->assertSessionHasErrors('email');
});

test('a subscriber can be unsubscribed from a list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();
    $list->subscribers()->attach($subscriber, ['status' => Status::SUBSCRIBED->value]);

    $this->post(route('lists.subscribers.unsubscribe', [$list, $subscriber]))
        ->assertRedirect(route('lists.subscribers.edit', [$list, $subscriber]));

    $pivot = $list->subscribers()->find($subscriber->id)->pivot;

    expect($pivot->status)->toBe(Status::UNSUBSCRIBED);
    expect($pivot->unsubscribed_at)->not->toBeNull();
});

test('the tags page lists tags with subscriber counts', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(
        Subscriber::factory()->create(['extra_attributes' => ['tags' => ['VIP', 'Sales']]]),
    );
    $list->subscribers()->attach(
        Subscriber::factory()->create(['extra_attributes' => ['tags' => ['VIP']]]),
    );
    $list->subscribers()->attach(
        Subscriber::factory()->create(['extra_attributes' => null]),
    );

    $this->get(route('lists.tags.index', $list))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Tags/Index')
                ->has('tags.data', 2)
                ->where('tags.data.0.name', 'VIP')
                ->where('tags.data.0.subscribers_count', 2)
                ->where('tags.data.1.name', 'Sales')
                ->where('tags.data.1.subscribers_count', 1)
        );
});

test('the tags page can search tags', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(
        Subscriber::factory()->create(['extra_attributes' => ['tags' => ['VIP', 'Sales']]]),
    );

    $this->get(route('lists.tags.index', [$list, 'search' => 'sal']))
        ->assertInertia(
            fn ($page) => $page
                ->has('tags.data', 1)
                ->where('tags.data.0.name', 'Sales')
        );
});

test('deleting a tag removes it from all subscribers on the list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $a = Subscriber::factory()->create(['extra_attributes' => ['tags' => ['VIP', 'Sales'], 'role' => 'Owner']]);
    $b = Subscriber::factory()->create(['extra_attributes' => ['tags' => ['VIP']]]);
    $list->subscribers()->attach($a);
    $list->subscribers()->attach($b);

    $this->delete(route('lists.tags.destroy', $list), ['tag' => 'VIP'])
        ->assertRedirect(route('lists.tags.index', $list));

    expect($a->fresh()->extra_attributes)->toBe(['tags' => ['Sales'], 'role' => 'Owner']);
    expect($b->fresh()->extra_attributes)->toBeNull();
});

test('deleting a tag requires a tag name', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->delete(route('lists.tags.destroy', $list), [])
        ->assertSessionHasErrors('tag');
});

test('the segments page lists segments with population counts', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    Segment::factory()->for($list)->create([
        'name' => 'Owners',
        'rules' => ['match' => 'all', 'conditions' => [
            ['type' => 'tags', 'comparison' => 'any', 'value' => ['Owner']],
        ]],
    ]);

    $list->subscribers()->attach(Subscriber::factory()->create(['extra_attributes' => ['tags' => ['Owner']]]));
    $list->subscribers()->attach(Subscriber::factory()->create(['extra_attributes' => ['tags' => ['Owner', 'VIP']]]));
    $list->subscribers()->attach(Subscriber::factory()->create(['extra_attributes' => ['tags' => ['Other']]]));

    $this->get(route('lists.segments.index', $list))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Segments/Index')
                ->has('segments', 1)
                ->where('segments.0.name', 'Owners')
                ->where('segments.0.population', 2)
        );
});

test('the segment builder exposes condition option sources', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->create([
        'extra_attributes' => ['tags' => ['VIP'], 'role' => 'Owner'],
    ]));
    EmailList::factory()->create();

    $this->get(route('lists.segments.create', $list))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Segments/Create')
                ->where('options.tags', ['VIP'])
                ->where('options.attributes', ['role'])
                ->has('options.lists', 1)
                ->has('options.campaigns')
                ->has('options.automationMails')
        );
});

test('a multi-condition AND segment counts subscribers matching all conditions', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    Segment::factory()->for($list)->create([
        'name' => 'VIP gmail',
        'rules' => ['match' => 'all', 'conditions' => [
            ['type' => 'tags', 'comparison' => 'all', 'value' => ['VIP']],
            ['type' => 'email', 'comparison' => 'ends_with', 'value' => '@gmail.com'],
        ]],
    ]);

    $list->subscribers()->attach(Subscriber::factory()->create(['email' => 'a@gmail.com', 'extra_attributes' => ['tags' => ['VIP']]]));
    $list->subscribers()->attach(Subscriber::factory()->create(['email' => 'b@yahoo.com', 'extra_attributes' => ['tags' => ['VIP']]]));
    $list->subscribers()->attach(Subscriber::factory()->create(['email' => 'c@gmail.com', 'extra_attributes' => ['tags' => ['Other']]]));

    $this->get(route('lists.segments.index', $list))
        ->assertInertia(fn ($page) => $page->where('segments.0.population', 1));
});

test('an opened-campaign condition counts subscribers with an open', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $campaign = Campaign::factory()->create(['email_list_id' => $list->id]);

    $opened = Subscriber::factory()->create();
    $notOpened = Subscriber::factory()->create();
    $list->subscribers()->attach([$opened->id, $notOpened->id]);

    Send::factory()->create([
        'sendable_type' => Campaign::class,
        'sendable_id' => $campaign->id,
        'subscriber_id' => $opened->id,
        'sent_at' => now(),
        'opened_at' => now(),
    ]);
    Send::factory()->create([
        'sendable_type' => Campaign::class,
        'sendable_id' => $campaign->id,
        'subscriber_id' => $notOpened->id,
        'sent_at' => now(),
        'opened_at' => null,
    ]);

    Segment::factory()->for($list)->create([
        'name' => 'Openers',
        'rules' => ['match' => 'all', 'conditions' => [
            ['type' => 'opened_campaign', 'value' => $campaign->id],
        ]],
    ]);

    $this->get(route('lists.segments.index', $list))
        ->assertInertia(fn ($page) => $page->where('segments.0.population', 1));
});

test('a segment can be created from conditions', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->post(route('lists.segments.store', $list), [
        'name' => 'Engaged',
        'match' => 'any',
        'conditions' => [
            ['type' => 'tags', 'comparison' => 'any', 'value' => ['Opened']],
            ['type' => 'email', 'comparison' => 'contains', 'value' => 'auto'],
        ],
    ])->assertRedirect(route('lists.segments.index', $list));

    $segment = $list->segments()->sole();
    expect($segment->name)->toBe('Engaged');
    expect($segment->rules['match'])->toBe('any');
    expect($segment->rules['conditions'])->toHaveCount(2);
});

test('creating a segment requires a name, match, and conditions', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->post(route('lists.segments.store', $list), [
        'match' => 'sometimes',
        'conditions' => [],
    ])->assertSessionHasErrors(['name', 'match', 'conditions']);
});

test('a tags condition requires at least one tag', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();

    $this->post(route('lists.segments.store', $list), [
        'name' => 'Broken',
        'match' => 'all',
        'conditions' => [
            ['type' => 'tags', 'comparison' => 'any', 'value' => []],
        ],
    ])->assertSessionHasErrors('conditions.0.value');
});

test('segment names are unique within a list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    Segment::factory()->for($list)->create(['name' => 'Owners']);

    $this->post(route('lists.segments.store', $list), [
        'name' => 'Owners',
        'match' => 'any',
        'conditions' => [['type' => 'tags', 'comparison' => 'any', 'value' => ['Owner']]],
    ])->assertSessionHasErrors('name');
});

test('the segment edit page loads existing rules', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->for($list)->create([
        'name' => 'Owners',
        'rules' => ['match' => 'any', 'conditions' => [
            ['type' => 'tags', 'comparison' => 'all', 'value' => ['Owner']],
        ]],
    ]);

    $this->get(route('lists.segments.edit', [$list, $segment]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Lists/Segments/Edit')
                ->where('segment.name', 'Owners')
                ->where('segment.match', 'any')
                ->has('segment.conditions', 1)
                ->has('options.tags')
        );
});

test('a segment can be updated', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->for($list)->create(['name' => 'Old']);

    $this->put(route('lists.segments.update', [$list, $segment]), [
        'name' => 'New name',
        'match' => 'all',
        'conditions' => [
            ['type' => 'email', 'comparison' => 'contains', 'value' => '@acme.com'],
        ],
    ])->assertRedirect(route('lists.segments.index', $list));

    $segment->refresh();
    expect($segment->name)->toBe('New name');
    expect($segment->rules['conditions'][0]['type'])->toBe('email');
});

test('a segment keeps its own name on update', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->for($list)->create(['name' => 'Keep']);

    $this->put(route('lists.segments.update', [$list, $segment]), [
        'name' => 'Keep',
        'match' => 'all',
        'conditions' => [['type' => 'tags', 'comparison' => 'any', 'value' => ['VIP']]],
    ])->assertSessionHasNoErrors();
});

test('a segment from another list cannot be edited via this list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->create();

    $this->get(route('lists.segments.edit', [$list, $segment]))->assertNotFound();
});

test('the segment evaluator previews matching subscribers', function () {
    $list = EmailList::factory()->create();
    $list->subscribers()->attach(Subscriber::factory()->create([
        'email' => 'a@gmail.com',
        'extra_attributes' => ['tags' => ['VIP']],
    ]));
    $list->subscribers()->attach(Subscriber::factory()->create([
        'email' => 'b@yahoo.com',
        'extra_attributes' => ['tags' => ['VIP']],
    ]));

    $result = app(App\Actions\EvaluateSegments::class)->preview($list, [
        'match' => 'all',
        'conditions' => [
            ['type' => 'tags', 'comparison' => 'all', 'value' => ['VIP']],
            ['type' => 'email', 'comparison' => 'ends_with', 'value' => '@gmail.com'],
        ],
    ]);

    expect($result['total'])->toBe(1);
    expect($result['subscribers'])->toHaveCount(1);
    expect($result['subscribers'][0]['email'])->toBe('a@gmail.com');
});

test('the segment preview is empty without conditions', function () {
    $list = EmailList::factory()->create();

    $result = app(App\Actions\EvaluateSegments::class)->preview($list, [
        'match' => 'all',
        'conditions' => [],
    ]);

    expect($result['total'])->toBe(0);
    expect($result['subscribers'])->toBe([]);
});

test('a segment can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->for($list)->create();

    $this->delete(route('lists.segments.destroy', [$list, $segment]))
        ->assertRedirect(route('lists.segments.index', $list));

    $this->assertDatabaseMissing('segments', ['id' => $segment->id]);
});

test('a segment from another list cannot be deleted via this list', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $segment = Segment::factory()->create();

    $this->delete(route('lists.segments.destroy', [$list, $segment]))
        ->assertNotFound();
});

test('a subscriber can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $list = EmailList::factory()->create();
    $subscriber = Subscriber::factory()->create();
    $list->subscribers()->attach($subscriber);

    $this->delete(route('lists.subscribers.destroy', [$list, $subscriber]))
        ->assertRedirect(route('lists.show', $list));

    $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
});
