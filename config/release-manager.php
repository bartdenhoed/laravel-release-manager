<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Initial Version
    |--------------------------------------------------------------------------
    |
    | The default version to use when initializing the release system.
    | This follows semantic versioning: MAJOR.MINOR.PATCH
    |
    */
    'default_version' => env('RELEASE_MANAGER_DEFAULT_VERSION', '0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Changelog File
    |--------------------------------------------------------------------------
    |
    | The path to the changelog file relative to the project root.
    |
    */
    'changelog_file' => env('RELEASE_MANAGER_CHANGELOG_FILE', 'CHANGELOG.md'),

    /*
    |--------------------------------------------------------------------------
    | Tag Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix to use for git tags. Common values are 'v' or empty string.
    | Example: 'v' will create tags like v1.0.0, empty will create 1.0.0
    |
    */
    'tag_prefix' => env('RELEASE_MANAGER_TAG_PREFIX', 'v'),

    /*
    |--------------------------------------------------------------------------
    | Commit Message Template
    |--------------------------------------------------------------------------
    |
    | Template for the release commit message.
    | Available variables: {version}
    |
    */
    'commit_message' => env('RELEASE_MANAGER_COMMIT_MESSAGE', 'chore(release): {version}'),

    /*
    |--------------------------------------------------------------------------
    | Conventional Commit Types
    |--------------------------------------------------------------------------
    |
    | Mapping of conventional commit types to changelog sections.
    | You can customize these to match your workflow.
    |
    */
    'commit_types' => [
        'feat' => 'Features',
        'fix' => 'Bug Fixes',
        'docs' => 'Documentation',
        'style' => 'Styles',
        'refactor' => 'Code Refactoring',
        'perf' => 'Performance Improvements',
        'test' => 'Tests',
        'build' => 'Build System',
        'ci' => 'Continuous Integration',
        'chore' => 'Chores',
    ],

    /*
    |--------------------------------------------------------------------------
    | Versioning Rules
    |--------------------------------------------------------------------------
    |
    | Rules for automatic version bumping based on commit types.
    |
    */
    'versioning' => [
        'breaking_change_bumps_major' => true,
        'feat_bumps_minor' => true,
        'fix_bumps_patch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for release notifications to external services.
    |
    */
    'notifications' => [
        'enabled' => env('RELEASE_MANAGER_NOTIFICATIONS_ENABLED', false),
        'default_driver' => env('RELEASE_MANAGER_NOTIFICATION_DRIVER', 'telegram'),
        
        'drivers' => [
            'telegram' => [
                'enabled' => env('RELEASE_MANAGER_TELEGRAM_ENABLED', false),
                'bot_token' => env('RELEASE_MANAGER_TELEGRAM_BOT_TOKEN'),
                'chat_id' => env('RELEASE_MANAGER_TELEGRAM_CHAT_ID'),
                'parse_mode' => env('RELEASE_MANAGER_TELEGRAM_PARSE_MODE', 'Markdown'),
            ],
            
            'slack' => [
                'enabled' => env('RELEASE_MANAGER_SLACK_ENABLED', false),
                'webhook_url' => env('RELEASE_MANAGER_SLACK_WEBHOOK_URL'),
                'channel' => env('RELEASE_MANAGER_SLACK_CHANNEL'),
                'username' => env('RELEASE_MANAGER_SLACK_USERNAME', 'Release Bot'),
                'icon_emoji' => env('RELEASE_MANAGER_SLACK_ICON_EMOJI', ':rocket:'),
            ],
            
            'discord' => [
                'enabled' => env('RELEASE_MANAGER_DISCORD_ENABLED', false),
                'webhook_url' => env('RELEASE_MANAGER_DISCORD_WEBHOOK_URL'),
                'username' => env('RELEASE_MANAGER_DISCORD_USERNAME', 'Release Bot'),
                'avatar_url' => env('RELEASE_MANAGER_DISCORD_AVATAR_URL'),
            ],
        ],
        
        'template' => [
            'include_changelog' => env('RELEASE_MANAGER_INCLUDE_CHANGELOG', true),
            'include_commit_count' => env('RELEASE_MANAGER_INCLUDE_COMMIT_COUNT', true),
            'include_release_type' => env('RELEASE_MANAGER_INCLUDE_RELEASE_TYPE', true),
            'max_changelog_lines' => env('RELEASE_MANAGER_MAX_CHANGELOG_LINES', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Description
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-generated human-readable descriptions of changes.
    |
    */
    'ai_description' => [
        'enabled' => env('RELEASE_MANAGER_AI_DESCRIPTION_ENABLED', false),
        'provider' => env('RELEASE_MANAGER_AI_PROVIDER', 'openai'),
        'template' => env('RELEASE_MANAGER_AI_TEMPLATE', 'default'), // 'default' or 'detailed'
        
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('RELEASE_MANAGER_OPENAI_MODEL', 'gpt-3.5-turbo'),
            'max_tokens' => env('RELEASE_MANAGER_OPENAI_MAX_TOKENS', 1000),
        ],
        
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('RELEASE_MANAGER_ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
            'max_tokens' => env('RELEASE_MANAGER_ANTHROPIC_MAX_TOKENS', 1000),
        ],
        
        'ollama' => [
            'base_url' => env('RELEASE_MANAGER_OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model' => env('RELEASE_MANAGER_OLLAMA_MODEL', 'llama2'),
        ],
    ],
];

