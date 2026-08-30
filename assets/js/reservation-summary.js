(() => {
    const summary = document.querySelector('#summary');
    const group = document.querySelector('[name="grupo_id"]');
    const subgroup = document.querySelector('#subgroup');
    const area = document.querySelector('[name="area_id"]');
    const date = document.querySelector('[name="data"]');
    const time = document.querySelector('[name="hora_inicio"]');
    const endTime = document.querySelector('[name="hora_fim"]');
    const recurrence = document.querySelector('[name="recorrencia"]');
    const allDay = document.querySelector('[name="dia_inteiro"]');

    if (!summary || !group || !subgroup || !area || !date || !time) {
        return;
    }

    fetch('api_config_agenda.php')
        .then((response) => response.json())
        .then(() => {
            updateSummary();
        })
        .catch(() => {});

    summary.className = 'reservation-summary';
    summary.innerHTML = '<span class="reservation-summary-label">Resumo</span><div class="reservation-summary-area"><img class="reservation-summary-image d-none" alt=""><span class="reservation-summary-placeholder">Área</span><strong class="reservation-summary-area-name">Selecione a área</strong></div><div class="reservation-summary-details"><h2 class="reservation-summary-group">Selecione o grupo</h2><p class="reservation-summary-subgroup">Selecione o subgrupo</p><div class="reservation-summary-meta"><span class="summary-date">Data não definida</span><span class="summary-time">Horário não definido</span><span class="summary-duration">Duração não definida</span><span class="summary-recurrence">Não recorrente</span></div></div>';

    const selectText = (select, fallback) => select.selectedIndex > 0 ? select.selectedOptions[0].text : fallback;
    const subgroupNames = (container, fallback) => {
        const checked = Array.from(container.querySelectorAll('input[type="checkbox"]:checked'));
        return checked.length ? checked.map((input) => input.closest('label')?.textContent.trim() ?? input.value).join(', ') : fallback;
    };
    let requestNumber = 0;

    async function updateSummary() {
        summary.classList.toggle('d-none', !group.value);
        if (!group.value) {
            return;
        }

        summary.querySelector('.reservation-summary-group').textContent = selectText(group, 'Selecione o grupo');
        summary.querySelector('.reservation-summary-subgroup').textContent = subgroupNames(subgroup, 'Selecione o subgrupo');
        const formattedDate = date.value ? new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: '2-digit', month: '2-digit', year: '2-digit' }).format(new Date(`${date.value}T12:00:00`)).replace(',', '') : 'Data não definida';
        const displayDate = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
        const [weekday, ...dateParts] = displayDate.split(' ');
        summary.querySelector('.summary-date').innerHTML = `${weekday}: <strong>${dateParts.join(' ')}</strong>`;
        const endLabel = endTime && endTime.value && !(allDay && allDay.checked) ? ` · Término: <strong>${endTime.value}</strong>` : '';
        summary.querySelector('.summary-time').innerHTML = time.value ? `Início: <strong>${time.value}</strong>${endLabel}` : 'Horário não definido';
        summary.querySelector('.summary-recurrence').textContent = recurrence && recurrence.value !== 'nenhuma' ? `Recorrência: ${recurrence.value === 'semanal' ? 'Semanal' : 'Mensal'}` : 'Não recorrente';
        if (allDay && allDay.checked) {
            summary.querySelector('.summary-duration').innerHTML = 'Duração: <strong>dia todo</strong>';
        } else if (time.value && endTime && endTime.value) {
            const minutes = (new Date(`2000-01-01T${endTime.value}`) - new Date(`2000-01-01T${time.value}`)) / 60000;
            summary.querySelector('.summary-duration').innerHTML = minutes > 0 ? `Duração: <strong>${minutes} min</strong>` : 'Duração não definida';
        } else {
            summary.querySelector('.summary-duration').innerHTML = 'Duração não definida';
        }

        const currentRequest = ++requestNumber;
        const info = await fetch(`api_reserva_info.php?grupo_id=${encodeURIComponent(group.value)}&area_id=${encodeURIComponent(area.value)}`).then((response) => response.json()).catch(() => ({}));
        if (currentRequest !== requestNumber) {
            return;
        }

        const image = summary.querySelector('.reservation-summary-image');
        const placeholder = summary.querySelector('.reservation-summary-placeholder');
        const areaName = summary.querySelector('.reservation-summary-area-name');
        const allAreas = document.querySelector('[name="todas_areas"]');
        if (allAreas && allAreas.checked) {
            image.removeAttribute('src');
            image.classList.add('d-none');
            placeholder.classList.remove('d-none');
            placeholder.textContent = 'Áreas';
            areaName.textContent = 'Todas as áreas';
            return;
        }
        areaName.textContent = info.area ? info.area.nome : 'Selecione a área';
        if (info.area && info.area.imagem) {
            image.src = `uploads/${encodeURIComponent(info.area.imagem)}`;
            image.classList.remove('d-none');
            placeholder.classList.add('d-none');
        } else {
            image.removeAttribute('src');
            image.classList.add('d-none');
            placeholder.classList.remove('d-none');
        }
    }

    [group, subgroup, area, date, time, endTime, recurrence, allDay].filter(Boolean).forEach((field) => {
        field.addEventListener('change', updateSummary);
        field.addEventListener('input', updateSummary);
    });

    document.addEventListener('reservation-area-mode-change', updateSummary);

    updateSummary();
})();
