(() => {
    const parameters = new URLSearchParams(window.location.search);
    const view = parameters.get('visao') || 'semana';
    const isAgendaPage = window.location.pathname.endsWith('/agenda.php');
    const isIndexPage = window.location.pathname.endsWith('/index.php') || window.location.pathname.endsWith('/');
    if (!isAgendaPage && !isIndexPage) {
        return;
    }
    if (!isAgendaPage && view !== 'semana' && view !== 'mes') {
        return;
    }

    const heading = document.querySelector('main h1');
    const period = heading?.nextElementSibling;
    const dateKey = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    const reference = parameters.get('data') || dateKey(new Date());
    if (!heading || !period || !reference) {
        return;
    }

    const previousLink = Array.from(document.querySelectorAll('a')).find((link) => link.textContent.trim() === 'Anterior');
    if (previousLink && !document.querySelector('[data-today-navigation]')) {
        const todayUrl = new URL(window.location.href);
        todayUrl.searchParams.set('data', dateKey(new Date()));
        const todayLink = document.createElement('a');
        todayLink.className = previousLink.className;
        todayLink.href = todayUrl.toString();
        todayLink.textContent = 'Hoje';
        todayLink.dataset.todayNavigation = 'true';
        previousLink.before(todayLink);
    }

    const activeFilters = ['meu', 'grupo_filtro', 'area_filtro']
        .map((name) => [name, parameters.get(name)])
        .filter(([, value]) => value !== null);
    if (activeFilters.length) {
        const navigationLabels = new Set(['Anterior', 'Próxima', 'Dia', 'Mês']);
        document.querySelectorAll('a').forEach((link) => {
            if (!navigationLabels.has(link.textContent.trim())) {
                return;
            }

            const target = new URL(link.href, window.location.href);
            activeFilters.forEach(([name, value]) => target.searchParams.set(name, value));
            link.href = target.toString();
        });
    }

    const markToday = (elements, firstDate) => {
        const todayKey = dateKey(new Date());
        elements.forEach((element, index) => {
            const date = new Date(firstDate);
            date.setDate(date.getDate() + index);
            if (dateKey(date) === todayKey) {
                element.classList.add('is-today');
            }
        });
    };

    const pageDate = new Date(`${reference}T12:00:00`);
    const dayRows = document.querySelectorAll('.agenda-day-row');
    if (dayRows.length) {
        markToday(dayRows, pageDate);
    }

    const monthCells = document.querySelectorAll('.month-grid .month-cell');
    if (monthCells.length) {
        const firstMonthDate = new Date(pageDate);
        firstMonthDate.setDate(1);
        markToday(monthCells, firstMonthDate);
    }

    const tabs = document.querySelector('.btn-group');
    if (tabs && !tabs.querySelector('[href^="agenda.php"]')) {
        const agendaTab = document.createElement('a');
        agendaTab.className = 'btn btn-sm btn-outline-secondary';
        agendaTab.href = `agenda.php?data=${encodeURIComponent(reference)}`;
        agendaTab.textContent = 'Mês';
        tabs.append(agendaTab);
    }

    tabs?.querySelector('[href*="visao=mes"]')?.replaceChildren('Dia');
    tabs?.querySelector('[href^="agenda.php"]')?.replaceChildren('Mês');
    const dayTab = tabs?.querySelector('[href*="visao=mes"]');
    const weekTab = tabs?.querySelector('[href*="visao=semana"]');
    const monthTab = tabs?.querySelector('[href^="agenda.php"]');
    if (tabs && dayTab && weekTab && monthTab) {
        tabs.append(dayTab, weekTab, monthTab);
    }

    if (isAgendaPage || view !== 'mes') {
        return;
    }

    const month = new Date(`${reference}T12:00:00`);
    const monthLabel = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(month);
    period.textContent = monthLabel.charAt(0).toUpperCase() + monthLabel.slice(1);
    period.classList.add('calendar-month-label');
    period.style.color = '#223764';
    period.style.fontSize = '1.25rem';
    period.style.fontWeight = '700';
})();
