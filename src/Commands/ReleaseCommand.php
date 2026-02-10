<?php

namespace Alegiac\ReleaseManager\Commands;

use Illuminate\Console\Command;
use Alegiac\ReleaseManager\Services\ReleaseManager;
use Alegiac\ReleaseManager\Services\NotificationService;
use Alegiac\ReleaseManager\Services\AIDescriptionService;

/**
 * Release Command
 *
 * Artisan command for creating automated releases with changelog generation.
 *
 * @package Alegiac\ReleaseManager\Commands
 */
class ReleaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release
                            {--patch : Force patch version bump}
                            {--minor : Force minor version bump}
                            {--major : Force major version bump}
                            {--dry-run : Show what would happen without making changes}
                            {--no-confirm : Skip confirmation prompts}
                            {--no-notify : Skip sending notifications}
                            {--no-push : Skip pushing commit and tag to origin}
                            {--ai-description : Generate human-readable AI description of changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new release with automatic changelog generation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Laravel Auto Release');
        $this->line('');

        // Check if git repository is clean
        exec('git status --porcelain', $output);
        if (!empty($output) && !$this->option('dry-run')) {
            $this->error('Repository is not clean. Please commit or stash your changes first.');
            return Command::FAILURE;
        }

        // Get latest tag
        exec('git describe --tags --abbrev=0 2>/dev/null', $tagOutput);
        $latestTag = $tagOutput[0] ?? 'v0.0.0';

        $this->info("Latest tag: {$latestTag}");

        // Parse version
        $version = ltrim($latestTag, 'v');
        $versionParts = explode('.', $version);
        $major = (int)($versionParts[0] ?? 0);
        $minor = (int)($versionParts[1] ?? 0);
        $patch = (int)($versionParts[2] ?? 0);

        // Get commits since last tag
        exec("git log {$latestTag}..HEAD --pretty=format:'%s'", $commits);

        if (empty($commits)) {
            $this->error('No commits found since last tag.');
            return Command::FAILURE;
        }

        // Analyze commits
        $releaseManager = new ReleaseManager();
        $analysis = $releaseManager->analyzeCommits($commits);

        // Determine version bump
        if ($this->option('major')) {
            $releaseType = 'major';
        } elseif ($this->option('minor')) {
            $releaseType = 'minor';
        } elseif ($this->option('patch')) {
            $releaseType = 'patch';
        } else {
            $releaseType = $releaseManager->determineReleaseType($analysis);
        }

        // Bump version
        switch ($releaseType) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                $this->warn("Detected MAJOR changes (breaking changes)");
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                $this->info("Detected MINOR changes (new features)");
                break;
            case 'patch':
                $patch++;
                $this->info("Detected PATCH changes (bug fixes)");
                break;
        }

        $newVersion = "v{$major}.{$minor}.{$patch}";
        $this->info("New version: {$newVersion}");
        $this->line('');

        // Generate changelog
        $this->info('📝 Generating changelog...');
        $changelogEntry = $releaseManager->generateChangelog($newVersion, $analysis);

        // Generate AI description if requested
        $aiDescription = null;
        if ($this->option('ai-description')) {
            $aiDescription = $this->generateAIDescription($newVersion, $analysis, $commits, $releaseType);

            // Add AI description to changelog entry
            if ($aiDescription && $aiDescription['success']) {
                $changelogEntry = $this->addAIDescriptionToChangelog($changelogEntry, $aiDescription['description']);
            }
        }

        $this->line('');
        $this->line($changelogEntry);
        $this->line('');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes were made');
            return Command::SUCCESS;
        }

        // Confirm
        if (!$this->option('no-confirm')) {
            if (!$this->confirm("Proceed with release {$newVersion}?", true)) {
                $this->warn('Release cancelled');
                return Command::SUCCESS;
            }
        }

        // Update CHANGELOG.md
        $releaseManager->updateChangelog($changelogEntry);
        $this->info('✓ CHANGELOG.md updated');

        // Git operations
        exec('git add CHANGELOG.md');
        exec("git commit -m 'chore(release): {$newVersion}'");
        $this->info('✓ Changelog committed');

        exec("git tag -a '{$newVersion}' -m 'Release {$newVersion}'");
        $this->info('✓ Tag created');

        // Push commit and tag automatically
        if (!$this->option('no-push')) {
            exec('git rev-parse --abbrev-ref HEAD', $branchOutput);
            $currentBranch = $branchOutput[0] ?? 'HEAD';
            exec("git push origin {$currentBranch}");
            exec("git push origin {$newVersion}");
            $this->info('✓ Release pushed to origin');
        }

        // Send notifications if enabled and not skipped
        if (!$this->option('no-notify')) {
            $this->sendNotifications($newVersion, $analysis, $changelogEntry, $commits);
        }

        $this->line('');
        $this->info("✅ Release {$newVersion} created successfully!");
        $this->line('');
        if ($this->option('no-push')) {
            $this->comment('Push skipped (--no-push).');
        } else {
            $this->comment('Release commit and tag were pushed to origin.');
        }

        return Command::SUCCESS;
    }

    /**
     * Send release notifications
     *
     * @param string $version
     * @param array $analysis
     * @param string $changelogEntry
     * @param array $commits
     * @return void
     */
    protected function sendNotifications(string $version, array $analysis, string $changelogEntry, array $commits): void
    {
        try {
            $notificationService = new NotificationService();

            if (!$notificationService->isNotificationsEnabled()) {
                $this->line('');
                $this->comment('💬 Notifications are disabled');
                return;
            }

            $this->line('');
            $this->info('📢 Sending notifications...');

            $success = $notificationService->sendReleaseNotification(
                $version,
                $analysis,
                $changelogEntry,
                $commits
            );

            if ($success) {
                $enabledDrivers = $notificationService->getEnabledDrivers();
                $this->info('✓ Notifications sent to: ' . implode(', ', $enabledDrivers));
            } else {
                $this->warn('⚠ Failed to send notifications');
            }

        } catch (\Exception $e) {
            $this->warn('⚠ Notification error: ' . $e->getMessage());
        }
    }

    /**
     * Generate AI description of changes
     *
     * @param string $version
     * @param array $analysis
     * @param array $commits
     * @param string $releaseType
     * @return array|null
     */
    protected function generateAIDescription(string $version, array $analysis, array $commits, string $releaseType): ?array
    {
        try {
            $this->line('');
            $this->info('🤖 Generating AI description...');

            $aiService = new AIDescriptionService();
            $result = $aiService->generateDescription($commits, $analysis, $version, $releaseType);

            if ($result['success']) {
                $this->info('✓ AI description generated and added to changelog');
                return $result;
            } else {
                $this->warn('⚠ Failed to generate AI description: ' . $result['error']);
                return null;
            }

        } catch (\Exception $e) {
            $this->warn('⚠ AI description error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add AI description to changelog entry
     *
     * @param string $changelogEntry
     * @param string $aiDescription
     * @return string
     */
    protected function addAIDescriptionToChangelog(string $changelogEntry, string $aiDescription): string
    {
        // Add AI description section to the changelog
        $aiSection = "\n### AI Description\n\n" . $aiDescription . "\n";

        // Insert AI description before the end of the changelog entry
        return $changelogEntry . $aiSection;
    }
}
