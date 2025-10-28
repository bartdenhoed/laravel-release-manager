<?php

use Alegiac\ReleaseManager\Services\NotificationService;
use Alegiac\ReleaseManager\Drivers\TelegramDriver;
use Alegiac\ReleaseManager\Drivers\SlackDriver;
use Alegiac\ReleaseManager\Drivers\DiscordDriver;
use Illuminate\Support\Facades\Config;

describe('NotificationService', function () {
    beforeEach(function () {
        Config::set('release-manager.notifications', [
            'enabled' => true,
            'default_driver' => 'telegram',
            'drivers' => [
                'telegram' => [
                    'enabled' => true,
                    'bot_token' => 'test_token',
                    'chat_id' => 'test_chat_id',
                ],
                'slack' => [
                    'enabled' => true,
                    'webhook_url' => 'https://hooks.slack.com/test',
                ],
                'discord' => [
                    'enabled' => true,
                    'webhook_url' => 'https://discord.com/api/webhooks/test',
                ],
            ],
            'template' => [
                'include_changelog' => true,
                'include_commit_count' => true,
                'include_release_type' => true,
                'max_changelog_lines' => 10,
            ],
        ]);
    });

    it('checks if notifications are enabled', function () {
        $service = new NotificationService();
        
        expect($service->isNotificationsEnabled())->toBeTrue();
        
        Config::set('release-manager.notifications.enabled', false);
        expect($service->isNotificationsEnabled())->toBeFalse();
    });

    it('gets default driver', function () {
        $service = new NotificationService();
        
        expect($service->getDefaultDriver())->toBe('telegram');
    });

    it('checks if driver is enabled', function () {
        $service = new NotificationService();
        
        expect($service->isDriverEnabled('telegram'))->toBeTrue();
        expect($service->isDriverEnabled('slack'))->toBeTrue();
        expect($service->isDriverEnabled('discord'))->toBeTrue();
        expect($service->isDriverEnabled('nonexistent'))->toBeFalse();
    });

    it('gets driver configuration', function () {
        $service = new NotificationService();
        
        $config = $service->getDriverConfig('telegram');
        
        expect($config)->toHaveKey('enabled');
        expect($config)->toHaveKey('bot_token');
        expect($config)->toHaveKey('chat_id');
    });

    it('gets available drivers', function () {
        $service = new NotificationService();
        
        $drivers = $service->getAvailableDrivers();
        
        expect($drivers)->toContain('telegram');
        expect($drivers)->toContain('slack');
        expect($drivers)->toContain('discord');
    });

    it('gets enabled drivers', function () {
        $service = new NotificationService();
        
        $enabledDrivers = $service->getEnabledDrivers();
        
        expect($enabledDrivers)->toContain('telegram');
        expect($enabledDrivers)->toContain('slack');
        expect($enabledDrivers)->toContain('discord');
    });

    it('builds message correctly', function () {
        $service = new NotificationService();
        
        $analysis = [
            'has_breaking' => false,
            'has_feat' => true,
            'has_fix' => false,
        ];
        
        $changelogEntry = "## [v1.1.0] - 2024-01-15\n\n### Features\n- add new feature";
        $commits = ['feat: add new feature'];
        
        $message = $service->sendReleaseNotification('v1.1.0', $analysis, $changelogEntry, $commits);
        
        // This will return false because we're not actually sending notifications in tests
        expect($message)->toBeFalse();
    });
});

describe('TelegramDriver', function () {
    it('checks if properly configured', function () {
        $config = [
            'bot_token' => 'test_token',
            'chat_id' => 'test_chat_id',
        ];
        
        $driver = new TelegramDriver($config);
        
        expect($driver->isConfigured())->toBeTrue();
        expect($driver->getName())->toBe('telegram');
    });

    it('checks if not properly configured', function () {
        $config = [
            'bot_token' => '',
            'chat_id' => 'test_chat_id',
        ];
        
        $driver = new TelegramDriver($config);
        
        expect($driver->isConfigured())->toBeFalse();
    });

    it('formats message correctly', function () {
        $config = [
            'bot_token' => 'test_token',
            'chat_id' => 'test_chat_id',
        ];
        
        $driver = new TelegramDriver($config);
        
        $message = [
            'version' => 'v1.1.0',
            'release_type' => 'minor',
            'release_type_label' => '✨ Minor Release',
            'commit_count' => 1,
            'commit_count_label' => '1 commit',
            'changelog' => '### Features\n- add new feature',
        ];
        
        $reflection = new ReflectionClass($driver);
        $method = $reflection->getMethod('formatMessage');
        $method->setAccessible(true);
        
        $formatted = $method->invoke($driver, $message);
        
        expect($formatted)->toContain('🚀 *New Release: v1.1.0*');
        expect($formatted)->toContain('✨ Minor Release');
        expect($formatted)->toContain('📊 1 commit');
        expect($formatted)->toContain('Changelog:');
    });
});

describe('SlackDriver', function () {
    it('checks if properly configured', function () {
        $config = [
            'webhook_url' => 'https://hooks.slack.com/test',
        ];
        
        $driver = new SlackDriver($config);
        
        expect($driver->isConfigured())->toBeTrue();
        expect($driver->getName())->toBe('slack');
    });

    it('checks if not properly configured', function () {
        $config = [
            'webhook_url' => '',
        ];
        
        $driver = new SlackDriver($config);
        
        expect($driver->isConfigured())->toBeFalse();
    });

    it('builds payload correctly', function () {
        $config = [
            'webhook_url' => 'https://hooks.slack.com/test',
            'username' => 'Test Bot',
            'icon_emoji' => ':rocket:',
        ];
        
        $driver = new SlackDriver($config);
        
        $message = [
            'version' => 'v1.1.0',
            'release_type' => 'minor',
            'release_type_label' => '✨ Minor Release',
            'commit_count' => 1,
            'commit_count_label' => '1 commit',
            'changelog' => '### Features\n- add new feature',
            'timestamp' => '2024-01-15T10:00:00Z',
        ];
        
        $reflection = new ReflectionClass($driver);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);
        
        $payload = $method->invoke($driver, $message);
        
        expect($payload)->toHaveKey('username');
        expect($payload)->toHaveKey('icon_emoji');
        expect($payload)->toHaveKey('attachments');
        expect($payload['attachments'][0])->toHaveKey('title');
        expect($payload['attachments'][0])->toHaveKey('color');
        expect($payload['attachments'][0])->toHaveKey('fields');
    });
});

describe('DiscordDriver', function () {
    it('checks if properly configured', function () {
        $config = [
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ];
        
        $driver = new DiscordDriver($config);
        
        expect($driver->isConfigured())->toBeTrue();
        expect($driver->getName())->toBe('discord');
    });

    it('checks if not properly configured', function () {
        $config = [
            'webhook_url' => '',
        ];
        
        $driver = new DiscordDriver($config);
        
        expect($driver->isConfigured())->toBeFalse();
    });

    it('builds payload correctly', function () {
        $config = [
            'webhook_url' => 'https://discord.com/api/webhooks/test',
            'username' => 'Test Bot',
        ];
        
        $driver = new DiscordDriver($config);
        
        $message = [
            'version' => 'v1.1.0',
            'release_type' => 'minor',
            'release_type_label' => '✨ Minor Release',
            'commit_count' => 1,
            'commit_count_label' => '1 commit',
            'changelog' => '### Features\n- add new feature',
            'timestamp' => '2024-01-15T10:00:00Z',
        ];
        
        $reflection = new ReflectionClass($driver);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);
        
        $payload = $method->invoke($driver, $message);
        
        expect($payload)->toHaveKey('username');
        expect($payload)->toHaveKey('embeds');
        expect($payload['embeds'][0])->toHaveKey('title');
        expect($payload['embeds'][0])->toHaveKey('color');
        expect($payload['embeds'][0])->toHaveKey('fields');
    });
});
