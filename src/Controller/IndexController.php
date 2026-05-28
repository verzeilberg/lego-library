<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig');
    }

    #[Route('/api/docs/api', name: 'app_api_docs')]
    public function apiDocs(): Response
    {
        $content = file_get_contents(__DIR__ . '/../../docs/api.md');
        return new Response(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>API Reference</title>'
            . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">'
            . '<style>body{max-width:960px;margin:2rem auto;padding:0 1rem;}'
            . 'h1,h2,h3{margin-top:2rem;}'
            . 'code{background:#f5f5f5;padding:.2em .4em;border-radius:3px;}'
            . 'pre{background:#f8f9fa;padding:1rem;border-radius:6px;overflow-x:auto;}</style></head>'
            . '<body class="container">'
            . '<div class="mb-4"><a href="/" class="btn btn-outline-secondary">&larr; Back to Home</a></div>'
            . $this->renderMarkdown($content)
            . '</body></html>'
        );
    }

    private function renderMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $lines = explode("\n", $text);
        $html = '';
        $inCode = false;
        $codeContent = '';

        foreach ($lines as $line) {
            if (str_starts_with($line, '```')) {
                if ($inCode) {
                    $html .= '<pre><code>' . $codeContent . '</code></pre>';
                    $codeContent = '';
                    $inCode = false;
                } else {
                    $inCode = true;
                }
                continue;
            }
            if ($inCode) {
                $codeContent .= $line . "\n";
                continue;
            }
            if (str_starts_with($line, '### ')) {
                $html .= '<h3>' . substr($line, 4) . '</h3>';
            } elseif (str_starts_with($line, '## ')) {
                $html .= '<h2>' . substr($line, 3) . '</h2>';
            } elseif (str_starts_with($line, '# ')) {
                $html .= '<h1>' . substr($line, 2) . '</h1>';
            } elseif (str_starts_with($line, '- **')) {
                $html .= '<li>' . substr($line, 2) . '</li>';
            } elseif (str_starts_with($line, '- ')) {
                $html .= '<li>' . substr($line, 2) . '</li>';
            } elseif (str_starts_with($line, '|')) {
                if (!str_contains($line, '---')) {
                    $html .= $line . "\n";
                }
            } elseif (trim($line) === '') {
                $html .= '</ul>';
            } else {
                $html .= '<p>' . $line . '</p>';
            }
        }

        // Basic link conversion
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html);

        return $html;
    }
}
