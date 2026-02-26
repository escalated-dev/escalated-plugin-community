<template>
    <div :class="['min-h-screen', dark ? 'bg-gray-900 text-gray-200' : 'bg-gray-50 text-gray-800']">
        <!-- ================================================================ -->
        <!-- Top Header Bar                                                   -->
        <!-- ================================================================ -->
        <div
            :class="[
                'px-6 py-4 border-b',
                dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
            ]"
        >
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <!-- Back to categories if viewing a category -->
                    <button
                        v-if="selectedCategory"
                        @click="clearCategory"
                        :class="[
                            'p-1.5 rounded-lg transition-colors',
                            dark ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-500',
                        ]"
                        title="Back to categories"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <div>
                        <h1 :class="['text-xl font-bold', dark ? 'text-gray-100' : 'text-gray-900']">
                            {{ selectedCategory ? selectedCategory.name : 'Community Forums' }}
                        </h1>
                        <p v-if="selectedCategory" :class="['text-sm mt-0.5', dark ? 'text-gray-400' : 'text-gray-500']">
                            {{ selectedCategory.description }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Search bar -->
                    <div class="relative">
                        <svg
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                            :class="dark ? 'text-gray-500' : 'text-gray-400'"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search topics..."
                            @keydown.enter="handleSearch"
                            :class="[
                                'pl-9 pr-4 py-2 text-sm rounded-lg border w-64 transition-colors',
                                dark
                                    ? 'bg-gray-900 border-gray-600 text-gray-200 placeholder-gray-500 focus:border-blue-500'
                                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500',
                            ]"
                        />
                    </div>

                    <!-- New Topic button -->
                    <button
                        v-if="selectedCategory"
                        @click="showNewTopicForm = true"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Topic
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-6 py-6">
            <!-- ============================================================ -->
            <!-- New Topic Form (modal overlay)                                -->
            <!-- ============================================================ -->
            <div
                v-if="showNewTopicForm"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="showNewTopicForm = false"
            >
                <div
                    :class="[
                        'w-full max-w-2xl rounded-xl shadow-2xl border p-6',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <h2 :class="['text-lg font-semibold mb-4', dark ? 'text-gray-100' : 'text-gray-900']">
                        Create New Topic
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label :class="['block text-sm font-medium mb-1', dark ? 'text-gray-300' : 'text-gray-700']">
                                Title
                            </label>
                            <input
                                v-model="newTopic.title"
                                type="text"
                                placeholder="Enter topic title..."
                                :class="[
                                    'w-full px-3 py-2 text-sm rounded-lg border transition-colors',
                                    dark
                                        ? 'bg-gray-900 border-gray-600 text-gray-200 placeholder-gray-500 focus:border-blue-500'
                                        : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500',
                                ]"
                            />
                        </div>

                        <div>
                            <label :class="['block text-sm font-medium mb-1', dark ? 'text-gray-300' : 'text-gray-700']">
                                Body
                            </label>
                            <textarea
                                v-model="newTopic.body"
                                rows="6"
                                placeholder="Write your topic content..."
                                :class="[
                                    'w-full px-3 py-2 text-sm rounded-lg border resize-none transition-colors',
                                    dark
                                        ? 'bg-gray-900 border-gray-600 text-gray-200 placeholder-gray-500 focus:border-blue-500'
                                        : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500',
                                ]"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button
                            @click="showNewTopicForm = false"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                                dark
                                    ? 'text-gray-300 hover:bg-gray-700'
                                    : 'text-gray-600 hover:bg-gray-100',
                            ]"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleCreateTopic"
                            :disabled="!newTopic.title.trim() || !newTopic.body.trim()"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors disabled:opacity-40"
                        >
                            Create Topic
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Category Grid (when no category is selected)                  -->
            <!-- ============================================================ -->
            <div v-if="!selectedCategory && !searchActive">
                <!-- Loading state -->
                <div v-if="loading" class="flex items-center justify-center py-16">
                    <svg class="w-8 h-8 animate-spin" :class="dark ? 'text-gray-500' : 'text-gray-400'" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectCategory(category)"
                        :class="[
                            'rounded-xl border p-5 cursor-pointer transition-all hover:shadow-md',
                            dark
                                ? 'bg-gray-800 border-gray-700 hover:border-blue-600'
                                : 'bg-white border-gray-200 hover:border-blue-400',
                        ]"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <div
                                :class="[
                                    'w-10 h-10 rounded-lg flex items-center justify-center',
                                    dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-50 text-blue-600',
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                </svg>
                            </div>
                            <span
                                :class="[
                                    'text-xs font-medium px-2 py-0.5 rounded-full',
                                    dark ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-500',
                                ]"
                            >
                                {{ category.topic_count || 0 }} {{ (category.topic_count || 0) === 1 ? 'topic' : 'topics' }}
                            </span>
                        </div>

                        <h3 :class="['text-base font-semibold mb-1', dark ? 'text-gray-100' : 'text-gray-900']">
                            {{ category.name }}
                        </h3>
                        <p :class="['text-sm leading-relaxed', dark ? 'text-gray-400' : 'text-gray-500']">
                            {{ category.description || 'No description' }}
                        </p>

                        <!-- Latest topic preview -->
                        <div
                            v-if="latestTopicByCategory[category.id]"
                            :class="[
                                'mt-3 pt-3 border-t',
                                dark ? 'border-gray-700' : 'border-gray-100',
                            ]"
                        >
                            <p :class="['text-xs', dark ? 'text-gray-500' : 'text-gray-400']">Latest topic</p>
                            <p :class="['text-sm font-medium truncate mt-0.5', dark ? 'text-gray-300' : 'text-gray-700']">
                                {{ latestTopicByCategory[category.id].title }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!loading && categories.length === 0"
                    class="flex flex-col items-center justify-center py-16"
                >
                    <div :class="['p-4 rounded-full mb-3', dark ? 'bg-gray-800' : 'bg-gray-100']">
                        <svg class="w-10 h-10" :class="dark ? 'text-gray-600' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <p :class="['text-base font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                        No categories yet
                    </p>
                    <p :class="['text-sm mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                        Categories will appear here once an admin creates them.
                    </p>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Topic List (when a category is selected or search is active)  -->
            <!-- ============================================================ -->
            <div v-if="selectedCategory || searchActive">
                <!-- Sort tabs -->
                <div
                    :class="[
                        'flex items-center gap-1 mb-4 border-b pb-3',
                        dark ? 'border-gray-700' : 'border-gray-200',
                    ]"
                >
                    <button
                        v-for="tab in sortTabs"
                        :key="tab.value"
                        @click="changeSort(tab.value)"
                        :class="[
                            'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                            currentSort === tab.value
                                ? (dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-50 text-blue-700')
                                : (dark ? 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'),
                        ]"
                    >
                        {{ tab.label }}
                    </button>

                    <span class="flex-1"></span>

                    <!-- Search results indicator -->
                    <span
                        v-if="searchActive"
                        :class="['text-sm', dark ? 'text-gray-400' : 'text-gray-500']"
                    >
                        {{ topics.length }} result{{ topics.length !== 1 ? 's' : '' }} for "{{ appliedSearch }}"
                        <button
                            @click="clearSearch"
                            :class="['ml-2 text-xs font-medium underline', dark ? 'text-blue-400' : 'text-blue-600']"
                        >Clear</button>
                    </span>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <svg class="w-7 h-7 animate-spin" :class="dark ? 'text-gray-500' : 'text-gray-400'" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </div>

                <!-- Topic cards -->
                <div v-else class="space-y-2">
                    <div
                        v-for="topic in topics"
                        :key="topic.id"
                        @click="$emit('navigate', 'community.topic', { topicId: topic.id })"
                        :class="[
                            'flex items-start gap-4 rounded-lg border p-4 cursor-pointer transition-all',
                            topic.is_pinned
                                ? (dark ? 'bg-yellow-900/10 border-yellow-700/30' : 'bg-yellow-50 border-yellow-200')
                                : (dark ? 'bg-gray-800 border-gray-700 hover:border-gray-600' : 'bg-white border-gray-200 hover:border-gray-300'),
                        ]"
                    >
                        <!-- Vote arrows -->
                        <div class="flex flex-col items-center gap-0.5 shrink-0 pt-1">
                            <button
                                @click.stop="handleVote(topic.id, 'up')"
                                :class="[
                                    'p-1 rounded transition-colors',
                                    getUserVote('topic', topic.id) === 'up'
                                        ? 'text-blue-500'
                                        : (dark ? 'text-gray-500 hover:text-gray-300' : 'text-gray-400 hover:text-gray-600'),
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>
                            </button>
                            <span
                                :class="[
                                    'text-sm font-semibold tabular-nums',
                                    (topic.vote_count || 0) > 0
                                        ? (dark ? 'text-blue-400' : 'text-blue-600')
                                        : (topic.vote_count || 0) < 0
                                            ? 'text-red-500'
                                            : (dark ? 'text-gray-500' : 'text-gray-400'),
                                ]"
                            >{{ topic.vote_count || 0 }}</span>
                            <button
                                @click.stop="handleVote(topic.id, 'down')"
                                :class="[
                                    'p-1 rounded transition-colors',
                                    getUserVote('topic', topic.id) === 'down'
                                        ? 'text-red-500'
                                        : (dark ? 'text-gray-500 hover:text-gray-300' : 'text-gray-400 hover:text-gray-600'),
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </div>

                        <!-- Topic content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Pinned badge -->
                                <span
                                    v-if="topic.is_pinned"
                                    :class="[
                                        'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-yellow-600/20 text-yellow-400' : 'bg-yellow-100 text-yellow-700',
                                    ]"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                    </svg>
                                    Pinned
                                </span>
                                <!-- Locked badge -->
                                <span
                                    v-if="topic.is_locked"
                                    :class="[
                                        'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-red-600/20 text-red-400' : 'bg-red-100 text-red-600',
                                    ]"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                    Locked
                                </span>
                                <!-- Answered badge -->
                                <span
                                    v-if="topic.is_answered"
                                    class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-600/20 dark:text-green-400"
                                    :class="dark ? 'bg-green-600/20 text-green-400' : 'bg-green-100 text-green-700'"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Answered
                                </span>
                            </div>

                            <h3 :class="['text-sm font-semibold mt-1', dark ? 'text-gray-100' : 'text-gray-900']">
                                {{ topic.title }}
                            </h3>
                            <p :class="['text-sm mt-1 line-clamp-2', dark ? 'text-gray-400' : 'text-gray-500']">
                                {{ truncateText(topic.body, 160) }}
                            </p>

                            <!-- Topic meta row -->
                            <div class="flex items-center gap-4 mt-2.5">
                                <!-- Author -->
                                <div class="flex items-center gap-1.5">
                                    <div
                                        :class="[
                                            'w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-semibold',
                                            topic.author_type === 'agent' || topic.author_type === 'admin'
                                                ? (dark ? 'bg-blue-600/30 text-blue-400' : 'bg-blue-100 text-blue-700')
                                                : (dark ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-600'),
                                        ]"
                                    >{{ getInitials(topic.author_name) }}</div>
                                    <span :class="['text-xs', dark ? 'text-gray-400' : 'text-gray-500']">
                                        {{ topic.author_name || 'Anonymous' }}
                                    </span>
                                </div>

                                <!-- Reply count -->
                                <span :class="['inline-flex items-center gap-1 text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z" />
                                    </svg>
                                    {{ topic.reply_count || 0 }}
                                </span>

                                <!-- View count -->
                                <span :class="['inline-flex items-center gap-1 text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ topic.view_count || 0 }}
                                </span>

                                <!-- Last updated -->
                                <span :class="['text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    {{ formatTime(topic.updated_at || topic.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty topic list -->
                <div
                    v-if="!loading && topics.length === 0"
                    class="flex flex-col items-center justify-center py-16"
                >
                    <div :class="['p-4 rounded-full mb-3', dark ? 'bg-gray-800' : 'bg-gray-100']">
                        <svg class="w-8 h-8" :class="dark ? 'text-gray-600' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 016 21a5.969 5.969 0 01-1.077-.098 4.483 4.483 0 00.923-1.785c.154-.601-.154-1.194-.586-1.641A8.214 8.214 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                    </div>
                    <p :class="['text-sm font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                        {{ searchActive ? 'No topics match your search' : 'No topics yet' }}
                    </p>
                    <p :class="['text-xs mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                        {{ searchActive ? 'Try a different search term' : 'Be the first to start a discussion!' }}
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="totalPages > 1"
                    :class="[
                        'flex items-center justify-center gap-2 mt-6 pt-4 border-t',
                        dark ? 'border-gray-700' : 'border-gray-200',
                    ]"
                >
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage <= 1"
                        :class="paginationBtnClass"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <template v-for="page in visiblePages" :key="page">
                        <span v-if="page === '...'" :class="['px-2 text-sm', dark ? 'text-gray-500' : 'text-gray-400']">...</span>
                        <button
                            v-else
                            @click="goToPage(page)"
                            :class="[
                                'w-8 h-8 text-sm font-medium rounded-lg transition-colors',
                                page === currentPage
                                    ? 'bg-blue-600 text-white'
                                    : (dark ? 'text-gray-400 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100'),
                            ]"
                        >{{ page }}</button>
                    </template>

                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage >= totalPages"
                        :class="paginationBtnClass"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, watch } from 'vue';

const dark = inject('esc-dark', false);
const communityService = inject('community', null);

// ---------------------------------------------------------------------------
// Props & Emits
// ---------------------------------------------------------------------------

const props = defineProps({
    pluginSlug: { type: String, default: 'community' },
    categoryId: { type: String, default: null },
});

const emit = defineEmits(['navigate']);

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const categories = ref([]);
const topics = ref([]);
const selectedCategory = ref(null);
const loading = ref(false);
const searchQuery = ref('');
const appliedSearch = ref('');
const searchActive = ref(false);
const currentSort = ref('recent');
const currentPage = ref(1);
const totalItems = ref(0);
const perPage = ref(20);
const showNewTopicForm = ref(false);
const latestTopicByCategory = ref({});

const newTopic = reactive({
    title: '',
    body: '',
});

// ---------------------------------------------------------------------------
// Sort tabs
// ---------------------------------------------------------------------------

const sortTabs = [
    { value: 'recent', label: 'Recent' },
    { value: 'popular', label: 'Popular' },
    { value: 'unanswered', label: 'Unanswered' },
];

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value));

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }

    pages.push(1);
    if (current > 3) pages.push('...');

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);

    if (current < total - 2) pages.push('...');
    pages.push(total);

    return pages;
});

const paginationBtnClass = computed(() => [
    'p-2 rounded-lg transition-colors disabled:opacity-30',
    dark
        ? 'text-gray-400 hover:bg-gray-700 disabled:hover:bg-transparent'
        : 'text-gray-500 hover:bg-gray-100 disabled:hover:bg-transparent',
]);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
}

function truncateText(text, maxLen) {
    if (!text) return '';
    if (text.length <= maxLen) return text;
    return text.substring(0, maxLen).trim() + '...';
}

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

function getUserVote(targetType, targetId) {
    if (communityService?.getUserVote) {
        return communityService.getUserVote(targetType, targetId);
    }
    return null;
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

async function fetchCategories() {
    loading.value = true;
    try {
        if (communityService?.fetchCategories) {
            await communityService.fetchCategories();
            categories.value = communityService.state.categories || [];
        } else {
            // Demo data
            categories.value = [
                { id: 'cat_1', name: 'General Discussion', slug: 'general', description: 'General topics and conversations', topic_count: 12, position: 0 },
                { id: 'cat_2', name: 'Feature Requests', slug: 'features', description: 'Suggest and vote on new features', topic_count: 8, position: 1 },
                { id: 'cat_3', name: 'Bug Reports', slug: 'bugs', description: 'Report issues and track fixes', topic_count: 5, position: 2 },
                { id: 'cat_4', name: 'Tips & Tricks', slug: 'tips', description: 'Share workflows and best practices', topic_count: 15, position: 3 },
                { id: 'cat_5', name: 'Announcements', slug: 'announcements', description: 'Official updates and news', topic_count: 3, position: 4 },
                { id: 'cat_6', name: 'Integrations', slug: 'integrations', description: 'Discuss third-party integrations', topic_count: 6, position: 5 },
            ];
        }
        // Fetch latest topic for each category
        await fetchLatestTopics();
    } catch (err) {
        console.error('[community] Failed to fetch categories:', err);
    } finally {
        loading.value = false;
    }
}

async function fetchLatestTopics() {
    const map = {};
    for (const cat of categories.value) {
        try {
            if (communityService?.fetchTopics) {
                const result = communityService.state;
                // We need a lightweight fetch - get 1 topic per category
                // For now, we rely on the service's existing topics
            }
        } catch {
            // ignore
        }
    }

    // Demo latest topics
    if (!communityService?.fetchTopics) {
        map['cat_1'] = { title: 'Welcome to the community forums!' };
        map['cat_2'] = { title: 'Dark mode for email templates' };
        map['cat_3'] = { title: 'Search not returning results for tags' };
        map['cat_4'] = { title: 'How to set up automated workflows' };
        map['cat_5'] = { title: 'Version 2.0 is here!' };
        map['cat_6'] = { title: 'Connecting Slack with ticket updates' };
    }
    latestTopicByCategory.value = map;
}

async function fetchTopics() {
    loading.value = true;
    try {
        const filters = {
            category_id: selectedCategory.value?.id || null,
            sort: currentSort.value,
            search: appliedSearch.value || '',
            page: currentPage.value,
            per_page: perPage.value,
        };

        if (communityService?.fetchTopics) {
            await communityService.fetchTopics(filters);
            topics.value = communityService.state.topics || [];
            totalItems.value = communityService.state.pagination?.total || 0;
        } else {
            // Demo topics
            topics.value = generateDemoTopics();
            totalItems.value = topics.value.length;
        }
    } catch (err) {
        console.error('[community] Failed to fetch topics:', err);
    } finally {
        loading.value = false;
    }
}

function generateDemoTopics() {
    const now = Date.now();
    return [
        {
            id: 'top_1', title: 'How to set up multi-channel support?', body: 'I want to configure email, chat, and social channels together. What is the recommended approach for setting up all three channels while maintaining a unified inbox?', author_name: 'Sarah Chen', author_type: 'customer', vote_count: 15, reply_count: 7, view_count: 234, is_pinned: true, is_answered: true, is_locked: false, created_at: new Date(now - 86400000 * 3).toISOString(), updated_at: new Date(now - 3600000).toISOString(),
        },
        {
            id: 'top_2', title: 'Feature Request: Bulk ticket assignment', body: 'It would be great to select multiple tickets and assign them to an agent in one action, rather than one by one.', author_name: 'Mike Johnson', author_type: 'customer', vote_count: 23, reply_count: 12, view_count: 456, is_pinned: false, is_answered: false, is_locked: false, created_at: new Date(now - 86400000 * 5).toISOString(), updated_at: new Date(now - 7200000).toISOString(),
        },
        {
            id: 'top_3', title: 'SLA breach notifications not working', body: 'I configured SLA policies but I am not receiving breach notifications. The SLA timer seems to work but alerts are not being sent.', author_name: 'Emma Wilson', author_type: 'customer', vote_count: 8, reply_count: 4, view_count: 89, is_pinned: false, is_answered: true, is_locked: false, created_at: new Date(now - 86400000 * 1).toISOString(), updated_at: new Date(now - 1800000).toISOString(),
        },
        {
            id: 'top_4', title: 'Best practices for ticket categorization', body: 'What taxonomy do you use for organizing tickets? Looking for ideas on categories and tags.', author_name: 'Alex Support', author_type: 'agent', vote_count: 31, reply_count: 18, view_count: 567, is_pinned: false, is_answered: false, is_locked: false, created_at: new Date(now - 86400000 * 7).toISOString(), updated_at: new Date(now - 86400000).toISOString(),
        },
        {
            id: 'top_5', title: 'API rate limiting documentation', body: 'Where can I find detailed info about API rate limits? The docs mention 1000 requests per minute but I need more details.', author_name: 'David Park', author_type: 'customer', vote_count: 5, reply_count: 2, view_count: 45, is_pinned: false, is_answered: false, is_locked: true, created_at: new Date(now - 86400000 * 2).toISOString(), updated_at: new Date(now - 43200000).toISOString(),
        },
    ];
}

function selectCategory(category) {
    selectedCategory.value = category;
    currentPage.value = 1;
    currentSort.value = 'recent';
    fetchTopics();
}

function clearCategory() {
    selectedCategory.value = null;
    topics.value = [];
    searchActive.value = false;
    appliedSearch.value = '';
    searchQuery.value = '';
}

function changeSort(sort) {
    currentSort.value = sort;
    currentPage.value = 1;
    fetchTopics();
}

function goToPage(page) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    fetchTopics();
}

function handleSearch() {
    const q = searchQuery.value.trim();
    if (!q) {
        clearSearch();
        return;
    }

    appliedSearch.value = q;
    searchActive.value = true;
    currentPage.value = 1;

    // If no category is selected, we still show results
    if (!selectedCategory.value) {
        // Search across all categories
    }

    fetchTopics();
}

function clearSearch() {
    searchQuery.value = '';
    appliedSearch.value = '';
    searchActive.value = false;
    currentPage.value = 1;

    if (selectedCategory.value) {
        fetchTopics();
    }
}

async function handleVote(topicId, direction) {
    if (communityService?.vote) {
        try {
            await communityService.vote('topic', topicId, direction);
        } catch (err) {
            console.error('[community] Vote failed:', err);
        }
    }
}

async function handleCreateTopic() {
    if (!newTopic.title.trim() || !newTopic.body.trim()) return;

    try {
        if (communityService?.saveTopic) {
            await communityService.saveTopic({
                title: newTopic.title,
                body: newTopic.body,
                category_id: selectedCategory.value?.id || '',
            });
        }

        newTopic.title = '';
        newTopic.body = '';
        showNewTopicForm.value = false;
        fetchTopics();
    } catch (err) {
        console.error('[community] Failed to create topic:', err);
    }
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(() => {
    if (props.categoryId) {
        // Direct category link
        selectCategory({ id: props.categoryId });
    } else {
        fetchCategories();
    }
});

watch(
    () => props.categoryId,
    (val) => {
        if (val) {
            selectCategory({ id: val });
        } else {
            clearCategory();
            fetchCategories();
        }
    },
);
</script>
