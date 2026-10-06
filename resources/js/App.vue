<template>
    <div class="page">
        <!-- En-tête institutionnel -->
        <header class="header">
            <div class="header-inner">
                <div class="brand">
                    <svg class="brand-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M3 21V8l9-5 9 5v13" />
                        <path d="M9 21v-6h6v6" />
                    </svg>
                    <div>
                        <p class="brand-top">République du Bénin</p>
                        <h1 class="brand-title">ASIN — Suivi des demandes d'actes</h1>
                        <div class="brand-stripe" aria-hidden="true">
                            <span class="green"></span>
                            <span class="yellow"></span>
                            <span class="red"></span>
                        </div>
                    </div>
                </div>
                <p class="brand-sub">Agence des Systèmes d'Information et du Numérique</p>
            </div>
        </header>

        <main class="main">
            <!-- Statistiques : nombre de demandes par statut (Bonus 2) -->
            <section class="stats" aria-label="Statistiques par statut">
                <div v-for="s in statusCards" :key="s.value" class="stat-card">
                    <span class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path :d="s.icon" />
                        </svg>
                    </span>
                    <span class="stat-text">
                        <span class="stat-value">{{ stats[s.value] ?? 0 }}</span>
                        <span class="stat-label" :class="'dot-' + s.value">{{ s.label }}</span>
                    </span>
                </div>
            </section>

            <!-- Recherche par NPI + filtre par statut -->
            <section class="panel">
                <h2 class="panel-title">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    Consulter les demandes d'un usager
                </h2>
                <form class="search-form" @submit.prevent="search">
                    <div class="field">
                        <label class="label" for="npi">NPI de l'usager</label>
                        <input
                            id="npi"
                            v-model.trim="npi"
                            class="input"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="10 chiffres, ex. 0123456789"
                        >
                    </div>
                    <div class="field">
                        <label class="label" for="status">Filtrer par statut</label>
                        <select id="status" v-model="statusFilter" class="input">
                            <option value="">Tous les statuts</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m14 5 7 7-7 7" />
                            <path d="M21 12H8" />
                            <path d="M3 7v10" />
                        </svg>
                        Rechercher
                    </button>
                </form>
                <p v-if="validationError" class="alert alert-error" role="alert">{{ validationError }}</p>
            </section>

            <!-- Résultats -->
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h16" />
                        </svg>
                        Demandes de l'usager <span class="mono">{{ npi }}</span>
                    </h2>
                    <span v-if="total !== null" class="muted">{{ total }} demande{{ total > 1 ? 's' : '' }}</span>
                </div>

                <p v-if="loading" class="muted">Chargement…</p>

                <p v-else-if="loadError" class="alert alert-error" role="alert">{{ loadError }}</p>

                <p v-else-if="requests.length === 0 && searched" class="muted">
                    Aucune demande trouvée pour ce NPI{{ statusFilter ? ' avec ce filtre de statut' : '' }}.
                </p>

                <table v-else-if="requests.length" class="table">
                    <thead>
                        <tr>
                            <th scope="col">Identifiant</th>
                            <th scope="col">Type d'acte</th>
                            <th scope="col">Copies</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Date de dépôt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="request in requests" :key="request.id">
                            <td class="mono">#{{ request.id }}</td>
                            <td>
                                <span class="act-name">{{ request.act_type_label }}</span>
                                <span v-if="request.status === 'rejected' && request.rejection_reason" class="reject-reason">
                                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 8v4" />
                                        <path d="M12 16h.01" />
                                    </svg>
                                    Motif : {{ request.rejection_reason }}
                                </span>
                            </td>
                            <td>{{ request.copies_count }}</td>
                            <td>
                                <span class="badge" :class="'badge-' + request.status">
                                    <span class="badge-dot" aria-hidden="true"></span>
                                    {{ statusLabel(request.status) }}
                                </span>
                            </td>
                            <td>{{ formatDate(request.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination (Bonus 1 : 20 demandes maximum par page) -->
                <nav v-if="lastPage > 1" class="pagination" aria-label="Pages">
                    <button class="btn btn-ghost" :disabled="page <= 1" @click="goToPage(page - 1)">Précédent</button>
                    <span class="muted">Page {{ page }} sur {{ lastPage }}</span>
                    <button class="btn btn-ghost" :disabled="page >= lastPage" @click="goToPage(page + 1)">Suivant</button>
                </nav>
            </section>
        </main>

        <Chatbot />

        <footer class="footer">
            <p>Suivi des demandes d'actes administratifs — démonstration technique</p>
        </footer>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Chatbot from './components/Chatbot.vue';

// Libellés français des statuts : source unique pour le filtre et les badges.
const statuses = [
    { value: 'submitted', label: 'Déposée' },
    { value: 'processing', label: 'En cours de traitement' },
    { value: 'approved', label: 'Validée' },
    { value: 'rejected', label: 'Rejetée' },
];

// Icônes SVG (chemins uniques) affichées dans les cartes de statistiques.
const statusCards = [
    { value: 'submitted', label: 'Déposée', icon: 'M7 3h7l4 4v14H7z M14 3v4h4' },
    { value: 'processing', label: 'En cours de traitement', icon: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M12 7v5l3 3' },
    { value: 'approved', label: 'Validée', icon: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M8.5 12.5l2.5 2.5 4.5-5' },
    { value: 'rejected', label: 'Rejetée', icon: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M9 9l6 6 M15 9l-6 6' },
];

const stats = ref({});
const npi = ref('');
const searchedNpi = ref('');
const statusFilter = ref('');
const requests = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(null);
const loading = ref(false);
const searched = ref(false);
const loadError = ref('');
const validationError = ref('');

const statusLabel = (value) =>
    statuses.find((s) => s.value === value)?.label ?? value;

const formatDate = (iso) =>
    new Date(iso).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });

/**
 * Recharge les statistiques globales (nombre de demandes par statut).
 */
async function loadStats() {
    try {
        const response = await fetch('/api/requests/stats');
        if (response.ok) {
            stats.value = await response.json();
        }
    } catch {
        // Les statistiques sont un bonus d'affichage : un échec
        // ne doit pas empêcher la consultation des demandes.
    }
}

/**
 * Charge la liste paginée des demandes d'un usager.
 * Erreurs métier : validation 422 (NPI/statut invalide) ou 404.
 */
async function loadRequests() {
    loading.value = true;
    loadError.value = '';
    validationError.value = '';

    const params = new URLSearchParams();
    if (statusFilter.value) {
        params.set('status', statusFilter.value);
    }
    params.set('page', String(page.value));

    try {
        const response = await fetch(`/api/users/${searchedNpi.value}/requests?${params}`);
        const body = await response.json();

        if (!response.ok) {
            const message = body?.message ?? 'Une erreur est survenue.';
            if (response.status === 422) {
                validationError.value = message;
            } else {
                loadError.value = message;
            }
            requests.value = [];
            total.value = null;
            return;
        }

        requests.value = body.data;
        page.value = body.meta.current_page;
        lastPage.value = body.meta.last_page;
        total.value = body.meta.total;
        searched.value = true;
    } catch {
        loadError.value = 'Impossible de contacter le serveur.';
        requests.value = [];
    } finally {
        loading.value = false;
    }
}

function search() {
    // Contrôle d'entrée léger côté interface : la validation
    // faisant foi reste celle du backend.
    if (!/^\d{10}$/.test(npi.value)) {
        validationError.value = 'Le NPI doit comporter exactement 10 chiffres.';
        return;
    }
    searchedNpi.value = npi.value;
    page.value = 1;
    loadRequests();
}

function goToPage(target) {
    page.value = target;
    loadRequests();
}

loadStats();
</script>
