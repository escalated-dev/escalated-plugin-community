<template>
    <div :class="['min-h-screen', dark ? 'bg-gray-900 text-gray-200' : 'bg-gray-50 text-gray-800']">
        <!-- ================================================================ -->
        <!-- Header                                                            -->
        <!-- ================================================================ -->
        <div
            :class="[
                'px-6 py-4 border-b',
                dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
            ]"
        >
            <div class="max-w-6xl mx-auto">
                <h1 :class="['text-xl font-bold', dark ? 'text-gray-100' : 'text-gray-900']">
                    Community Management
                </h1>
                <p :class="['text-sm mt-0.5', dark ? 'text-gray-400' : 'text-gray-500']">
                    Manage categories, moderation, and community settings
                </p>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-6 py-6">
            <!-- ============================================================ -->
            <!-- Stats Cards                                                   -->
            <!-- ============================================================ -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div
                    v-for="stat in statsCards"
                    :key="stat.label"
                    :class="[
                        'rounded-xl border p-4',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'w-10 h-10 rounded-lg flex items-center justify-center',
                                stat.iconBg,
                            ]"
                        >
                            <svg class="w-5 h-5" :class="stat.iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="stat.iconPath" />
                            </svg>
                        </div>
                        <div>
                            <p :class="['text-2xl font-bold tabular-nums', dark ? 'text-gray-100' : 'text-gray-900']">
                                {{ stat.value }}
                            </p>
                            <p :class="['text-xs', dark ? 'text-gray-400' : 'text-gray-500']">
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Tab Navigation                                                -->
            <!-- ============================================================ -->
            <div
                :class="[
                    'flex items-center gap-1 border-b mb-6',
                    dark ? 'border-gray-700' : 'border-gray-200',
                ]"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    @click="activeTab = tab.value"
                    :class="[
                        'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                        activeTab === tab.value
                            ? (dark ? 'border-blue-400 text-blue-400' : 'border-blue-600 text-blue-600')
                            : (dark ? 'border-transparent text-gray-400 hover:text-gray-200' : 'border-transparent text-gray-500 hover:text-gray-700'),
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count !== undefined"
                        :class="[
                            'ml-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full',
                            activeTab === tab.value
                                ? (dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-100 text-blue-600')
                                : (dark ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-500'),
                        ]"
                    >{{ tab.count }}</span>
                </button>
            </div>

            <!-- ============================================================ -->
            <!-- Categories Tab                                                -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'categories'">
                <!-- Add category button -->
                <div class="flex items-center justify-between mb-4">
                    <h2 :class="['text-base font-semibold', dark ? 'text-gray-200' : 'text-gray-800']">
                        Forum Categories
                    </h2>
                    <button
                        @click="openCategoryForm(null)"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Category
                    </button>
                </div>

                <p :class="['text-xs mb-3', dark ? 'text-gray-500' : 'text-gray-400']">
                    Drag categories to reorder them. Changes are saved automatically.
                </p>

                <!-- Category list (drag-reorderable) -->
                <div class="space-y-2">
                    <div
                        v-for="(category, index) in categories"
                        :key="category.id"
                        :draggable="true"
                        @dragstart="handleDragStart(index, $event)"
                        @dragover.prevent="handleDragOver(index, $event)"
                        @drop="handleDrop(index)"
                        @dragend="dragIndex = null"
                        :class="[
                            'flex items-center gap-3 rounded-lg border p-4 transition-all',
                            dragIndex === index
                                ? (dark ? 'opacity-50 bg-gray-700 border-blue-600' : 'opacity-50 bg-blue-50 border-blue-300')
                                : (dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'),
                            dragOverIndex === index && dragIndex !== index
                                ? (dark ? 'border-blue-500' : 'border-blue-400')
                                : '',
                        ]"
                    >
                        <!-- Drag handle -->
                        <div
                            :class="['cursor-grab shrink-0', dark ? 'text-gray-500' : 'text-gray-400']"
                            title="Drag to reorder"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                            </svg>
                        </div>

                        <!-- Category info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 :class="['text-sm font-semibold', dark ? 'text-gray-100' : 'text-gray-900']">
                                    {{ category.name }}
                                </h3>
                                <span
                                    :class="[
                                        'text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-500',
                                    ]"
                                >/{{ category.slug }}</span>
                            </div>
                            <p :class="['text-xs mt-0.5', dark ? 'text-gray-400' : 'text-gray-500']">
                                {{ category.description || 'No description' }}
                            </p>
                        </div>

                        <!-- Topic count -->
                        <div :class="['text-center shrink-0 px-3', dark ? 'text-gray-400' : 'text-gray-500']">
                            <p class="text-lg font-bold tabular-nums">{{ category.topic_count || 0 }}</p>
                            <p class="text-[10px]">topics</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 shrink-0">
                            <button
                                @click="openCategoryForm(category)"
                                :class="[
                                    'p-1.5 rounded transition-colors',
                                    dark ? 'text-gray-400 hover:text-gray-200 hover:bg-gray-700' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100',
                                ]"
                                title="Edit category"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            <button
                                @click="handleDeleteCategory(category.id)"
                                :class="[
                                    'p-1.5 rounded transition-colors',
                                    dark ? 'text-red-400 hover:bg-red-900/30' : 'text-red-500 hover:bg-red-50',
                                ]"
                                title="Delete category"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty categories -->
                <div
                    v-if="categories.length === 0"
                    :class="[
                        'text-center py-12 rounded-xl border',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <p :class="['text-sm', dark ? 'text-gray-400' : 'text-gray-500']">
                        No categories configured. Add your first category to get started.
                    </p>
                </div>

                <!-- Category edit/add modal -->
                <div
                    v-if="showCategoryForm"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @click.self="showCategoryForm = false"
                >
                    <div
                        :class="[
                            'w-full max-w-lg rounded-xl shadow-2xl border p-6',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                        ]"
                    >
                        <h2 :class="['text-lg font-semibold mb-4', dark ? 'text-gray-100' : 'text-gray-900']">
                            {{ editingCategory ? 'Edit Category' : 'Add Category' }}
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <label :class="['block text-sm font-medium mb-1', dark ? 'text-gray-300' : 'text-gray-700']">Name</label>
                                <input
                                    v-model="categoryForm.name"
                                    type="text"
                                    placeholder="e.g., General Discussion"
                                    :class="inputClass"
                                />
                            </div>
                            <div>
                                <label :class="['block text-sm font-medium mb-1', dark ? 'text-gray-300' : 'text-gray-700']">Slug</label>
                                <input
                                    v-model="categoryForm.slug"
                                    type="text"
                                    placeholder="auto-generated from name"
                                    :class="inputClass"
                                />
                                <p :class="['text-[11px] mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                                    Leave blank to auto-generate from name.
                                </p>
                            </div>
                            <div>
                                <label :class="['block text-sm font-medium mb-1', dark ? 'text-gray-300' : 'text-gray-700']">Description</label>
                                <textarea
                                    v-model="categoryForm.description"
                                    rows="3"
                                    placeholder="Short description of this category"
                                    :class="[inputClass, 'resize-none']"
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button
                                @click="showCategoryForm = false"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                    dark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100',
                                ]"
                            >Cancel</button>
                            <button
                                @click="handleSaveCategory"
                                :disabled="!categoryForm.name.trim()"
                                class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors disabled:opacity-40"
                            >
                                {{ editingCategory ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Moderation Tab                                                -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'moderation'">
                <h2 :class="['text-base font-semibold mb-4', dark ? 'text-gray-200' : 'text-gray-800']">
                    Moderation Queue
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="item in moderationQueue"
                        :key="item.id"
                        :class="[
                            'flex items-start gap-4 rounded-lg border p-4',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                        ]"
                    >
                        <!-- Flag indicator -->
                        <div
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                                dark ? 'bg-red-600/20' : 'bg-red-50',
                            ]"
                        >
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span :class="[
                                    'text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded',
                                    item.type === 'topic'
                                        ? (dark ? 'bg-indigo-600/20 text-indigo-400' : 'bg-indigo-100 text-indigo-600')
                                        : (dark ? 'bg-purple-600/20 text-purple-400' : 'bg-purple-100 text-purple-600'),
                                ]">{{ item.type }}</span>
                                <span :class="['text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    Reported {{ formatTime(item.reported_at) }}
                                </span>
                            </div>
                            <p :class="['text-sm font-medium', dark ? 'text-gray-200' : 'text-gray-800']">
                                {{ item.title || item.body?.substring(0, 100) }}
                            </p>
                            <p :class="['text-xs mt-1', dark ? 'text-gray-400' : 'text-gray-500']">
                                by {{ item.author_name }} &middot; Reason: {{ item.reason }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                @click="handleApproveModeration(item.id)"
                                :class="[
                                    'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                                    dark
                                        ? 'text-green-400 border border-green-700/30 hover:bg-green-900/30'
                                        : 'text-green-600 border border-green-200 hover:bg-green-50',
                                ]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Approve
                            </button>
                            <button
                                @click="handleRemoveModeration(item.id)"
                                :class="[
                                    'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                                    dark
                                        ? 'text-red-400 border border-red-700/30 hover:bg-red-900/30'
                                        : 'text-red-600 border border-red-200 hover:bg-red-50',
                                ]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty moderation -->
                <div
                    v-if="moderationQueue.length === 0"
                    :class="[
                        'text-center py-12 rounded-xl border',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <svg class="w-8 h-8 mx-auto mb-2" :class="dark ? 'text-gray-600' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p :class="['text-sm font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                        Moderation queue is empty
                    </p>
                    <p :class="['text-xs mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                        No flagged or reported content to review.
                    </p>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Pinned Topics Tab                                             -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'pinned'">
                <h2 :class="['text-base font-semibold mb-4', dark ? 'text-gray-200' : 'text-gray-800']">
                    Pinned Topics
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="topic in pinnedTopics"
                        :key="topic.id"
                        :class="[
                            'flex items-center gap-4 rounded-lg border p-4',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                        ]"
                    >
                        <!-- Pin icon -->
                        <div :class="['shrink-0', dark ? 'text-yellow-400' : 'text-yellow-600']">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                            </svg>
                        </div>

                        <!-- Topic info -->
                        <div class="flex-1 min-w-0">
                            <h3 :class="['text-sm font-semibold', dark ? 'text-gray-100' : 'text-gray-900']">
                                {{ topic.title }}
                            </h3>
                            <p :class="['text-xs mt-0.5', dark ? 'text-gray-400' : 'text-gray-500']">
                                {{ topic.author_name }} &middot; {{ topic.reply_count || 0 }} replies &middot; {{ topic.vote_count || 0 }} votes
                            </p>
                        </div>

                        <!-- Category badge -->
                        <span
                            :class="[
                                'text-xs font-medium px-2 py-0.5 rounded shrink-0',
                                dark ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-500',
                            ]"
                        >{{ getCategoryName(topic.category_id) }}</span>

                        <!-- Unpin button -->
                        <button
                            @click="handleUnpinTopic(topic.id)"
                            :class="[
                                'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors shrink-0',
                                dark ? 'text-yellow-400 hover:bg-yellow-900/30' : 'text-yellow-600 hover:bg-yellow-50',
                            ]"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Unpin
                        </button>
                    </div>
                </div>

                <!-- Empty pinned -->
                <div
                    v-if="pinnedTopics.length === 0"
                    :class="[
                        'text-center py-12 rounded-xl border',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <p :class="['text-sm font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                        No pinned topics
                    </p>
                    <p :class="['text-xs mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                        Pin important topics from the topic view to feature them at the top of their category.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted } from 'vue';

const dark = inject('esc-dark', false);
const communityService = inject('community', null);

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

const props = defineProps({
    pluginSlug: { type: String, default: 'community' },
});

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const activeTab = ref('categories');
const categories = ref([]);
const pinnedTopics = ref([]);
const moderationQueue = ref([]);
const stats = reactive({
    total_topics: 0,
    total_posts: 0,
    active_users: 0,
    topics_this_week: 0,
    answered_topics: 0,
});

// Category form
const showCategoryForm = ref(false);
const editingCategory = ref(null);
const categoryForm = reactive({
    name: '',
    slug: '',
    description: '',
});

// Drag state
const dragIndex = ref(null);
const dragOverIndex = ref(null);

// ---------------------------------------------------------------------------
// Tabs
// ---------------------------------------------------------------------------

const tabs = computed(() => [
    { value: 'categories', label: 'Categories', count: categories.value.length },
    { value: 'moderation', label: 'Moderation', count: moderationQueue.value.length },
    { value: 'pinned', label: 'Pinned Topics', count: pinnedTopics.value.length },
]);

// ---------------------------------------------------------------------------
// Stats cards
// ---------------------------------------------------------------------------

const statsCards = computed(() => [
    {
        label: 'Total Topics',
        value: stats.total_topics,
        iconPath: 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155',
        iconBg: dark ? 'bg-blue-600/20' : 'bg-blue-50',
        iconColor: dark ? 'text-blue-400' : 'text-blue-600',
    },
    {
        label: 'Total Posts',
        value: stats.total_posts,
        iconPath: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z',
        iconBg: dark ? 'bg-green-600/20' : 'bg-green-50',
        iconColor: dark ? 'text-green-400' : 'text-green-600',
    },
    {
        label: 'Active Users',
        value: stats.active_users,
        iconPath: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        iconBg: dark ? 'bg-purple-600/20' : 'bg-purple-50',
        iconColor: dark ? 'text-purple-400' : 'text-purple-600',
    },
    {
        label: 'Topics This Week',
        value: stats.topics_this_week,
        iconPath: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
        iconBg: dark ? 'bg-orange-600/20' : 'bg-orange-50',
        iconColor: dark ? 'text-orange-400' : 'text-orange-600',
    },
]);

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

const inputClass = computed(() => [
    'w-full px-3 py-2 text-sm rounded-lg border transition-colors',
    dark
        ? 'bg-gray-900 border-gray-600 text-gray-200 placeholder-gray-500 focus:border-blue-500'
        : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500',
]);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return '';

    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHrs < 24) return `${diffHrs}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function getCategoryName(categoryId) {
    const cat = categories.value.find((c) => c.id === categoryId);
    return cat?.name || 'Unknown';
}

// ---------------------------------------------------------------------------
// Drag & Drop for category reorder
// ---------------------------------------------------------------------------

function handleDragStart(index, event) {
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(index, event) {
    dragOverIndex.value = index;
    event.dataTransfer.dropEffect = 'move';
}

function handleDrop(targetIndex) {
    if (dragIndex.value === null || dragIndex.value === targetIndex) {
        dragIndex.value = null;
        dragOverIndex.value = null;
        return;
    }

    const items = [...categories.value];
    const [moved] = items.splice(dragIndex.value, 1);
    items.splice(targetIndex, 0, moved);

    // Update positions
    items.forEach((item, idx) => {
        item.position = idx;
    });

    categories.value = items;
    dragIndex.value = null;
    dragOverIndex.value = null;

    // Persist the new order
    const orderedIds = items.map((c) => c.id);
    if (communityService?.reorderCategories) {
        communityService.reorderCategories(orderedIds).catch((err) => {
            console.error('[community] Failed to reorder categories:', err);
        });
    }
}

// ---------------------------------------------------------------------------
// Category CRUD
// ---------------------------------------------------------------------------

function openCategoryForm(category) {
    editingCategory.value = category;
    if (category) {
        categoryForm.name = category.name || '';
        categoryForm.slug = category.slug || '';
        categoryForm.description = category.description || '';
    } else {
        categoryForm.name = '';
        categoryForm.slug = '';
        categoryForm.description = '';
    }
    showCategoryForm.value = true;
}

async function handleSaveCategory() {
    if (!categoryForm.name.trim()) return;

    const data = {
        name: categoryForm.name,
        slug: categoryForm.slug || '',
        description: categoryForm.description,
    };

    if (editingCategory.value) {
        data.id = editingCategory.value.id;
    }

    try {
        if (communityService?.saveCategory) {
            const saved = await communityService.saveCategory(data);

            if (editingCategory.value) {
                const idx = categories.value.findIndex((c) => c.id === saved.id);
                if (idx >= 0) categories.value[idx] = saved;
            } else {
                categories.value.push(saved);
            }
        } else {
            // Demo: add locally
            const demoId = 'cat_' + Date.now();
            const item = {
                id: data.id || demoId,
                name: data.name,
                slug: data.slug || data.name.toLowerCase().replace(/\s+/g, '-'),
                description: data.description,
                topic_count: editingCategory.value?.topic_count || 0,
                position: categories.value.length,
            };

            if (editingCategory.value) {
                const idx = categories.value.findIndex((c) => c.id === item.id);
                if (idx >= 0) categories.value[idx] = item;
            } else {
                categories.value.push(item);
            }
        }
    } catch (err) {
        console.error('[community] Failed to save category:', err);
    }

    showCategoryForm.value = false;
}

async function handleDeleteCategory(categoryId) {
    if (!confirm('Delete this category? Topics in this category will become uncategorized.')) return;

    try {
        if (communityService?.deleteCategory) {
            await communityService.deleteCategory(categoryId);
        }
        categories.value = categories.value.filter((c) => c.id !== categoryId);
    } catch (err) {
        console.error('[community] Failed to delete category:', err);
    }
}

// ---------------------------------------------------------------------------
// Moderation actions
// ---------------------------------------------------------------------------

function handleApproveModeration(itemId) {
    moderationQueue.value = moderationQueue.value.filter((m) => m.id !== itemId);
}

function handleRemoveModeration(itemId) {
    moderationQueue.value = moderationQueue.value.filter((m) => m.id !== itemId);
}

// ---------------------------------------------------------------------------
// Pinned topics management
// ---------------------------------------------------------------------------

async function handleUnpinTopic(topicId) {
    try {
        if (communityService?.pinTopic) {
            await communityService.pinTopic(topicId, false);
        }
        pinnedTopics.value = pinnedTopics.value.filter((t) => t.id !== topicId);
    } catch (err) {
        console.error('[community] Failed to unpin topic:', err);
    }
}

// ---------------------------------------------------------------------------
// Data loading
// ---------------------------------------------------------------------------

async function loadData() {
    try {
        // Fetch categories
        if (communityService?.fetchCategories) {
            await communityService.fetchCategories();
            categories.value = communityService.state.categories || [];
        } else {
            categories.value = [
                { id: 'cat_1', name: 'General Discussion', slug: 'general-discussion', description: 'General topics and conversations', topic_count: 24, position: 0 },
                { id: 'cat_2', name: 'Feature Requests', slug: 'feature-requests', description: 'Suggest and vote on new features', topic_count: 18, position: 1 },
                { id: 'cat_3', name: 'Bug Reports', slug: 'bug-reports', description: 'Report issues and track fixes', topic_count: 9, position: 2 },
                { id: 'cat_4', name: 'Tips & Tricks', slug: 'tips-tricks', description: 'Share workflows and best practices', topic_count: 31, position: 3 },
            ];
        }

        // Fetch stats
        if (communityService?.fetchStats) {
            const data = await communityService.fetchStats();
            if (data) {
                Object.assign(stats, data);
            }
        } else {
            stats.total_topics = 82;
            stats.total_posts = 347;
            stats.active_users = 56;
            stats.topics_this_week = 12;
            stats.answered_topics = 45;
        }

        // Fetch pinned topics
        if (communityService?.fetchTopics) {
            // Fetch all pinned topics
            await communityService.fetchTopics({ sort: 'recent', per_page: 100 });
            const allTopics = communityService.state.topics || [];
            pinnedTopics.value = allTopics.filter((t) => t.is_pinned);
        } else {
            pinnedTopics.value = [
                { id: 'top_p1', title: 'Welcome to the Community Forums!', author_name: 'Admin', reply_count: 5, vote_count: 42, category_id: 'cat_1', is_pinned: true },
                { id: 'top_p2', title: 'Community Guidelines and Rules', author_name: 'Admin', reply_count: 2, vote_count: 28, category_id: 'cat_1', is_pinned: true },
            ];
        }

        // Demo moderation items
        if (!communityService) {
            const now = Date.now();
            moderationQueue.value = [
                { id: 'mod_1', type: 'post', title: null, body: 'This is spam content that was flagged by a user for containing promotional links and off-topic material.', author_name: 'SpamUser99', reason: 'Spam', reported_at: new Date(now - 3600000).toISOString() },
                { id: 'mod_2', type: 'topic', title: 'URGENT: Free gifts for everyone!!!', body: null, author_name: 'NewUser123', reason: 'Spam / Promotional', reported_at: new Date(now - 7200000).toISOString() },
            ];
        }
    } catch (err) {
        console.error('[community] Failed to load admin data:', err);
    }
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(() => {
    loadData();
});
</script>
