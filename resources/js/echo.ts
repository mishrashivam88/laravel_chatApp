import Echo from 'laravel-echo';

const echo = new Echo({
    broadcaster: 'reverb',
    host: window.location.hostname + ':8080',
});

export default echo;