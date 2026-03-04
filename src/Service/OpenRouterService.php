<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $openrouterApiKey
    ) {}

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

Generate a book about: $topic
";

        $res = $this->http->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->openrouterApiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'https://backend-flutter-yizw.onrender.com',
                'X-Title' => 'Symfony Book Generator',
            ],
            'json' => [
                'model' => 'openai/gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a JSON API. Return only JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500
            ],
        ]);

        $payload = $res->toArray(false);

        $content = $payload['choices'][0]['message']['content'] ?? '';

        // extraire JSON
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false) {
            return ['data' => []];
        }

        $json = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (!isset($decoded['data'][0])) {
            return ['data' => []];
        }

        $book = $decoded['data'][0];

        return [
            'data' => [[
                'titre' => $book['titre'] ?? '',
                'auteur' => $book['auteur'] ?? '',
                'description' => $book['description'] ?? '',
                'pages' => $book['pages'] ?? 0,
                'category' => [
                    'name' => $book['category']['name'] ?? '',
                    'description' => $book['category']['description'] ?? '',
                    'image' => $book['category']['image'] ?? ''
                ]
            ]]
        ];
    }
}