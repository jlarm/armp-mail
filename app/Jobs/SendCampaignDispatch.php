<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\EvaluateSegments;
use App\Enums\Status;
use App\Models\CampaignDispatch;
use App\Models\Segment;
use App\Models\Send;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCampaignDispatch implements ShouldQueue
{
    use Queueable;

    /** Retry up to 3 times before marking failed. */
    public int $tries = 3;

    /** Kill the job after 2 hours — enough for very large lists. */
    public int $timeout = 7200;

    public function __construct(public CampaignDispatch $dispatch) {}

    public function handle(EvaluateSegments $evaluator): void
    {
        $campaign = $this->dispatch->campaign;

        if ($campaign === null || $campaign->emailList === null) {
            return;
        }

        $baseHtml = $campaign->structured_html ?: ($campaign->html ?? '');
        $sent = 0;

        $segment = $campaign->segment_id
            ? Segment::find($campaign->segment_id)
            : null;

        $sets = $segment ? $evaluator->prepareSets($segment) : [];

        $campaign->emailList
            ->subscribers()
            ->wherePivot('status', Status::SUBSCRIBED->value)
            ->lazyById()
            ->each(function (Subscriber $subscriber) use ($campaign, $baseHtml, $segment, $sets, $evaluator, &$sent): void {
                if ($segment && ! $evaluator->matches($segment, $subscriber, $sets)) {
                    return;
                }

                $send = new Send;
                $send->uuid = (string) Str::ulid();
                $send->subscriber_id = $subscriber->id;
                $send->sent_at = now();
                $send->sendable()->associate($this->dispatch);
                $send->save();

                $html = $this->prepareHtml(
                    $baseHtml,
                    $send,
                    (bool) $campaign->track_opens,
                    (bool) $campaign->track_clicks,
                );

                try {
                    Mail::html($html, function (Message $message) use ($campaign, $subscriber, $send): void {
                        $message->to($subscriber->email)
                            ->subject($campaign->subject ?: $campaign->name);

                        if ($campaign->from_email) {
                            $message->from($campaign->from_email, $campaign->from_name ?: null);
                        }

                        if ($campaign->reply_to_email) {
                            $message->replyTo($campaign->reply_to_email);
                        }

                        // Embed the Send UUID so Mailgun webhook events can look
                        // up this exact delivery record.
                        $message->getSymfonyMessage()->getHeaders()->addTextHeader(
                            'X-Mailgun-Variables',
                            (string) json_encode(['send_uuid' => $send->uuid]),
                        );
                    });

                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Campaign mail failed for subscriber', [
                        'send_uuid' => $send->uuid,
                        'subscriber_id' => $subscriber->id,
                        'error' => $e->getMessage(),
                    ]);

                    $send->forceFill(['failed_at' => now(), 'failure_reason' => $e->getMessage()])->save();
                }
            });

        $this->dispatch->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_to_count' => $sent,
        ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('SendCampaignDispatch failed permanently', [
            'dispatch_id' => $this->dispatch->id,
            'campaign_id' => $this->dispatch->campaign_id,
            'error' => $e->getMessage(),
        ]);

        $this->dispatch->update(['status' => 'failed']);
    }

    /**
     * Inject click tracking and an open pixel into a recipient's HTML.
     */
    private function prepareHtml(string $html, Send $send, bool $trackOpens, bool $trackClicks): string
    {
        // Replace the unsubscribe placeholder before click tracking so the URL
        // is not wrapped in a click-redirect URL.
        $html = str_replace(
            '[[[unsubscribe_url]]]',
            route('campaigns.track.unsubscribe', ['send' => $send->uuid]),
            $html,
        );

        if ($trackClicks) {
            $html = (string) preg_replace_callback(
                '/href="(https?:\/\/[^"]+)"/i',
                function (array $match) use ($send): string {
                    $url = route('campaigns.track.click', ['send' => $send->uuid, 'u' => $match[1]]);

                    return 'href="'.htmlspecialchars($url, ENT_QUOTES).'"';
                },
                $html,
            );
        }

        if ($trackOpens) {
            $pixel = '<img src="'.route('campaigns.track.open', ['send' => $send->uuid]).'" width="1" height="1" alt="" style="display:none" />';
            $html = str_contains($html, '</body>')
                ? str_replace('</body>', $pixel.'</body>', $html)
                : $html.$pixel;
        }

        return $html;
    }
}
