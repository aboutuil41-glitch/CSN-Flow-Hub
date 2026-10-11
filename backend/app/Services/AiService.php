<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function analyzeFeedBack(array $feedback): array
    {
        $prompt = $this->buildPrompt($feedback);

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY'),
            [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 0.2
                ]
            ]
        );

        if (!$response->successful()) {
            dd($response->status(), $response->body(), $response->json());
        }

        $output = trim($response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');

        $output = preg_replace('/```json|```/', '', $output);

        $result = json_decode($output, true);



        if (!$result || !isset($result['simplified_feedback'])) {
            throw new \RuntimeException('Invalid AI response: ' . $output);
        }

        return $result;
    }

    private function buildPrompt(array $feedback): string
    {
        $message = $feedback['message'] ?? '';
        $type = $feedback['type'] ?? 'comment';

        return "
    You are an assistant for a project management platform.

    Analyze this client feedback and return ONLY valid JSON.

    Feedback type: {$type}
    Feedback message: {$message}

    Return this exact JSON structure:

    {
    \"simplified_feedback\": \"short simple version of the feedback\",
    \"sentiment\": \"positive | neutral | negative\",
    \"priority\": \"low | medium | high\",
    \"suggested_action\": \"what the team should do next\"
    }

    Rules:
    - Do not add markdown.
    - Do not add explanations.
    - Do not wrap response in ```json.
    - Keep answers short.
    ";
    }

}