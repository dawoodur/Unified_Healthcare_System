@extends('layouts.app')
@section('title', 'Health Chat Training')
@section('content')
<div class="card">
  <h1>Health Chat Training</h1>
  <p class="muted">
    Messages patients typed into the health assistant. Label the ones it was unsure about, then retrain so the
    model learns real wording. Patient names are not shown here — only the message text.
  </p>

  <div class="row row-cols-2 row-cols-lg-4 g-3 mb-1">
    @foreach (['pending' => 'Waiting for review', 'labelled' => 'Labelled', 'auto' => 'Answered confidently', 'discarded' => 'Discarded'] as $key => $caption)
      <div class="col">
        <div class="card stat-card-sm">
          <div class="stat-card-sm-icon {{ $key === 'pending' ? 'bg-warning-subtle text-warning' : ($key === 'labelled' ? 'bg-success-subtle text-success' : 'icon-tint-brand') }}">
            <i class="bi {{ $key === 'pending' ? 'bi-hourglass-split' : ($key === 'labelled' ? 'bi-check2-circle' : ($key === 'auto' ? 'bi-robot' : 'bi-trash')) }}"></i>
          </div>
          <div>
            <div class="muted small">{{ $caption }}</div>
            <div class="stat-card-sm-value">{{ $counts[$key]->total ?? 0 }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <p class="mb-0">
    @foreach (['pending' => 'To review', 'labelled' => 'Labelled', 'auto' => 'Confident', 'discarded' => 'Discarded'] as $key => $label)
      <a href="{{ route('admin.chat-training', ['status' => $key]) }}"
         class="btn {{ $status === $key ? '' : 'btn-secondary' }}" style="padding:0.3rem 0.8rem;">{{ $label }}</a>
    @endforeach
  </p>
</div>

<div class="card">
  <h2>{{ ucfirst($status) }} messages</h2>

  @if ($samples->isEmpty())
    <p class="muted">Nothing here right now.</p>
  @else
    <div class="table-responsive">
      <table>
        <thead>
          <tr><th>Message</th><th>Lang</th><th>Assistant answered</th><th>Times asked</th><th style="min-width:260px;">Correct topic</th></tr>
        </thead>
        <tbody>
          @foreach ($samples as $sample)
            <tr>
              <td>{{ $sample->message }}</td>
              <td><span class="badge">{{ strtoupper($sample->lang) }}</span></td>
              <td>
                @if ($sample->predicted_slug)
                  {{ $sample->predicted_slug }}
                  <div class="muted" style="font-size:0.8rem;">
                    {{ $sample->engine === 'model' ? 'model' : 'keyword rules' }}@if ($sample->confidence), {{ round($sample->confidence * 100) }}% sure @endif
                  </div>
                @else
                  <span class="muted">not understood</span>
                @endif
                @if ($sample->status === 'labelled')
                  <div class="muted" style="font-size:0.8rem;">labelled: <strong>{{ $sample->label_slug }}</strong></div>
                @endif
              </td>
              <td>{{ $sample->hits }}</td>
              <td>
                @if (in_array($sample->status, ['pending', 'auto'], true))
                  <form method="POST" action="{{ route('admin.chat-training.label', $sample) }}" style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                    @csrf
                    <select name="label_slug" required style="flex:1;min-width:170px;">
                      <option value="">— pick the correct topic —</option>
                      @foreach ($guides as $guide)
                        <option value="{{ $guide->slug }}" @selected($sample->predicted_slug === $guide->slug)>
                          {{ $guide->title_en }}@if ($guide->is_emergency) (emergency) @endif
                        </option>
                      @endforeach
                    </select>
                    <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Save</button>
                  </form>
                  <form method="POST" action="{{ route('admin.chat-training.discard', $sample) }}" data-confirm="Discard this message so it is never used for training?">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding:0.2rem 0.6rem;margin-top:0.35rem;">Discard</button>
                  </form>
                @else
                  <span class="muted">reviewed {{ $sample->reviewed_at?->format('M j, Y') }}</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $samples->links() }}</div>
  @endif
</div>

<div class="card">
  <h2>Retrain after labelling</h2>
  <p class="muted">Run these two commands in the project folder, then copy <code>storage/app/ml/first_aid_model.json</code> to the served copy:</p>
  <pre style="white-space:pre-wrap;margin:0;">php artisan firstaid:dataset
./.venv-ml/Scripts/python.exe database/ml/train_first_aid.py</pre>
</div>
@endsection
