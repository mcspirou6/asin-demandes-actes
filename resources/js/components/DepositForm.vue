<template>
    <section class="panel">
        <h2 class="panel-title">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M7 3h7l4 4v14H7z" />
                <path d="M14 3v4h4" />
            </svg>
            Déposer une demande — {{ act.label }}
        </h2>

        <!-- Formulaire de dépôt -->
        <form v-if="!result" class="deposit-form" @submit.prevent="submit">
            <div class="field">
                <label class="label" for="deposit-npi">Votre NPI</label>
                <input
                    id="deposit-npi"
                    v-model.trim="npi"
                    class="input"
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    placeholder="10 chiffres, ex. 0123456789"
                >
                <span class="muted">Votre Numéro Personnel d'Identification (10 chiffres).</span>
            </div>
            <div class="field">
                <label class="label" for="deposit-copies">Nombre de copies</label>
                <select id="deposit-copies" v-model.number="copies" class="input">
                    <option v-for="n in 5" :key="n" :value="n">{{ n }} copie{{ n > 1 ? 's' : '' }}</option>
                </select>
            </div>
            <div class="deposit-actions">
                <button class="btn btn-primary" type="submit" :disabled="submitting">
                    {{ submitting ? 'Envoi en cours…' : 'Déposer ma demande' }}
                </button>
                <button class="btn btn-ghost" type="button" @click="$emit('cancel')">Annuler</button>
            </div>
            <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        </form>

        <!-- Confirmation : la demande est enregistrée, on remet le code de suivi -->
        <div v-else class="success-box" role="status">
            <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" />
                <path d="M8.5 12.5l2.5 2.5 4.5-5" />
            </svg>
            <p class="success-title">Votre demande a bien été prise en charge.</p>
            <p class="muted">Conservez soigneusement votre code de suivi, il vous permettra de suivre l'état de votre demande :</p>
            <p class="tracking-code">{{ result.tracking_code }}</p>
            <div class="deposit-actions">
                <button class="btn btn-primary" type="button" @click="$emit('track', result.tracking_code)">Suivre cette demande</button>
                <button class="btn btn-ghost" type="button" @click="reset">Nouvelle demande</button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    act: { type: Object, required: true }, // { value, label }
});

const emit = defineEmits(['cancel', 'track']);

const npi = ref('');
const copies = ref(1);
const submitting = ref(false);
const result = ref(null);
const error = ref('');

function reset() {
    result.value = null;
    error.value = '';
    npi.value = '';
    copies.value = 1;
}

/**
 * Dépose la demande via l'API. Le statut initial (déposée) et le code
 * de suivi sont posés par le serveur ; la validation faisant foi
 * reste celle du backend (erreurs 422 affichées telles quelles).
 */
async function submit() {
    if (!/^\d{10}$/.test(npi.value)) {
        error.value = 'Le NPI doit comporter exactement 10 chiffres.';
        return;
    }

    submitting.value = true;
    error.value = '';

    try {
        const response = await fetch('/api/requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                npi: npi.value,
                act_type: props.act.value,
                copies_count: copies.value,
            }),
        });
        const body = await response.json();

        if (!response.ok) {
            error.value = body?.message ?? 'Une erreur est survenue, veuillez réessayer.';
            return;
        }

        result.value = body.data;
    } catch {
        error.value = 'Impossible de contacter le serveur.';
    } finally {
        submitting.value = false;
    }
}
</script>
