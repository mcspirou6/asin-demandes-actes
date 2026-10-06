<template>
    <div class="chatbot">
        <!-- Panneau de conversation -->
        <div v-if="open" class="chat-panel" role="dialog" aria-label="Assistant de suivi">
            <div class="chat-head">
                <h2 class="chat-title">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5z" />
                    </svg>
                    Assistant de suivi
                </h2>
                <button class="chat-close" type="button" aria-label="Fermer l'assistant" @click="open = false">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12" />
                        <path d="M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <!-- Messages : réponses prédéfinies servies par l'API Laravel -->
            <div ref="messagesBox" class="chat-messages">
                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="chat-message"
                    :class="message.from === 'user' ? 'chat-message-user' : 'chat-message-bot'"
                >
                    {{ message.text }}
                </div>
                <p v-if="loading" class="chat-typing muted">L'assistant écrit…</p>
            </div>

            <form class="chat-form" @submit.prevent="send">
                <input
                    v-model.trim="draft"
                    class="chat-input"
                    type="text"
                    placeholder="Posez votre question…"
                    aria-label="Votre question"
                >
                <button class="chat-send" type="submit" aria-label="Envoyer" :disabled="loading || draft.length === 0">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 2 11 13" />
                        <path d="M22 2 15 22l-4-9-9-4z" />
                    </svg>
                </button>
            </form>
        </div>

        <!-- Bulle orange d'ouverture, positionnée comme sur le site ANIP -->
        <button v-else class="chat-bubble" type="button" aria-label="Ouvrir l'assistant de suivi" @click="open = true">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12a8 8 0 0 1-8 8H4l2-3a8 8 0 1 1 15-5z" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { nextTick, ref } from 'vue';

const open = ref(false);
const loading = ref(false);
const draft = ref('');
const messagesBox = ref(null);

// Message d'accueil affiché à l'ouverture du panneau.
const messages = ref([
    {
        from: 'bot',
        text: "Bonjour ! Je réponds aux questions sur le traitement des demandes : suivi, statuts, copies, NPI…",
    },
]);

/**
 * Envoie la question à l'API : les réponses sont prédéfinies côté
 * serveur (config/chatbot.php), aucune réponse n'est générée ici.
 */
async function send() {
    const question = draft.value;
    if (!question || loading.value) {
        return;
    }

    messages.value.push({ from: 'user', text: question });
    draft.value = '';
    loading.value = true;
    await scrollToBottom();

    try {
        const response = await fetch(`/api/chatbot?q=${encodeURIComponent(question)}`);
        const body = await response.json();
        messages.value.push({
            from: 'bot',
            text: body?.answer ?? 'Une erreur est survenue, veuillez réessayer.',
        });
    } catch {
        messages.value.push({
            from: 'bot',
            text: "Impossible de contacter l'assistant. Vérifiez votre connexion.",
        });
    } finally {
        loading.value = false;
        await scrollToBottom();
    }
}

async function scrollToBottom() {
    await nextTick();
    if (messagesBox.value) {
        messagesBox.value.scrollTop = messagesBox.value.scrollHeight;
    }
}
</script>
