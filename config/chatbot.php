<?php

/*
|--------------------------------------------------------------------------
| Assistant conversationnel — réponses prédéfinies
|--------------------------------------------------------------------------
|
| Chatbot SANS intelligence artificielle : chaque entrée associe une
| liste de mots-clés à une réponse préparée à l'avance. Le contrôleur
| normalise la question (minuscules, sans accents) puis cherche la
| meilleure correspondance par mots-clés. Aucune réponse n'est
| inventée dynamiquement : tout le contenu ci-dessous est figé.
|
| Les réponses reflètent uniquement les règles de gestion de l'énoncé.
*/

return [

    // Réponse renvoyée quand aucun mot-clé ne correspond.
    'fallback' => "Désolé, je n'ai pas de réponse préparée pour cette question. "
        ."Vous pouvez me demander : comment suivre votre demande, quels types d'actes "
        ."sont disponibles, combien de copies sont possibles, ou ce qu'est le NPI.",

    'entries' => [

        [
            'keywords' => ['suivre', 'suivi', 'statut', 'etat', 'avancement', 'ou en est', 'parcours'],
            'answer' => "Votre demande suit ce parcours : déposée, puis en cours de traitement, "
                ."et enfin validée ou rejetée. Une demande validée ou rejetée ne change plus. "
                ."Consultez vos demandes par NPI depuis cette page.",
        ],

        [
            'keywords' => ['npi', 'numero personnel', 'identification'],
            'answer' => "Le NPI est votre Numéro Personnel d'Identification : une série d'exactement "
                ."10 chiffres. Il est nécessaire pour déposer et consulter vos demandes d'actes.",
        ],

        [
            'keywords' => ['copie', 'copies', 'exemplaire', 'exemplaires'],
            'answer' => "Pour un même acte, vous pouvez demander entre 1 et 5 copies incluses.",
        ],

        [
            'keywords' => ['type', 'acte', 'naissance', 'casier', 'residence', 'certificat'],
            'answer' => "Trois types d'actes peuvent être demandés : l'acte de naissance, "
                ."le casier judiciaire et le certificat de résidence.",
        ],

        [
            'keywords' => ['rejet', 'rejete', 'refuse', 'refus', 'motif'],
            'answer' => "Un rejet est toujours motivé : l'agent indique un motif clair qui s'affiche "
                ."avec la demande. Une demande rejetée ne peut plus être modifiée.",
        ],

        [
            'keywords' => ['modifier', 'corriger', 'annuler', 'changer', 'supprimer'],
            'answer' => "Une demande validée ou rejetée est définitive : plus aucune modification "
                ."n'est possible. Seule une demande déposée ou en cours de traitement peut évoluer.",
        ],

        [
            'keywords' => ['deposer', 'creer', 'nouvelle demande', 'comment faire', 'sinscrire'],
            'answer' => "Le dépôt se fait via l'API : POST /api/requests avec votre NPI (10 chiffres), "
                ."le type d'acte et le nombre de copies (1 à 5). La demande reçoit un identifiant "
                ."et le statut « déposée ».",
        ],

        [
            'keywords' => ['delai', 'temps', 'quand', 'duree', 'attente'],
            'answer' => "Aucun délai n'est garanti dans cette démonstration : la demande passe d'abord "
                ."en « en cours de traitement » par un agent, puis est validée ou rejetée.",
        ],

        [
            'keywords' => ['prix', 'cout', 'tarif', 'fcfa', 'payer', 'gratuit'],
            'answer' => "Cette démonstration ne gère pas les frais : elle se concentre sur le dépôt "
                ."et le suivi des demandes. Consultez le site de l'ANIP pour les tarifs officiels.",
        ],

        [
            'keywords' => ['valide', 'validation', 'approuve', 'accepte'],
            'answer' => "Une demande est validée après traitement par un agent. Une fois validée, "
                ."elle est définitive et n'évolue plus. Vous pouvez suivre son statut par NPI "
                ."depuis cette page.",
        ],
    ],
];
