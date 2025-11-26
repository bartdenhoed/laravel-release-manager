<?php

namespace Alegiac\ReleaseManager\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * AI Description Service
 * 
 * Generates human-readable descriptions of changes using AI.
 * 
 * @package Alegiac\ReleaseManager\Services
 */
class AIDescriptionService
{
    /**
     * Generate AI description for release changes
     *
     * @param array $commits
     * @param array $analysis
     * @param string $version
     * @param string $releaseType
     * @return array
     */
    public function generateDescription(array $commits, array $analysis, string $version, string $releaseType): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'AI description is not enabled or configured'
            ];
        }

        try {
            $prompt = $this->buildPrompt($commits, $analysis, $version, $releaseType);
            $response = $this->callAI($prompt);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'description' => $response['description'],
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'description' => null,
                'error' => $response['error']
            ];

        } catch (\Exception $e) {
            Log::error('Release Manager: AI description generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'description' => null,
                'error' => 'Failed to generate AI description: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if AI description is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Config::get('release-manager.ai_description.enabled', false);
    }

    /**
     * Get AI provider configuration
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return Config::get('release-manager.ai_description', []);
    }

    /**
     * Build prompt for AI
     *
     * @param array $commits
     * @param array $analysis
     * @param string $version
     * @param string $releaseType
     * @return string
     */
    protected function buildPrompt(array $commits, array $analysis, string $version, string $releaseType): string
    {
        $commitList = implode("\n", array_map(function($commit) {
            return "- " . $commit;
        }, $commits));

        $template = Config::get('release-manager.ai_description.template', 'default');
        
        if ($template === 'detailed') {
            return $this->buildDetailedPrompt($commits, $analysis, $version, $releaseType, $commitList);
        }

        return $this->buildDefaultPrompt($commits, $analysis, $version, $releaseType, $commitList);
    }

    /**
     * Build default prompt
     *
     * @param array $commits
     * @param array $analysis
     * @param string $version
     * @param string $releaseType
     * @param string $commitList
     * @return string
     */
    protected function buildDefaultPrompt(array $commits, array $analysis, string $version, string $releaseType, string $commitList): string
    {
        return "Analizza questi commit per una release {$releaseType} ({$version}) e crea una descrizione umana professionale che spieghi:

1. COSA È CAMBIATO: Riassumi le modifiche principali in linguaggio chiaro
2. IMPATTI LATO CODICE: Quali parti del sistema sono state modificate
3. IMPATTI LATO SISTEMA: Effetti su performance, sicurezza, architettura
4. IMPATTI LATO CLIENTE: Cosa cambia per l'utente finale

Commits:
{$commitList}

Rispondi in italiano, in formato markdown, massimo 300 parole. Sii specifico e professionale.";
    }

    /**
     * Build detailed prompt
     *
     * @param array $commits
     * @param array $analysis
     * @param string $version
     * @param string $releaseType
     * @param string $commitList
     * @return string
     */
    protected function buildDetailedPrompt(array $commits, array $analysis, string $version, string $releaseType, string $commitList): string
    {
        $features = $analysis['by_type']['feat'] ?? [];
        $fixes = $analysis['by_type']['fix'] ?? [];
        $perf = $analysis['by_type']['perf'] ?? [];
        $breaking = $analysis['has_breaking'] ? 'SÌ - Cambiamenti breaking' : 'NO';

        return "Analizza questa release {$releaseType} ({$version}) e crea una descrizione dettagliata per stakeholder tecnici e non tecnici.

CONTESTO:
- Tipo release: {$releaseType}
- Breaking changes: {$breaking}
- Numero commit: " . count($commits) . "

COMMIT DETTAGLIATI:
{$commitList}

CATEGORIE IDENTIFICATE:
" . ($features ? "Features: " . implode(', ', $features) . "\n" : '') . "
" . ($fixes ? "Bug fixes: " . implode(', ', $fixes) . "\n" : '') . "
" . ($perf ? "Performance: " . implode(', ', $perf) . "\n" : '') . "

Crea una descrizione che includa:
1. **Panoramica**: Cosa è cambiato in termini semplici
2. **Modifiche Tecniche**: Dettagli per sviluppatori
3. **Impatto Sistema**: Effetti su architettura, performance, sicurezza
4. **Impatto Utente**: Cosa cambia per gli utenti finali
5. **Considerazioni**: Note importanti per deployment e manutenzione

Formato: Markdown, massimo 500 parole, italiano, professionale.";
    }

    /**
     * Call AI service
     *
     * @param string $prompt
     * @return array
     */
    protected function callAI(string $prompt): array
    {
        $config = $this->getConfig();
        $provider = $config['provider'] ?? 'openai';

        switch ($provider) {
            case 'openai':
                return $this->callOpenAI($prompt, $config);
            case 'anthropic':
                return $this->callAnthropic($prompt, $config);
            case 'ollama':
                return $this->callOllama($prompt, $config);
            default:
                return [
                    'success' => false,
                    'description' => null,
                    'error' => "Unsupported AI provider: {$provider}"
                ];
        }
    }

    /**
     * Call OpenAI API
     *
     * @param string $prompt
     * @param array $config
     * @return array
     */
    protected function callOpenAI(string $prompt, array $config): array
    {
        $apiKey = $config['openai']['api_key'] ?? null;
        $model = $config['openai']['model'] ?? 'gpt-3.5-turbo';
        $maxTokens = $config['openai']['max_tokens'] ?? 1000;

        if (!$apiKey) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'OpenAI API key not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $description = $data['choices'][0]['message']['content'] ?? null;

                return [
                    'success' => true,
                    'description' => $description,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'description' => null,
                'error' => 'OpenAI API error: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'OpenAI API call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call Anthropic API
     *
     * @param string $prompt
     * @param array $config
     * @return array
     */
    protected function callAnthropic(string $prompt, array $config): array
    {
        $apiKey = $config['anthropic']['api_key'] ?? null;
        $model = $config['anthropic']['model'] ?? 'claude-3-sonnet-20240229';
        $maxTokens = $config['anthropic']['max_tokens'] ?? 1000;

        if (!$apiKey) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'Anthropic API key not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $description = $data['content'][0]['text'] ?? null;

                return [
                    'success' => true,
                    'description' => $description,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'description' => null,
                'error' => 'Anthropic API error: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'Anthropic API call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call Ollama (local) API
     *
     * @param string $prompt
     * @param array $config
     * @return array
     */
    protected function callOllama(string $prompt, array $config): array
    {
        $baseUrl = $config['ollama']['base_url'] ?? 'http://localhost:11434';
        $model = $config['ollama']['model'] ?? 'llama2';

        try {
            $response = Http::post("{$baseUrl}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $description = $data['response'] ?? null;

                return [
                    'success' => true,
                    'description' => $description,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'description' => null,
                'error' => 'Ollama API error: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'description' => null,
                'error' => 'Ollama API call failed: ' . $e->getMessage()
            ];
        }
    }
}
