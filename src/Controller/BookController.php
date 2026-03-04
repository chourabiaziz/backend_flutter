<?php

namespace App\Controller;

use App\Service\OpenRouterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BookController extends AbstractController
{
    #[Route('/generate', name: 'api_book_generate', methods: ['POST'])]
    public function generate(Request $request, OpenRouterService $openRouter): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $topic = (string)($body['topic'] ?? 'Any book');

        $data = $openRouter->generateBook($topic);

        return $this->json($data);
    }
}
