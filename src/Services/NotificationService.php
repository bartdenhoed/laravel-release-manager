<?php

namespace Alegiac\ReleaseManager\Services;

use Alegiac\ReleaseManager\Contracts\NotificationDriverInterface;
use Alegiac\ReleaseManager\Drivers\TelegramDriver;
use Alegiac\ReleaseManager\Drivers\SlackDriver;
use Alegiac\ReleaseManager\Drivers\DiscordDriver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Notification Service
 * 
 * Handles sending release notifications to external services.
 * 
 * @package Alegiac\ReleaseManager\Services
 */
class NotificationService
{
    /**
     * Available notification drivers
     *
     * @var array<string, string>
     */
    protected array $drivers = [
        'telegram' => TelegramDriver::class,
        'slack' => SlackDriver::class,
        'discord' => DiscordDriver::class,
    ];

    /**
     * Send release notification
     *
     * @param string $version
     * @param array $analysis
     * @param string $changelogEntry
     * @param array $commits
     * @param string|null $driver
     * @return bool
     */
    public function sendReleaseNotification(
        string $version,
        array $analysis,
        string $changelogEntry,
        array $commits,
        ?string $driver = null
    ): bool {
        if (!$this->isNotificationsEnabled()) {
            return false;
        }

        $driver = $driver ?? $this->getDefaultDriver();
        
        if (!$this->isDriverEnabled($driver)) {
            Log::warning("Release Manager: Driver '{$driver}' is not enabled or configured");
            return false;
        }

        try {
            $driverInstance = $this->createDriver($driver);
            
            if (!$driverInstance) {
                Log::error("Release Manager: Could not create driver instance for '{$driver}'");
                return false;
            }

            $message = $this->buildMessage($version, $analysis, $changelogEntry, $commits);
            
            return $driverInstance->send($message);
            
        } catch (\Exception $e) {
            Log::error("Release Manager: Failed to send notification via '{$driver}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if notifications are enabled
     *
     * @return bool
     */
    public function isNotificationsEnabled(): bool
    {
        return Config::get('release-manager.notifications.enabled', false);
    }

    /**
     * Get default driver
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return Config::get('release-manager.notifications.default_driver', 'telegram');
    }

    /**
     * Check if driver is enabled
     *
     * @param string $driver
     * @return bool
     */
    public function isDriverEnabled(string $driver): bool
    {
        $config = Config::get("release-manager.notifications.drivers.{$driver}", []);
        
        if (empty($config)) {
            return false;
        }

        return $config['enabled'] ?? false;
    }

    /**
     * Get driver configuration
     *
     * @param string $driver
     * @return array
     */
    public function getDriverConfig(string $driver): array
    {
        return Config::get("release-manager.notifications.drivers.{$driver}", []);
    }

    /**
     * Create driver instance
     *
     * @param string $driver
     * @return NotificationDriverInterface|null
     */
    protected function createDriver(string $driver): ?NotificationDriverInterface
    {
        if (!isset($this->drivers[$driver])) {
            return null;
        }

        $driverClass = $this->drivers[$driver];
        $config = $this->getDriverConfig($driver);

        return new $driverClass($config);
    }

    /**
     * Build notification message
     *
     * @param string $version
     * @param array $analysis
     * @param string $changelogEntry
     * @param array $commits
     * @return array
     */
    protected function buildMessage(string $version, array $analysis, string $changelogEntry, array $commits): array
    {
        $template = Config::get('release-manager.notifications.template', []);
        
        $message = [
            'version' => $version,
            'release_type' => $this->getReleaseType($analysis),
            'commit_count' => count($commits),
            'changelog' => $this->formatChangelog($changelogEntry, $template),
            'timestamp' => Carbon::now()->toISOString(),
        ];

        // Add optional fields based on template configuration
        if ($template['include_release_type'] ?? true) {
            $message['release_type_label'] = $this->getReleaseTypeLabel($message['release_type']);
        }

        if ($template['include_commit_count'] ?? true) {
            $message['commit_count_label'] = $this->getCommitCountLabel($message['commit_count']);
        }

        return $message;
    }

    /**
     * Get release type from analysis
     *
     * @param array $analysis
     * @return string
     */
    protected function getReleaseType(array $analysis): string
    {
        if ($analysis['has_breaking'] ?? false) {
            return 'major';
        }

        if ($analysis['has_feat'] ?? false) {
            return 'minor';
        }

        return 'patch';
    }

    /**
     * Get release type label
     *
     * @param string $type
     * @return string
     */
    protected function getReleaseTypeLabel(string $type): string
    {
        return match ($type) {
            'major' => '🚨 Major Release',
            'minor' => '✨ Minor Release',
            'patch' => '🔧 Patch Release',
            default => '📦 Release',
        };
    }

    /**
     * Get commit count label
     *
     * @param int $count
     * @return string
     */
    protected function getCommitCountLabel(int $count): string
    {
        return match ($count) {
            1 => '1 commit',
            default => "{$count} commits",
        };
    }

    /**
     * Format changelog for notification
     *
     * @param string $changelogEntry
     * @param array $template
     * @return string|null
     */
    protected function formatChangelog(string $changelogEntry, array $template): ?string
    {
        if (!($template['include_changelog'] ?? true)) {
            return null;
        }

        $maxLines = $template['max_changelog_lines'] ?? 10;
        $lines = explode("\n", trim($changelogEntry));
        
        // Remove the version header line
        $lines = array_slice($lines, 1);
        
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[] = '...';
        }

        return implode("\n", array_filter($lines));
    }

    /**
     * Get available drivers
     *
     * @return array
     */
    public function getAvailableDrivers(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Get enabled drivers
     *
     * @return array
     */
    public function getEnabledDrivers(): array
    {
        $enabled = [];
        
        foreach ($this->getAvailableDrivers() as $driver) {
            if ($this->isDriverEnabled($driver)) {
                $enabled[] = $driver;
            }
        }

        return $enabled;
    }
}
