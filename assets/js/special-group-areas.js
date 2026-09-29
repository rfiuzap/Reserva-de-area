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

    const multiAreaBox = document.querySelector('#multiAreaBox');
    if (multiAreaBox) {
        const multiAreaToggle = document.querySelector('#multiAreaToggle');
        const toggleMultiArea = () => {
            const checked = !!multiAreaToggle && multiAreaToggle.checked;
            multiAreaBox.classList.toggle('d-none', !checked);
            area.disabled = checked;
            area.required = !checked;
            if (checked) {
                area.value = '';
            }
        };

        multiAreaToggle?.addEventListener('change', () => {
            toggleMultiArea();
            document.dispatchEvent(new Event('reservation-area-mode-change'));
        });
        toggleMultiArea();
    }

    function updateAvailability() {
        const isSpecial = group.selectedOptions[0]?.dataset.special === '1';
        allAreasBox.classList.toggle('d-none', !isSpecial);
        if (!isSpecial) allAreas.checked = false;
        if (multiAreaBox && multiAreaBox.classList.contains('d-none')) {
            area.disabled = allAreas.checked;
            area.required = !allAreas.checked;
        }
        document.dispatchEvent(new Event('reservation-area-mode-change'));
    }

    group.addEventListener('change', updateAvailability);
    allAreas.addEventListener('change', updateAvailability);
    updateAvailability();
})();
