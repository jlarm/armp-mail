<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Template;

test('the factory creates a persistable campaign', function () {
    $campaign = Campaign::factory()->create();

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'email_list_id' => $campaign->email_list_id,
        'name' => $campaign->name,
    ]);
});

test('it mass assigns fillable attributes', function () {
    $emailList = EmailList::factory()->create();

    $campaign = Campaign::create([
        'email_list_id' => $emailList->id,
        'name' => 'Spring Sale',
        'subject' => 'Save 20% today',
        'from_email' => 'hello@example.com',
        'from_name' => 'Example',
        'reply_to_email' => 'reply@example.com',
        'html' => '<h1>Sale</h1>',
        'content_json' => ['pages' => []],
        'structured_html' => '<h1>Sale</h1>',
        'status' => CampaignStatus::DRAFT,
        'track_opens' => false,
        'track_clicks' => false,
    ]);

    expect($campaign->name)->toBe('Spring Sale')
        ->and($campaign->subject)->toBe('Save 20% today')
        ->and($campaign->track_opens)->toBeFalse()
        ->and($campaign->track_clicks)->toBeFalse();
});

test('status is cast to the CampaignStatus enum', function () {
    $campaign = Campaign::factory()->create()->fresh();

    expect($campaign->status)->toBeInstanceOf(CampaignStatus::class)
        ->and($campaign->status)->toBe(CampaignStatus::DRAFT);
});

test('content_json is cast to and from an array', function () {
    $campaign = Campaign::factory()->create()->fresh();

    expect($campaign->content_json)->toBeArray();
});

test('counters are cast to integers', function () {
    $campaign = Campaign::factory()->create()->fresh();

    expect($campaign->sent_to_count)->toBeInt()
        ->and($campaign->open_count)->toBeInt()
        ->and($campaign->click_count)->toBeInt();
});

test('timestamps are cast to date instances', function () {
    $campaign = Campaign::factory()->create()->fresh();

    expect($campaign->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($campaign->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('it belongs to an email list', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign->emailList)->toBeInstanceOf(EmailList::class)
        ->and($campaign->emailList->id)->toBe($campaign->email_list_id);
});

test('it optionally belongs to a segment and template', function () {
    $campaign = Campaign::factory()
        ->for(Segment::factory())
        ->for(Template::factory())
        ->create();

    expect($campaign->segment)->toBeInstanceOf(Segment::class)
        ->and($campaign->template)->toBeInstanceOf(Template::class);
});

test('the scheduled state sets a future scheduled_at', function () {
    $campaign = Campaign::factory()->scheduled()->create();

    expect($campaign->status)->toBe(CampaignStatus::DRAFT)
        ->and($campaign->scheduled_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($campaign->scheduled_at->isFuture())->toBeTrue();
});

test('the sent state marks the campaign as sent', function () {
    $campaign = Campaign::factory()->sent()->create();

    expect($campaign->status)->toBe(CampaignStatus::SENT)
        ->and($campaign->sent_at)->toBeInstanceOf(DateTimeInterface::class);
});
