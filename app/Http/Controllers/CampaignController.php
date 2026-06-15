<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CampaignFrequency;
use App\Enums\CampaignStatus;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignDispatch;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CampaignController extends Controller
{
    /**
     * Statuses a campaign can still be edited in.
     */
    private const EDITABLE = [CampaignStatus::DRAFT, CampaignStatus::PAUSED, CampaignStatus::CANCELLED];

    /**
     * List campaigns.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = CampaignStatus::tryFrom((string) $request->string('status'));

        $campaigns = Campaign::query()
            ->with('emailList:id,name')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Campaign $campaign): array => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'status' => $campaign->status->value,
                'list' => $campaign->emailList?->name,
                'sent_to_count' => $campaign->sent_to_count,
                'unique_open_count' => $campaign->unique_open_count,
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
                'sent_at' => $campaign->sent_at?->toIso8601String(),
                'updated_at' => $campaign->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => ['search' => $search, 'status' => $status?->value],
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Show the new-campaign form.
     */
    public function create(): Response
    {
        return Inertia::render('Campaigns/Create', [
            'lists' => $this->listOptions(),
            'templates' => $this->templateOptions(),
        ]);
    }

    /**
     * Create a draft campaign.
     */
    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $list = EmailList::findOrFail($data['email_list_id']);
        $template = ! empty($data['template_id']) ? Template::find($data['template_id']) : null;

        $campaign = Campaign::create([
            'email_list_id' => $list->id,
            'template_id' => $template?->id,
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'from_email' => $list->default_from_email,
            'from_name' => $list->default_from_name,
            'reply_to_email' => $list->default_reply_to_email,
            'html' => $template?->html,
            'structured_html' => $template?->structured_html,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign created.')]);

        return to_route('campaigns.edit', $campaign);
    }

    /**
     * Show the campaign editor.
     */
    public function edit(Campaign $campaign): Response
    {
        $campaign->load('emailList:id,name', 'template:id,name');

        return Inertia::render('Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'from_name' => $campaign->from_name,
                'from_email' => $campaign->from_email,
                'reply_to_email' => $campaign->reply_to_email,
                'segment_id' => $campaign->segment_id,
                'template' => $campaign->template?->name,
                'list' => $campaign->emailList?->name,
                'content' => $campaign->content_json ?? [],
                'track_opens' => $campaign->track_opens,
                'track_clicks' => $campaign->track_clicks,
                'frequency' => $campaign->frequency->value,
                'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d\TH:i'),
                'next_run_at' => $campaign->next_run_at?->toIso8601String(),
                'status' => $campaign->status->value,
                'editable' => in_array($campaign->status, self::EDITABLE, true),
                'stats' => [
                    'sent_to_count' => $campaign->sent_to_count,
                    'unique_open_count' => $campaign->unique_open_count,
                    'unique_click_count' => $campaign->unique_click_count,
                    'bounce_count' => $campaign->bounce_count,
                    'unsubscribe_count' => $campaign->unsubscribe_count,
                ],
            ],
            'segments' => Segment::query()
                ->where('email_list_id', $campaign->email_list_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Segment $segment): array => ['value' => $segment->id, 'label' => $segment->name])
                ->all(),
            'frequencies' => array_map(
                fn (CampaignFrequency $frequency): array => ['value' => $frequency->value, 'label' => $frequency->label()],
                CampaignFrequency::cases(),
            ),
            'dispatches' => $campaign->dispatches()
                ->latest('scheduled_at')
                ->take(20)
                ->get()
                ->map(fn (CampaignDispatch $dispatch): array => [
                    'id' => $dispatch->id,
                    'status' => $dispatch->status,
                    'scheduled_at' => $dispatch->scheduled_at?->toIso8601String(),
                    'sent_at' => $dispatch->sent_at?->toIso8601String(),
                    'sent_to_count' => $dispatch->sent_to_count,
                    'unique_open_count' => $dispatch->unique_open_count,
                    'unique_click_count' => $dispatch->unique_click_count,
                ]),
        ]);
    }

    /**
     * Update a draft campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, self::EDITABLE, true), 403);

        $data = $request->validated();
        $html = $data['html'] ?? null;
        $scheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        $campaign->update([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'reply_to_email' => $data['reply_to_email'] ?? null,
            'segment_id' => $data['segment_id'] ?? null,
            'track_opens' => $data['track_opens'] ?? false,
            'track_clicks' => $data['track_clicks'] ?? false,
            'frequency' => $data['frequency'] ?? CampaignFrequency::ONCE->value,
            'scheduled_at' => $scheduledAt,
            // The first run is the scheduled time; the scheduler advances it afterwards.
            'next_run_at' => $scheduledAt,
            'content_json' => $data['content'] ?? [],
            'html' => $html,
            'structured_html' => $html ? (new CssToInlineStyles)->convert($html) : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign saved.')]);

        return to_route('campaigns.edit', $campaign);
    }

    /**
     * Delete a campaign.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign deleted.')]);

        return to_route('campaigns.index');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CampaignStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            CampaignStatus::cases(),
        );
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function listOptions(): array
    {
        return EmailList::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (EmailList $list): array => ['value' => $list->id, 'label' => $list->name])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function templateOptions(): array
    {
        return Template::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Template $template): array => ['value' => $template->id, 'label' => $template->name])
            ->all();
    }
}
