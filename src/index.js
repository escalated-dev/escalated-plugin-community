import { defineEscalatedPlugin } from '@escalated-dev/escalated';
import CommunityForumPanel from './components/CommunityForumPanel.vue';

export default defineEscalatedPlugin({
    name: 'Community Forums',
    slug: 'community',
    version: '0.1.0',
    description: 'Community forums with topics, replies, upvotes, and moderation',

    extensions: {
        menuItems: [
            {
                id: 'community-forums',
                label: 'Community',
                icon: 'user-group',
                route: '/community',
            },
        ],
        settingsPanels: [
            {
                id: 'community-settings',
                title: 'Community Forums',
                component: CommunityForumPanel,
                icon: 'user-group',
                category: 'features',
            },
        ],
        ticketActions: [
            {
                id: 'community-convert-to-topic',
                label: 'Convert to Forum Topic',
                icon: 'chat-bubble-left-right',
                handler: (ticket) => {
                    // Convert ticket to community forum topic
                },
            },
        ],
        pageComponents: {
            'community-forums': CommunityForumPanel,
        },
    },

    hooks: {
        'ticket.resolved': (ticket) => {
            // Optionally suggest posting resolution as community article
        },
    },

    setup(context) {
        context.provide('community', {
            // Community service will be provided here
        });
    },
});
