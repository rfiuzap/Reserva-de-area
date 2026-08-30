(() => {
    const calendar = document.querySelector('.calendar');
    if (!calendar) return;

    const events = [...calendar.querySelectorAll('.calendar-event')];
    const ids = events.map((event) => new URL(event.href).searchParams.get('id')).filter(Boolean);
    if (!ids.length) return;

    const payload = new FormData();
    ids.forEach((id) => payload.append('ids[]', id));
    fetch('api_reservas_dia_inteiro.php', { method: 'POST', body: payload })
        .then((response) => response.json())
        .then((allDayReservations) => {
            const allDayIds = new Set(allDayReservations.map((reservation) => String(reservation.id)));
            events.forEach((event) => {
                const id = new URL(event.href).searchParams.get('id');
                if (!allDayIds.has(id)) return;
                event.classList.add('calendar-event-all-day');
                event.title = `${event.title} - Dia todo`;
                const sourceCell = event.parentElement;
                const cells = [...calendar.children];
                const column = cells.indexOf(sourceCell) % 8;
                for (let cellIndex = 8 + column; cellIndex < cells.length; cellIndex += 8) {
                    if (cells[cellIndex] === sourceCell) continue;
                    const fill = document.createElement('span');
                    fill.className = 'calendar-all-day-fill';
                    fill.style.backgroundColor = event.style.backgroundColor;
                    cells[cellIndex].prepend(fill);
                }
            });
        })
        .catch(() => {});
})();
