import { defineEscalatedPlugin } from '@escalated-dev/escalated';
import CommunityForum from './components/CommunityForum.vue';
import TopicView from './components/TopicView.vue';
import CommunityAdmin from './components/CommunityAdmin.vue';

export default defineEscalatedPlugin({
    name: 'Community Forums',
    slug: 'community',
    version: '0.1.0',
    description: 'Community forums with topics, replies, upvotes, and moderation',

    extensions: {
        menuItems: [
            {
                id: 'community-forum',
                label: 'Community',
                icon: 'chat-bubble-left-right',
                route: '/community',
                order: 40,
            },
            {
                id: 'community-admin',
                label: 'Community',
                icon: 'chat-bubble-left-right',
                route: '/admin/community',
                parent: 'admin-settings',
                order: 50,
                capability: 'manage_settings',
            },
        ],
        ticketActions: [
            {
                id: 'community-convert-to-topic',
                label: 'Convert to Community Topic',
                icon: 'chat-bubble-left-right',
                color: 'indigo',
                handler: (ticket, context) => {
                    const service = context?.$escalated?.inject?.('community');
                    if (!service) return;
                    service.showConvertDialog(ticket);
                },
            },
        ],
        pageComponents: {
            'community': CommunityForum,
            'community.topic': TopicView,
            'admin.community': CommunityAdmin,
        },
        settingsPanels: [
            {
                id: 'community-settings',
                title: 'Community Forums',
                component: CommunityAdmin,
                icon: 'chat-bubble-left-right',
                category: 'features',
            },
        ],
    },

    hooks: {
        /**
         * Add "Convert to Community Topic" to ticket action menu.
         */
        'ticket.actions': (actions, ticket, context) => {
            return [
                ...actions,
                {
                    id: 'community-convert-to-topic',
                    label: 'Convert to Community Topic',
                    icon: 'chat-bubble-left-right',
                    color: 'indigo',
                    data: { ticket_id: ticket?.id },
                },
            ];
        },

        /**
         * When a ticket is resolved, optionally suggest converting to
         * a community knowledge article.
         */
        'ticket.resolved': (ticket, context) => {
            const service = context?.$escalated?.inject?.('community');
            if (!service) return;
            // The service can show a suggestion notification
            service.suggestConversion(ticket);
        },

        /**
         * Add community entry to admin settings navigation.
         */
        'admin.settings.nav': (items) => {
            return [
                ...items,
                {
                    id: 'community-admin',
                    label: 'Community',
                    icon: 'chat-bubble-left-right',
                    section: 'features',
                    order: 50,
                },
            ];
        },
    },

    setup(context) {
        const { reactive, ref } = context.vue || {};
        const _reactive = reactive || ((o) => o);
        const _ref = ref || ((v) => ({ value: v }));

        // ------------------------------------------------------------------
        // Reactive state
        // ------------------------------------------------------------------
        const state = _reactive({
            categories: [],
            topics: [],
            currentTopic: null,
            currentPosts: [],
            stats: null,
            pagination: {
                total: 0,
                page: 1,
                per_page: 20,
            },
            filters: {
                category_id: null,
                sort: 'recent',
                search: '',
            },
            loading: false,
            convertDialog: {
                visible: false,
                ticket: null,
                categoryId: '',
            },
            userVotes: {},  // keyed by `${targetType}_${targetId}` => 'up'|'down'|null
        });

        const saving = _ref(false);

        // ------------------------------------------------------------------
        // API helpers
        // ------------------------------------------------------------------
        const apiBase = () => {
            if (context.route) {
                return context.route('plugins.community.api');
            }
            return '/api/plugins/community';
        };

        async function apiRequest(path, options = {}) {
            const url = `${apiBase()}${path}`;
            const headers = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            };

            if (options.body && typeof options.body === 'object') {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const response = await fetch(url, { ...options, headers });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `API request failed: ${response.status}`);
            }

            return response.json();
        }

        // ------------------------------------------------------------------
        // Category CRUD
        // ------------------------------------------------------------------

        async function fetchCategories() {
            state.loading = true;
            try {
                const data = await apiRequest('/categories');
                state.categories = Array.isArray(data) ? data : (data.categories || []);
            } catch (err) {
                console.error('[community] Failed to fetch categories:', err);
            } finally {
                state.loading = false;
            }
        }

        async function saveCategory(category) {
            saving.value = true;
            try {
                const method = category.id ? 'PUT' : 'POST';
                const path = category.id
                    ? `/categories/${category.id}`
                    : '/categories';

                const data = await apiRequest(path, { method, body: category });

                const index = state.categories.findIndex((c) => c.id === data.id);
                if (index >= 0) {
                    state.categories[index] = data;
                } else {
                    state.categories.push(data);
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to save category:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        async function deleteCategory(categoryId) {
            try {
                await apiRequest(`/categories/${categoryId}`, { method: 'DELETE' });
                state.categories = state.categories.filter((c) => c.id !== categoryId);
            } catch (err) {
                console.error('[community] Failed to delete category:', err);
                throw err;
            }
        }

        async function reorderCategories(orderedIds) {
            try {
                await apiRequest('/categories/reorder', {
                    method: 'POST',
                    body: { order: orderedIds },
                });
                await fetchCategories();
            } catch (err) {
                console.error('[community] Failed to reorder categories:', err);
                throw err;
            }
        }

        // ------------------------------------------------------------------
        // Topic CRUD
        // ------------------------------------------------------------------

        async function fetchTopics(filters = {}) {
            state.loading = true;
            try {
                const mergedFilters = { ...state.filters, ...filters };
                const params = new URLSearchParams();

                if (mergedFilters.category_id) params.set('category_id', mergedFilters.category_id);
                if (mergedFilters.sort) params.set('sort', mergedFilters.sort);
                if (mergedFilters.search) params.set('search', mergedFilters.search);
                if (mergedFilters.page) params.set('page', mergedFilters.page);
                if (mergedFilters.per_page) params.set('per_page', mergedFilters.per_page);

                const query = params.toString();
                const data = await apiRequest(`/topics${query ? '?' + query : ''}`);

                state.topics = data.topics || [];
                state.pagination = {
                    total: data.total || 0,
                    page: data.page || 1,
                    per_page: data.per_page || 20,
                };

                // Update filters state
                Object.assign(state.filters, mergedFilters);
            } catch (err) {
                console.error('[community] Failed to fetch topics:', err);
            } finally {
                state.loading = false;
            }
        }

        async function fetchTopic(topicId) {
            state.loading = true;
            try {
                const data = await apiRequest(`/topics/${topicId}`);
                state.currentTopic = data.topic || data;

                // Increment view count
                apiRequest(`/topics/${topicId}/view`, { method: 'POST' }).catch(() => {});

                return state.currentTopic;
            } catch (err) {
                console.error('[community] Failed to fetch topic:', err);
                return null;
            } finally {
                state.loading = false;
            }
        }

        async function saveTopic(topic) {
            saving.value = true;
            try {
                const method = topic.id ? 'PUT' : 'POST';
                const path = topic.id
                    ? `/topics/${topic.id}`
                    : '/topics';

                const data = await apiRequest(path, { method, body: topic });

                // Update in list
                const index = state.topics.findIndex((t) => t.id === data.id);
                if (index >= 0) {
                    state.topics[index] = data;
                } else {
                    state.topics.unshift(data);
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to save topic:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        async function deleteTopic(topicId) {
            try {
                await apiRequest(`/topics/${topicId}`, { method: 'DELETE' });
                state.topics = state.topics.filter((t) => t.id !== topicId);

                if (state.currentTopic?.id === topicId) {
                    state.currentTopic = null;
                }
            } catch (err) {
                console.error('[community] Failed to delete topic:', err);
                throw err;
            }
        }

        // ------------------------------------------------------------------
        // Post (reply) CRUD
        // ------------------------------------------------------------------

        async function fetchPosts(topicId) {
            try {
                const data = await apiRequest(`/topics/${topicId}/posts`);
                state.currentPosts = Array.isArray(data) ? data : (data.posts || []);
                return state.currentPosts;
            } catch (err) {
                console.error('[community] Failed to fetch posts:', err);
                return [];
            }
        }

        async function savePost(post) {
            saving.value = true;
            try {
                const method = post.id ? 'PUT' : 'POST';
                const path = post.id
                    ? `/posts/${post.id}`
                    : `/topics/${post.topic_id}/posts`;

                const data = await apiRequest(path, { method, body: post });

                if (post.id) {
                    const index = state.currentPosts.findIndex((p) => p.id === data.id);
                    if (index >= 0) {
                        state.currentPosts[index] = data;
                    }
                } else {
                    state.currentPosts.push(data);
                }

                // Update the topic reply count locally
                if (state.currentTopic && !post.id) {
                    state.currentTopic.reply_count = (state.currentTopic.reply_count || 0) + 1;
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to save post:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        async function deletePost(postId) {
            try {
                await apiRequest(`/posts/${postId}`, { method: 'DELETE' });
                state.currentPosts = state.currentPosts.filter((p) => p.id !== postId);

                if (state.currentTopic) {
                    state.currentTopic.reply_count = Math.max(0, (state.currentTopic.reply_count || 0) - 1);
                }
            } catch (err) {
                console.error('[community] Failed to delete post:', err);
                throw err;
            }
        }

        async function markAnswer(postId) {
            saving.value = true;
            try {
                const data = await apiRequest(`/posts/${postId}/answer`, { method: 'POST' });

                // Update local state
                state.currentPosts.forEach((p) => {
                    p.is_answer = p.id === postId;
                });

                if (state.currentTopic) {
                    state.currentTopic.is_answered = true;
                    state.currentTopic.answer_post_id = postId;
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to mark answer:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        async function unmarkAnswer(topicId) {
            saving.value = true;
            try {
                await apiRequest(`/topics/${topicId}/unmark-answer`, { method: 'POST' });

                state.currentPosts.forEach((p) => {
                    p.is_answer = false;
                });

                if (state.currentTopic) {
                    state.currentTopic.is_answered = false;
                    state.currentTopic.answer_post_id = null;
                }
            } catch (err) {
                console.error('[community] Failed to unmark answer:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        // ------------------------------------------------------------------
        // Voting
        // ------------------------------------------------------------------

        async function vote(targetType, targetId, direction) {
            try {
                const data = await apiRequest('/vote', {
                    method: 'POST',
                    body: { target_type: targetType, target_id: targetId, direction },
                });

                const key = `${targetType}_${targetId}`;
                const existingVote = state.userVotes[key];

                if (existingVote === direction) {
                    // Toggle off
                    state.userVotes[key] = null;
                } else {
                    state.userVotes[key] = direction;
                }

                // Update local vote count
                const newCount = data.vote_count ?? 0;
                if (targetType === 'topic') {
                    const topic = state.topics.find((t) => t.id === targetId);
                    if (topic) topic.vote_count = newCount;
                    if (state.currentTopic?.id === targetId) {
                        state.currentTopic.vote_count = newCount;
                    }
                } else {
                    const post = state.currentPosts.find((p) => p.id === targetId);
                    if (post) post.vote_count = newCount;
                }

                return newCount;
            } catch (err) {
                console.error('[community] Failed to vote:', err);
                throw err;
            }
        }

        function getUserVote(targetType, targetId) {
            return state.userVotes[`${targetType}_${targetId}`] || null;
        }

        // ------------------------------------------------------------------
        // Moderation
        // ------------------------------------------------------------------

        async function pinTopic(topicId, pin = true) {
            try {
                const data = await apiRequest(`/topics/${topicId}/pin`, {
                    method: 'POST',
                    body: { pinned: pin },
                });

                const topic = state.topics.find((t) => t.id === topicId);
                if (topic) topic.is_pinned = pin;
                if (state.currentTopic?.id === topicId) {
                    state.currentTopic.is_pinned = pin;
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to pin/unpin topic:', err);
                throw err;
            }
        }

        async function lockTopic(topicId, lock = true) {
            try {
                const data = await apiRequest(`/topics/${topicId}/lock`, {
                    method: 'POST',
                    body: { locked: lock },
                });

                const topic = state.topics.find((t) => t.id === topicId);
                if (topic) topic.is_locked = lock;
                if (state.currentTopic?.id === topicId) {
                    state.currentTopic.is_locked = lock;
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to lock/unlock topic:', err);
                throw err;
            }
        }

        async function moveTopic(topicId, newCategoryId) {
            try {
                const data = await apiRequest(`/topics/${topicId}/move`, {
                    method: 'POST',
                    body: { category_id: newCategoryId },
                });

                const topic = state.topics.find((t) => t.id === topicId);
                if (topic) topic.category_id = newCategoryId;
                if (state.currentTopic?.id === topicId) {
                    state.currentTopic.category_id = newCategoryId;
                }

                return data;
            } catch (err) {
                console.error('[community] Failed to move topic:', err);
                throw err;
            }
        }

        // ------------------------------------------------------------------
        // Ticket <-> Topic conversion
        // ------------------------------------------------------------------

        async function convertTicketToTopic(ticketId, categoryId) {
            saving.value = true;
            try {
                const data = await apiRequest('/convert-from-ticket', {
                    method: 'POST',
                    body: { ticket_id: ticketId, category_id: categoryId },
                });

                return data;
            } catch (err) {
                console.error('[community] Failed to convert ticket to topic:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        async function convertTopicToTicket(topicId) {
            saving.value = true;
            try {
                const data = await apiRequest(`/topics/${topicId}/convert-to-ticket`, {
                    method: 'POST',
                });

                return data;
            } catch (err) {
                console.error('[community] Failed to convert topic to ticket:', err);
                throw err;
            } finally {
                saving.value = false;
            }
        }

        function showConvertDialog(ticket) {
            state.convertDialog.visible = true;
            state.convertDialog.ticket = ticket;
            state.convertDialog.categoryId = '';
        }

        function hideConvertDialog() {
            state.convertDialog.visible = false;
            state.convertDialog.ticket = null;
            state.convertDialog.categoryId = '';
        }

        // ------------------------------------------------------------------
        // Subscriptions
        // ------------------------------------------------------------------

        async function subscribe(topicId) {
            try {
                await apiRequest(`/topics/${topicId}/subscribe`, { method: 'POST' });
            } catch (err) {
                console.error('[community] Failed to subscribe:', err);
            }
        }

        async function unsubscribe(topicId) {
            try {
                await apiRequest(`/topics/${topicId}/unsubscribe`, { method: 'POST' });
            } catch (err) {
                console.error('[community] Failed to unsubscribe:', err);
            }
        }

        // ------------------------------------------------------------------
        // Statistics
        // ------------------------------------------------------------------

        async function fetchStats() {
            try {
                const data = await apiRequest('/stats');
                state.stats = data;
                return data;
            } catch (err) {
                console.error('[community] Failed to fetch stats:', err);
                return null;
            }
        }

        // ------------------------------------------------------------------
        // Search
        // ------------------------------------------------------------------

        async function searchTopics(query) {
            try {
                const data = await apiRequest(`/search?q=${encodeURIComponent(query)}`);
                return data.topics || [];
            } catch (err) {
                console.error('[community] Failed to search:', err);
                return [];
            }
        }

        // ------------------------------------------------------------------
        // Suggestion after ticket resolve
        // ------------------------------------------------------------------

        function suggestConversion(ticket) {
            // This can be wired to a toast/notification system
            if (context.notify) {
                context.notify({
                    type: 'info',
                    title: 'Share with the Community?',
                    message: `This resolved ticket could help others. Convert "${ticket?.subject || 'this ticket'}" to a community topic?`,
                    action: {
                        label: 'Convert',
                        handler: () => showConvertDialog(ticket),
                    },
                });
            }
        }

        // ------------------------------------------------------------------
        // Provide the community service
        // ------------------------------------------------------------------
        context.provide('community', {
            state,
            saving,
            // Categories
            fetchCategories,
            saveCategory,
            deleteCategory,
            reorderCategories,
            // Topics
            fetchTopics,
            fetchTopic,
            saveTopic,
            deleteTopic,
            // Posts
            fetchPosts,
            savePost,
            deletePost,
            markAnswer,
            unmarkAnswer,
            // Voting
            vote,
            getUserVote,
            // Moderation
            pinTopic,
            lockTopic,
            moveTopic,
            // Conversion
            convertTicketToTopic,
            convertTopicToTicket,
            showConvertDialog,
            hideConvertDialog,
            // Subscriptions
            subscribe,
            unsubscribe,
            // Stats & search
            fetchStats,
            searchTopics,
            suggestConversion,
        });
    },
});
