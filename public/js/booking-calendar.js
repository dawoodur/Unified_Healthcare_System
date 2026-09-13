// A small, dependency-free month calendar for picking a booking date. No
// build step, no npm package — just plain JavaScript, since this project
// doesn't use a JS framework (see README).
//
// How to use it (see patient/book-facility.blade.php and
// patient/book-appointment.blade.php for real examples):
//
//   renderBookingCalendar('some-div-id', availableDates, function (dateStr, info) {
//     // runs whenever the patient clicks a clickable date
//   });
//
// `availableDates` is an array like:
//   [{ date: '2026-07-14', already_booked: false }, ...]
// Any date NOT in this array (or before today) is shown greyed out and
// can't be clicked — that's what makes "unavailable" dates unselectable.
function renderBookingCalendar(containerId, availableDates, onSelect) {
  const container = document.getElementById(containerId);

  // Turn the array into a lookup object keyed by date string, so we can
  // instantly answer "is 2026-07-14 available?" while drawing each day
  // cell, instead of searching the whole array every time.
  const byDate = {};
  availableDates.forEach(function (d) { byDate[d.date] = d; });

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  // Start the calendar on the month of the earliest available date (or
  // today, if nothing's available at all) so the patient doesn't land on
  // an empty month and have to click "next" just to see anything.
  const sortedDates = Object.keys(byDate).sort();
  const firstAvailable = sortedDates.length ? new Date(sortedDates[0] + 'T00:00:00') : today;
  let viewYear = firstAvailable.getFullYear();
  let viewMonth = firstAvailable.getMonth();

  function toDateString(year, month, day) {
    // month/day can overflow past their normal range here (e.g. month -1,
    // day 32) — the Date constructor below normalizes that for us before
    // we format it, which is how the "previous/next month" filler days
    // get their correct real date.
    const d = new Date(year, month, day);
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
  }

  const todayString = toDateString(today.getFullYear(), today.getMonth(), today.getDate());

  // One shared cell renderer for the current month's real days AND the
  // greyed-out filler days that spill in from the previous/next month —
  // same rules decide whether each one is clickable, they're only styled
  // slightly fainter when they belong to a different month.
  function cellHtml(year, month, day, isOutsideMonth) {
    const dateStr = toDateString(year, month, day);
    const info = byDate[dateStr];
    const isPast = dateStr < todayString;

    let cls = 'cal-cell cal-day';
    if (isOutsideMonth) cls += ' cal-outside';
    if (dateStr === todayString) cls += ' cal-today';
    if (!info || isPast) {
      cls += ' cal-disabled';
    } else if (info.already_booked) {
      cls += ' cal-booked-by-you';
    } else {
      cls += ' cal-available';
    }

    const realDay = new Date(year, month, day).getDate();
    return '<div class="' + cls + '" data-date="' + dateStr + '"><span>' + realDay + '</span></div>';
  }

  function render() {
    const monthStart = new Date(viewYear, viewMonth, 1);
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const startWeekday = monthStart.getDay(); // 0 = Sunday
    const monthLabel = monthStart.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    // Fill the grid out to a full rectangle (always a multiple of 7 cells)
    // with real day numbers from the previous/next month, the way a
    // printed wall calendar shows "30 31" before day 1 instead of leaving
    // the corner blank.
    const totalCellsBeforeTrailing = startWeekday + daysInMonth;
    const totalRows = Math.ceil(totalCellsBeforeTrailing / 7);
    const trailingDays = (totalRows * 7) - totalCellsBeforeTrailing;

    let html = '<div class="cal-widget">';
    html += '<div class="cal-header">';
    html += '<button type="button" class="cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>';
    html += '<strong>' + monthLabel + '</strong>';
    html += '<button type="button" class="cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>';
    html += '</div>';

    html += '<div class="cal-grid">';

    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(function (label) {
      html += '<div class="cal-weekday">' + label + '</div>';
    });

    for (let i = 0; i < startWeekday; i++) {
      html += cellHtml(viewYear, viewMonth, i - startWeekday + 1, true);
    }
    for (let day = 1; day <= daysInMonth; day++) {
      html += cellHtml(viewYear, viewMonth, day, false);
    }
    for (let day = 1; day <= trailingDays; day++) {
      html += cellHtml(viewYear, viewMonth + 1, day, true);
    }

    html += '</div>'; // .cal-grid

    html += '<div class="cal-legend">';
    html += '<span><i class="cal-dot cal-dot-available"></i>Available</span>';
    html += '<span><i class="cal-dot cal-dot-booked"></i>Your booking</span>';
    html += '<span><i class="cal-dot cal-dot-disabled"></i>Unavailable</span>';
    html += '</div>';

    html += '</div>'; // .cal-widget

    container.innerHTML = html;

    container.querySelector('[data-nav="prev"]').addEventListener('click', function () {
      viewMonth--;
      if (viewMonth < 0) { viewMonth = 11; viewYear--; }
      render();
    });
    container.querySelector('[data-nav="next"]').addEventListener('click', function () {
      viewMonth++;
      if (viewMonth > 11) { viewMonth = 0; viewYear++; }
      render();
    });

    container.querySelectorAll('.cal-available, .cal-booked-by-you').forEach(function (cell) {
      cell.addEventListener('click', function () {
        container.querySelectorAll('.cal-day').forEach(function (c) { c.classList.remove('cal-selected'); });
        cell.classList.add('cal-selected');
        onSelect(cell.dataset.date, byDate[cell.dataset.date]);
      });
    });
  }

  render();
}
