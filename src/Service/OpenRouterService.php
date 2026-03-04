<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterService
{
    public function __construct(
        private HttpClientInterface $http,
     ) {}

    /**
     * Retourne EXACTEMENT:
     * { "data": [ { "title":"", "author":"", "genre":"", "description":"" } ] }
     */
    public function generateBook(string $topic): array
    {
        $prompt = "
        Return ONLY valid JSON in this exact format:
        
        {
          \"data\": [
            {
              \"titre\": \"\",
              \"auteur\": \"\",
              \"description\": \"\",
              \"pages\": 0,
              \"category\": {
                \"name\": \"\",
                \"description\": \"\",
                \"image\": \"\"
              }
            }
          ]
        }
        
        Generate a book about $topic.
        ";

        $res = $this->http->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . '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',
                'Content-Type' => 'application/json',
                // OpenRouter recommande souvent ces headers (optionnel)
                'HTTP-Referer' => 'http://localhost', // ou ton domaine
                'X-Title' => 'Symfony Book Generator',
            ],
            'json' => [
                'model' => 'openai/gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a JSON API. Output ONLY JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ],
        ]);

        $payload = $res->toArray(false);

        // OpenRouter: contenu souvent dans choices[0].message.content
        $content = $payload['choices'][0]['message']['content'] ?? '';

        // Si modèle retourne tool_calls (ex: function calling), on prend arguments
        if (empty($content) && !empty($payload['choices'][0]['message']['tool_calls'][0]['function']['arguments'])) {
            $content = $payload['choices'][0]['message']['tool_calls'][0]['function']['arguments'];
        }

        // Extraire le JSON (au cas où il y aurait du texte autour)
        $text = (string) $content;
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            return ['data' => [[ 'title' => '', 'author' => '', 'genre' => '', 'description' => '' ]]];
        }

        $jsonText = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($jsonText, true);

        if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
            return ['data' => [[ 'title' => '', 'author' => '', 'genre' => '', 'description' => '' ]]];
        }

        // Normalisation stricte
        $book = $decoded['data'][0] ?? [];

        return [
            'data' => [[
                'titre' => (string)($book['titre'] ?? ''),
                'auteur' => (string)($book['auteur'] ?? ''),
                'description' => (string)($book['description'] ?? ''),
                'pages' => (int)($book['pages'] ?? 0),
                'category' => [
                    'name' => (string)($book['category']['name'] ?? ''),
                    'description' => (string)($book['category']['description'] ?? ''),
                    'image' => (string)($book['category']['image'] ?? ''),
                ]
            ]]
        ];
    }
}