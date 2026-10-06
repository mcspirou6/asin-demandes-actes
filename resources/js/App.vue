<template>
    <div class="page">
        <!-- En-tête institutionnel -->
        <header class="header">
            <div class="header-inner header-user">
                <div class="brand">
                    <svg class="brand-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                <a class="btn btn-outline-light" href="/admin">Vous êtes agent ?</a>
            </div>
        </header>

        <main class="main">
            <!-- Accueil de l'usager -->
            <section class="hero">
                <h2 class="hero-title">Bienvenue, cher usager</h2>
                <p class="hero-sub">Que voulez-vous faire ?</p>
            </section>

            <!-- Options : dépôt des actes + suivi -->
            <section class="actions-grid" aria-label="Options disponibles">
                <button
                    v-for="action in actions"
                    :key="action.key"
                    class="action-card"
                    :class="{ 'action-card-active': activeAction === action.key }"
                    type="button"
                    @click="choose(action)"
                >
                    <span class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path v-for="(d, i) in action.icon" :key="i" :d="d" />
                        </svg>
                    </span>
                    <span class="action-text">
                        <span class="action-title">{{ action.label }}</span>
                        <span class="action-sub">{{ action.sub }}</span>
                    </span>
                </button>
            </section>

            <!-- Dépôt d'une demande -->
            <DepositForm
                v-if="activeDeposit"
                :act="activeDeposit"
                @cancel="activeAction = null"
                @track="goToTrack"
            />

            <!-- Suivi par code -->
            <TrackForm v-if="activeAction === 'track'" :initial-code="trackInitial" />
        </main>

        <footer class="footer">
            <p>Suivi des demandes d'actes administratifs — démonstration technique</p>
        </footer>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import DepositForm from './components/DepositForm.vue';
import TrackForm from './components/TrackForm.vue';

// Options de l'accueil : dépôt des trois types d'actes + suivi.
const actions = [
    { key: 'birth_certificate', label: 'Acte de naissance', sub: 'Déposer une demande', icon: ['M7 3h7l4 4v14H7z', 'M14 3v4h4'] },
    { key: 'criminal_record', label: 'Casier judiciaire', sub: 'Déposer une demande', icon: ['M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z', 'M9 12l2 2 4-4'] },
    { key: 'residence_certificate', label: 'Certificat de résidence', sub: 'Déposer une demande', icon: ['M3 21V8l9-5 9 5v13', 'M9 21v-6h6v6'] },
    { key: 'track', label: 'Suivre ma demande', sub: "Consulter l'état d'avancement", icon: ['M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14z', 'M20 20l-3.5-3.5'] },
];

const activeAction = ref(null);
const trackInitial = ref('');

const activeDeposit = computed(() => {
    if (!activeAction.value || activeAction.value === 'track') {
        return null;
    }
    return { value: activeAction.value, label: labelOf(activeAction.value) };
});

const labelOf = (value) =>
    actions.find((a) => a.key === value)?.label ?? value;

function choose(action) {
    activeAction.value = action.key;
    if (action.key !== 'track') {
        trackInitial.value = '';
    }
}

function goToTrack(code) {
    trackInitial.value = code;
    activeAction.value = 'track';
}
</script>
