import React from 'react';

function pad(n) {
  return String(n).padStart(2, '0');
}

function ymd(year, month, day) {
  return `${year}-${pad(month + 1)}-${pad(day)}`;
}

export default function MiniCalendar({
  busyDates,
  selectedDate = null,
  onSelectDate = null,
}) {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const todayStr = ymd(year, month, now.getDate());
  const busy = new Set(busyDates || []);

  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const leadingBlanks = new Date(year, month, 1).getDay();
  const monthLabel = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  const cells = [
    ...Array.from({ length: leadingBlanks }, (_, index) => (
      <span
        key={`blank-${index}`}
        className="cal-cell cal-empty"
        aria-hidden="true"
      />
    )),
  ];

  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = ymd(year, month, day);
    const isToday = dateStr === todayStr;
    const isBusy = busy.has(dateStr);
    const isSelected = dateStr === selectedDate;

    cells.push(
      <button
        key={day}
        type="button"
        className={[
          'cal-cell',
          'cal-day',
          'cal-day-button',
          isToday ? 'cal-today' : '',
          isBusy ? 'cal-available' : '',
          isSelected ? 'cal-selected' : '',
        ].filter(Boolean).join(' ')}
        onClick={() => onSelectDate?.(dateStr)}
        aria-pressed={isSelected}
        aria-label={`${monthLabel} ${day}${isBusy ? ', has appointments' : ', no appointments'}`}
      >
        <span>{day}</span>
        {isBusy && <i className="cal-busy-dot" aria-hidden="true"></i>}
      </button>
    );
  }

  return (
    <div className="cal-widget mini-cal">
      <div className="cal-header">
        <strong>{monthLabel}</strong>
      </div>

      <div className="cal-grid">
        {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map((d) => (
          <div key={d} className="cal-weekday">{d}</div>
        ))}
        {cells}
      </div>
    </div>
  );
}
