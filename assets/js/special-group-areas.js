(() => {
    const group = document.querySelector('#group');
    const area = document.querySelector('[name="area_id"]');
    const allDayBox = document.querySelector('#allDayBox');
    if (!group || !area || !allDayBox) return;

    const allAreasBox = document.createElement('div');
    allAreasBox.className = 'form-check mb-3 d-none';
    allAreasBox.innerHTML = '<input class="form-check-input" type="checkbox" name="todas_areas" id="allAreas"><label class="form-check-label" for="allAreas">Marcar todas as áreas</label>';
    allDayBox.after(allAreasBox);
    const allAreas = allAreasBox.querySelector('#allAreas');

    function updateAvailability() {
        const isSpecial = group.selectedOptions[0]?.dataset.special === '1';
        allAreasBox.classList.toggle('d-none', !isSpecial);
        if (!isSpecial) allAreas.checked = false;
        area.disabled = allAreas.checked;
        document.dispatchEvent(new Event('reservation-area-mode-change'));
    }

    group.addEventListener('change', updateAvailability);
    allAreas.addEventListener('change', updateAvailability);
    updateAvailability();
})();
