// Mobile Menu
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('nav-links');

if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
}

// Service Worker & PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js')
            .then(reg => console.log('SW registrado!', reg))
            .catch(err => console.log('SW falhou:', err));
    });
}

let deferredPrompt;
const installBtn = document.getElementById('install-btn');
const installCtaBtn = document.getElementById('install-cta');

if (installBtn) {
    const ua = window.navigator.userAgent.toLowerCase();
    const isIos = /iphone|ipad|ipod/.test(ua);
    const isAndroid = /android/.test(ua);
    const isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const showIosInstallHint = () => {
        let hint = document.getElementById('ios-install-hint');
        if (hint) {
            hint.style.display = 'block';
            return;
        }

        hint = document.createElement('div');
        hint.id = 'ios-install-hint';
        hint.className = 'ios-install-hint';
        hint.innerHTML = '<strong>Instalar no iPhone:</strong> toque em Compartilhar e depois em Adicionar a Tela de Início.';
        document.body.appendChild(hint);
    };

    const showAndroidInstallHint = () => {
        let hint = document.getElementById('android-install-hint');
        if (hint) {
            hint.style.display = 'block';
            return;
        }

        hint = document.createElement('div');
        hint.id = 'android-install-hint';
        hint.className = 'ios-install-hint';
        hint.innerHTML = '<strong>Instalar no Android:</strong> abra o menu do navegador (3 pontos) e toque em Instalar aplicativo ou Adicionar à tela inicial.';
        document.body.appendChild(hint);
    };

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        installBtn.style.display = 'block';
        installBtn.textContent = 'Instalar App';
        if (installCtaBtn) {
            installCtaBtn.textContent = 'Instalar App';
        }
    });

    const requestInstall = () => {
        if (deferredPrompt) {
            installBtn.style.display = 'none';
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Usuário aceitou a instalação');
                }
                deferredPrompt = null;
            });
            return;
        }

        if (isIos && !isInStandaloneMode) {
            showIosInstallHint();
            return;
        }

        if (isAndroid && !isInStandaloneMode) {
            showAndroidInstallHint();
        }
    };

    installBtn.addEventListener('click', requestInstall);
    if (installCtaBtn) {
        installCtaBtn.addEventListener('click', requestInstall);
    }

    window.addEventListener('appinstalled', () => {
        installBtn.style.display = 'none';
        const hint = document.getElementById('ios-install-hint');
        if (hint) {
            hint.remove();
        }
    });

    if (isIos && !isInStandaloneMode) {
        installBtn.style.display = 'block';
        installBtn.textContent = 'Como instalar no iPhone';
        if (installCtaBtn) {
            installCtaBtn.textContent = 'Como instalar no iPhone';
        }
    } else if (isAndroid && !isInStandaloneMode) {
        installBtn.style.display = 'block';
        installBtn.textContent = 'Instalar no Android';
        if (installCtaBtn) {
            installCtaBtn.textContent = 'Instalar no Android';
        }
    }
}
