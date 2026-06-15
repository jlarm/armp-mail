<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailListRequest;
use App\Http\Requests\UpdateEmailListRequest;
use App\Models\EmailList;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ListsController extends Controller
{
    /**
     * Display the audience email lists.
     */
    public function index(): Response
    {
        $lists = EmailList::query()
            ->withCount('subscribers')
            ->latest()
            ->get()
            ->map(fn (EmailList $list): array => [
                'id' => $list->id,
                'name' => $list->name,
                'slug' => $list->slug,
                'description' => $list->description,
                'from_name' => $list->default_from_name,
                'from_email' => $list->default_from_email,
                'requires_confirmation' => $list->requires_confirmation,
                'subscribers_count' => $list->subscribers_count,
                'created_at' => $list->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Lists/Index', [
            'lists' => $lists,
        ]);
    }

    /**
     * Display a single email list and its subscribers.
     */
    public function show(Request $request, EmailList $list): Response
    {
        $list->loadCount('subscribers');

        $search = trim((string) $request->string('search'));

        $subscribers = $list->subscribers()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest('email_list_subscribers.created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Subscriber $subscriber): array => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'name' => trim("{$subscriber->first_name} {$subscriber->last_name}") ?: null,
                'status' => $subscriber->pivot->status->value,
                'subscribed_at' => $subscriber->pivot->subscribed_at?->toIso8601String(),
            ]);

        return Inertia::render('Lists/Show', [
            'list' => [
                'id' => $list->id,
                'name' => $list->name,
                'slug' => $list->slug,
                'description' => $list->description,
                'from_name' => $list->default_from_name,
                'from_email' => $list->default_from_email,
                'reply_to_email' => $list->default_reply_to_email,
                'requires_confirmation' => $list->requires_confirmation,
                'subscribers_count' => $list->subscribers_count,
                'created_at' => $list->created_at?->toIso8601String(),
            ],
            'subscribers' => $subscribers,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the settings page for an email list.
     */
    public function edit(EmailList $list): Response
    {
        return Inertia::render('Lists/Edit', [
            'list' => [
                'name' => $list->name,
                'slug' => $list->slug,
                'description' => $list->description,
                'default_from_name' => $list->default_from_name,
                'default_from_email' => $list->default_from_email,
                'default_reply_to_email' => $list->default_reply_to_email,
                'requires_confirmation' => $list->requires_confirmation,
                'redirect_after_subscribed' => $list->redirect_after_subscribed,
                'redirect_after_unsubscribed' => $list->redirect_after_unsubscribed,
                'campaign_mails_per_minute' => $list->campaign_mails_per_minute,
            ],
        ]);
    }

    /**
     * Update an email list's settings.
     */
    public function update(UpdateEmailListRequest $request, EmailList $list): RedirectResponse
    {
        $list->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('List updated.')]);

        return to_route('lists.edit', $list);
    }

    /**
     * Store a newly created email list.
     */
    public function store(StoreEmailListRequest $request): RedirectResponse
    {
        $list = EmailList::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->string('name')->value()),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('List created.')]);

        return to_route('lists.show', $list);
    }

    /**
     * Generate a slug for the list that is unique among existing lists.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'list';
        $slug = $base;
        $suffix = 2;

        while (EmailList::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
