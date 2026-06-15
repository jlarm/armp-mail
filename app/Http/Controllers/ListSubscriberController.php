<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportListSubscribers;
use App\Enums\Status;
use App\Http\Requests\ImportSubscribersRequest;
use App\Http\Requests\StoreSubscriberRequest;
use App\Http\Requests\UpdateSubscriberRequest;
use App\Models\EmailList;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ListSubscriberController extends Controller
{
    /**
     * Add a subscriber to the given list, creating the subscriber if needed.
     */
    public function store(StoreSubscriberRequest $request, EmailList $list): RedirectResponse
    {
        $data = $request->validated();

        $subscriber = Subscriber::firstWhere('email', $data['email']);

        if ($subscriber === null) {
            $subscriber = new Subscriber;
            $subscriber->email = $data['email'];
            $subscriber->first_name = $data['first_name'] ?? null;
            $subscriber->last_name = $data['last_name'] ?? null;
            $subscriber->uuid = (string) Str::uuid();
            $subscriber->save();
        }

        $status = $list->requires_confirmation ? Status::UNCONFIRMED : Status::SUBSCRIBED;

        $list->subscribers()->attach($subscriber, [
            'status' => $status->value,
            'subscribed_at' => $status === Status::SUBSCRIBED ? now() : null,
            'subscribe_source' => 'manual',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscriber added.')]);

        return to_route('lists.show', $list);
    }

    /**
     * Import subscribers into the list from an uploaded CSV export.
     */
    public function import(ImportSubscribersRequest $request, EmailList $list, ImportListSubscribers $importer): RedirectResponse
    {
        $result = $importer->handle($list, $request->file('file')->getRealPath());

        $message = trans_choice(
            '{0}No subscribers were imported.|{1}Imported :imported subscriber.|[2,*]Imported :imported subscribers.',
            $result['imported'],
            ['imported' => number_format($result['imported'])],
        );

        if ($result['skipped'] > 0) {
            $message .= ' '.trans_choice(
                '{1}:skipped row was skipped.|[2,*]:skipped rows were skipped.',
                $result['skipped'],
                ['skipped' => number_format($result['skipped'])],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('lists.show', $list);
    }

    /**
     * Show the edit screen for a subscriber on the given list.
     */
    public function edit(EmailList $list, Subscriber $subscriber): Response
    {
        $extra = $subscriber->extra_attributes ?? [];

        $tags = array_values(array_filter(
            array_map('strval', (array) ($extra['tags'] ?? [])),
        ));

        unset($extra['tags']);

        $attributes = collect($extra)
            ->map(fn ($value, $key): array => [
                'key' => (string) $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
            ])
            ->values();

        return Inertia::render('Lists/Subscribers/Edit', [
            'list' => [
                'name' => $list->name,
                'slug' => $list->slug,
            ],
            'subscriber' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'tags' => $tags,
                'attributes' => $attributes,
                'status' => $subscriber->pivot->status->value,
                'subscribed_at' => $subscriber->pivot->subscribed_at?->toIso8601String(),
                'unsubscribed_at' => $subscriber->pivot->unsubscribed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update the subscriber's details and extra attributes.
     */
    public function update(UpdateSubscriberRequest $request, EmailList $list, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validated();

        $extra = [];

        foreach ($data['attributes'] ?? [] as $row) {
            $key = trim((string) ($row['key'] ?? ''));

            if ($key === '' || $key === 'tags') {
                continue;
            }

            $extra[$key] = $row['value'] ?? null;
        }

        $tags = array_values(array_filter(array_map('trim', $data['tags'] ?? [])));

        if ($tags !== []) {
            $extra['tags'] = $tags;
        }

        $subscriber->update([
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'extra_attributes' => $extra === [] ? null : $extra,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscriber updated.')]);

        return to_route('lists.subscribers.edit', [$list, $subscriber]);
    }

    /**
     * Unsubscribe the subscriber from the given list.
     */
    public function unsubscribe(EmailList $list, Subscriber $subscriber): RedirectResponse
    {
        $list->subscribers()->updateExistingPivot($subscriber->id, [
            'status' => Status::UNSUBSCRIBED->value,
            'unsubscribed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscriber unsubscribed.')]);

        return to_route('lists.subscribers.edit', [$list, $subscriber]);
    }

    /**
     * Delete the subscriber entirely.
     */
    public function destroy(EmailList $list, Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscriber deleted.')]);

        return to_route('lists.show', $list);
    }
}
