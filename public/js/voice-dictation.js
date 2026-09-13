// Voice-to-text dictation for any textarea, via the browser's built-in
// Web Speech API — no server/API call involved, so it only works in
// browsers that support it (Chrome/Edge; not Firefox/Safari as of
// writing). Wire it up by giving a button class="voice-dictate-btn" and
// data-target="<textarea id>". Buttons are auto-hidden if the browser
// doesn't support speech recognition at all, rather than showing
// something that just won't work when clicked.
document.addEventListener('DOMContentLoaded', function () {
  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  var buttons = document.querySelectorAll('.voice-dictate-btn');
  if (buttons.length === 0) return;

  if (!SpeechRecognition) {
    buttons.forEach(function (btn) { btn.style.display = 'none'; });
    return;
  }

  buttons.forEach(function (btn) {
    var targetId = btn.dataset.target;
    var textarea = document.getElementById(targetId);
    if (!textarea) return;

    var recognition = new SpeechRecognition();
    recognition.lang = btn.dataset.lang || 'en-US';
    recognition.continuous = true;
    recognition.interimResults = false;

    var listening = false;
    // Whatever was already in the textarea before this recording session
    // started — new speech is appended after it, never overwriting notes
    // the doctor already typed.
    var baseText = '';

    function setListening(state) {
      listening = state;
      btn.textContent = state ? '⏺ Listening… (click to stop)' : '🎤 Dictate';
      btn.classList.toggle('btn-danger', state);
    }

    recognition.addEventListener('result', function (event) {
      var transcript = '';
      for (var i = 0; i < event.results.length; i++) {
        transcript += event.results[i][0].transcript;
      }
      textarea.value = (baseText ? baseText + ' ' : '') + transcript.trim();
    });

    recognition.addEventListener('end', function () {
      // Browsers auto-stop a recognition session after a period of
      // silence — if the doctor never clicked "stop" themselves, just
      // reset the button state rather than leaving it stuck on "Listening".
      setListening(false);
    });

    recognition.addEventListener('error', function () {
      setListening(false);
    });

    btn.addEventListener('click', function () {
      if (listening) {
        recognition.stop();
        setListening(false);
        return;
      }

      baseText = textarea.value.trim();
      recognition.start();
      setListening(true);
    });
  });
});
