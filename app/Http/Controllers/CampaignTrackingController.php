<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SendFeedbackType;
use App\Enums\Status;
use App\Models\CampaignDispatch;
use App\Models\Send;
use App\Models\SendFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CampaignTrackingController extends Controller
{
    /**
     * A 1×1 transparent GIF.
     */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * Record an open and return the tracking pixel.
     */
    public function open(Request $request, Send $send): Response
    {
        $this->record($send, SendFeedbackType::OPEN, $request);

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Record a click and redirect to the original URL.
     */
    public function click(Request $request, Send $send): RedirectResponse
    {
        $url = (string) $request->query('u');

        abort_unless(str_starts_with($url, 'http://') || str_starts_with($url, 'https://'), 404);

        $this->record($send, SendFeedbackType::CLICK, $request, $url);

        return redirect()->away($url);
    }

    /**
     * Unsubscribe the recipient from the list and show a confirmation page.
     */
    public function unsubscribe(Send $send): Response
    {
        $subscriber = $send->subscriber;
        $dispatch = $send->sendable;
        $list = $dispatch instanceof CampaignDispatch
            ? $dispatch->campaign?->emailList
            : null;

        if ($subscriber && $list && $send->unsubscribed_at === null) {
            $list->subscribers()->updateExistingPivot($subscriber->id, [
                'status' => Status::UNSUBSCRIBED->value,
                'unsubscribed_at' => now(),
            ]);

            $send->forceFill(['unsubscribed_at' => now()])->save();

            if ($dispatch instanceof CampaignDispatch) {
                $dispatch->increment('unsubscribe_count');
            }
        }

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Unsubscribed</title>
<style>
  body{margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
  .card{background:#fff;border-radius:12px;padding:48px 40px;max-width:480px;width:100%;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.08);}
  h1{margin:0 0 12px;font-size:22px;color:#0f172a;}
  p{margin:0;font-size:15px;color:#64748b;line-height:1.6;}
</style>
</head>
<body>
<div class="card">
  <h1>You've been unsubscribed</h1>
  <p>You will no longer receive emails from this list. If this was a mistake, please contact the sender directly.</p>
</div>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Persist the event, set the first-event timestamp, and roll up the
     * dispatch's counters.
     */
    private function record(Send $send, SendFeedbackType $type, Request $request, ?string $url = null): void
    {
        $column = $type === SendFeedbackType::OPEN ? 'opened_at' : 'clicked_at';
        $isFirst = $send->{$column} === null;

        if ($isFirst) {
            $send->forceFill([$column => now()])->save();
        }

        $dispatch = $send->sendable;

        if ($dispatch instanceof CampaignDispatch) {
            if ($type === SendFeedbackType::OPEN) {
                $dispatch->increment('open_count');
                $isFirst && $dispatch->increment('unique_open_count');
            } else {
                $dispatch->increment('click_count');
                $isFirst && $dispatch->increment('unique_click_count');
            }
        }

        SendFeedback::create([
            'send_id' => $send->id,
            'type' => $type->value,
            'url' => $url,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'happened_at' => now(),
        ]);
    }
}
