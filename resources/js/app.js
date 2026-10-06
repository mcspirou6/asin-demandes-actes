import { createApp } from 'vue';
import App from './App.vue';
import '../css/app.css';

// Interface de consultation des demandes (Bonus 4).
// La logique métier critique reste dans Laravel : ce frontend
// ne fait que consommer l'API en HTTP/JSON.
createApp(App).mount('#app');
