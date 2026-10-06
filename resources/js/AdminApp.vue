<template>
    <div class="page">
        <!-- En-tête institutionnel -->
        <header class="header">
            <div class="header-inner header-admin">
                <div class="brand">
                    <svg class="brand-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 21V8l9-5 9 5v13" />
                        <path d="M9 21v-6h6v6" />
                    </svg>
                    <div>
                        <p class="brand-top">République du Bénin</p>
                        <h1 class="brand-title">ASIN — Espace agent</h1>
                        <div class="brand-stripe" aria-hidden="true">
                            <span class="green"></span>
                            <span class="yellow"></span>
                            <span class="red"></span>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-outline-light" href="/">Espace usager</a>
                    <button v-if="authed" class="btn btn-outline-light" type="button" @click="logout">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="M16 17l5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>
                        Déconnexion
                    </button>
                </div>
            </div>
        </header>

        <main class="main">
            <!-- Connexion de l'agent -->
            <section v-if="!authed" class="login-wrap">
                <form class="panel login-card" @submit.prevent="login">
                    <h2 class="panel-title">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="4" y="11" width="16" height="9" rx="2" />
                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                        </svg>
                        Connexion agent
                    </h2>
                    <div class="field">
                        <label class="label" for="email">Adresse email</label>
                        <input id="email" v-model.trim="email" class="input" type="email" autocomplete="username" placeholder="agent@asin.bj">
                    </div>
                    <div class="field">
                        <label class="label" for="password">Mot de passe</label>
                        <input id="password" v-model="password" class="input" type="password" autocomplete="current-password" placeholder="Mot de passe">
                    </div>
                    <button class="btn btn-primary" type="submit" :disabled="logging">{{ logging ? 'Connexion…' : 'Se connecter' }}</button>
                    <p v-if="loginError" class="alert alert-error" role="alert">{{ loginError }}</p>
                </form>
            </section>

            <!-- Dashboard agent -->
            <template v-else>
                <!-- Notification : demandes en attente de traitement -->
                <div v-if="pendingCount > 0" class="notif-banner" role="status">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                        <path d="M10 21a2 2 0 0 0 4 0" />
                    </svg>
                    <span><strong>{{ pendingCount }}</strong> nouvelle{{ pendingCount > 1 ? 's' : '' }} demande{{ pendingCount > 1 ? 's' : '' }} déposée{{ pendingCount > 1 ? 's' : '' }} en attente de traitement.</span>
                    <button class="btn btn-ghost btn-sm" type="button" @click="refresh">Actualiser</button>
                </div>
                <div v-else class="notif-banner notif-banner-ok" role="status">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" />
                        <path d="M8.5 12.5l2.5 2.5 4.5-5" />
                    </svg>
                    <span>Aucune nouvelle demande en attente.</span>
                    <button class="btn btn-ghost btn-sm" type="button" @click="refresh">Actualiser</button>
                </div>

                <p v-if="feedback" class="alert" :class="feedback.type === 'error' ? 'alert-error' : 'alert-ok'" role="status">
                    {{ feedback.message }}
                </p>

                <!-- Filtres de tri -->
                <section class="panel">
                    <h2 class="panel-title">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 5h16l-6 8v5l-4 2v-7z" />
                        </svg>
                        Trier les demandes
                    </h2>
                    <form class="search-form" @submit.prevent="loadRequests">
                        <div class="field">
                            <label class="label" for="admin-status">Filtrer par statut</label>
                            <select id="admin-status" v-model="statusFilter" class="input">
                                <option value="">Tous les statuts</option>
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="admin-npi">NPI exact</label>
                            <input id="admin-npi" v-model.trim="npiFilter" class="input mono" type="text" inputmode="numeric" maxlength="10" placeholder="10 chiffres">
                        </div>
                        <button class="btn btn-primary" type="submit">Appliquer</button>
                        <button class="btn btn-ghost" type="button" @click="resetFilters">Réinitialiser</button>
                    </form>
                </section>

                <!-- Tableau de traitement -->
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Demandes reçues</h2>
                        <div v-if="total !== null" class="panel-head-tools">
                            <span class="muted">{{ total }} demande{{ total > 1 ? 's' : '' }}</span>
                            <label class="page-size-control">
                                <span class="label">Par page</span>
                                <select v-model.number="perPage" class="input" aria-label="Nombre de demandes par page" @change="changePageSize">
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <p v-if="loading" class="muted loading-line">Chargement…</p>

                    <table v-else-if="requests.length" class="table">
                        <thead>
                            <tr>
                                <th scope="col">Code de suivi</th>
                                <th scope="col">NPI</th>
                                <th scope="col">Type d'acte</th>
                                <th scope="col">Copies</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Date et heure du dépôt</th>
                                <th scope="col">Traitement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="request in requests" :key="request.id">
                                <tr>
                                    <td class="mono">{{ request.tracking_code }}</td>
                                    <td class="mono">{{ request.npi }}</td>
                                    <td>{{ request.act_type_label }}</td>
                                    <td>{{ request.copies_count }}</td>
                                    <td>
                                        <span class="badge" :class="'badge-' + request.status">
                                            <span class="badge-dot" aria-hidden="true"></span>
                                            {{ statusLabel(request.status) }}
                                        </span>
                                        <span v-if="request.status === 'rejected' && request.rejection_reason" class="reject-reason">
                                            {{ request.rejection_reason }}
                                        </span>
                                    </td>
                                    <td>{{ formatDateTime(request.created_at) }}</td>
                                    <td>
                                        <div class="row-actions">
                                            <button
                                                v-if="request.status === 'submitted'"
                                                class="btn btn-sm btn-primary"
                                                type="button"
                                                @click="act(request, 'processing')"
                                            >
                                                Prendre en charge
                                            </button>
                                            <button
                                                v-if="request.status === 'processing'"
                                                class="btn btn-sm btn-success"
                                                type="button"
                                                @click="act(request, 'approved')"
                                            >
                                                Valider
                                            </button>
                                            <button
                                                v-if="request.status === 'processing'"
                                                class="btn btn-sm btn-danger"
                                                type="button"
                                                @click="openReject(request)"
                                            >
                                                Rejeter
                                            </button>
                                            <span v-if="request.status === 'approved' || request.status === 'rejected'" class="muted">
                                                Dossier clos
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Saisie du motif de rejet, ligne dédiée -->
                                <tr v-if="rejectTarget && rejectTarget.id === request.id">
                                    <td colspan="7">
                                        <form class="motif-box" @submit.prevent="confirmReject">
                                            <label class="label" :for="'motif-' + request.id">Motif du rejet (obligatoire)</label>
                                            <textarea
                                                :id="'motif-' + request.id"
                                                v-model.trim="rejectReason"
                                                class="input"
                                                rows="2"
                                                maxlength="500"
                                                placeholder="Expliquez clairement la raison du rejet…"
                                            ></textarea>
                                            <div class="deposit-actions">
                                                <button class="btn btn-sm btn-danger" type="submit">Confirmer le rejet</button>
                                                <button class="btn btn-sm btn-ghost" type="button" @click="rejectTarget = null">Annuler</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p v-else class="muted loading-line">Aucune demande pour ces filtres.</p>

                    <nav v-if="lastPage > 1" class="pagination" aria-label="Pages">
                        <button class="btn btn-ghost" :disabled="page <= 1" @click="goToPage(page - 1)">Précédent</button>
                        <span class="muted">Page {{ page }} sur {{ lastPage }}</span>
                        <button class="btn btn-ghost" :disabled="page >= lastPage" @click="goToPage(page + 1)">Suivant</button>
                    </nav>
                </section>
            </template>
        </main>

        <footer class="footer">
            <p>Espace agent — réservé aux agents habilités de l'ASIN</p>
        </footer>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const statuses = [
    { value: 'submitted', label: 'Déposée' },
    { value: 'processing', label: 'En cours de traitement' },
    { value: 'approved', label: 'Validée' },
    { value: 'rejected', label: 'Rejetée' },
];

const authed = ref(false);
const email = ref('');
const password = ref('');
const logging = ref(false);
const loginError = ref('');

const requests = ref([]);
const page = ref(1);
const lastPage = ref(1);
const perPage = ref(20);
const total = ref(null);
const loading = ref(false);
const statusFilter = ref('');
const npiFilter = ref('');
const pendingCount = ref(0);
const feedback = ref(null);
const stats = ref({});

// Rejet en cours : la demande cible + le motif saisi.
const rejectTarget = ref(null);
const rejectReason = ref('');

let statsTimer = null;

const statusLabel = (value) =>
    statuses.find((s) => s.value === value)?.label ?? value;

const formatDateTime = (iso) =>
    new Date(iso).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

function showFeedback(type, message) {
    feedback.value = { type, message };
    // Le message d'information disparaît après quelques secondes.
    setTimeout(() => { feedback.value = null; }, 4000);
}

async function login() {
    logging.value = true;
    loginError.value = '';

    try {
        const response = await fetch('/api/admin/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: email.value, password: password.value }),
        });
        const body = await response.json();

        if (!response.ok) {
            loginError.value = body?.message ?? 'Connexion impossible.';
            return;
        }

        authed.value = true;
        password.value = '';
        await Promise.all([loadRequests(), loadStats()]);
    } catch {
        loginError.value = 'Impossible de contacter le serveur.';
    } finally {
        logging.value = false;
    }
}

async function logout() {
    await fetch('/api/admin/logout', { method: 'POST', headers: { 'Accept': 'application/json' } });
    authed.value = false;
    requests.value = [];
    pendingCount.value = 0;
}

async function loadRequests() {
    loading.value = true;

    const params = new URLSearchParams();
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (npiFilter.value) params.set('npi', npiFilter.value);
    params.set('page', String(page.value));
    params.set('per_page', String(perPage.value));

    try {
        const response = await fetch(`/api/admin/requests?${params}`, { headers: { 'Accept': 'application/json' } });
        const body = await response.json();

        if (response.status === 401) {
            authed.value = false;
            return;
        }
        if (!response.ok) {
            showFeedback('error', body?.message ?? 'Impossible de charger les demandes.');
            return;
        }

        requests.value = body.data;
        page.value = body.meta.current_page;
        lastPage.value = body.meta.last_page;
        total.value = body.meta.total;
    } catch {
        showFeedback('error', 'Impossible de contacter le serveur.');
    } finally {
        loading.value = false;
    }
}

/**
 * Notifications : comptage des demandes "submitted", rafraîchi
 * périodiquement pendant la session agent.
 */
async function loadStats() {
    try {
        const response = await fetch('/api/admin/stats', { headers: { 'Accept': 'application/json' } });
        if (response.ok) {
            stats.value = await response.json();
            pendingCount.value = stats.value.submitted ?? 0;
        } else if (response.status === 401) {
            authed.value = false;
        }
    } catch {
        // Silencieux : la notification n'est pas critique.
    }
}

function refresh() {
    loadRequests();
    loadStats();
}

function resetFilters() {
    statusFilter.value = '';
    npiFilter.value = '';
    page.value = 1;
    loadRequests();
}

function goToPage(target) {
    page.value = target;
    loadRequests();
}

function changePageSize() {
    page.value = 1;
    loadRequests();
}

/**
 * Faire avancer une demande (prise en charge / validation).
 * Le serveur contrôle la transition : 409 si interdite.
 */
async function act(request, status) {
    try {
        const response = await fetch(`/api/requests/${request.id}/status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status }),
        });
        const body = await response.json();

        if (!response.ok) {
            showFeedback('error', body?.message ?? 'Action impossible.');
            return;
        }

        showFeedback('ok', `Demande ${request.tracking_code} : ${statusLabel(status)}.`);
        refresh();
    } catch {
        showFeedback('error', 'Impossible de contacter le serveur.');
    }
}

function openReject(request) {
    rejectTarget.value = request;
    rejectReason.value = '';
}

async function confirmReject() {
    const request = rejectTarget.value;
    if (!request) return;

    if (!rejectReason.value) {
        showFeedback('error', 'Un rejet doit obligatoirement être motivé.');
        return;
    }

    try {
        const response = await fetch(`/api/requests/${request.id}/status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status: 'rejected', rejection_reason: rejectReason.value }),
        });
        const body = await response.json();

        if (!response.ok) {
            showFeedback('error', body?.message ?? 'Action impossible.');
            return;
        }

        showFeedback('ok', `Demande ${request.tracking_code} rejetée.`);
        rejectTarget.value = null;
        rejectReason.value = '';
        refresh();
    } catch {
        showFeedback('error', 'Impossible de contacter le serveur.');
    }
}

onMounted(async () => {
    // Au chargement, on vérifie si une session agent est déjà active.
    try {
        const response = await fetch('/api/admin/me', { headers: { 'Accept': 'application/json' } });
        if (response.ok) {
            const body = await response.json();
            if (body.authenticated) {
                authed.value = true;
                await Promise.all([loadRequests(), loadStats()]);
            }
        }
    } catch {
        // L'écran de connexion reste affiché.
    }

    // Notification périodique des nouvelles demandes.
    statsTimer = setInterval(loadStats, 30000);
});

onUnmounted(() => {
    if (statsTimer) {
        clearInterval(statsTimer);
    }
});
</script>
