@extends('errors::minimal')

@section('title', __('Interner Serverfehler'))
@section('code', '500')
@section('message')
    @php($reference = request()->attributes->get('error_reference'))

    <div class="error-card">
        <img class="error-logo" src="{{ asset('img/logo/pfarrplaner.svg') }}" alt="Pfarrplaner">
        <div class="error-eyebrow">Da ist etwas schiefgelaufen</div>
        <h1>Wir kümmern uns darum.</h1>
        <p class="error-intro">
            Pfarrplaner konnte diese Seite gerade nicht laden. Bitte versuche es noch einmal
            oder sende uns einen kurzen Hinweis – damit hilfst du uns sehr.
        </p>

        @if ($reference)
            <div class="error-reference">
                <span>Fehlerreferenz</span>
                <code id="error-reference">{{ $reference }}</code>
                <button type="button" class="copy-button" onclick="navigator.clipboard?.writeText(document.getElementById('error-reference').textContent)">
                    Kopieren
                </button>
            </div>
        @endif

        @if (session('feedback_success'))
            <div class="feedback-message success" role="status">Danke! Dein Hinweis wurde übermittelt.</div>
        @elseif (session('feedback_error'))
            <div class="feedback-message warning" role="status">Der Hinweis konnte gerade nicht automatisch übermittelt werden. Bitte notiere die Fehlerreferenz und wende dich an den Support.</div>
        @elseif (preg_match('/^[0-9a-f]{32}$/i', (string) $reference))
            <form class="feedback-form" method="POST" action="{{ route('errors.feedback') }}">
                @csrf
                <input type="hidden" name="event_id" value="{{ preg_match('/^[0-9a-f]{32}$/i', (string) $reference) ? $reference : '' }}">
                <label for="comments">Was ist passiert?</label>
                <textarea id="comments" name="comments" rows="4" maxlength="5000" required placeholder="Zum Beispiel: Ich habe gerade den Gottesdienstkalender geöffnet …"></textarea>
                <div class="feedback-row">
                    <div>
                        <label for="name">Dein Name <span>(optional)</span></label>
                        <input id="name" name="name" maxlength="120" type="text">
                    </div>
                    <div>
                        <label for="email">E-Mail für Rückfragen <span>(optional)</span></label>
                        <input id="email" name="email" maxlength="255" type="email">
                    </div>
                </div>
                @error('comments')<div class="field-error">{{ $message }}</div>@enderror
                <button class="submit-button" type="submit">Hinweis senden</button>
            </form>
        @else
            <div class="feedback-message warning" role="status">Bitte notiere die Fehlerreferenz und wende dich an den Support. Eine automatische Zuordnung war gerade nicht möglich.</div>
        @endif

        <a class="back-link" href="{{ url('/') }}">Zurück zu Pfarrplaner</a>
    </div>
@endsection
@section('button')
    <span class="error-code-label">Fehler 500</span>
@endsection
