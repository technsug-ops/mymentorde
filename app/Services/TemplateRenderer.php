<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TemplateRenderer
{
    /**
     * Template body içindeki {{variable}} placeholder'larını replace eder.
     *
     * @param  string $template  — body_tr, body_de veya body_en içeriği
     * @param  array  $variables — ['student_name' => 'Ahmet', 'due_date' => '2026-03-15', ...]
     * @return string            — render edilmiş body
     */
    public function render(string $template, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function (array $matches) use ($variables): string {
                $key = $matches[1];

                if (!array_key_exists($key, $variables)) {
                    Log::debug('TemplateRenderer: bilinmeyen değişken.', ['key' => $key]);
                    return '';
                }

                // XSS koruması — çıktıyı HTML-encode et
                return htmlspecialchars(
                    (string) ($variables[$key] ?? ''),
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );
            },
            $template
        );
    }

    /**
     * MessageTemplate modelinden dil seçimi yapıp render eder.
     *
     * @param  \App\Models\MessageTemplate $template
     * @param  array                       $variables
     * @param  string                      $lang        — 'tr' | 'de' | 'en'
     * @return array{subject: string, body: string}
     */
    public function renderTemplate(\App\Models\MessageTemplate $template, array $variables, string $lang = 'tr'): array
    {
        $lang = in_array($lang, ['tr', 'de', 'en'], true) ? $lang : 'tr';

        $subjectField = "subject_{$lang}";
        $bodyField    = "body_{$lang}";

        // Fallback: yoksa TR
        $rawSubject = (string) ($template->{$subjectField} ?? $template->subject_tr ?? '');
        $rawBody    = (string) ($template->{$bodyField}    ?? $template->body_tr    ?? '');

        return [
            'subject' => $this->render($rawSubject, $variables),
            'body'    => $this->render($rawBody, $variables),
        ];
    }
}
