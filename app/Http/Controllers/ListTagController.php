<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ListTagController extends Controller
{
    /**
     * Number of tags shown per page.
     */
    private const PER_PAGE = 50;

    /**
     * List the tags used by the list's subscribers with subscriber counts.
     */
    public function index(Request $request, EmailList $list): Response
    {
        $search = trim((string) $request->string('search'));

        $tags = $this->tagCounts($list)
            ->when($search !== '', fn (Collection $tags) => $tags->filter(
                fn (int $count, string $name): bool => str_contains(mb_strtolower($name), mb_strtolower($search)),
            ))
            ->map(fn (int $count, string $name): array => [
                'name' => $name,
                'subscribers_count' => $count,
            ])
            ->sortBy([['subscribers_count', 'desc'], ['name', 'asc']])
            ->values();

        $page = max(1, $request->integer('page', 1));

        $paginator = new LengthAwarePaginator(
            $tags->forPage($page, self::PER_PAGE)->values(),
            $tags->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('Lists/Tags/Index', [
            'list' => [
                'name' => $list->name,
                'slug' => $list->slug,
            ],
            'tags' => $paginator,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Remove a tag from every subscriber on the list.
     */
    public function destroy(Request $request, EmailList $list): RedirectResponse
    {
        $tag = (string) $request->validate([
            'tag' => ['required', 'string'],
        ])['tag'];

        $affected = 0;

        $list->subscribers()
            ->select('subscribers.id', 'subscribers.extra_attributes')
            ->lazyById()
            ->each(function (Subscriber $subscriber) use ($tag, &$affected): void {
                $extra = $subscriber->extra_attributes ?? [];
                $tags = (array) ($extra['tags'] ?? []);

                if (! in_array($tag, $tags, true)) {
                    return;
                }

                $tags = array_values(array_filter($tags, fn ($value): bool => $value !== $tag));

                if ($tags === []) {
                    unset($extra['tags']);
                } else {
                    $extra['tags'] = $tags;
                }

                $subscriber->extra_attributes = $extra === [] ? null : $extra;
                $subscriber->save();

                $affected++;
            });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(
                '{0}Tag was not in use.|{1}Tag removed from :count subscriber.|[2,*]Tag removed from :count subscribers.',
                $affected,
                ['count' => number_format($affected)],
            ),
        ]);

        return to_route('lists.tags.index', $list);
    }

    /**
     * Aggregate tag => subscriber-count for the list, streaming subscribers
     * in chunks so memory stays flat for large lists.
     *
     * @return Collection<string, int>
     */
    private function tagCounts(EmailList $list): Collection
    {
        $counts = [];

        $list->subscribers()
            ->select('subscribers.id', 'subscribers.extra_attributes')
            ->lazyById()
            ->each(function (Subscriber $subscriber) use (&$counts): void {
                foreach ((array) ($subscriber->extra_attributes['tags'] ?? []) as $tag) {
                    if (! is_string($tag) || $tag === '') {
                        continue;
                    }

                    $counts[$tag] = ($counts[$tag] ?? 0) + 1;
                }
            });

        return collect($counts);
    }
}
