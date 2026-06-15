<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Status;
use App\Models\EmailList;
use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ImportListSubscribers
{
    /**
     * Number of rows buffered before each bulk database write.
     */
    private const CHUNK_SIZE = 500;

    /**
     * Fixed column positions for the Mailcoach export. The header row in these
     * files is misaligned with the data, so columns are mapped by position
     * rather than by name.
     */
    private const COL_EXTRA = 0;

    private const COL_SUBSCRIBED_AT = 1;

    private const COL_UNSUBSCRIBED_AT = 2;

    private const COL_EMAIL = 3;

    private const COL_FIRST_NAME = 4;

    private const COL_LAST_NAME = 5;

    private const COL_TAGS = 6;

    /**
     * Stream a CSV export and import its subscribers into the given list.
     *
     * The file is read row by row and flushed to the database in chunks so
     * memory stays flat regardless of how many thousands of rows it contains.
     *
     * @return array{imported: int, skipped: int}
     */
    public function handle(EmailList $list, string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $imported = 0;
        $skipped = 0;

        /** @var array<string, array<string, mixed>> $buffer */
        $buffer = [];

        // Discard the (misaligned) header row.
        fgetcsv($handle, escape: '');

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $mapped = $this->mapRow($row);

            if ($mapped === null) {
                $skipped++;

                continue;
            }

            // De-duplicate within the file by email; the last occurrence wins.
            $buffer[$mapped['email']] = $mapped;

            if (count($buffer) >= self::CHUNK_SIZE) {
                $imported += $this->flush($list, $buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $imported += $this->flush($list, $buffer);
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Map a raw CSV row to a normalised subscriber payload, or null when the
     * row has no usable email address.
     *
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row): ?array
    {
        $email = strtolower(trim((string) ($row[self::COL_EMAIL] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $extra = [];
        $decoded = json_decode((string) ($row[self::COL_EXTRA] ?? ''), true);

        if (is_array($decoded)) {
            $extra = $decoded;
        }

        $tags = array_values(array_filter(array_map(
            'trim',
            explode(';', (string) ($row[self::COL_TAGS] ?? '')),
        )));

        if ($tags !== []) {
            $extra['tags'] = $tags;
        }

        return [
            'email' => $email,
            'first_name' => $this->clean($row[self::COL_FIRST_NAME] ?? null),
            'last_name' => $this->clean($row[self::COL_LAST_NAME] ?? null),
            'subscribed_at' => $this->parseDate($row[self::COL_SUBSCRIBED_AT] ?? null),
            'unsubscribed_at' => $this->parseDate($row[self::COL_UNSUBSCRIBED_AT] ?? null),
            'extra_attributes' => $extra === [] ? null : json_encode($extra),
        ];
    }

    /**
     * Persist a chunk of mapped rows: bulk-insert new subscribers, then upsert
     * the list pivot records.
     *
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function flush(EmailList $list, array $rows): int
    {
        return DB::transaction(function () use ($list, $rows): int {
            $now = now();
            $emails = array_keys($rows);

            /** @var Collection<string, int> $existing */
            $existing = Subscriber::whereIn('email', $emails)->pluck('id', 'email');

            $toInsert = [];

            foreach ($rows as $email => $data) {
                if ($existing->has($email)) {
                    continue;
                }

                $toInsert[] = [
                    'email' => $email,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'extra_attributes' => $data['extra_attributes'],
                    'uuid' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($toInsert !== []) {
                Subscriber::insertOrIgnore($toInsert);
                $existing = Subscriber::whereIn('email', $emails)->pluck('id', 'email');
            }

            $pivot = [];

            foreach ($rows as $email => $data) {
                $subscriberId = $existing->get($email);

                if ($subscriberId === null) {
                    continue;
                }

                $status = match (true) {
                    $data['unsubscribed_at'] !== null => Status::UNSUBSCRIBED,
                    $list->requires_confirmation => Status::UNCONFIRMED,
                    default => Status::SUBSCRIBED,
                };

                $pivot[] = [
                    'email_list_id' => $list->id,
                    'subscriber_id' => $subscriberId,
                    'status' => $status->value,
                    'subscribed_at' => $data['subscribed_at'],
                    'unsubscribed_at' => $data['unsubscribed_at'],
                    'subscribe_source' => 'import',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($pivot !== []) {
                DB::table('email_list_subscribers')->upsert(
                    $pivot,
                    ['email_list_id', 'subscriber_id'],
                    ['status', 'subscribed_at', 'unsubscribed_at', 'subscribe_source', 'updated_at'],
                );
            }

            return count($pivot);
        });
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
