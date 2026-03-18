import { definePlugin } from '@escalated-dev/plugin-sdk'

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface ForumCategory {
    id: string
    name: string
    slug: string
    description?: string
    order: number
    is_public: boolean
}

interface ForumTopic {
    id: string
    category_id: string
    title: string
    body: string
    author_id: string | number
    author_type: 'contact' | 'agent'
    status: 'open' | 'closed' | 'pinned' | 'resolved'
    votes: number
    reply_count: number
    ticket_id?: string | number | null   // set when converted from ticket
    created_at: string
}

interface ForumReply {
    id: string
    topic_id: string
    body: string
    author_id: string | number
    author_type: 'contact' | 'agent'
    is_solution: boolean
    votes: number
    created_at: string
}

// ---------------------------------------------------------------------------
// Plugin definition
// ---------------------------------------------------------------------------

export default definePlugin({
    name: 'community',
    version: '0.1.0',
    description: 'Public community forums with categories, topics, replies, voting, moderation, and ticket-to-topic conversion',

    config: [
        { name: 'title', label: 'Community Title', type: 'text', default: 'Community Forum' },
        { name: 'allow_anonymous_viewing', label: 'Allow Anonymous Viewing', type: 'boolean', default: true },
        { name: 'require_auth_to_post', label: 'Require Auth to Post', type: 'boolean', default: true },
        { name: 'moderation_mode', label: 'Moderation Mode', type: 'select',
            options: [
                { value: 'post_first', label: 'Post First, Moderate Later' },
                { value: 'pre_moderated', label: 'Approve Before Publish' },
            ],
            default: 'post_first',
        },
        { name: 'enable_voting', label: 'Enable Voting', type: 'boolean', default: true },
    ],

    onActivate: async (ctx) => {
        // Create a default category on first activation
        const existing = await ctx.store.query('categories', {})
        if (existing.length === 0) {
            await ctx.store.insert('categories', {
                id: 'general',
                name: 'General',
                slug: 'general',
                description: 'General discussion',
                order: 1,
                is_public: true,
            })
        }
        ctx.log.info('[community] Plugin activated')
    },

    onDeactivate: async (ctx) => {
        ctx.log.info('[community] Plugin deactivated')
    },

    // -----------------------------------------------------------------------
    // Action hooks
    // -----------------------------------------------------------------------

    actions: {
        'community.post.created': async (event, ctx) => {
            const post = event as { topic_id: string; author_id: string | number; is_reply: boolean }
            await ctx.broadcast.toChannel('community', 'post.created', post)
            ctx.log.info('[community] Post created', { topic_id: post.topic_id })
        },
    },

    // -----------------------------------------------------------------------
    // Filter hooks
    // -----------------------------------------------------------------------

    filters: {
        'ticket.actions': {
            priority: 10,
            handler: (actions, _ctx) => [
                ...(actions as unknown[]),
                {
                    id: 'community-convert-to-topic',
                    label: 'Convert to Community Topic',
                    icon: 'chat-bubble-left-right',
                    color: 'indigo',
                    capability: 'manage_tickets',
                },
            ],
        },
    },

    // -----------------------------------------------------------------------
    // Pages & components
    // -----------------------------------------------------------------------

    pages: [
        {
            route: 'community',
            component: 'CommunityForum',
            layout: 'public',
            menu: { label: 'Community', section: 'agent', position: 40, icon: 'chat-bubble-left-right' },
        },
        {
            route: 'admin/community',
            component: 'CommunityAdmin',
            layout: 'admin',
            capability: 'manage_settings',
            menu: { label: 'Community', section: 'admin', position: 50, icon: 'chat-bubble-left-right' },
        },
    ],

    components: [
        {
            page: 'community',
            slot: 'main',
            component: 'CommunityForum',
            props: { pluginSlug: 'community' },
            order: 10,
        },
        {
            page: 'admin.community',
            slot: 'main',
            component: 'CommunityAdmin',
            props: { pluginSlug: 'community' },
            order: 10,
            capability: 'manage_settings',
        },
    ],

    // -----------------------------------------------------------------------
    // Endpoints
    // -----------------------------------------------------------------------

    endpoints: {
        // Categories
        'GET /categories': {
            handler: async (ctx) => ctx.store.query('categories', {}, { orderBy: 'order', order: 'asc' }),
        },
        'POST /categories': {
            capability: 'manage_settings',
            handler: async (ctx, req) => {
                return ctx.store.insert('categories', {
                    ...req.body,
                    id: `cat_${Date.now()}`,
                })
            },
        },
        'PUT /categories/:id': {
            capability: 'manage_settings',
            handler: async (ctx, req) => ctx.store.update('categories', req.params.id, req.body as Record<string, unknown>),
        },
        'DELETE /categories/:id': {
            capability: 'manage_settings',
            handler: async (ctx, req) => {
                await ctx.store.delete('categories', req.params.id)
                return { success: true }
            },
        },

        // Topics
        'GET /topics': {
            handler: async (ctx, req) => {
                const filter = req.query.category_id ? { category_id: req.query.category_id } : {}
                return ctx.store.query('topics', filter, { orderBy: 'created_at', order: 'desc', limit: 50 })
            },
        },
        'POST /topics': {
            handler: async (ctx, req) => {
                const topic = req.body as Omit<ForumTopic, 'id' | 'votes' | 'reply_count' | 'created_at'>
                const cfg = await ctx.config.all()
                const status = cfg.moderation_mode === 'pre_moderated' ? 'pending' : 'open'
                const inserted = await ctx.store.insert('topics', {
                    ...topic,
                    id: `topic_${Date.now()}`,
                    status,
                    votes: 0,
                    reply_count: 0,
                    created_at: new Date().toISOString(),
                })
                await ctx.emit('community.post.created', { topic_id: (inserted as unknown as ForumTopic).id, is_reply: false })
                return inserted
            },
        },
        'GET /topics/:id': {
            handler: async (ctx, req) => {
                const topics = await ctx.store.query('topics', { id: req.params.id })
                if (topics.length === 0) return null
                const replies = await ctx.store.query('replies', { topic_id: req.params.id },
                    { orderBy: 'created_at', order: 'asc' })
                return { topic: topics[0], replies }
            },
        },
        'POST /topics/:id/replies': {
            handler: async (ctx, req) => {
                const reply = await ctx.store.insert('replies', {
                    ...req.body,
                    id: `reply_${Date.now()}`,
                    topic_id: req.params.id,
                    is_solution: false,
                    votes: 0,
                    created_at: new Date().toISOString(),
                })
                await ctx.emit('community.post.created', { topic_id: req.params.id, is_reply: true })
                return reply
            },
        },
        'POST /topics/:id/vote': {
            handler: async (ctx, req) => {
                const topics = await ctx.store.query('topics', { id: req.params.id })
                if (topics.length === 0) return { success: false }
                const topic = topics[0] as unknown as ForumTopic
                await ctx.store.update('topics', req.params.id, { votes: topic.votes + 1 })
                return { success: true, votes: topic.votes + 1 }
            },
        },
        'POST /topics/:id/convert-from-ticket': {
            capability: 'manage_tickets',
            handler: async (ctx, req) => {
                const { ticket_id } = req.body as { ticket_id: string | number }
                const ticket = await ctx.tickets.find(ticket_id)
                if (!ticket) return { success: false, message: 'Ticket not found' }

                const topic = await ctx.store.insert('topics', {
                    id: `topic_${Date.now()}`,
                    category_id: 'general',
                    title: ticket.title,
                    body: '',
                    author_type: 'agent',
                    status: 'open',
                    votes: 0,
                    reply_count: 0,
                    ticket_id: ticket.id,
                    created_at: new Date().toISOString(),
                })

                return { success: true, topic }
            },
        },
        'GET /settings': {
            capability: 'manage_settings',
            handler: async (ctx) => ctx.config.all(),
        },
        'POST /settings': {
            capability: 'manage_settings',
            handler: async (ctx, req) => {
                await ctx.config.set(req.body as Record<string, unknown>)
                return { success: true }
            },
        },
    },
})
