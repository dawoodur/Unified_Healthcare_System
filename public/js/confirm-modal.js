// Shared in-page confirmation dialog.
// Keeps the existing showConfirmModal(message, onConfirm) API and all
// data-confirm behavior, but presents every confirmation using the same
// Telemedicine visual language.

function showConfirmModal(message, onConfirm) {
  const locale = (document.documentElement.lang || 'en').toLowerCase();
  const isBangla = locale.startsWith('bn');

  const labels = isBangla
    ? {
        eyebrow: 'নিশ্চিতকরণ',
        title: 'কাজটি নিশ্চিত করুন',
        cancel: 'বাতিল',
        confirm: 'নিশ্চিত করুন',
        close: 'বন্ধ করুন'
      }
    : {
        eyebrow: 'Confirmation',
        title: 'Confirm this action',
        cancel: 'Cancel',
        confirm: 'Confirm',
        close: 'Close'
      };

  const previouslyFocused = document.activeElement;

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';

  const box = document.createElement('section');
  box.className = 'modal-box patient-confirm-modal';
  box.setAttribute('role', 'dialog');
  box.setAttribute('aria-modal', 'true');
  box.setAttribute('aria-labelledby', 'patient-confirm-modal-title');
  box.setAttribute('aria-describedby', 'patient-confirm-modal-message');

  const header = document.createElement('header');
  header.className = 'patient-confirm-modal-header';

  const heading = document.createElement('div');
  heading.className = 'patient-confirm-modal-heading';

  const icon = document.createElement('span');
  icon.className = 'patient-confirm-modal-icon';
  icon.setAttribute('aria-hidden', 'true');

  const iconGlyph = document.createElement('i');
  iconGlyph.className = 'bi bi-shield-check';
  icon.appendChild(iconGlyph);

  const headingCopy = document.createElement('div');

  const eyebrow = document.createElement('small');
  eyebrow.textContent = labels.eyebrow;

  const title = document.createElement('h2');
  title.id = 'patient-confirm-modal-title';
  title.textContent = labels.title;

  headingCopy.appendChild(eyebrow);
  headingCopy.appendChild(title);

  heading.appendChild(icon);
  heading.appendChild(headingCopy);

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'patient-confirm-modal-close';
  closeBtn.setAttribute('aria-label', labels.close);

  const closeIcon = document.createElement('i');
  closeIcon.className = 'bi bi-x-lg';
  closeIcon.setAttribute('aria-hidden', 'true');
  closeBtn.appendChild(closeIcon);

  header.appendChild(heading);
  header.appendChild(closeBtn);

  const body = document.createElement('div');
  body.className = 'patient-confirm-modal-body';

  const text = document.createElement('p');
  text.id = 'patient-confirm-modal-message';
  text.textContent = message;
  body.appendChild(text);

  const actions = document.createElement('div');
  actions.className = 'modal-actions patient-confirm-modal-actions';

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.className = 'patient-confirm-modal-cancel';
  cancelBtn.textContent = labels.cancel;

  const confirmBtn = document.createElement('button');
  confirmBtn.type = 'button';
  confirmBtn.className = 'patient-confirm-modal-confirm';

  const confirmIcon = document.createElement('i');
  confirmIcon.className = 'bi bi-check2';
  confirmIcon.setAttribute('aria-hidden', 'true');

  const confirmText = document.createElement('span');
  confirmText.textContent = labels.confirm;

  confirmBtn.appendChild(confirmIcon);
  confirmBtn.appendChild(confirmText);

  actions.appendChild(cancelBtn);
  actions.appendChild(confirmBtn);

  box.appendChild(header);
  box.appendChild(body);
  box.appendChild(actions);
  overlay.appendChild(box);
  document.body.appendChild(overlay);

  function close() {
    overlay.remove();
    document.removeEventListener('keydown', onKeydown);

    if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
      previouslyFocused.focus();
    }
  }

  function onKeydown(event) {
    if (event.key === 'Escape') {
      close();
      return;
    }

    if (event.key === 'Tab') {
      const focusable = [closeBtn, cancelBtn, confirmBtn];
      const currentIndex = focusable.indexOf(document.activeElement);

      if (event.shiftKey && currentIndex <= 0) {
        event.preventDefault();
        confirmBtn.focus();
      } else if (!event.shiftKey && currentIndex === focusable.length - 1) {
        event.preventDefault();
        closeBtn.focus();
      }
    }
  }

  document.addEventListener('keydown', onKeydown);

  overlay.addEventListener('click', function (event) {
    if (event.target === overlay) close();
  });

  closeBtn.addEventListener('click', close);
  cancelBtn.addEventListener('click', close);

  confirmBtn.addEventListener('click', function () {
    close();
    onConfirm();
  });

  confirmBtn.focus();
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.confirmed === 'true') {
        return;
      }

      event.preventDefault();

      showConfirmModal(form.dataset.confirm, function () {
        form.dataset.confirmed = 'true';
        form.requestSubmit ? form.requestSubmit() : form.submit();
      });
    });
  });

  document.querySelectorAll('button[data-confirm]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();

      showConfirmModal(button.dataset.confirm, function () {
        const form = button.form;
        if (!form) return;

        if (form.requestSubmit) {
          form.requestSubmit(button);
        } else {
          form.submit();
        }
      });
    });
  });
});
