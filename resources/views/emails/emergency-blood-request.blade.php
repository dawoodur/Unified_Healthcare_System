<p>Hello {{ $recipientName }},</p>
<p><strong>{{ $hospitalName }}</strong> has an urgent need for <strong>{{ $bloodGroup }}</strong> blood, and our records show you're an eligible donor of that type.</p>
@if ($requestMessage)
  <p>{{ $requestMessage }}</p>
@endif
<p>If you're able to donate, please contact or visit {{ $hospitalName }} as soon as you can. Thank you for considering it — it can make a real difference.</p>
<p style="color:#64748b;font-size:0.85rem;">You're receiving this because your registered blood group matches this request and you haven't logged a donation in the last 3 months. You can log a donation any time from your dashboard.</p>
