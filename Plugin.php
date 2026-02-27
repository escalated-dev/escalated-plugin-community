<?php

/**
 * Community Forums Plugin for Escalated
 *
 * Adds a public community forum to the helpdesk with categories, topics,
 * replies, voting, moderation, and ticket-to-topic conversion.
 */

if (!defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

// Load plugin classes
require_once __DIR__ . '/Support/Config.php';
require_once __DIR__ . '/Services/CommunityService.php';
require_once __DIR__ . '/Handlers/EventHandler.php';

use Escalated\Plugins\Community\Support\Config;
use Escalated\Plugins\Community\Handlers\EventHandler;

// Action hooks
escalated_add_action('community.post.created', [EventHandler::class, 'onPostCreated'], 10);

// Filter hooks
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

// UI registration
escalated_register_page('/community', 'CommunityForum', [
    'title'  => 'Community',
    'public' => true,
    'props'  => ['pluginSlug' => Config::SLUG],
]);

escalated_register_page('/admin/community', 'CommunityAdmin', [
    'title'      => 'Community Management',
    'capability' => 'manage_settings',
    'props'      => ['pluginSlug' => Config::SLUG],
]);

escalated_add_page_component('community', 'main', [
    'component' => 'CommunityForum',
    'props'     => ['pluginSlug' => Config::SLUG],
    'order'     => 10,
]);

escalated_add_page_component('admin.community', 'main', [
    'component' => 'CommunityAdmin',
    'props'     => ['pluginSlug' => Config::SLUG],
    'order'     => 10,
]);

escalated_register_menu_item([
    'id'    => 'community-forum',
    'label' => 'Community',
    'icon'  => 'chat-bubble-left-right',
    'route' => '/community',
    'order' => 40,
]);

escalated_register_menu_item([
    'id'         => 'community-admin',
    'label'      => 'Community',
    'icon'       => 'chat-bubble-left-right',
    'route'      => '/admin/community',
    'parent'     => 'admin-settings',
    'order'      => 50,
    'capability' => 'manage_settings',
]);

// Lifecycle hooks
escalated_add_action('escalated_plugin_activated_community', [Config::class, 'onActivate'], 10);
escalated_add_action('escalated_plugin_deactivated_community', [Config::class, 'onDeactivate'], 10);
