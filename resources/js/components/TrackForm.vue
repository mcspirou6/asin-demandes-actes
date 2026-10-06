<template>
    <section class="panel">
        <h2 class="panel-title">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
            </svg>
            Suivre ma demande
        </h2>

        <form class="deposit-form" @submit.prevent="search">
            <div class="field">
                <label class="label" for="track-code">Code de suivi</label>
                <input
                    id="track-code"
                    v-model.trim="code"
                    class="input mono"
                    type="text"
                    maxlength="16"
                    placeholder="Ex. ASIN-DEMO01"
                >
                <span class="muted">Le code qui vous a été remis au moment du dépôt.</span>
            </div>
            <div class="deposit-actions">
                <button class="btn btn-primary" type="submit" :disabled="loading">
                    {{ loading ? 'Recherche…' : 'Suivre ma demande' }}
                </button>
            </div>
            <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        </form>

        <!-- État de la demande retrouvée -->
        <div v-if="request" class="track-result">
            <div class="track-row">
                <span class="muted">Code de suivi</span>
                <span class="mono">{{ request.tracking_code }}</span>
            </div>
            <div class="track-row">
                <span class="muted">Type d'acte</span>
                <span class="act-name">{{ request.act_type_label }}</span>
            </div>
            <div class="track-row">
                <span class="muted">Nombre de copies</span>
                <span>{{ request.copies_count }}</span>
            </div>
            <div class="track-row">
                <span class="muted">Statut</span>
                <span class="badge" :class="'badge-' + request.status">
                    <span class="badge-dot" aria-hidden="true"></span>
                    {{ statusLabel(request.status) }}
                </span>
            </div>
            <div class="track-row">
                <span class="muted">Date de dépôt</span>
                <span>{{ formatDate(request.created_at) }}</span>
            </div>
            <div v-if="request.status === 'rejected' && request.rejection_reason" class="track-row">
                <span class="muted">Motif du rejet</span>
                <span class="reject-reason">{{ request.rejection_reason }}</span>
            </div>
            <button class="btn btn-ghost" type="button" :disabled="loading" @click="search">
                {{ loading ? 'Actualisation…' : 'Actualiser le statut' }}
            </button>
        </div>
    </section>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    initialCode: { type: String, default: '' },
});

const code = ref(props.initialCode);
const loading = ref(false);
const error = ref('');
const request = ref(null);

const statusLabel = (value) =>
    ({ submitted: 'Déposée', processing: 'En cours de traitement', approved: 'Validée', rejected: 'Rejetée' })[value] ?? value;

const formatDate = (iso) =>
    new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });

async function search() {
    if (!code.value) {
        error.value = 'Veuillez saisir votre code de suivi.';
        return;
    }

    loading.value = true;
    error.value = '';
    request.value = null;

    try {
        const response = await fetch(`/api/requests/track/${encodeURIComponent(code.value)}`);
        const body = await response.json();

        if (!response.ok) {
            error.value = body?.message ?? 'Une erreur est survenue.';
            return;
        }

        request.value = body.data;
    } catch {
        error.value = 'Impossible de contacter le serveur.';
    } finally {
        loading.value = false;
    }
}

// Si l'usager vient d'une confirmation de dépôt, la recherche est lancée
// automatiquement avec le code qui vient de lui être remis.
watch(
    () => props.initialCode,
    (value) => {
        if (value) {
            code.value = value;
            search();
        }
    }
);
</script>
