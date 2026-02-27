<?php

namespace Escalated\Plugins\Community\Handlers;

use Escalated\Plugins\Community\Services\CommunityService;

class EventHandler
{
    /**
     * Notify subscribed agents when a new post is created in a topic.
     */
    public static function onPostCreated($post): void
    {
        $postData = is_array($post) ? $post : (array) $post;
        $topicId  = $postData['topic_id'] ?? '';

        if (empty($topicId)) {
            return;
        }

        $subscribers = CommunityService::getSubscribers($topicId);

        if (empty($subscribers)) {
            return;
        }

        $topic = CommunityService::getTopic($topicId);

        if (function_exists('escalated_broadcast')) {
            foreach ($subscribers as $agentId) {
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
    }
}
