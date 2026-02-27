<?php

namespace Escalated\Plugins\Community\Services;

use Escalated\Plugins\Community\Support\Config;

class CommunityService
{
    // -----------------------------------------------------------------------
    // Category templates & CRUD
    // -----------------------------------------------------------------------

    /**
     * Return an empty category template.
     */
    public static function categoryTemplate(): array
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
    public static function getCategories(): array
    {
        $categories = Config::readJson(Config::CATEGORIES_FILE);

        usort($categories, function ($a, $b) {
            return ($a['position'] ?? 0) - ($b['position'] ?? 0);
        });

        return $categories;
    }

    /**
     * Get a single category by ID.
     */
    public static function getCategory(string $id): ?array
    {
        $categories = self::getCategories();

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
    public static function getCategoryBySlug(string $slug): ?array
    {
        $categories = self::getCategories();

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
    public static function saveCategory(array $category): array
    {
        $categories = Config::readJson(Config::CATEGORIES_FILE);

        if (empty($category['id'])) {
            $category['id'] = Config::generateId('cat_');
        }

        if (empty($category['slug']) && !empty($category['name'])) {
            $category['slug'] = Config::slugify($category['name']);
        }

        $category = array_merge(self::categoryTemplate(), $category);

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

        Config::writeJson(Config::CATEGORIES_FILE, $categories);

        return $category;
    }

    /**
     * Delete a category by ID. Returns true if deleted.
     */
    public static function deleteCategory(string $id): bool
    {
        $categories = Config::readJson(Config::CATEGORIES_FILE);
        $filtered = array_filter($categories, function ($c) use ($id) {
            return ($c['id'] ?? '') !== $id;
        });

        if (count($filtered) === count($categories)) {
            return false;
        }

        return Config::writeJson(Config::CATEGORIES_FILE, $filtered);
    }

    /**
     * Reorder categories by providing an ordered array of category IDs.
     */
    public static function reorderCategories(array $orderedIds): bool
    {
        $categories = Config::readJson(Config::CATEGORIES_FILE);
        $map = [];

        foreach ($categories as $cat) {
            $map[$cat['id'] ?? ''] = $cat;
        }

        foreach ($orderedIds as $position => $catId) {
            if (isset($map[$catId])) {
                $map[$catId]['position'] = $position;
            }
        }

        return Config::writeJson(Config::CATEGORIES_FILE, array_values($map));
    }

    /**
     * Recalculate topic_count for a category.
     */
    public static function recalculateCategoryCount(string $categoryId): void
    {
        $topics = Config::readJson(Config::TOPICS_FILE);
        $count  = 0;

        foreach ($topics as $topic) {
            if (($topic['category_id'] ?? '') === $categoryId) {
                $count++;
            }
        }

        $category = self::getCategory($categoryId);
        if ($category !== null) {
            $category['topic_count'] = $count;
            self::saveCategory($category);
        }
    }

    // -----------------------------------------------------------------------
    // Topic templates & CRUD
    // -----------------------------------------------------------------------

    /**
     * Return an empty topic template.
     */
    public static function topicTemplate(): array
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
    public static function getTopics(array $filters = []): array
    {
        $topics = Config::readJson(Config::TOPICS_FILE);

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
    public static function getTopic(string $id): ?array
    {
        $topics = Config::readJson(Config::TOPICS_FILE);

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
    public static function getTopicBySlug(string $slug): ?array
    {
        $topics = Config::readJson(Config::TOPICS_FILE);

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
    public static function saveTopic(array $topic): array
    {
        $topics = Config::readJson(Config::TOPICS_FILE);
        $now    = gmdate('Y-m-d\TH:i:s\Z');
        $isNew  = empty($topic['id']);

        if ($isNew) {
            $topic['id']         = Config::generateId('top_');
            $topic['created_at'] = $now;
        }

        $topic['updated_at'] = $now;

        if (empty($topic['slug']) && !empty($topic['title'])) {
            $topic['slug'] = Config::slugify($topic['title']);
        }

        $topic = array_merge(self::topicTemplate(), $topic);

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

        Config::writeJson(Config::TOPICS_FILE, $topics);

        if ($isNew && !empty($topic['category_id'])) {
            self::recalculateCategoryCount($topic['category_id']);
        }

        return $topic;
    }

    /**
     * Delete a topic by ID and all its posts.
     */
    public static function deleteTopic(string $id): bool
    {
        $topics = Config::readJson(Config::TOPICS_FILE);
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

        Config::writeJson(Config::TOPICS_FILE, $filtered);

        // Delete all posts for this topic
        $posts = Config::readJson(Config::POSTS_FILE);
        $remainingPosts = array_filter($posts, function ($p) use ($id) {
            return ($p['topic_id'] ?? '') !== $id;
        });
        Config::writeJson(Config::POSTS_FILE, $remainingPosts);

        // Delete votes for this topic and its posts
        $votes = Config::readJson(Config::VOTES_FILE);
        $remainingVotes = array_filter($votes, function ($v) use ($id) {
            return ($v['topic_id'] ?? '') !== $id;
        });
        Config::writeJson(Config::VOTES_FILE, $remainingVotes);

        if (!empty($topic['category_id'])) {
            self::recalculateCategoryCount($topic['category_id']);
        }

        return true;
    }

    /**
     * Increment view count for a topic.
     */
    public static function incrementViews(string $topicId): void
    {
        $topics = Config::readJson(Config::TOPICS_FILE);

        foreach ($topics as $index => $topic) {
            if (($topic['id'] ?? '') === $topicId) {
                $topics[$index]['view_count'] = ($topic['view_count'] ?? 0) + 1;
                break;
            }
        }

        Config::writeJson(Config::TOPICS_FILE, $topics);
    }

    // -----------------------------------------------------------------------
    // Post (reply) templates & CRUD
    // -----------------------------------------------------------------------

    /**
     * Return an empty post template.
     */
    public static function postTemplate(): array
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
    public static function getPosts(string $topicId): array
    {
        $posts = Config::readJson(Config::POSTS_FILE);

        $filtered = array_filter($posts, function ($p) use ($topicId) {
            return ($p['topic_id'] ?? '') === $topicId;
        });

        $filtered = array_values($filtered);

        usort($filtered, function ($a, $b) {
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
    public static function getPost(string $id): ?array
    {
        $posts = Config::readJson(Config::POSTS_FILE);

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
    public static function savePost(array $post): array
    {
        $posts = Config::readJson(Config::POSTS_FILE);
        $now   = gmdate('Y-m-d\TH:i:s\Z');
        $isNew = empty($post['id']);

        if ($isNew) {
            $post['id']         = Config::generateId('post_');
            $post['created_at'] = $now;
        }

        $post['updated_at'] = $now;

        $post = array_merge(self::postTemplate(), $post);

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

        Config::writeJson(Config::POSTS_FILE, $posts);

        if ($isNew && !empty($post['topic_id'])) {
            self::recalculateReplyCount($post['topic_id']);

            $topic = self::getTopic($post['topic_id']);
            if ($topic !== null) {
                $topic['updated_at'] = $now;
                self::saveTopic($topic);
            }

            if (function_exists('escalated_do_action')) {
                escalated_do_action('community.post.created', $post);
            }
        }

        return $post;
    }

    /**
     * Delete a post by ID.
     */
    public static function deletePost(string $id): bool
    {
        $posts = Config::readJson(Config::POSTS_FILE);
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

        Config::writeJson(Config::POSTS_FILE, $filtered);

        if (!empty($post['topic_id'])) {
            self::recalculateReplyCount($post['topic_id']);

            if (!empty($post['is_answer'])) {
                $topic = self::getTopic($post['topic_id']);
                if ($topic !== null && ($topic['answer_post_id'] ?? '') === $id) {
                    $topic['is_answered']    = false;
                    $topic['answer_post_id'] = null;
                    self::saveTopic($topic);
                }
            }
        }

        // Delete votes for this post
        $votes = Config::readJson(Config::VOTES_FILE);
        $remainingVotes = array_filter($votes, function ($v) use ($id) {
            return ($v['target_id'] ?? '') !== $id;
        });
        Config::writeJson(Config::VOTES_FILE, $remainingVotes);

        return true;
    }

    /**
     * Recalculate reply_count for a topic.
     */
    public static function recalculateReplyCount(string $topicId): void
    {
        $posts = Config::readJson(Config::POSTS_FILE);
        $count = 0;

        foreach ($posts as $post) {
            if (($post['topic_id'] ?? '') === $topicId) {
                $count++;
            }
        }

        $topics = Config::readJson(Config::TOPICS_FILE);
        foreach ($topics as $index => $topic) {
            if (($topic['id'] ?? '') === $topicId) {
                $topics[$index]['reply_count'] = $count;
                break;
            }
        }

        Config::writeJson(Config::TOPICS_FILE, $topics);
    }

    /**
     * Mark a post as the accepted answer for its topic.
     */
    public static function markAnswer(string $postId): ?array
    {
        $post = self::getPost($postId);
        if ($post === null) {
            return null;
        }

        $topicId = $post['topic_id'] ?? '';
        if (empty($topicId)) {
            return null;
        }

        $posts = Config::readJson(Config::POSTS_FILE);
        foreach ($posts as $index => $p) {
            if (($p['topic_id'] ?? '') === $topicId && !empty($p['is_answer'])) {
                $posts[$index]['is_answer'] = false;
            }
            if (($p['id'] ?? '') === $postId) {
                $posts[$index]['is_answer'] = true;
            }
        }
        Config::writeJson(Config::POSTS_FILE, $posts);

        $topic = self::getTopic($topicId);
        if ($topic !== null) {
            $topic['is_answered']    = true;
            $topic['answer_post_id'] = $postId;
            self::saveTopic($topic);
        }

        return self::getPost($postId);
    }

    /**
     * Unmark the accepted answer for a topic.
     */
    public static function unmarkAnswer(string $topicId): void
    {
        $posts = Config::readJson(Config::POSTS_FILE);
        foreach ($posts as $index => $p) {
            if (($p['topic_id'] ?? '') === $topicId && !empty($p['is_answer'])) {
                $posts[$index]['is_answer'] = false;
            }
        }
        Config::writeJson(Config::POSTS_FILE, $posts);

        $topic = self::getTopic($topicId);
        if ($topic !== null) {
            $topic['is_answered']    = false;
            $topic['answer_post_id'] = null;
            self::saveTopic($topic);
        }
    }

    // -----------------------------------------------------------------------
    // Vote tracking
    // -----------------------------------------------------------------------

    /**
     * Cast or change a vote. Returns the resulting vote_count on the target.
     *
     * If the user already voted in the same direction, the vote is removed (toggle).
     * If the user voted in the opposite direction, the vote is changed.
     */
    public static function vote(string $userId, string $targetType, string $targetId, string $direction): int
    {
        $votes = Config::readJson(Config::VOTES_FILE);

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

        $topicId = '';
        if ($targetType === 'topic') {
            $topicId = $targetId;
        } else {
            $post = self::getPost($targetId);
            $topicId = $post['topic_id'] ?? '';
        }

        if ($existingIndex !== null) {
            $existingDirection = $votes[$existingIndex]['direction'] ?? '';

            if ($existingDirection === $direction) {
                array_splice($votes, $existingIndex, 1);
            } else {
                $votes[$existingIndex]['direction']  = $direction;
                $votes[$existingIndex]['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
            }
        } else {
            $votes[] = [
                'id'          => Config::generateId('vote_'),
                'user_id'     => $userId,
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'topic_id'    => $topicId,
                'direction'   => $direction,
                'created_at'  => gmdate('Y-m-d\TH:i:s\Z'),
            ];
        }

        Config::writeJson(Config::VOTES_FILE, $votes);

        $voteCount = self::calculateVoteCount($targetType, $targetId, $votes);

        if ($targetType === 'topic') {
            $topics = Config::readJson(Config::TOPICS_FILE);
            foreach ($topics as $idx => $t) {
                if (($t['id'] ?? '') === $targetId) {
                    $topics[$idx]['vote_count'] = $voteCount;
                    break;
                }
            }
            Config::writeJson(Config::TOPICS_FILE, $topics);
        } else {
            $posts = Config::readJson(Config::POSTS_FILE);
            foreach ($posts as $idx => $p) {
                if (($p['id'] ?? '') === $targetId) {
                    $posts[$idx]['vote_count'] = $voteCount;
                    break;
                }
            }
            Config::writeJson(Config::POSTS_FILE, $posts);
        }

        return $voteCount;
    }

    /**
     * Get the current user's vote on a target, or null if not voted.
     */
    public static function getUserVote(string $userId, string $targetType, string $targetId): ?string
    {
        $votes = Config::readJson(Config::VOTES_FILE);

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
    public static function calculateVoteCount(string $targetType, string $targetId, ?array $votes = null): int
    {
        if ($votes === null) {
            $votes = Config::readJson(Config::VOTES_FILE);
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

    // -----------------------------------------------------------------------
    // Topic search
    // -----------------------------------------------------------------------

    /**
     * Search topics by query string. Returns matching topics with relevance.
     */
    public static function searchTopics(string $query, int $limit = 20): array
    {
        if (empty(trim($query))) {
            return [];
        }

        $result = self::getTopics([
            'search'   => $query,
            'per_page' => $limit,
            'sort'     => 'popular',
        ]);

        return $result['topics'] ?? [];
    }

    // -----------------------------------------------------------------------
    // Moderation functions
    // -----------------------------------------------------------------------

    /**
     * Pin or unpin a topic.
     */
    public static function pinTopic(string $topicId, bool $pin = true): ?array
    {
        $topic = self::getTopic($topicId);
        if ($topic === null) return null;

        $topic['is_pinned'] = $pin;
        return self::saveTopic($topic);
    }

    /**
     * Lock or unlock a topic.
     */
    public static function lockTopic(string $topicId, bool $lock = true): ?array
    {
        $topic = self::getTopic($topicId);
        if ($topic === null) return null;

        $topic['is_locked'] = $lock;
        return self::saveTopic($topic);
    }

    /**
     * Move a topic to a different category.
     */
    public static function moveTopic(string $topicId, string $newCategoryId): ?array
    {
        $topic = self::getTopic($topicId);
        if ($topic === null) return null;

        $oldCategoryId = $topic['category_id'] ?? '';
        $topic['category_id'] = $newCategoryId;
        $saved = self::saveTopic($topic);

        if ($oldCategoryId) {
            self::recalculateCategoryCount($oldCategoryId);
        }
        self::recalculateCategoryCount($newCategoryId);

        return $saved;
    }

    // -----------------------------------------------------------------------
    // Convert ticket to topic
    // -----------------------------------------------------------------------

    /**
     * Convert a ticket into a community forum topic.
     */
    public static function convertTicketToTopic(array $ticket, string $categoryId): array
    {
        $topic = self::saveTopic([
            'category_id'      => $categoryId,
            'title'            => $ticket['subject'] ?? $ticket['title'] ?? 'Untitled',
            'body'             => $ticket['body'] ?? $ticket['description'] ?? '',
            'author_id'        => $ticket['customer_id'] ?? $ticket['author_id'] ?? '',
            'author_type'      => 'customer',
            'author_name'      => $ticket['customer_name'] ?? $ticket['author_name'] ?? 'Customer',
            'source_ticket_id' => $ticket['id'] ?? null,
        ]);

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
     */
    public static function convertTopicToTicket(string $topicId): ?array
    {
        $topic = self::getTopic($topicId);
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

    // -----------------------------------------------------------------------
    // Subscriptions
    // -----------------------------------------------------------------------

    /**
     * Subscribe an agent to a topic for notifications.
     */
    public static function subscribe(string $agentId, string $topicId): bool
    {
        $subs = Config::readJson(Config::SUBSCRIPTIONS_FILE);

        foreach ($subs as $sub) {
            if (($sub['agent_id'] ?? '') === $agentId && ($sub['topic_id'] ?? '') === $topicId) {
                return true;
            }
        }

        $subs[] = [
            'id'         => Config::generateId('sub_'),
            'agent_id'   => $agentId,
            'topic_id'   => $topicId,
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        return Config::writeJson(Config::SUBSCRIPTIONS_FILE, $subs);
    }

    /**
     * Unsubscribe an agent from a topic.
     */
    public static function unsubscribe(string $agentId, string $topicId): bool
    {
        $subs = Config::readJson(Config::SUBSCRIPTIONS_FILE);

        $filtered = array_filter($subs, function ($s) use ($agentId, $topicId) {
            return !(($s['agent_id'] ?? '') === $agentId && ($s['topic_id'] ?? '') === $topicId);
        });

        if (count($filtered) === count($subs)) {
            return false;
        }

        return Config::writeJson(Config::SUBSCRIPTIONS_FILE, $filtered);
    }

    /**
     * Get all agent IDs subscribed to a topic.
     */
    public static function getSubscribers(string $topicId): array
    {
        $subs = Config::readJson(Config::SUBSCRIPTIONS_FILE);
        $agentIds = [];

        foreach ($subs as $sub) {
            if (($sub['topic_id'] ?? '') === $topicId) {
                $agentIds[] = $sub['agent_id'];
            }
        }

        return $agentIds;
    }

    // -----------------------------------------------------------------------
    // Community statistics
    // -----------------------------------------------------------------------

    /**
     * Get community-wide statistics.
     */
    public static function getStats(): array
    {
        $topics = Config::readJson(Config::TOPICS_FILE);
        $posts  = Config::readJson(Config::POSTS_FILE);

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

        $weekAgo = gmdate('Y-m-d\TH:i:s\Z', strtotime('-7 days'));
        $topicsThisWeek = 0;
        foreach ($topics as $t) {
            if (($t['created_at'] ?? '') >= $weekAgo) {
                $topicsThisWeek++;
            }
        }

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
}
