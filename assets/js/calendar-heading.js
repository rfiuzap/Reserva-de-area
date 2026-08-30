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
    const reference = parameters.get('data') || new Date().toISOString().slice(0, 10);
    if (!heading || !period || !reference) {
        return;
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
