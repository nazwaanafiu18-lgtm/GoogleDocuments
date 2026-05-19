import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

let tabId = sessionStorage.getItem('tab_id');
if (!tabId) {
    tabId = Math.floor(Math.random() * 900000 + 100000).toString();
    sessionStorage.setItem('tab_id', tabId);
}

let userName = sessionStorage.getItem('user_name');
if (!userName) {
    userName = 'User ' + Math.floor(Math.random() * 899 + 100);
    sessionStorage.setItem('user_name', userName);
}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        params: {
            tab_id: tabId,
            user_name: userName
        }
    }
});