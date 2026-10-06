import { createApp } from 'vue';
import '../css/app.css';
import App from './App.vue';
import AdminApp from './AdminApp.vue';

// Deux interfaces distinctes montées selon l'URL :
//  - "/"      : espace usager (dépôt, suivi par code, consultation, chatbot)
//  - "/admin" : espace agent (connexion, dashboard, traitement)
const root = document.getElementById('app');

if (window.location.pathname.startsWith('/admin')) {
    createApp(AdminApp).mount(root);
} else {
    createApp(App).mount(root);
}
