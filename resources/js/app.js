import { createApp } from 'vue';

import Counter from './Components/Counter.vue';

const appElement = document.getElementById('app');

if (appElement) {
    const app = createApp({});

    app.component('counter', Counter);

    app.mount(appElement);
}
