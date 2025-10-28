<?php

namespace Alegiac\ReleaseManager\Drivers;

use Alegiac\ReleaseManager\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Discord Notification Driver
 * 
 * Sends release notifications to Discord.
 * 
 * @package Alegiac\ReleaseManager\Drivers
 */
class DiscordDriver implements NotificationDriverInterface
{
    /**
     * Driver configuration
     *
     * @var array
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Send notification message
     *
     * @param array $message
     * @return bool
     */
    public function send(array $message): bool
    {
        if (!$this->isConfigured()) {
            Log::error('Release Manager: Discord driver not properly configured');
            return false;
        }

        try {
            $payload = $this->buildPayload($message);
            
            $response = Http::post($this->config['webhook_url'], $payload);

            if ($response->successful()) {
                Log::info("Release Manager: Discord notification sent successfully for version {$message['version']}");
                return true;
            }

            Log::error('Release Manager: Discord webhook error: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Release Manager: Discord notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if driver is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['webhook_url']);
    }

    /**
     * Get driver name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'discord';
    }

    /**
     * Build Discord payload
     *
     * @param array $message
     * @return array
     */
    protected function buildPayload(array $message): array
    {
        $payload = [
            'username' => $this->config['username'] ?? 'Release Bot',
            'embeds' => [
                [
                    'title' => "🚀 New Release: {$message['version']}",
                    'description' => $this->buildDescription($message),
                    'color' => $this->getColorForReleaseType($message['release_type']),
                    'fields' => [],
                    'footer' => [
                        'text' => 'Release Manager',
                    ],
                    'timestamp' => $message['timestamp'],
                ]
            ]
        ];

        // Add avatar URL if specified
        if (!empty($this->config['avatar_url'])) {
            $payload['avatar_url'] = $this->config['avatar_url'];
        }

        $embed = &$payload['embeds'][0];

        // Add release type field
        if (isset($message['release_type_label'])) {
            $embed['fields'][] = [
                'name' => 'Release Type',
                'value' => $message['release_type_label'],
                'inline' => true,
            ];
        }

        // Add commit count field
        if (isset($message['commit_count_label'])) {
            $embed['fields'][] = [
                'name' => 'Commits',
                'value' => $message['commit_count_label'],
                'inline' => true,
            ];
        }

        // Add changelog if available
        if (!empty($message['changelog'])) {
            $embed['fields'][] = [
                'name' => 'Changelog',
                'value' => $this->formatChangelogForDiscord($message['changelog']),
                'inline' => false,
            ];
        }

        return $payload;
    }

    /**
     * Build description for Discord embed
     *
     * @param array $message
     * @return string
     */
    protected function buildDescription(array $message): string
    {
        $description = "A new release has been created successfully!";
        
        if (isset($message['release_type_label'])) {
            $description .= "\n\n**{$message['release_type_label']}**";
        }
        
        if (isset($message['commit_count_label'])) {
            $description .= "\n📊 {$message['commit_count_label']}";
        }

        return $description;
    }

    /**
     * Get color for release type
     *
     * @param string $releaseType
     * @return int
     */
    protected function getColorForReleaseType(string $releaseType): int
    {
        return match ($releaseType) {
            'major' => 0xff0000, // Red
            'minor' => 0xffa500, // Orange
            'patch' => 0x00ff00, // Green
            default => 0x36a64f, // Default green
        };
    }

    /**
     * Format changelog for Discord
     *
     * @param string $changelog
     * @return string
     */
    protected function formatChangelogForDiscord(string $changelog): string
    {
        // Discord has a limit on embed field value length
        $maxLength = 1024;
        
        if (strlen($changelog) > $maxLength) {
            $changelog = substr($changelog, 0, $maxLength - 3) . '...';
        }

        // Escape Discord markdown
        $changelog = str_replace(['*', '_', '`'], ['\*', '\_', '\`'], $changelog);

        return $changelog;
    }
}
