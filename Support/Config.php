<?php

namespace Escalated\Plugins\Community\Support;

class Config
{
    const VERSION = '0.1.0';
    const SLUG = 'community';
    const CONFIG_DIR = __DIR__ . '/../config';
    const CATEGORIES_FILE = self::CONFIG_DIR . '/categories.json';
    const TOPICS_FILE = self::CONFIG_DIR . '/topics.json';
    const POSTS_FILE = self::CONFIG_DIR . '/posts.json';
    const VOTES_FILE = self::CONFIG_DIR . '/votes.json';
    const SUBSCRIPTIONS_FILE = self::CONFIG_DIR . '/subscriptions.json';

    /**
     * Read a JSON data file. Returns an empty array if the file does not exist.
     */
    public static function readJson(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Write data to a JSON file, creating the config directory if needed.
     */
    public static function writeJson(string $file, array $data): bool
    {
        if (!is_dir(self::CONFIG_DIR)) {
            mkdir(self::CONFIG_DIR, 0755, true);
        }

        $json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return file_put_contents($file, $json) !== false;
    }

    /**
     * Generate a unique ID with a prefix.
     */
    public static function generateId(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(8));
    }

    /**
     * Create a URL-safe slug from a string.
     */
    public static function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'untitled';
    }

    public static function onActivate(): void
    {
        if (!is_dir(self::CONFIG_DIR)) {
            mkdir(self::CONFIG_DIR, 0755, true);
        }

        $files = [
            self::CATEGORIES_FILE,
            self::TOPICS_FILE,
            self::POSTS_FILE,
            self::VOTES_FILE,
            self::SUBSCRIPTIONS_FILE,
        ];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                self::writeJson($file, []);
            }
        }

        // Create default categories if none exist
        $categories = self::readJson(self::CATEGORIES_FILE);
        if (empty($categories)) {
            $defaults = [
                [
                    'name'        => 'General Discussion',
                    'slug'        => 'general-discussion',
                    'description' => 'General topics and conversations',
                    'position'    => 0,
                ],
                [
                    'name'        => 'Feature Requests',
                    'slug'        => 'feature-requests',
                    'description' => 'Suggest and vote on new features',
                    'position'    => 1,
                ],
                [
                    'name'        => 'Bug Reports',
                    'slug'        => 'bug-reports',
                    'description' => 'Report issues and track fixes',
                    'position'    => 2,
                ],
            ];

            // We need CommunityService for save, but at activation time
            // we can bootstrap the categories directly
            foreach ($defaults as $cat) {
                $cat['id']          = self::generateId('cat_');
                $cat['topic_count'] = 0;
                $categories[]       = $cat;
            }

            self::writeJson(self::CATEGORIES_FILE, $categories);
        }

        if (function_exists('escalated_update_option')) {
            escalated_update_option('community_plugin_version', self::VERSION);
        }
    }

    public static function onDeactivate(): void
    {
        if (function_exists('escalated_broadcast')) {
            escalated_broadcast('admin', 'community.deactivated', [
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }
    }
}
