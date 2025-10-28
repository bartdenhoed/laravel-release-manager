<?php

namespace Alegiac\ReleaseManager\Drivers;

use Alegiac\ReleaseManager\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Slack Notification Driver
 * 
 * Sends release notifications to Slack.
 * 
 * @package Alegiac\ReleaseManager\Drivers
 */
class SlackDriver implements NotificationDriverInterface
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
            Log::error('Release Manager: Slack driver not properly configured');
            return false;
        }

        try {
            $payload = $this->buildPayload($message);
            
            $response = Http::post($this->config['webhook_url'], $payload);

            if ($response->successful()) {
                Log::info("Release Manager: Slack notification sent successfully for version {$message['version']}");
                return true;
            }

            Log::error('Release Manager: Slack webhook error: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Release Manager: Slack notification failed: ' . $e->getMessage());
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
        return 'slack';
    }

    /**
     * Build Slack payload
     *
     * @param array $message
     * @return array
     */
    protected function buildPayload(array $message): array
    {
        $payload = [
            'username' => $this->config['username'] ?? 'Release Bot',
            'icon_emoji' => $this->config['icon_emoji'] ?? ':rocket:',
            'attachments' => [
                [
                    'color' => $this->getColorForReleaseType($message['release_type']),
                    'title' => "🚀 New Release: {$message['version']}",
                    'title_link' => $this->getReleaseUrl($message['version']),
                    'fields' => [],
                    'footer' => 'Release Manager',
                    'ts' => Carbon::now()->timestamp,
                ]
            ]
        ];

        // Add channel if specified
        if (!empty($this->config['channel'])) {
            $payload['channel'] = $this->config['channel'];
        }

        $attachment = &$payload['attachments'][0];

        // Add release type field
        if (isset($message['release_type_label'])) {
            $attachment['fields'][] = [
                'title' => 'Release Type',
                'value' => $message['release_type_label'],
                'short' => true,
            ];
        }

        // Add commit count field
        if (isset($message['commit_count_label'])) {
            $attachment['fields'][] = [
                'title' => 'Commits',
                'value' => $message['commit_count_label'],
                'short' => true,
            ];
        }

        // Add changelog if available
        if (!empty($message['changelog'])) {
            $attachment['fields'][] = [
                'title' => 'Changelog',
                'value' => $this->formatChangelogForSlack($message['changelog']),
                'short' => false,
            ];
        }

        return $payload;
    }

    /**
     * Get color for release type
     *
     * @param string $releaseType
     * @return string
     */
    protected function getColorForReleaseType(string $releaseType): string
    {
        return match ($releaseType) {
            'major' => 'danger',
            'minor' => 'warning',
            'patch' => 'good',
            default => '#36a64f',
        };
    }

    /**
     * Format changelog for Slack
     *
     * @param string $changelog
     * @return string
     */
    protected function formatChangelogForSlack(string $changelog): string
    {
        // Slack has a limit on attachment text length
        $maxLength = 2000;
        
        if (strlen($changelog) > $maxLength) {
            $changelog = substr($changelog, 0, $maxLength - 3) . '...';
        }

        return $changelog;
    }

    /**
     * Get release URL (can be customized)
     *
     * @param string $version
     * @return string|null
     */
    protected function getReleaseUrl(string $version): ?string
    {
        // This could be configured to point to GitHub releases, GitLab, etc.
        return null;
    }
}
