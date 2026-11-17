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

/*
| Relógio do cronômetro: <div x-data="timerClock($wire, pomodoroCfg, pomodoroOn, todaySeconds, goalSeconds, nome)">
| O servidor guarda início/pausas; aqui só desenhamos. Com $wire, lê $wire.clock (atualizado a cada ação);
| sem $wire (barra do cronômetro), usa o estado fixo recebido.
*/
const pad = (n) => String(n).padStart(2, '0');
const clockText = (s) => `${pad(Math.floor(s / 3600))}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
const shortText = (s) => `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;

const beep = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [0, 0.25].forEach((t) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.15, ctx.currentTime + t);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.2);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + t);
            osc.stop(ctx.currentTime + t + 0.2);
        });
    } catch { /* sem áudio */ }
};

const alertUser = (title, body) => {
    navigator.vibrate?.([200, 100, 200]);
    beep();
    if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
        navigator.serviceWorker?.getRegistration().then((reg) => {
            if (reg) reg.showNotification(title, { body, icon: '/icons/icon-192.png', tag: 'estudy-timer' });
            else new Notification(title, { body });
        }).catch(() => {});
    }
    window.Alpine?.store('toast').show(title, 'info');
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('timerClock', (wire, pomodoro, pomodoroOn, todaySeconds, goalSeconds, name, fixedState = null) => ({
        display: '00:00:00', label: '', sub: '', ring: 0, ringDone: false, paused: false,
        lastBlock: null, breakAlerted: false, timer: null, lock: null, offset: 0, lastServerNow: null,

        state() { return wire ? wire.clock : fixedState; },

        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 250);
            if (wire) {
                this.requestWakeLock();
                this.onVisible = () => { if (document.visibilityState === 'visible') this.requestWakeLock(); };
                document.addEventListener('visibilitychange', this.onVisible);
                if (pomodoroOn && 'Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission().catch(() => {});
                }
            }
        },

        destroy() {
            clearInterval(this.timer);
            if (this.onVisible) document.removeEventListener('visibilitychange', this.onVisible);
            this.lock?.release?.().catch(() => {});
            if (wire) document.title = 'Estudy';
        },

        async requestWakeLock() {
            try { this.lock = await navigator.wakeLock?.request('screen'); } catch { /* sem suporte */ }
        },

        tick() {
            const s = this.state();
            if (!s || !s.startedAt) return;
            // Diferença entre o relógio do aparelho e o do servidor (recalculada a cada resposta)
            if (s.serverNow !== this.lastServerNow) { this.offset = s.serverNow - Date.now(); this.lastServerNow = s.serverNow; }

            const now = Date.now() + this.offset;
            this.paused = s.pausedAt !== null && s.pausedAt !== undefined;
            const end = this.paused ? s.pausedAt : now;
            const elapsed = Math.max(0, Math.floor((end - s.startedAt) / 1000) - (s.pausedSeconds || 0));
            this.display = clockText(elapsed);

            if (!wire) return;

            if (pomodoroOn && pomodoro) {
                const focus = pomodoro.focus * 60;
                const block = Math.floor(elapsed / focus);
                if (!this.paused) {
                    this.breakAlerted = false;
                    if (this.lastBlock !== null && block > this.lastBlock) {
                        alertUser('Hora da pausa!', `${name}: ${pomodoro.focus} min de foco concluídos.`);
                        wire.pause();
                    }
                    this.lastBlock = block;
                    const inBlock = elapsed % focus;
                    this.label = `Foco ${block + 1}`;
                    this.sub = `pausa em ${shortText(focus - inBlock)}`;
                    this.ring = inBlock / focus;
                    this.ringDone = false;
                } else {
                    this.lastBlock = block;
                    const breakLen = (block > 0 && block % pomodoro.every === 0 ? pomodoro.long : pomodoro.short) * 60;
                    const taken = Math.max(0, Math.floor((now - s.pausedAt) / 1000));
                    const left = breakLen - taken;
                    this.label = 'Pausa';
                    this.sub = left > 0 ? `volte em ${shortText(left)}` : 'pausa encerrada — bora voltar!';
                    this.ring = Math.min(1, taken / breakLen);
                    this.ringDone = left <= 0;
                    if (left <= 0 && !this.breakAlerted) {
                        this.breakAlerted = true;
                        alertUser('Pausa encerrada', `Hora de voltar para ${name}.`);
                    }
                }
            } else {
                const total = todaySeconds + elapsed;
                this.label = this.paused ? 'Pausado' : 'Estudando';
                this.ring = goalSeconds > 0 ? Math.min(1, total / goalSeconds) : 0;
                this.ringDone = this.ring >= 1;
                this.sub = `hoje: ${Math.floor(total / 60)} de ${Math.round(goalSeconds / 60)} min`;
            }

            document.title = `${this.paused ? '⏸ ' : ''}${this.display} · ${name}`;
        },
    }));
});
