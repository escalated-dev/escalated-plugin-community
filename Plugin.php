<?php

/**
 * Community Forums Plugin for Escalated
 *
 * Adds a public community forum to the helpdesk with categories, topics,
 * replies, voting, moderation, and ticket-to-topic conversion. Customers
 * and agents can discuss issues, share solutions, and vote on helpful
 * content. Agents can pin, lock, move, and delete topics, and mark
 * replies as accepted answers.
 *
 * Data is persisted as JSON files in the plugin's config directory.
 */

// Prevent direct access
if (!defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

define('ESC_COMMUNITY_VERSION', '0.1.0');
define('ESC_COMMUNITY_SLUG', 'community');
define('ESC_COMMUNITY_CONFIG_DIR', __DIR__ . '/config');
define('ESC_COMMUNITY_CATEGORIES_FILE', ESC_COMMUNITY_CONFIG_DIR . '/categories.json');
define('ESC_COMMUNITY_TOPICS_FILE', ESC_COMMUNITY_CONFIG_DIR . '/topics.json');
define('ESC_COMMUNITY_POSTS_FILE', ESC_COMMUNITY_CONFIG_DIR . '/posts.json');
define('ESC_COMMUNITY_VOTES_FILE', ESC_COMMUNITY_CONFIG_DIR . '/votes.json');
define('ESC_COMMUNITY_SUBSCRIPTIONS_FILE', ESC_COMMUNITY_CONFIG_DIR . '/subscriptions.json');

// ---------------------------------------------------------------------------
// JSON storage helpers
// ---------------------------------------------------------------------------

/**
 * Read a JSON data file. Returns an empty array if the file does not exist.
 */
function esc_community_read_json(string $file): array
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
function esc_community_write_json(string $file, array $data): bool
{
    if (!is_dir(ESC_COMMUNITY_CONFIG_DIR)) {
        mkdir(ESC_COMMUNITY_CONFIG_DIR, 0755, true);
    }

    $json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return file_put_contents($file, $json) !== false;
}

/**
 * Generate a unique ID with a prefix.
 */
function esc_community_generate_id(string $prefix = ''): string
{
    return $prefix . bin2hex(random_bytes(8));
}

/**
 * Create a URL-safe slug from a string.
 */
function esc_community_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug ?: 'untitled';
}

// ---------------------------------------------------------------------------
// Category templates & CRUD
// ---------------------------------------------------------------------------

/**
 * Return an empty category template.
 *
 * Category structure:
 *   id          - Unique identifier (cat_ prefix)
 *   name        - Display name
 *   slug        - URL-safe slug
 *   description - Short description
 *   position    - Sort order (lower = higher)
 *   topic_count - Cached count of topics in this category
 */
function esc_community_category_template(): array
{
    return [
        'id'          => '',
        'name'        => '',
        'slug'        => '',
        'description' => '',
        'position'    => 0,
        'topic_count' => 0,
    ];
}

/**
 * Get all categories, sorted by position.
 */
function esc_community_get_categories(): array
{
    $categories = esc_community_read_json(ESC_COMMUNITY_CATEGORIES_FILE);

    usort($categories, function ($a, $b) {
        return ($a['position'] ?? 0) - ($b['position'] ?? 0);
    });

    return $categories;
}

/**
 * Get a single category by ID.
 */
function esc_community_get_category(string $id): ?array
{
    $categories = esc_community_get_categories();

    foreach ($categories as $cat) {
        if (($cat['id'] ?? '') === $id) {
            return $cat;
        }
    }

    return null;
}

/**
 * Get a single category by slug.
 */
function esc_community_get_category_by_slug(string $slug): ?array
{
    $categories = esc_community_get_categories();

    foreach ($categories as $cat) {
        if (($cat['slug'] ?? '') === $slug) {
            return $cat;
        }
    }

    return null;
}

/**
 * Save (create or update) a category. Returns the saved category.
 */
function esc_community_save_category(array $category): array
{
    $categories = esc_community_read_json(ESC_COMMUNITY_CATEGORIES_FILE);

    // Assign an ID if new
    if (empty($category['id'])) {
        $category['id'] = esc_community_generate_id('cat_');
    }

    // Auto-generate slug from name if not provided
    if (empty($category['slug']) && !empty($category['name'])) {
        $category['slug'] = esc_community_slugify($category['name']);
    }

    // Merge with template to ensure all keys exist
    $category = array_merge(esc_community_category_template(), $category);

    // Update existing or append new
    $found = false;
    foreach ($categories as $index => $existing) {
        if (($existing['id'] ?? '') === $category['id']) {
            $categories[$index] = $category;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $categories[] = $category;
    }

    esc_community_write_json(ESC_COMMUNITY_CATEGORIES_FILE, $categories);

    return $category;
}

/**
 * Delete a category by ID. Returns true if deleted.
 */
function esc_community_delete_category(string $id): bool
{
    $categories = esc_community_read_json(ESC_COMMUNITY_CATEGORIES_FILE);
    $filtered = array_filter($categories, function ($c) use ($id) {
        return ($c['id'] ?? '') !== $id;
    });

    if (count($filtered) === count($categories)) {
        return false;
    }

    return esc_community_write_json(ESC_COMMUNITY_CATEGORIES_FILE, $filtered);
}

/**
 * Reorder categories by providing an ordered array of category IDs.
 */
function esc_community_reorder_categories(array $orderedIds): bool
{
    $categories = esc_community_read_json(ESC_COMMUNITY_CATEGORIES_FILE);
    $map = [];

    foreach ($categories as $cat) {
        $map[$cat['id'] ?? ''] = $cat;
    }

    foreach ($orderedIds as $position => $catId) {
        if (isset($map[$catId])) {
            $map[$catId]['position'] = $position;
        }
    }

    return esc_community_write_json(ESC_COMMUNITY_CATEGORIES_FILE, array_values($map));
}

// ---------------------------------------------------------------------------
// Topic templates & CRUD
// ---------------------------------------------------------------------------

/**
 * Return an empty topic template.
 *
 * Topic structure:
 *   id              - Unique identifier (top_ prefix)
 *   category_id     - Parent category ID
 *   title           - Topic title
 *   slug            - URL-safe slug
 *   body            - Original post body (Markdown/plain text)
 *   author_id       - User or agent ID who created the topic
 *   author_type     - 'customer' | 'agent' | 'admin'
 *   author_name     - Display name of the author
 *   is_pinned       - Whether the topic is pinned to the top
 *   is_locked       - Whether replies are disabled
 *   is_answered     - Whether the topic has an accepted answer
 *   answer_post_id  - ID of the accepted answer post
 *   vote_count      - Net vote score (upvotes minus downvotes)
 *   reply_count     - Number of replies
 *   view_count      - Number of views
 *   source_ticket_id - If converted from a ticket, the original ticket ID
 *   created_at      - ISO-8601 creation timestamp
 *   updated_at      - ISO-8601 last update timestamp
 */
function esc_community_topic_template(): array
{
    return [
        'id'               => '',
        'category_id'      => '',
        'title'            => '',
        'slug'             => '',
        'body'             => '',
        'author_id'        => '',
        'author_type'      => 'customer',
        'author_name'      => '',
        'is_pinned'        => false,
        'is_locked'        => false,
        'is_answered'      => false,
        'answer_post_id'   => null,
        'vote_count'       => 0,
        'reply_count'      => 0,
        'view_count'       => 0,
        'source_ticket_id' => null,
        'created_at'       => '',
        'updated_at'       => '',
    ];
}

/**
 * Get all topics, optionally filtered.
 *
 * @param array $filters Optional filters:
 *   - category_id: Filter by category
 *   - sort: 'popular' | 'recent' | 'unanswered' (default: 'recent')
 *   - search: Search query (matches title and body)
 *   - page: Page number (1-based)
 *   - per_page: Items per page
 * @return array { topics: array, total: int, page: int, per_page: int }
 */
function esc_community_get_topics(array $filters = []): array
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);

    // Filter by category
    if (!empty($filters['category_id'])) {
        $catId = $filters['category_id'];
        $topics = array_filter($topics, function ($t) use ($catId) {
            return ($t['category_id'] ?? '') === $catId;
        });
    }

    // Search
    if (!empty($filters['search'])) {
        $query = strtolower($filters['search']);
        $topics = array_filter($topics, function ($t) use ($query) {
            $title = strtolower($t['title'] ?? '');
            $body  = strtolower($t['body'] ?? '');
            return strpos($title, $query) !== false || strpos($body, $query) !== false;
        });
    }

    // Filter unanswered
    $sort = $filters['sort'] ?? 'recent';
    if ($sort === 'unanswered') {
        $topics = array_filter($topics, function ($t) {
            return empty($t['is_answered']);
        });
    }

    $topics = array_values($topics);

    // Sort
    switch ($sort) {
        case 'popular':
            usort($topics, function ($a, $b) {
                // Pinned first, then by vote_count descending
                $pinA = !empty($a['is_pinned']) ? 1 : 0;
                $pinB = !empty($b['is_pinned']) ? 1 : 0;
                if ($pinA !== $pinB) return $pinB - $pinA;
                return ($b['vote_count'] ?? 0) - ($a['vote_count'] ?? 0);
            });
            break;

        case 'unanswered':
            usort($topics, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });
            break;

        case 'recent':
        default:
            usort($topics, function ($a, $b) {
                // Pinned first, then by updated_at descending
                $pinA = !empty($a['is_pinned']) ? 1 : 0;
                $pinB = !empty($b['is_pinned']) ? 1 : 0;
                if ($pinA !== $pinB) return $pinB - $pinA;
                return strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? '');
            });
            break;
    }

    // Pagination
    $total   = count($topics);
    $page    = max(1, (int) ($filters['page'] ?? 1));
    $perPage = max(1, (int) ($filters['per_page'] ?? 20));
    $offset  = ($page - 1) * $perPage;
    $paged   = array_slice($topics, $offset, $perPage);

    return [
        'topics'   => $paged,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
    ];
}

/**
 * Get a single topic by ID.
 */
function esc_community_get_topic(string $id): ?array
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);

    foreach ($topics as $topic) {
        if (($topic['id'] ?? '') === $id) {
            return $topic;
        }
    }

    return null;
}

/**
 * Get a single topic by slug.
 */
function esc_community_get_topic_by_slug(string $slug): ?array
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);

    foreach ($topics as $topic) {
        if (($topic['slug'] ?? '') === $slug) {
            return $topic;
        }
    }

    return null;
}

/**
 * Save (create or update) a topic. Returns the saved topic.
 */
function esc_community_save_topic(array $topic): array
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
    $now    = gmdate('Y-m-d\TH:i:s\Z');
    $isNew  = empty($topic['id']);

    if ($isNew) {
        $topic['id']         = esc_community_generate_id('top_');
        $topic['created_at'] = $now;
    }

    $topic['updated_at'] = $now;

    // Auto-generate slug
    if (empty($topic['slug']) && !empty($topic['title'])) {
        $topic['slug'] = esc_community_slugify($topic['title']);
    }

    // Merge with template
    $topic = array_merge(esc_community_topic_template(), $topic);

    // Update or append
    $found = false;
    foreach ($topics as $index => $existing) {
        if (($existing['id'] ?? '') === $topic['id']) {
            $topics[$index] = $topic;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $topics[] = $topic;
    }

    esc_community_write_json(ESC_COMMUNITY_TOPICS_FILE, $topics);

    // Update category topic count if this is a new topic
    if ($isNew && !empty($topic['category_id'])) {
        esc_community_recalculate_category_count($topic['category_id']);
    }

    return $topic;
}

/**
 * Delete a topic by ID and all its posts.
 */
function esc_community_delete_topic(string $id): bool
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
    $topic  = null;

    $filtered = array_filter($topics, function ($t) use ($id, &$topic) {
        if (($t['id'] ?? '') === $id) {
            $topic = $t;
            return false;
        }
        return true;
    });

    if (count($filtered) === count($topics)) {
        return false;
    }

    esc_community_write_json(ESC_COMMUNITY_TOPICS_FILE, $filtered);

    // Delete all posts for this topic
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    $remainingPosts = array_filter($posts, function ($p) use ($id) {
        return ($p['topic_id'] ?? '') !== $id;
    });
    esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $remainingPosts);

    // Delete votes for this topic and its posts
    $votes = esc_community_read_json(ESC_COMMUNITY_VOTES_FILE);
    $remainingVotes = array_filter($votes, function ($v) use ($id) {
        return ($v['topic_id'] ?? '') !== $id;
    });
    esc_community_write_json(ESC_COMMUNITY_VOTES_FILE, $remainingVotes);

    // Recalculate category count
    if (!empty($topic['category_id'])) {
        esc_community_recalculate_category_count($topic['category_id']);
    }

    return true;
}

/**
 * Increment view count for a topic.
 */
function esc_community_increment_views(string $topicId): void
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);

    foreach ($topics as $index => $topic) {
        if (($topic['id'] ?? '') === $topicId) {
            $topics[$index]['view_count'] = ($topic['view_count'] ?? 0) + 1;
            break;
        }
    }

    esc_community_write_json(ESC_COMMUNITY_TOPICS_FILE, $topics);
}

/**
 * Recalculate topic_count for a category.
 */
function esc_community_recalculate_category_count(string $categoryId): void
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
    $count  = 0;

    foreach ($topics as $topic) {
        if (($topic['category_id'] ?? '') === $categoryId) {
            $count++;
        }
    }

    $category = esc_community_get_category($categoryId);
    if ($category !== null) {
        $category['topic_count'] = $count;
        esc_community_save_category($category);
    }
}

// ---------------------------------------------------------------------------
// Post (reply) templates & CRUD
// ---------------------------------------------------------------------------

/**
 * Return an empty post template.
 *
 * Post structure:
 *   id          - Unique identifier (post_ prefix)
 *   topic_id    - Parent topic ID
 *   body        - Reply body text
 *   author_id   - User or agent ID
 *   author_type - 'customer' | 'agent' | 'admin'
 *   author_name - Display name of the author
 *   is_answer   - Whether this post is the accepted answer
 *   vote_count  - Net vote score
 *   created_at  - ISO-8601 creation timestamp
 *   updated_at  - ISO-8601 last update timestamp
 */
function esc_community_post_template(): array
{
    return [
        'id'          => '',
        'topic_id'    => '',
        'body'        => '',
        'author_id'   => '',
        'author_type' => 'customer',
        'author_name' => '',
        'is_answer'   => false,
        'vote_count'  => 0,
        'created_at'  => '',
        'updated_at'  => '',
    ];
}

/**
 * Get all posts for a topic, sorted by creation date.
 */
function esc_community_get_posts(string $topicId): array
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);

    $filtered = array_filter($posts, function ($p) use ($topicId) {
        return ($p['topic_id'] ?? '') === $topicId;
    });

    $filtered = array_values($filtered);

    // Sort: accepted answer first, then by created_at ascending
    usort($filtered, function ($a, $b) {
        // Answer floats to top (but after original post, which is first)
        $ansA = !empty($a['is_answer']) ? 1 : 0;
        $ansB = !empty($b['is_answer']) ? 1 : 0;
        if ($ansA !== $ansB) return $ansB - $ansA;

        return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
    });

    return $filtered;
}

/**
 * Get a single post by ID.
 */
function esc_community_get_post(string $id): ?array
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);

    foreach ($posts as $post) {
        if (($post['id'] ?? '') === $id) {
            return $post;
        }
    }

    return null;
}

/**
 * Save (create or update) a post. Returns the saved post.
 */
function esc_community_save_post(array $post): array
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    $now   = gmdate('Y-m-d\TH:i:s\Z');
    $isNew = empty($post['id']);

    if ($isNew) {
        $post['id']         = esc_community_generate_id('post_');
        $post['created_at'] = $now;
    }

    $post['updated_at'] = $now;

    // Merge with template
    $post = array_merge(esc_community_post_template(), $post);

    // Update or append
    $found = false;
    foreach ($posts as $index => $existing) {
        if (($existing['id'] ?? '') === $post['id']) {
            $posts[$index] = $post;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $posts[] = $post;
    }

    esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $posts);

    // Update reply count on the parent topic
    if ($isNew && !empty($post['topic_id'])) {
        esc_community_recalculate_reply_count($post['topic_id']);

        // Update topic's updated_at to bubble it up in "recent" sort
        $topic = esc_community_get_topic($post['topic_id']);
        if ($topic !== null) {
            $topic['updated_at'] = $now;
            esc_community_save_topic($topic);
        }

        // Fire action for new post notification
        if (function_exists('escalated_do_action')) {
            escalated_do_action('community.post.created', $post);
        }
    }

    return $post;
}

/**
 * Delete a post by ID.
 */
function esc_community_delete_post(string $id): bool
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    $post  = null;

    $filtered = array_filter($posts, function ($p) use ($id, &$post) {
        if (($p['id'] ?? '') === $id) {
            $post = $p;
            return false;
        }
        return true;
    });

    if (count($filtered) === count($posts)) {
        return false;
    }

    esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $filtered);

    // Recalculate reply count
    if (!empty($post['topic_id'])) {
        esc_community_recalculate_reply_count($post['topic_id']);

        // If this was the accepted answer, un-mark the topic
        if (!empty($post['is_answer'])) {
            $topic = esc_community_get_topic($post['topic_id']);
            if ($topic !== null && ($topic['answer_post_id'] ?? '') === $id) {
                $topic['is_answered']    = false;
                $topic['answer_post_id'] = null;
                esc_community_save_topic($topic);
            }
        }
    }

    // Delete votes for this post
    $votes = esc_community_read_json(ESC_COMMUNITY_VOTES_FILE);
    $remainingVotes = array_filter($votes, function ($v) use ($id) {
        return ($v['target_id'] ?? '') !== $id;
    });
    esc_community_write_json(ESC_COMMUNITY_VOTES_FILE, $remainingVotes);

    return true;
}

/**
 * Recalculate reply_count for a topic.
 */
function esc_community_recalculate_reply_count(string $topicId): void
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    $count = 0;

    foreach ($posts as $post) {
        if (($post['topic_id'] ?? '') === $topicId) {
            $count++;
        }
    }

    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
    foreach ($topics as $index => $topic) {
        if (($topic['id'] ?? '') === $topicId) {
            $topics[$index]['reply_count'] = $count;
            break;
        }
    }

    esc_community_write_json(ESC_COMMUNITY_TOPICS_FILE, $topics);
}

/**
 * Mark a post as the accepted answer for its topic.
 */
function esc_community_mark_answer(string $postId): ?array
{
    $post = esc_community_get_post($postId);
    if ($post === null) {
        return null;
    }

    $topicId = $post['topic_id'] ?? '';
    if (empty($topicId)) {
        return null;
    }

    // Un-mark any existing answer on this topic
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    foreach ($posts as $index => $p) {
        if (($p['topic_id'] ?? '') === $topicId && !empty($p['is_answer'])) {
            $posts[$index]['is_answer'] = false;
        }
        if (($p['id'] ?? '') === $postId) {
            $posts[$index]['is_answer'] = true;
        }
    }
    esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $posts);

    // Update the topic
    $topic = esc_community_get_topic($topicId);
    if ($topic !== null) {
        $topic['is_answered']    = true;
        $topic['answer_post_id'] = $postId;
        esc_community_save_topic($topic);
    }

    return esc_community_get_post($postId);
}

/**
 * Unmark the accepted answer for a topic.
 */
function esc_community_unmark_answer(string $topicId): void
{
    $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
    foreach ($posts as $index => $p) {
        if (($p['topic_id'] ?? '') === $topicId && !empty($p['is_answer'])) {
            $posts[$index]['is_answer'] = false;
        }
    }
    esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $posts);

    $topic = esc_community_get_topic($topicId);
    if ($topic !== null) {
        $topic['is_answered']    = false;
        $topic['answer_post_id'] = null;
        esc_community_save_topic($topic);
    }
}

// ---------------------------------------------------------------------------
// Vote tracking
// ---------------------------------------------------------------------------

/**
 * Vote structure:
 *   id         - Unique vote ID
 *   user_id    - The user who voted
 *   target_type - 'topic' | 'post'
 *   target_id  - The topic or post ID
 *   topic_id   - The parent topic ID (for cascade deletion)
 *   direction  - 'up' | 'down'
 *   created_at - ISO-8601 timestamp
 */

/**
 * Cast or change a vote. Returns the resulting vote_count on the target.
 *
 * If the user already voted in the same direction, the vote is removed (toggle).
 * If the user voted in the opposite direction, the vote is changed.
 *
 * @param  string $userId     User ID
 * @param  string $targetType 'topic' | 'post'
 * @param  string $targetId   Topic or post ID
 * @param  string $direction  'up' | 'down'
 * @return int    New vote_count on the target
 */
function esc_community_vote(string $userId, string $targetType, string $targetId, string $direction): int
{
    $votes = esc_community_read_json(ESC_COMMUNITY_VOTES_FILE);

    // Find the user's existing vote on this target
    $existingIndex = null;
    foreach ($votes as $index => $vote) {
        if (
            ($vote['user_id'] ?? '') === $userId &&
            ($vote['target_type'] ?? '') === $targetType &&
            ($vote['target_id'] ?? '') === $targetId
        ) {
            $existingIndex = $index;
            break;
        }
    }

    // Determine the parent topic_id for cascade reference
    $topicId = '';
    if ($targetType === 'topic') {
        $topicId = $targetId;
    } else {
        $post = esc_community_get_post($targetId);
        $topicId = $post['topic_id'] ?? '';
    }

    if ($existingIndex !== null) {
        $existingDirection = $votes[$existingIndex]['direction'] ?? '';

        if ($existingDirection === $direction) {
            // Toggle off: remove the vote
            array_splice($votes, $existingIndex, 1);
        } else {
            // Change direction
            $votes[$existingIndex]['direction']  = $direction;
            $votes[$existingIndex]['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
        }
    } else {
        // New vote
        $votes[] = [
            'id'          => esc_community_generate_id('vote_'),
            'user_id'     => $userId,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'topic_id'    => $topicId,
            'direction'   => $direction,
            'created_at'  => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    esc_community_write_json(ESC_COMMUNITY_VOTES_FILE, $votes);

    // Recalculate vote count on the target
    $voteCount = esc_community_calculate_vote_count($targetType, $targetId, $votes);

    // Persist the new vote_count on the target
    if ($targetType === 'topic') {
        $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
        foreach ($topics as $idx => $t) {
            if (($t['id'] ?? '') === $targetId) {
                $topics[$idx]['vote_count'] = $voteCount;
                break;
            }
        }
        esc_community_write_json(ESC_COMMUNITY_TOPICS_FILE, $topics);
    } else {
        $posts = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);
        foreach ($posts as $idx => $p) {
            if (($p['id'] ?? '') === $targetId) {
                $posts[$idx]['vote_count'] = $voteCount;
                break;
            }
        }
        esc_community_write_json(ESC_COMMUNITY_POSTS_FILE, $posts);
    }

    return $voteCount;
}

/**
 * Get the current user's vote on a target, or null if not voted.
 */
function esc_community_get_user_vote(string $userId, string $targetType, string $targetId): ?string
{
    $votes = esc_community_read_json(ESC_COMMUNITY_VOTES_FILE);

    foreach ($votes as $vote) {
        if (
            ($vote['user_id'] ?? '') === $userId &&
            ($vote['target_type'] ?? '') === $targetType &&
            ($vote['target_id'] ?? '') === $targetId
        ) {
            return $vote['direction'] ?? null;
        }
    }

    return null;
}

/**
 * Calculate the net vote count for a target from the votes array.
 */
function esc_community_calculate_vote_count(string $targetType, string $targetId, array $votes = null): int
{
    if ($votes === null) {
        $votes = esc_community_read_json(ESC_COMMUNITY_VOTES_FILE);
    }

    $count = 0;
    foreach ($votes as $vote) {
        if (
            ($vote['target_type'] ?? '') === $targetType &&
            ($vote['target_id'] ?? '') === $targetId
        ) {
            $count += ($vote['direction'] === 'up') ? 1 : -1;
        }
    }

    return $count;
}

// ---------------------------------------------------------------------------
// Topic search
// ---------------------------------------------------------------------------

/**
 * Search topics by query string. Returns matching topics with relevance.
 *
 * @param  string $query   Search query
 * @param  int    $limit   Maximum results (default 20)
 * @return array           Array of matching topics
 */
function esc_community_search_topics(string $query, int $limit = 20): array
{
    if (empty(trim($query))) {
        return [];
    }

    $result = esc_community_get_topics([
        'search'   => $query,
        'per_page' => $limit,
        'sort'     => 'popular',
    ]);

    return $result['topics'] ?? [];
}

// ---------------------------------------------------------------------------
// Moderation functions
// ---------------------------------------------------------------------------

/**
 * Pin or unpin a topic.
 */
function esc_community_pin_topic(string $topicId, bool $pin = true): ?array
{
    $topic = esc_community_get_topic($topicId);
    if ($topic === null) return null;

    $topic['is_pinned'] = $pin;
    return esc_community_save_topic($topic);
}

/**
 * Lock or unlock a topic.
 */
function esc_community_lock_topic(string $topicId, bool $lock = true): ?array
{
    $topic = esc_community_get_topic($topicId);
    if ($topic === null) return null;

    $topic['is_locked'] = $lock;
    return esc_community_save_topic($topic);
}

/**
 * Move a topic to a different category.
 */
function esc_community_move_topic(string $topicId, string $newCategoryId): ?array
{
    $topic = esc_community_get_topic($topicId);
    if ($topic === null) return null;

    $oldCategoryId = $topic['category_id'] ?? '';
    $topic['category_id'] = $newCategoryId;
    $saved = esc_community_save_topic($topic);

    // Recalculate counts on both categories
    if ($oldCategoryId) {
        esc_community_recalculate_category_count($oldCategoryId);
    }
    esc_community_recalculate_category_count($newCategoryId);

    return $saved;
}

// ---------------------------------------------------------------------------
// Convert ticket to topic
// ---------------------------------------------------------------------------

/**
 * Convert a ticket into a community forum topic.
 *
 * @param  array  $ticket     Ticket data with id, subject, body, customer_name, etc.
 * @param  string $categoryId Target category ID
 * @return array              The created topic
 */
function esc_community_convert_ticket_to_topic(array $ticket, string $categoryId): array
{
    $topic = esc_community_save_topic([
        'category_id'      => $categoryId,
        'title'            => $ticket['subject'] ?? $ticket['title'] ?? 'Untitled',
        'body'             => $ticket['body'] ?? $ticket['description'] ?? '',
        'author_id'        => $ticket['customer_id'] ?? $ticket['author_id'] ?? '',
        'author_type'      => 'customer',
        'author_name'      => $ticket['customer_name'] ?? $ticket['author_name'] ?? 'Customer',
        'source_ticket_id' => $ticket['id'] ?? null,
    ]);

    // Broadcast event
    if (function_exists('escalated_broadcast')) {
        escalated_broadcast('community', 'topic.converted_from_ticket', [
            'topic_id'  => $topic['id'],
            'ticket_id' => $ticket['id'] ?? '',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }

    return $topic;
}

/**
 * Convert a topic back to a ticket.
 *
 * @param  string $topicId The topic to convert
 * @return array           Ticket-like data structure for the platform to create
 */
function esc_community_convert_topic_to_ticket(string $topicId): ?array
{
    $topic = esc_community_get_topic($topicId);
    if ($topic === null) return null;

    return [
        'subject'       => $topic['title'] ?? 'Untitled',
        'body'          => $topic['body'] ?? '',
        'customer_name' => $topic['author_name'] ?? '',
        'customer_id'   => $topic['author_id'] ?? '',
        'channel'       => 'community',
        'source'        => 'community_topic',
        'source_id'     => $topicId,
        'metadata'      => [
            'community_topic_id' => $topicId,
            'community_category' => $topic['category_id'] ?? '',
        ],
    ];
}

// ---------------------------------------------------------------------------
// Subscriptions (for agent notifications on new posts)
// ---------------------------------------------------------------------------

/**
 * Subscribe an agent to a topic for notifications.
 */
function esc_community_subscribe(string $agentId, string $topicId): bool
{
    $subs = esc_community_read_json(ESC_COMMUNITY_SUBSCRIPTIONS_FILE);

    // Check if already subscribed
    foreach ($subs as $sub) {
        if (($sub['agent_id'] ?? '') === $agentId && ($sub['topic_id'] ?? '') === $topicId) {
            return true; // Already subscribed
        }
    }

    $subs[] = [
        'id'         => esc_community_generate_id('sub_'),
        'agent_id'   => $agentId,
        'topic_id'   => $topicId,
        'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];

    return esc_community_write_json(ESC_COMMUNITY_SUBSCRIPTIONS_FILE, $subs);
}

/**
 * Unsubscribe an agent from a topic.
 */
function esc_community_unsubscribe(string $agentId, string $topicId): bool
{
    $subs = esc_community_read_json(ESC_COMMUNITY_SUBSCRIPTIONS_FILE);

    $filtered = array_filter($subs, function ($s) use ($agentId, $topicId) {
        return !(($s['agent_id'] ?? '') === $agentId && ($s['topic_id'] ?? '') === $topicId);
    });

    if (count($filtered) === count($subs)) {
        return false; // Was not subscribed
    }

    return esc_community_write_json(ESC_COMMUNITY_SUBSCRIPTIONS_FILE, $filtered);
}

/**
 * Get all agent IDs subscribed to a topic.
 */
function esc_community_get_subscribers(string $topicId): array
{
    $subs = esc_community_read_json(ESC_COMMUNITY_SUBSCRIPTIONS_FILE);
    $agentIds = [];

    foreach ($subs as $sub) {
        if (($sub['topic_id'] ?? '') === $topicId) {
            $agentIds[] = $sub['agent_id'];
        }
    }

    return $agentIds;
}

// ---------------------------------------------------------------------------
// Community statistics
// ---------------------------------------------------------------------------

/**
 * Get community-wide statistics.
 */
function esc_community_get_stats(): array
{
    $topics = esc_community_read_json(ESC_COMMUNITY_TOPICS_FILE);
    $posts  = esc_community_read_json(ESC_COMMUNITY_POSTS_FILE);

    // Unique authors across topics and posts
    $authorIds = [];
    foreach ($topics as $t) {
        if (!empty($t['author_id'])) {
            $authorIds[$t['author_id']] = true;
        }
    }
    foreach ($posts as $p) {
        if (!empty($p['author_id'])) {
            $authorIds[$p['author_id']] = true;
        }
    }

    // Topics created this week
    $weekAgo = gmdate('Y-m-d\TH:i:s\Z', strtotime('-7 days'));
    $topicsThisWeek = 0;
    foreach ($topics as $t) {
        if (($t['created_at'] ?? '') >= $weekAgo) {
            $topicsThisWeek++;
        }
    }

    // Answered topics
    $answeredCount = 0;
    foreach ($topics as $t) {
        if (!empty($t['is_answered'])) {
            $answeredCount++;
        }
    }

    return [
        'total_topics'     => count($topics),
        'total_posts'      => count($posts),
        'active_users'     => count($authorIds),
        'topics_this_week' => $topicsThisWeek,
        'answered_topics'  => $answeredCount,
    ];
}

// ---------------------------------------------------------------------------
// Page registration: public community forum
// ---------------------------------------------------------------------------

escalated_register_page('/community', 'CommunityForum', [
    'title'  => 'Community',
    'public' => true,
    'props'  => [
        'pluginSlug' => ESC_COMMUNITY_SLUG,
    ],
]);

// ---------------------------------------------------------------------------
// Page registration: admin community management
// ---------------------------------------------------------------------------

escalated_register_page('/admin/community', 'CommunityAdmin', [
    'title'      => 'Community Management',
    'capability' => 'manage_settings',
    'props'      => [
        'pluginSlug' => ESC_COMMUNITY_SLUG,
    ],
]);

// ---------------------------------------------------------------------------
// Menu items
// ---------------------------------------------------------------------------

// Agent sidebar menu item
escalated_register_menu_item([
    'id'    => 'community-forum',
    'label' => 'Community',
    'icon'  => 'chat-bubble-left-right',
    'route' => '/community',
    'order' => 40,
]);

// Admin sidebar menu item
escalated_register_menu_item([
    'id'         => 'community-admin',
    'label'      => 'Community',
    'icon'       => 'chat-bubble-left-right',
    'route'      => '/admin/community',
    'parent'     => 'admin-settings',
    'order'      => 50,
    'capability' => 'manage_settings',
]);

// ---------------------------------------------------------------------------
// Page component: community forum & admin
// ---------------------------------------------------------------------------

escalated_add_page_component('community', 'main', [
    'component' => 'CommunityForum',
    'props'     => [
        'pluginSlug' => ESC_COMMUNITY_SLUG,
    ],
    'order' => 10,
]);

escalated_add_page_component('admin.community', 'main', [
    'component' => 'CommunityAdmin',
    'props'     => [
        'pluginSlug' => ESC_COMMUNITY_SLUG,
    ],
    'order' => 10,
]);

// ---------------------------------------------------------------------------
// Filter: ticket.actions -- add "Convert to Community Topic" action
// ---------------------------------------------------------------------------

escalated_add_filter('ticket.actions', function (array $actions, $ticket = null) {
    $actions[] = [
        'id'         => 'community-convert-to-topic',
        'label'      => 'Convert to Community Topic',
        'icon'       => 'chat-bubble-left-right',
        'color'      => 'indigo',
        'capability' => 'manage_tickets',
        'data'       => [
            'ticket_id' => is_array($ticket) ? ($ticket['id'] ?? '') : '',
        ],
    ];

    return $actions;
}, 10);

// ---------------------------------------------------------------------------
// Action: community.post.created -- notify subscribed agents
// ---------------------------------------------------------------------------

escalated_add_action('community.post.created', function ($post) {
    $postData = is_array($post) ? $post : (array) $post;
    $topicId  = $postData['topic_id'] ?? '';

    if (empty($topicId)) {
        return;
    }

    $subscribers = esc_community_get_subscribers($topicId);

    if (empty($subscribers)) {
        return;
    }

    $topic = esc_community_get_topic($topicId);

    // Broadcast notification to each subscribed agent
    if (function_exists('escalated_broadcast')) {
        foreach ($subscribers as $agentId) {
            // Don't notify the author of their own post
            if ($agentId === ($postData['author_id'] ?? '')) {
                continue;
            }

            escalated_broadcast('agent.' . $agentId, 'community.new_reply', [
                'topic_id'    => $topicId,
                'topic_title' => $topic['title'] ?? 'Untitled',
                'post_id'     => $postData['id'] ?? '',
                'author_name' => $postData['author_name'] ?? 'Someone',
                'excerpt'     => mb_substr($postData['body'] ?? '', 0, 100),
                'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }
    }
}, 10);

// ---------------------------------------------------------------------------
// Activation hook
// ---------------------------------------------------------------------------

escalated_add_action('escalated_plugin_activated_community', function () {
    // Ensure config directory exists
    if (!is_dir(ESC_COMMUNITY_CONFIG_DIR)) {
        mkdir(ESC_COMMUNITY_CONFIG_DIR, 0755, true);
    }

    // Create empty data files if they don't exist
    $files = [
        ESC_COMMUNITY_CATEGORIES_FILE,
        ESC_COMMUNITY_TOPICS_FILE,
        ESC_COMMUNITY_POSTS_FILE,
        ESC_COMMUNITY_VOTES_FILE,
        ESC_COMMUNITY_SUBSCRIPTIONS_FILE,
    ];

    foreach ($files as $file) {
        if (!file_exists($file)) {
            esc_community_write_json($file, []);
        }
    }

    // Create a default "General" category if no categories exist
    $categories = esc_community_get_categories();
    if (empty($categories)) {
        esc_community_save_category([
            'name'        => 'General Discussion',
            'slug'        => 'general-discussion',
            'description' => 'General topics and conversations',
            'position'    => 0,
        ]);
        esc_community_save_category([
            'name'        => 'Feature Requests',
            'slug'        => 'feature-requests',
            'description' => 'Suggest and vote on new features',
            'position'    => 1,
        ]);
        esc_community_save_category([
            'name'        => 'Bug Reports',
            'slug'        => 'bug-reports',
            'description' => 'Report issues and track fixes',
            'position'    => 2,
        ]);
    }

    // Store plugin version
    if (function_exists('escalated_update_option')) {
        escalated_update_option('community_plugin_version', ESC_COMMUNITY_VERSION);
    }
}, 10);

// ---------------------------------------------------------------------------
// Deactivation hook
// ---------------------------------------------------------------------------

escalated_add_action('escalated_plugin_deactivated_community', function () {
    // Preserve all data so re-activation restores state.
    if (function_exists('escalated_broadcast')) {
        escalated_broadcast('admin', 'community.deactivated', [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }
}, 10);
