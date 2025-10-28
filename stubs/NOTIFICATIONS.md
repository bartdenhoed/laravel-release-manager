# Release Manager Notifications Configuration

This file contains example environment variables for configuring release notifications.

## Enable Notifications

```bash
# Enable notifications globally
RELEASE_MANAGER_NOTIFICATIONS_ENABLED=true

# Set default driver (telegram, slack, discord)
RELEASE_MANAGER_NOTIFICATION_DRIVER=telegram
```

## Telegram Configuration

```bash
# Enable Telegram notifications
RELEASE_MANAGER_TELEGRAM_ENABLED=true

# Bot token from @BotFather
RELEASE_MANAGER_TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz

# Chat ID (can be a group or channel)
RELEASE_MANAGER_TELEGRAM_CHAT_ID=-1001234567890

# Parse mode (Markdown or HTML)
RELEASE_MANAGER_TELEGRAM_PARSE_MODE=Markdown
```

### Getting Telegram Bot Token

1. Message @BotFather on Telegram
2. Send `/newbot` command
3. Follow the instructions to create your bot
4. Copy the bot token to `RELEASE_MANAGER_TELEGRAM_BOT_TOKEN`

### Getting Chat ID

1. Add your bot to the group/channel
2. Send a message to the group/channel
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Look for the `chat.id` in the response

## Slack Configuration

```bash
# Enable Slack notifications
RELEASE_MANAGER_SLACK_ENABLED=true

# Webhook URL from Slack app
RELEASE_MANAGER_SLACK_WEBHOOK_URL=<your_slack_webhook_url>

# Channel to send notifications (optional)
RELEASE_MANAGER_SLACK_CHANNEL=#releases

# Bot username (optional)
RELEASE_MANAGER_SLACK_USERNAME=Release Bot

# Bot icon emoji (optional)
RELEASE_MANAGER_SLACK_ICON_EMOJI=:rocket:
```

### Creating Slack Webhook

1. Go to https://api.slack.com/apps
2. Create a new app or select existing one
3. Go to "Incoming Webhooks" section
4. Activate incoming webhooks
5. Click "Add New Webhook to Workspace"
6. Select channel and copy the webhook URL

## Discord Configuration

```bash
# Enable Discord notifications
RELEASE_MANAGER_DISCORD_ENABLED=true

# Webhook URL from Discord channel
RELEASE_MANAGER_DISCORD_WEBHOOK_URL=<your_discord_webhook_url>

# Bot username (optional)
RELEASE_MANAGER_DISCORD_USERNAME=Release Bot

# Bot avatar URL (optional)
RELEASE_MANAGER_DISCORD_AVATAR_URL=https://example.com/bot-avatar.png
```

### Creating Discord Webhook

1. Go to your Discord server
2. Right-click on the channel where you want notifications
3. Select "Edit Channel"
4. Go to "Integrations" tab
5. Click "Create Webhook"
6. Copy the webhook URL

## Notification Template Configuration

```bash
# Include changelog in notifications (default: true)
RELEASE_MANAGER_INCLUDE_CHANGELOG=true

# Include commit count (default: true)
RELEASE_MANAGER_INCLUDE_COMMIT_COUNT=true

# Include release type (default: true)
RELEASE_MANAGER_INCLUDE_RELEASE_TYPE=true

# Maximum changelog lines to include (default: 10)
RELEASE_MANAGER_MAX_CHANGELOG_LINES=10
```

## Example Notification Messages

### Telegram
```
🚀 *New Release: v1.2.0*

✨ Minor Release
📊 3 commits
📅 2024-01-15 14:30:00

*Changelog:*
```
### Features

- add payment gateway support
- improve error handling

### Bug Fixes

- fix timeout issue
```

🎉 Release created successfully!
```

### Slack
Rich attachment with:
- Title: "🚀 New Release: v1.2.0"
- Color: Orange (for minor release)
- Fields: Release Type, Commits, Changelog
- Footer: "Release Manager"

### Discord
Rich embed with:
- Title: "🚀 New Release: v1.2.0"
- Color: Orange (for minor release)
- Fields: Release Type, Commits, Changelog
- Footer: "Release Manager"
- Timestamp

## Troubleshooting

### Notifications not sending

1. Check if notifications are enabled:
   ```bash
   RELEASE_MANAGER_NOTIFICATIONS_ENABLED=true
   ```

2. Verify driver configuration:
   ```bash
   # For Telegram
   RELEASE_MANAGER_TELEGRAM_ENABLED=true
   RELEASE_MANAGER_TELEGRAM_BOT_TOKEN=your_token
   RELEASE_MANAGER_TELEGRAM_CHAT_ID=your_chat_id
   ```

3. Check Laravel logs for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Test notifications

You can test your configuration by running a dry-run release:

```bash
php artisan release --dry-run
```

This will show you what would be sent without actually creating a release.

### Multiple services

You can enable multiple notification services at once:

```bash
RELEASE_MANAGER_TELEGRAM_ENABLED=true
RELEASE_MANAGER_SLACK_ENABLED=true
RELEASE_MANAGER_DISCORD_ENABLED=true
```

All enabled services will receive notifications.
