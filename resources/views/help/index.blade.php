@extends('layouts.app')
@section('title', __('patient.help_center.page_title'))
@section('content')
@if (auth()->user()?->role === 'patient')
@php
  $categoryIcons = [
    'Appointments' => 'bi-calendar2-check',
    'Account' => 'bi-person-circle',
    'Payments' => 'bi-wallet2',
    'Rewards' => 'bi-stars',
    'Operations' => 'bi-bandaid',
    'Blood Donation' => 'bi-droplet',
    'Messaging' => 'bi-chat-square-text',
    'Orders' => 'bi-bag-check',
    'Reviews' => 'bi-star',
    'Medical Records' => 'bi-clipboard2-pulse',
    'Settings' => 'bi-gear',
  ];

  $translatedCategory = function ($category) {
      $category = $category ?: 'General';
      $slug = \Illuminate\Support\Str::of($category)->lower()->replace([' ', '-'], '_')->toString();
      $key = 'patient.help_center.categories.' . $slug;
      $translated = __($key);
      return $translated === $key ? $category : $translated;
  };

  $translatedFaq = function ($faq, $field) {
      $key = 'patient.help_center.faqs.' . $faq->faq_id . '.' . $field;
      $translated = __($key);
      return $translated === $key ? $faq->{$field} : $translated;
  };
@endphp

<div class="patient-help-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.help_center.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-help-hero" aria-labelledby="patient-help-title">
    <div class="patient-help-hero-grid">
      <div class="patient-help-hero-copy">
        <div class="patient-help-breadcrumb">
          <a href="{{ route('patient.dashboard') }}">{{ __('patient.help_center.home') }}</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span>{{ __('patient.help_center.title') }}</span>
        </div>
        <h1 id="patient-help-title">{{ __('patient.help_center.title') }}</h1>
        <p>{{ __('patient.help_center.hero_desc') }}</p>

        <form class="patient-help-search" method="POST" action="{{ route('help.search') }}">
          @csrf
          <label class="visually-hidden" for="help-question">{{ __('patient.help_center.search_label') }}</label>
          <span class="patient-help-search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
          <input
            id="help-question"
            type="search"
            name="question"
            value="{{ old('question', $question) }}"
            maxlength="500"
            placeholder="{{ __('patient.help_center.search_placeholder') }}"
            required
          >
          <button type="submit">{{ __('patient.help_center.search_button') }}</button>
        </form>
      </div>

      <div class="patient-help-hero-note" aria-hidden="true">
        <span class="patient-help-hero-note-icon"><i class="bi bi-chat-square-text"></i></span>
        <div>
          <strong>{{ __('patient.help_center.hero_note_title') }}</strong>
          <p>{{ __('patient.help_center.hero_note_desc') }}</p>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-help-layout">
    <aside class="patient-help-topics" aria-label="{{ __('patient.help_center.topics') }}">
      <a class="active" href="#help-faq-list">
        <i class="bi bi-grid" aria-hidden="true"></i>
        <span>{{ __('patient.help_center.all_topics') }}</span>
        <em>{{ $faqCount ?? $allFaqs->flatten()->count() }}</em>
      </a>
      @foreach ($allFaqs as $category => $faqs)
        <a href="#faq-category-{{ \Illuminate\Support\Str::slug($category ?: 'general') }}">
          <i class="bi {{ $categoryIcons[$category] ?? 'bi-question-circle' }}" aria-hidden="true"></i>
          <span>{{ $translatedCategory($category) }}</span>
          <em>{{ $faqs->count() }}</em>
        </a>
      @endforeach
    </aside>

    <main class="patient-help-faq-card" id="help-faq-list">
      @if ($question !== null)
        <section class="patient-help-search-result {{ $answer ? 'has-match' : 'no-match' }}" aria-live="polite">
          <span class="patient-help-search-result-icon">
            <i class="bi {{ $answer ? 'bi-check-circle' : 'bi-search' }}" aria-hidden="true"></i>
          </span>
          <div>
            <small>{{ __('patient.help_center.search_result_for', ['query' => $question]) }}</small>
            @if ($answer)
              <strong>{{ $translatedFaq($answer, 'question') }}</strong>
              <p>{{ $translatedFaq($answer, 'answer') }}</p>
            @else
              <strong>{{ __('patient.help_center.no_match_title') }}</strong>
              <p>{{ __('patient.help_center.no_match_desc') }}</p>
            @endif
          </div>
        </section>
      @endif

      <header class="patient-help-faq-heading">
        <div>
          <h2>{{ __('patient.help_center.faq_heading') }}</h2>
          <p>{{ __('patient.help_center.faq_subtitle') }}</p>
        </div>
        <span>{{ trans_choice('patient.help_center.showing_articles', $faqCount ?? $allFaqs->flatten()->count(), ['count' => $faqCount ?? $allFaqs->flatten()->count()]) }}</span>
      </header>

      <div class="patient-help-faq-groups">
        @foreach ($allFaqs as $category => $faqs)
          <section class="patient-help-faq-group" id="faq-category-{{ \Illuminate\Support\Str::slug($category ?: 'general') }}">
            <div class="patient-help-faq-group-title">
              <span><i class="bi {{ $categoryIcons[$category] ?? 'bi-question-circle' }}" aria-hidden="true"></i></span>
              <h3>{{ $translatedCategory($category) }}</h3>
            </div>

            @foreach ($faqs as $faq)
              <details class="patient-help-faq-item" {{ $answer && $answer->faq_id === $faq->faq_id ? 'open' : '' }}>
                <summary>
                  <span>{{ $translatedFaq($faq, 'question') }}</span>
                  <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="patient-help-faq-answer">
                  <p>{{ $translatedFaq($faq, 'answer') }}</p>
                </div>
              </details>
            @endforeach
          </section>
        @endforeach
      </div>
    </main>

    <aside class="patient-help-sidebar">
      <section class="patient-help-side-card patient-help-side-card-primary">
        <span class="patient-help-side-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.help_center.still_need_help') }}</h2>
          <p>{{ __('patient.help_center.still_need_help_desc') }}</p>
        </div>
        <a href="{{ route('report.index') }}">{{ __('patient.help_center.report_issue') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <a class="patient-help-side-secondary" href="{{ route('inbox.index') }}">{{ __('patient.help_center.open_inbox') }}</a>
      </section>

      <section class="patient-help-side-card patient-help-quick-links">
        <div class="patient-help-side-heading">
          <span class="patient-help-side-icon"><i class="bi bi-link-45deg" aria-hidden="true"></i></span>
          <h2>{{ __('patient.help_center.quick_links') }}</h2>
        </div>
        <nav aria-label="{{ __('patient.help_center.quick_links') }}">
          <a href="{{ route('report.index') }}"><span><i class="bi bi-exclamation-circle"></i>{{ __('patient.help_center.report_issue') }}</span><i class="bi bi-arrow-right"></i></a>
          <a href="{{ route('patient.appointments') }}"><span><i class="bi bi-calendar2-check"></i>{{ __('patient.help_center.my_appointments') }}</span><i class="bi bi-arrow-right"></i></a>
          <a href="{{ route('patient.prescriptions') }}"><span><i class="bi bi-file-earmark-medical"></i>{{ __('patient.help_center.my_prescriptions') }}</span><i class="bi bi-arrow-right"></i></a>
          <a href="{{ route('patient.facility-bookings') }}"><span><i class="bi bi-hospital"></i>{{ __('patient.help_center.my_facility_bookings') }}</span><i class="bi bi-arrow-right"></i></a>
          <a href="{{ route('patient.operations') }}"><span><i class="bi bi-bandaid"></i>{{ __('patient.help_center.my_surgery_requests') }}</span><i class="bi bi-arrow-right"></i></a>
          <a href="{{ route('patient.records') }}"><span><i class="bi bi-clipboard2"></i>{{ __('patient.help_center.my_records') }}</span><i class="bi bi-arrow-right"></i></a>
        </nav>
      </section>

      <section class="patient-help-info-card">
        <div class="patient-help-info-heading">
          <span><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
          <h2>{{ __('patient.help_center.how_search_works') }}</h2>
        </div>
        <p>{{ __('patient.help_center.how_search_works_desc') }}</p>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-help-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@else
<div class="card">
  <h1>Help</h1>
  <p class="muted">Type a question in plain words and this will try to find the closest answer. This is a simple keyword-matching tool, not a live agent — for anything it can't answer, use your Inbox or "Report an issue".</p>

  <form method="POST" action="{{ route('help.search') }}">
    @csrf
    <div class="field">
      <label for="question">Your question</label>
      <input type="text" id="question" name="question" value="{{ $question }}" maxlength="500" placeholder="e.g. How do I cancel an appointment?" required autofocus>
    </div>
    <button type="submit" class="btn">Ask</button>
  </form>
</div>

@if ($question !== null)
  <div class="card">
    @if ($answer)
      <h2>{{ $answer->question }}</h2>
      <p>{{ $answer->answer }}</p>
    @else
      <p class="muted">Nothing matched that question — try different or more specific words, or browse the full list below.</p>
    @endif
  </div>
@endif

<div class="card">
  <h2>Browse All FAQs</h2>
  @foreach ($allFaqs as $category => $faqs)
    <h3 style="margin-bottom:0.4rem;">{{ $category ?? 'General' }}</h3>
    @foreach ($faqs as $faq)
      <details style="margin-bottom:0.5rem;">
        <summary style="cursor:pointer;font-weight:600;">{{ $faq->question }}</summary>
        <p class="muted" style="margin-top:0.4rem;">{{ $faq->answer }}</p>
      </details>
    @endforeach
  @endforeach
</div>
@endif
@endsection
