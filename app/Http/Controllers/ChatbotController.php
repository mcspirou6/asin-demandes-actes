<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

/**
 * Assistant conversationnel (réponses prédéfinies, sans IA).
 *
 * Fonctionnement : la question est normalisée (minuscules, sans accents),
 * puis comparée aux mots-clés déclarés dans config/chatbot.php. L'entrée
 * qui a le plus de mots-clés correspondants gagne. Sans correspondance,
 * la réponse de repli est renvoyée (matched = false).
 */
class ChatbotController extends Controller
{
    public function answer(HttpRequest $httpRequest): JsonResponse
    {
        $validated = $httpRequest->validate([
            'q' => ['required', 'string', 'min:2', 'max:300'],
        ], [
            'q.required' => 'La question est obligatoire.',
            'q.min' => 'La question est trop courte.',
            'q.max' => 'La question est trop longue.',
        ]);

        $question = $this->normalize($validated['q']);

        // Score = nombre de mots-clés présents dans la question normalisée.
        $best = null;
        $bestScore = 0;

        foreach (config('chatbot.entries') as $entry) {
            $score = 0;
            foreach ($entry['keywords'] as $keyword) {
                if (str_contains($question, $this->normalize($keyword))) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        return response()->json([
            'matched' => $best !== null,
            'answer' => $best !== null ? $best['answer'] : config('chatbot.fallback'),
        ]);
    }

    /**
     * Normalisation : minuscules + suppression des accents, afin que
     * "résidence", "RESIDENCE" et "residence" produisent le même résultat.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'œ' => 'oe', 'ÿ' => 'y', 'ñ' => 'n',
        ];

        return strtr($text, $accents);
    }
}
