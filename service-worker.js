const CACHE_NAME = 'paroquia-smg-v3';
const ASSETS_TO_CACHE = [
    './',
    './index.html',
    './biblia.html',
    './login.html',
    './oracoes.html',
    './teologia.html',
    './quiz.html',
    './documentos-igreja.html',
    './style.css',
    './js/app.js',
    './js/bible.js',
    './js/google-auth.js',
    './js/liturgy.js',
    './js/quiz.js',
    './js/share.js',
    './assets/favicon.png',
    './assets/icon-192.png',
    './assets/icon-512.png',
    './assets/santa-maria-goretti-public-domain.jpg',
    './assets/igreja.jpg',
    './assets/padre.jpg',
    './assets/whats.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                return response || fetch(event.request);
            })
    );
});
