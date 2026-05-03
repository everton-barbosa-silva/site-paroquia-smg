const GOOGLE_CLIENT_ID = '619640683871-f46vb52k6g31p4h20ltvte00l7h7a5h7.apps.googleusercontent.com';
const STORAGE_KEY = 'paroquia_smg_user_stats';

function parseJwt(token) {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map((c) => {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    return JSON.parse(jsonPayload);
}

function getStoredData() {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
}

function saveStoredData(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function getToday() {
    return formatDate(new Date());
}

function updateDashboard(userId) {
    const data = getStoredData();
    const user = data[userId];
    if (!user) {
        return;
    }

    document.getElementById('dashboard-greeting').textContent = `Olá, ${user.name}!`;
    document.getElementById('bible-days').textContent = `${user.bibleDates.length} dias registrados`;
    document.getElementById('liturgia-days').textContent = `${user.liturgiaDates.length} dias registrados`;
    document.getElementById('prayer-days').textContent = `${user.prayerDates.length} dias registrados`;
    document.getElementById('dashboard-last-activity').textContent = user.lastAction
        ? `Última ação registrada: ${user.lastAction}`
        : 'Nenhuma ação registrada ainda.';
}

function formatActionMessage(type, date) {
    const label = type === 'bible' ? 'Leitura da Bíblia' : type === 'liturgia' ? 'Liturgia' : 'Oração';
    return `${label} registrada em ${date}`;
}

function markToday(userId, type) {
    const data = getStoredData();
    const user = data[userId];
    if (!user) {
        return;
    }
    const today = getToday();
    const key = type === 'bible' ? 'bibleDates' : type === 'liturgia' ? 'liturgiaDates' : 'prayerDates';
    if (!user[key].includes(today)) {
        user[key].push(today);
    }
    user.lastAction = formatActionMessage(type, today);
    saveStoredData(data);
    updateDashboard(userId);
}

function showElement(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'block';
    }
}

function hideElement(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'none';
    }
}

function setWarning(message) {
    const warning = document.getElementById('login-warning');
    if (!warning) return;
    warning.textContent = message;
    warning.style.display = message ? 'block' : 'none';
}

function logout() {
    localStorage.removeItem('paroquia_smg_current_user');
    hideElement('dashboard-panel');
    showElement('login-panel');
    setWarning('Você saiu do painel. Para voltar, faça login com sua conta Google.');
}

function initializeDashboard(userInfo) {
    const data = getStoredData();
    const currentUserId = userInfo.sub;
    if (!data[currentUserId]) {
        data[currentUserId] = {
            name: userInfo.name || userInfo.email,
            email: userInfo.email,
            bibleDates: [],
            liturgiaDates: [],
            prayerDates: [],
            lastAction: '',
        };
    }

    saveStoredData(data);
    localStorage.setItem('paroquia_smg_current_user', currentUserId);
    updateDashboard(currentUserId);
    hideElement('login-panel');
    showElement('dashboard-panel');
}

function handleCredentialResponse(response) {
    try {
        const payload = parseJwt(response.credential);
        if (!payload || !payload.sub) {
            setWarning('Não foi possível autenticar. Tente novamente.');
            return;
        }
        initializeDashboard(payload);
    } catch (error) {
        setWarning('Erro ao processar login. Verifique se o ID do Google está configurado.');
    }
}

window.addEventListener('load', () => {
    if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
        setWarning('Configure o seu Google Client ID em js/google-auth.js para usar o login com Google.');
    }

    google.accounts.id.initialize({
        client_id: GOOGLE_CLIENT_ID,
        callback: handleCredentialResponse,
    });
    google.accounts.id.renderButton(
        document.getElementById('google-signin-button'),
        { theme: 'outline', size: 'large', width: '100%' }
    );
    google.accounts.id.prompt();

    const currentUserId = localStorage.getItem('paroquia_smg_current_user');
    if (currentUserId) {
        const stored = getStoredData();
        if (stored[currentUserId]) {
            hideElement('login-panel');
            showElement('dashboard-panel');
            updateDashboard(currentUserId);
        }
    }

    document.getElementById('mark-bible').addEventListener('click', () => {
        const current = localStorage.getItem('paroquia_smg_current_user');
        if (current) markToday(current, 'bible');
    });
    document.getElementById('mark-liturgia').addEventListener('click', () => {
        const current = localStorage.getItem('paroquia_smg_current_user');
        if (current) markToday(current, 'liturgia');
    });
    document.getElementById('mark-prayer').addEventListener('click', () => {
        const current = localStorage.getItem('paroquia_smg_current_user');
        if (current) markToday(current, 'prayer');
    });
    document.getElementById('logout-button').addEventListener('click', logout);
});
