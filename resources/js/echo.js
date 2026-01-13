import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.PUSHER_APP_ID,
    wsHost: import.meta.env.PUSHER_HOST,
    wsPort: import.meta.env.PUSHER_PORT ?? 80,
    wssPort: import.meta.env.PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
