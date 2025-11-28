const CACHE_NAME = 'paroquia-smg-v1';
const ASSETS_TO_CACHE = [
    './',
    './index.html',
    './style.css',
    './js/liturgy.js',
    './js/quiz.js',
    './js/share.js',
    './assets/favicon.png',
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
