/*
| Estudy — Alpine vem embutido no Livewire. Stores registrados no "alpine:init".
*/

const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;

let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.Alpine?.store('install').refresh();
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Toasts: $store.toast.show('Salvo!') ou, no Livewire, $this->dispatch('toast', message: '...')
    Alpine.store('toast', {
        items: [],
        show(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            if (type === 'success' && navigator.vibrate) navigator.vibrate(10);
            setTimeout(() => this.dismiss(id), 3200);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    // Instalação do PWA
    Alpine.store('install', {
        canPrompt: false,
        standalone: isStandalone(),
        ios: isIos(),
        refresh() {
            this.canPrompt = deferredInstallPrompt !== null;
            this.standalone = isStandalone();
        },
        async prompt() {
            if (deferredInstallPrompt) {
                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
                deferredInstallPrompt = null;
                this.refresh();
                return;
            }
            if (this.ios) {
                window.dispatchEvent(new CustomEvent('open-sheet', { detail: 'ios-install' }));
                return;
            }
            Alpine.store('toast').show('Use o menu do navegador e escolha "Instalar app".', 'info');
        },
    });
});

document.addEventListener('alpine:initialized', () => window.Alpine.store('install').refresh());

document.addEventListener('livewire:init', () => {
    // Sessão expirada (419), erro do servidor ou sem rede: aviso em português em vez do diálogo padrão
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                window.location.reload();
            } else if (status >= 500) {
                preventDefault();
                window.Alpine.store('toast').show('Algo deu errado. Tente novamente.', 'error');
            } else if (status === 0) {
                preventDefault();
                window.Alpine.store('toast').show('Sem conexão com a internet.', 'error');
            }
        });
    });

    window.Livewire.on('toast', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        window.Alpine.store('toast').show(data.message, data.type ?? 'success');
    });
});
