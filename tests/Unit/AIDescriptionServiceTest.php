<?php

use Alegiac\ReleaseManager\Services\AIDescriptionService;
use Illuminate\Support\Facades\Config;

describe('AIDescriptionService', function () {
    beforeEach(function () {
        Config::set('release-manager.ai_description', [
            'enabled' => true,
            'provider' => 'openai',
            'template' => 'default',
            'openai' => [
                'api_key' => 'test-key',
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 1000,
            ],
        ]);
    });

    it('checks if AI description is enabled', function () {
        $service = new AIDescriptionService();
        
        expect($service->isEnabled())->toBeTrue();
        
        Config::set('release-manager.ai_description.enabled', false);
        expect($service->isEnabled())->toBeFalse();
    });

    it('builds default prompt correctly', function () {
        $service = new AIDescriptionService();
        
        $commits = [
            'feat: add user authentication',
            'fix: resolve login bug',
        ];
        
        $analysis = [
            'has_breaking' => false,
            'has_feat' => true,
            'has_fix' => true,
            'by_type' => [
                'feat' => ['add user authentication'],
                'fix' => ['resolve login bug'],
            ],
        ];
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, $commits, $analysis, 'v1.1.0', 'minor');
        
        expect($prompt)->toContain('release minor (v1.1.0)');
        expect($prompt)->toContain('feat: add user authentication');
        expect($prompt)->toContain('fix: resolve login bug');
        expect($prompt)->toContain('COSA È CAMBIATO');
        expect($prompt)->toContain('IMPATTI LATO CODICE');
        expect($prompt)->toContain('IMPATTI LATO SISTEMA');
        expect($prompt)->toContain('IMPATTI LATO CLIENTE');
    });

    it('builds detailed prompt correctly', function () {
        Config::set('release-manager.ai_description.template', 'detailed');
        
        $service = new AIDescriptionService();
        
        $commits = [
            'feat: add payment processing',
            'feat!: change API response format',
            'fix: resolve memory leak',
        ];
        
        $analysis = [
            'has_breaking' => true,
            'has_feat' => true,
            'has_fix' => true,
            'by_type' => [
                'feat' => ['add payment processing', 'change API response format'],
                'fix' => ['resolve memory leak'],
            ],
        ];
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, $commits, $analysis, 'v2.0.0', 'major');
        
        expect($prompt)->toContain('release major (v2.0.0)');
        expect($prompt)->toContain('Breaking changes: SÌ');
        expect($prompt)->toContain('Features: add payment processing, change API response format');
        expect($prompt)->toContain('Bug fixes: resolve memory leak');
        expect($prompt)->toContain('Panoramica');
        expect($prompt)->toContain('Modifiche Tecniche');
        expect($prompt)->toContain('Impatto Sistema');
        expect($prompt)->toContain('Impatto Utente');
    });

    it('handles disabled AI description', function () {
        Config::set('release-manager.ai_description.enabled', false);
        
        $service = new AIDescriptionService();
        
        $result = $service->generateDescription([], [], 'v1.0.0', 'patch');
        
        expect($result['success'])->toBeFalse();
        expect($result['description'])->toBeNull();
        expect($result['error'])->toContain('not enabled');
    });

    it('handles missing API key for OpenAI', function () {
        Config::set('release-manager.ai_description.openai.api_key', null);
        
        $service = new AIDescriptionService();
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('callOpenAI');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, 'test prompt', []);
        
        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('API key not configured');
    });

    it('handles unsupported provider', function () {
        Config::set('release-manager.ai_description.provider', 'unsupported');
        
        $service = new AIDescriptionService();
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('callAI');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, 'test prompt');
        
        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Unsupported AI provider');
    });

    it('builds commit list correctly', function () {
        $service = new AIDescriptionService();
        
        $commits = [
            'feat: add new feature',
            'fix: resolve bug',
            'docs: update README',
        ];
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, $commits, [], 'v1.0.0', 'minor');
        
        expect($prompt)->toContain('- feat: add new feature');
        expect($prompt)->toContain('- fix: resolve bug');
        expect($prompt)->toContain('- docs: update README');
    });

    it('handles empty commits array', function () {
        $service = new AIDescriptionService();
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, [], [], 'v1.0.0', 'patch');
        
        expect($prompt)->toContain('Commits:');
        expect($prompt)->toContain('release patch (v1.0.0)');
    });

    it('includes breaking changes in detailed template', function () {
        Config::set('release-manager.ai_description.template', 'detailed');
        
        $service = new AIDescriptionService();
        
        $analysis = [
            'has_breaking' => true,
            'by_type' => [],
        ];
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildDetailedPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, [], $analysis, 'v2.0.0', 'major', '');
        
        expect($prompt)->toContain('Breaking changes: SÌ - Cambiamenti breaking');
    });

    it('includes features and fixes in detailed template', function () {
        Config::set('release-manager.ai_description.template', 'detailed');
        
        $service = new AIDescriptionService();
        
        $analysis = [
            'has_breaking' => false,
            'by_type' => [
                'feat' => ['add feature 1', 'add feature 2'],
                'fix' => ['fix bug 1'],
                'perf' => ['optimize query'],
            ],
        ];
        
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildDetailedPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, [], $analysis, 'v1.1.0', 'minor', '');
        
        expect($prompt)->toContain('Features: add feature 1, add feature 2');
        expect($prompt)->toContain('Bug fixes: fix bug 1');
        expect($prompt)->toContain('Performance: optimize query');
    });
});
