<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportListSubscribers;
use App\Enums\Status;
use App\Http\Requests\ImportSubscribersRequest;
use App\Http\Requests\StoreSubscriberRequest;
use App\Models\EmailList;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
}
