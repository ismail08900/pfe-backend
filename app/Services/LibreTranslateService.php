<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LibreTranslateService
{
    public function translate($text, $source = 'en', $target = 'fr')
    {
        if (trim($text) === '') return $text;
        $response = Http::post('http://localhost:5000/translate', [
            'q' => $text,
            'source' => $source,
            'target' => $target,
        ]);
        if ($response->successful() && isset($response->json()['translatedText'])) {
            return $response->json()['translatedText'];
        }
        return $text;
    }
}
