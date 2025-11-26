# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v1.2.0] - 2024-01-24

### Features

- Add AI description generation with --ai-description option
- Add support for OpenAI, Anthropic (Claude), and Ollama AI providers
- Add configurable AI description templates (default/detailed)
- Add human-readable change descriptions for stakeholders
- Add AIDescriptionService for managing AI-generated content

### Improvements

- Add backward compatibility for Laravel 8+ and PHP 8.0+
- Remove unused Process facade import
- Update test matrix to include Laravel 8 and PHP 8.0

### Documentation

- Add AI description configuration examples
- Update README with AI description setup and usage
- Add comprehensive AI provider configuration guide
- Update requirements to reflect Laravel 8+ and PHP 8.0+ support

## [v1.1.0] - 2024-01-24

### Features

- Add release notifications support for Telegram, Slack, and Discord
- Add NotificationService for managing external notifications
- Add configurable notification templates
- Add --no-notify option to skip notifications
- Add comprehensive notification documentation

### Documentation

- Add NOTIFICATIONS.md with setup guides for all supported services
- Update README with notification configuration examples
- Add troubleshooting guide for notification issues

## [v1.0.0] - 2024-01-24

### Features

- Initial release
- Laravel Artisan commands (`php artisan release` and `php artisan release:setup`)
- Automatic version bumping based on Conventional Commits
- Automatic CHANGELOG.md generation
- Breaking changes detection
- Support for all conventional commit types (feat, fix, docs, etc.)
- Dry-run mode for testing
- Standalone bash scripts option
- Git repository initialization
- GitHub repository creation (with gh CLI)
- Bitbucket repository linking
- Standard branches setup (main, develop, stage)
- Automatic push to remote
- Configurable initial version (default: 0.0.1)
- Full documentation with examples
- Complete test suite

