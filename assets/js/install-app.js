let deferredInstallPrompt;
const installButton = document.querySelector('#install-app-button');

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}

if (installButton && !window.matchMedia('(display-mode: standalone)').matches) {
    installButton.hidden = false;
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    if (installButton) installButton.hidden = false;
});

installButton?.addEventListener('click', async () => {
    if (!deferredInstallPrompt) {
        alert('Abra o menu do navegador e escolha "Adicionar à tela inicial" ou "Instalar aplicativo".');
        return;
    }
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    installButton.hidden = true;
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    if (installButton) installButton.hidden = true;
});
