(() => {
    const form = document.querySelector('#reservationForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Verificando disponibilidade...';

        try {
            const response = await fetch('api_verificar_conflito.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            if (!response.ok) {
                showConflictMessage(result.error || 'Não foi possível validar a disponibilidade.');
                return;
            }
            if (result.hasConflict) {
                const formattedDates = result.dates.map((date) => new Intl.DateTimeFormat('pt-BR').format(new Date(`${date}T12:00:00`))).join(', ');
                showConflictMessage(`Já existe uma reserva ativa nesta área para ${formattedDates}. A reserva não foi criada.`);
                return;
            }
            submitButton.textContent = 'Salvando reserva...';
            const saveResponse = await fetch('api_salvar_reserva.php', { method: 'POST', body: new FormData(form) });
            const saveResult = await saveResponse.json();
            showReservationFeedback(saveResult.message || 'Não foi possível concluir a reserva.', saveResponse.ok && saveResult.success);
        } catch {
            showReservationFeedback('Não foi possível concluir a reserva. A reserva não foi criada.', false);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Confirmar reserva';
        }
    });

    function showConflictMessage(message) {
        const modalElement = document.querySelector('#conflictModal');
        if (!modalElement) {
            return;
        }
        modalElement.querySelector('.modal-body').textContent = message;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }

    function showReservationFeedback(message, success) {
        const modalElement = document.querySelector('#reservationFeedbackModal');
        if (!modalElement) {
            return;
        }
        modalElement.querySelector('.modal-title').textContent = success ? 'Reserva concluída' : 'Não foi possível concluir a reserva';
        modalElement.querySelector('.modal-body').textContent = message;
        const actionButton = modalElement.querySelector('.reservation-feedback-action');
        actionButton.textContent = success ? 'Ver agenda' : 'Entendi';
        actionButton.className = `btn reservation-feedback-action ${success ? 'btn-gold' : 'btn-navy'}`;
        actionButton.onclick = success ? () => { window.location.href = 'index.php'; } : null;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
})();
