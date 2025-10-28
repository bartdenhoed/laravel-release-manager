<?php

namespace Alegiac\ReleaseManager\Drivers;

use Alegiac\ReleaseManager\Contracts\NotificationDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Telegram Notification Driver
 * 
 * Sends release notifications to Telegram.
 * 
 * @package Alegiac\ReleaseManager\Drivers
 */
class TelegramDriver implements NotificationDriverInterface
{
    /**
     * Driver configuration
     *
     * @var array
     */
    protected array $config;

    /**
     * Telegram API base URL
     */
    protected const API_URL = 'https://api.telegram.org/bot';

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
            Log::error('Release Manager: Telegram driver not properly configured');
            return false;
        }

        try {
            $text = $this->formatMessage($message);
            
            $response = Http::post($this->getApiUrl('sendMessage'), [
                'chat_id' => $this->config['chat_id'],
                'text' => $text,
                'parse_mode' => $this->config['parse_mode'] ?? 'Markdown',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                Log::info("Release Manager: Telegram notification sent successfully for version {$message['version']}");
                return true;
            }

            Log::error('Release Manager: Telegram API error: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Release Manager: Telegram notification failed: ' . $e->getMessage());
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
        return !empty($this->config['bot_token']) && !empty($this->config['chat_id']);
    }

    /**
     * Get driver name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'telegram';
    }

    /**
     * Format message for Telegram
     *
     * @param array $message
     * @return string
     */
    protected function formatMessage(array $message): string
    {
        $text = "🚀 *New Release: {$message['version']}*\n\n";
        
        if (isset($message['release_type_label'])) {
            $text .= "{$message['release_type_label']}\n";
        }
        
        if (isset($message['commit_count_label'])) {
            $text .= "📊 {$message['commit_count_label']}\n";
        }
        
        $text .= "📅 " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";
        
        if (!empty($message['changelog'])) {
            $text .= "*Changelog:*\n";
            $text .= "```\n{$message['changelog']}\n```\n";
        }
        
        $text .= "\n🎉 Release created successfully!";
        
        return $text;
    }

    /**
     * Get Telegram API URL
     *
     * @param string $method
     * @return string
     */
    protected function getApiUrl(string $method): string
    {
        return self::API_URL . $this->config['bot_token'] . '/' . $method;
    }
}
