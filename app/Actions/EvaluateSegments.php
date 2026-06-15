<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AutomationStep;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Segment;
use App\Models\Send;
use App\Models\Subscriber;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EvaluateSegments
{
    /**
     * Count, in a single pass over the list's subscribers, how many match
     * each segment. Set-based conditions (sends, list membership, engagement)
     * are precomputed once so the per-subscriber check is all in-memory.
     *
     * @param  Collection<int, Segment>  $segments
     * @return array<int, int>
     */
    public function counts(EmailList $list, Collection $segments): array
    {
        if ($segments->isEmpty()) {
            return [];
        }

        $sets = $this->precomputeSets($segments);
        $counts = array_fill_keys($segments->pluck('id')->all(), 0);

        $list->subscribers()
            ->lazyById()
            ->each(function (Subscriber $subscriber) use ($segments, $sets, &$counts): void {
                foreach ($segments as $segment) {
                    if ($this->matches($segment, $subscriber, $sets)) {
                        $counts[$segment->id]++;
                    }
                }
            });

        return $counts;
    }

    /**
     * Preview which of the list's subscribers match an unsaved rule set.
     *
     * @param  array{match?: string, conditions?: array<int, array<string, mixed>>}  $rules
     * @return array{total: int, subscribers: array<int, array{id: int, email: string, name: string|null}>}
     */
    public function preview(EmailList $list, array $rules, int $limit = 25): array
    {
        $conditions = $rules['conditions'] ?? [];

        if (! is_array($conditions) || $conditions === []) {
            return ['total' => 0, 'subscribers' => []];
        }

        $segment = new Segment;
        $segment->rules = $rules;

        $sets = $this->precomputeSets(collect([$segment]));

        $total = 0;
        $subscribers = [];

        $list->subscribers()
            ->lazyById()
            ->each(function (Subscriber $subscriber) use ($segment, $sets, $limit, &$total, &$subscribers): void {
                if (! $this->matches($segment, $subscriber, $sets)) {
                    return;
                }

                $total++;

                if (count($subscribers) < $limit) {
                    $subscribers[] = [
                        'id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'name' => trim("{$subscriber->first_name} {$subscriber->last_name}") ?: null,
                    ];
                }
            });

        return ['total' => $total, 'subscribers' => $subscribers];
    }

    /**
     * Whether a subscriber satisfies a segment's conditions.
     *
     * @param  array<string, array<int, true>>  $sets
     */
    public function matches(Segment $segment, Subscriber $subscriber, array $sets): bool
    {
        $rules = $segment->rules ?? [];
        $conditions = $rules['conditions'] ?? [];

        if ($conditions === []) {
            return false;
        }

        $results = array_map(
            fn (array $condition): bool => $this->evaluate($condition, $subscriber, $sets),
            $conditions,
        );

        return ($rules['match'] ?? 'all') === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, array<int, true>>  $sets
     */
    private function evaluate(array $condition, Subscriber $subscriber, array $sets): bool
    {
        $type = (string) ($condition['type'] ?? '');
        $value = $condition['value'] ?? null;
        $comparison = (string) ($condition['comparison'] ?? '');
        $id = $subscriber->id;

        return match ($type) {
            'tags' => $this->matchesTags($subscriber, $comparison, (array) $value),
            'email' => $this->compareString((string) ($subscriber->email ?? ''), $comparison, (string) $value),
            'attribute' => $this->matchesAttribute($subscriber, $condition),
            'subscribed_at' => $this->matchesSubscribedAt($subscriber, $comparison, (string) $value),
            'not_in_list' => ! isset($sets["not_in_list:{$value}"][$id]),
            'received_campaign',
            'opened_campaign',
            'clicked_campaign',
            'opened_automation_mail',
            'clicked_automation_mail' => isset($sets["{$type}:{$value}"][$id]),
            'engagement' => $this->matchesEngagement($id, (string) $value, $sets),
            default => false,
        };
    }

    /**
     * @param  array<int, mixed>  $value
     */
    private function matchesTags(Subscriber $subscriber, string $comparison, array $value): bool
    {
        $tags = array_values(array_filter(
            (array) ($subscriber->extra_attributes['tags'] ?? []),
            'is_string',
        ));

        $wanted = array_values(array_filter(array_map('strval', $value)));

        if ($wanted === []) {
            return false;
        }

        $intersection = array_intersect($wanted, $tags);

        return match ($comparison) {
            'all' => count($intersection) === count($wanted),
            'none' => $intersection === [],
            default => $intersection !== [],
        };
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function matchesAttribute(Subscriber $subscriber, array $condition): bool
    {
        $key = (string) ($condition['attribute'] ?? '');

        if ($key === '') {
            return false;
        }

        $raw = $subscriber->extra_attributes[$key] ?? null;
        $actual = is_scalar($raw) ? (string) $raw : (is_array($raw) ? (string) json_encode($raw) : '');

        return $this->compareString($actual, (string) ($condition['comparison'] ?? ''), (string) ($condition['value'] ?? ''));
    }

    private function matchesSubscribedAt(Subscriber $subscriber, string $comparison, string $value): bool
    {
        $subscribedAt = $subscriber->pivot->subscribed_at ?? null;

        if ($subscribedAt === null || $value === '') {
            return false;
        }

        $date = Carbon::parse($value);

        return $comparison === 'before'
            ? $subscribedAt->lt($date)
            : $subscribedAt->gt($date);
    }

    /**
     * @param  array<string, array<int, true>>  $sets
     */
    private function matchesEngagement(int $id, string $value, array $sets): bool
    {
        $received = isset($sets['engagement:received'][$id]);
        $engaged = isset($sets['engagement:engaged'][$id]);

        return match ($value) {
            'never_received' => ! $received,
            'disengaged' => $received && ! $engaged,
            default => $engaged,
        };
    }

    private function compareString(string $actual, string $comparison, string $value): bool
    {
        $actual = mb_strtolower($actual);
        $value = mb_strtolower($value);

        return match ($comparison) {
            'not_equals' => $actual !== $value,
            'contains' => $value !== '' && str_contains($actual, $value),
            'starts_with' => $value !== '' && str_starts_with($actual, $value),
            'ends_with' => $value !== '' && str_ends_with($actual, $value),
            default => $actual === $value,
        };
    }

    /**
     * @param  Collection<int, Segment>  $segments
     * @return array<string, array<int, true>>
     */
    private function precomputeSets(Collection $segments): array
    {
        $sets = [];
        $needsEngagement = false;

        foreach ($segments as $segment) {
            foreach ($segment->rules['conditions'] ?? [] as $condition) {
                $type = (string) ($condition['type'] ?? '');

                if ($type === 'engagement') {
                    $needsEngagement = true;

                    continue;
                }

                $value = $condition['value'] ?? null;

                // Only set-based conditions carry a scalar value we precompute.
                if (! is_scalar($value) || $value === '') {
                    continue;
                }

                $set = match ($type) {
                    'received_campaign' => $this->sendSet(Campaign::class, (int) $value, 'sent_at'),
                    'opened_campaign' => $this->sendSet(Campaign::class, (int) $value, 'opened_at'),
                    'clicked_campaign' => $this->sendSet(Campaign::class, (int) $value, 'clicked_at'),
                    'opened_automation_mail' => $this->sendSet(AutomationStep::class, (int) $value, 'opened_at'),
                    'clicked_automation_mail' => $this->sendSet(AutomationStep::class, (int) $value, 'clicked_at'),
                    'not_in_list' => $this->listMemberSet((int) $value),
                    default => null,
                };

                if ($set !== null) {
                    $sets["{$type}:{$value}"] ??= $set;
                }
            }
        }

        if ($needsEngagement) {
            $sets['engagement:received'] = $this->sendSetWhere(fn ($query) => $query->whereNotNull('sent_at'));
            $sets['engagement:engaged'] = $this->sendSetWhere(
                fn ($query) => $query->where(fn ($query) => $query->whereNotNull('opened_at')->orWhereNotNull('clicked_at')),
            );
        }

        return $sets;
    }

    /**
     * @return array<int, true>
     */
    private function sendSet(string $sendableType, int $sendableId, string $timestampColumn): array
    {
        return $this->sendSetWhere(fn ($query) => $query
            ->where('sendable_type', $sendableType)
            ->where('sendable_id', $sendableId)
            ->whereNotNull($timestampColumn));
    }

    /**
     * @param  Closure(Builder<Send>): mixed  $constrain
     * @return array<int, true>
     */
    private function sendSetWhere(Closure $constrain): array
    {
        $query = Send::query();
        $constrain($query);

        return array_fill_keys($query->distinct()->pluck('subscriber_id')->all(), true);
    }

    /**
     * @return array<int, true>
     */
    private function listMemberSet(int $listId): array
    {
        return array_fill_keys(
            DB::table('email_list_subscribers')->where('email_list_id', $listId)->pluck('subscriber_id')->all(),
            true,
        );
    }
}
