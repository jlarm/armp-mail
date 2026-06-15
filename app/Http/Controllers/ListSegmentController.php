<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\EvaluateSegments;
use App\Enums\AutomationStepType;
use App\Http\Requests\StoreSegmentRequest;
use App\Models\AutomationStep;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListSegmentController extends Controller
{
    /**
     * List the list's segments with their live population counts.
     */
    public function index(Request $request, EmailList $list, EvaluateSegments $evaluator): Response
    {
        $search = trim((string) $request->string('search'));

        $segments = $list->segments()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->get();

        $population = $evaluator->counts($list, $segments);

        return Inertia::render('Lists/Segments/Index', [
            'list' => [
                'name' => $list->name,
                'slug' => $list->slug,
            ],
            'segments' => $segments->map(fn (Segment $segment): array => [
                'id' => $segment->id,
                'name' => $segment->name,
                'conditions' => $segment->rules['conditions'] ?? [],
                'match' => $segment->rules['match'] ?? 'all',
                'population' => $population[$segment->id] ?? 0,
                'created_at' => $segment->created_at?->toIso8601String(),
            ]),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the segment builder.
     */
    public function create(Request $request, EmailList $list, EvaluateSegments $evaluator): Response
    {
        return Inertia::render('Lists/Segments/Create', [
            'list' => ['name' => $list->name, 'slug' => $list->slug],
            'options' => $this->builderOptions($list),
            'preview' => Inertia::optional(fn (): array => $this->preview($request, $list, $evaluator)),
        ]);
    }

    /**
     * Create a new segment for the list.
     */
    public function store(StoreSegmentRequest $request, EmailList $list): RedirectResponse
    {
        $data = $request->validated();

        $list->segments()->create([
            'name' => $data['name'],
            'rules' => [
                'match' => $data['match'],
                'conditions' => array_values($data['conditions']),
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Segment created.')]);

        return to_route('lists.segments.index', $list);
    }

    /**
     * Show the builder for an existing segment.
     */
    public function edit(Request $request, EmailList $list, Segment $segment, EvaluateSegments $evaluator): Response
    {
        return Inertia::render('Lists/Segments/Edit', [
            'list' => ['name' => $list->name, 'slug' => $list->slug],
            'options' => $this->builderOptions($list),
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
                'match' => $segment->rules['match'] ?? 'all',
                'conditions' => $segment->rules['conditions'] ?? [],
            ],
            'preview' => Inertia::optional(fn (): array => $this->preview($request, $list, $evaluator)),
        ]);
    }

    /**
     * Update an existing segment.
     */
    public function update(StoreSegmentRequest $request, EmailList $list, Segment $segment): RedirectResponse
    {
        $data = $request->validated();

        $segment->update([
            'name' => $data['name'],
            'rules' => [
                'match' => $data['match'],
                'conditions' => array_values($data['conditions']),
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Segment updated.')]);

        return to_route('lists.segments.index', $list);
    }

    /**
     * Delete a segment.
     */
    public function destroy(EmailList $list, Segment $segment): RedirectResponse
    {
        $segment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Segment deleted.')]);

        return to_route('lists.segments.index', $list);
    }

    /**
     * Build a live preview of subscribers matching the request's draft rules.
     *
     * @return array{total: int, subscribers: array<int, array{id: int, email: string, name: string|null}>}
     */
    private function preview(Request $request, EmailList $list, EvaluateSegments $evaluator): array
    {
        return $evaluator->preview($list, [
            'match' => (string) $request->input('match', 'all'),
            'conditions' => array_values((array) $request->input('conditions', [])),
        ]);
    }

    /**
     * Option sources for the condition builder.
     *
     * @return array<string, mixed>
     */
    private function builderOptions(EmailList $list): array
    {
        return [
            'tags' => $this->availableTags($list),
            'attributes' => $this->availableAttributes($list),
            'campaigns' => Campaign::query()
                ->where('email_list_id', $list->id)
                ->orderByDesc('created_at')
                ->get(['id', 'name'])
                ->map(fn (Campaign $campaign): array => ['value' => $campaign->id, 'label' => $campaign->name])
                ->all(),
            'automationMails' => $this->automationMails($list),
            'lists' => EmailList::query()
                ->whereKeyNot($list->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (EmailList $other): array => ['value' => $other->id, 'label' => $other->name])
                ->all(),
        ];
    }

    /**
     * Distinct tag names used by the list's subscribers.
     *
     * @return array<int, string>
     */
    private function availableTags(EmailList $list): array
    {
        $tags = [];

        $list->subscribers()
            ->select('subscribers.id', 'subscribers.extra_attributes')
            ->lazyById()
            ->each(function (Subscriber $subscriber) use (&$tags): void {
                foreach ((array) ($subscriber->extra_attributes['tags'] ?? []) as $tag) {
                    if (is_string($tag) && $tag !== '') {
                        $tags[$tag] = true;
                    }
                }
            });

        $names = array_keys($tags);
        sort($names);

        return $names;
    }

    /**
     * Distinct extra-attribute keys (excluding tags) used by subscribers.
     *
     * @return array<int, string>
     */
    private function availableAttributes(EmailList $list): array
    {
        $keys = [];

        $list->subscribers()
            ->select('subscribers.id', 'subscribers.extra_attributes')
            ->lazyById()
            ->each(function (Subscriber $subscriber) use (&$keys): void {
                foreach (array_keys((array) $subscriber->extra_attributes) as $key) {
                    if (is_string($key) && $key !== '' && $key !== 'tags') {
                        $keys[$key] = true;
                    }
                }
            });

        $names = array_keys($keys);
        sort($names);

        return $names;
    }

    /**
     * Mail steps from the list's automations, for the action conditions.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function automationMails(EmailList $list): array
    {
        return AutomationStep::query()
            ->where('type', AutomationStepType::SEND_MAIL)
            ->whereHas('automation', fn ($query) => $query->where('email_list_id', $list->id))
            ->with('automation:id,name')
            ->orderBy('automation_id')
            ->orderBy('order')
            ->get()
            ->map(fn (AutomationStep $step): array => [
                'value' => $step->id,
                'label' => trim(sprintf(
                    '%s — %s',
                    $step->automation->name ?? 'Automation',
                    $step->config['subject'] ?? "Step {$step->order}",
                )),
            ])
            ->all();
    }
}
