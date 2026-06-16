<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Campaign Reminder: {{ $campaign->name }}</title>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
  body{background:#f4f4f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1a1a1a;padding:40px 16px;}
  .wrap{max-width:560px;margin:0 auto;}
  .card{background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e5e5e2;}
  .header{background:#1a1a1a;padding:28px 32px;}
  .header h1{color:#fff;font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;}
  .body{padding:32px;}
  .eyebrow{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#888;margin-bottom:8px;}
  .campaign-name{font-size:22px;font-weight:700;color:#1a1a1a;margin-bottom:24px;line-height:1.3;}
  .meta{border:1px solid #e5e5e2;border-radius:6px;padding:16px 20px;margin-bottom:24px;}
  .meta-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0ed;font-size:14px;}
  .meta-row:last-child{border-bottom:none;padding-bottom:0;}
  .meta-row:first-child{padding-top:0;}
  .meta-label{color:#666;font-weight:500;}
  .meta-value{color:#1a1a1a;font-weight:600;text-align:right;}
  .message{font-size:14px;color:#555;line-height:1.6;margin-bottom:24px;}
  .btn{display:inline-block;background:#1a1a1a;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:14px;font-weight:600;margin-right:8px;margin-bottom:8px;}
  .footer{padding:20px 32px;border-top:1px solid #f0f0ed;font-size:12px;color:#aaa;}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="header">
      <h1>Campaign Reminder</h1>
    </div>
    <div class="body">
      <div class="eyebrow">Scheduled for tomorrow</div>
      <div class="campaign-name">{{ $campaign->name }}</div>
      <div class="meta">
        <div class="meta-row">
          <span class="meta-label">Subject</span>
          <span class="meta-value">{{ $campaign->subject ?: '(no subject)' }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">List</span>
          <span class="meta-value">{{ $campaign->emailList?->name ?? '—' }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">Frequency</span>
          <span class="meta-value">{{ $campaign->frequency->label() }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">Scheduled at</span>
          <span class="meta-value">{{ $campaign->next_run_at->format('M j, Y \a\t g:i A T') }}</span>
        </div>
      </div>
      <p class="message">
        This campaign will send automatically tomorrow. If you no longer need this send, open the campaign and pause or cancel it before it goes out.
      </p>
      <a href="{{ $editUrl }}" class="btn">Open Campaign</a>
    </div>
    <div class="footer">
      You received this because you are an administrator of {{ config('app.name') }}.
    </div>
  </div>
</div>
</body>
</html>
