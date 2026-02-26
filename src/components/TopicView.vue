<template>
    <div :class="['min-h-screen', dark ? 'bg-gray-900 text-gray-200' : 'bg-gray-50 text-gray-800']">
        <!-- ================================================================ -->
        <!-- Header / Breadcrumb                                              -->
        <!-- ================================================================ -->
        <div
            :class="[
                'px-6 py-3 border-b',
                dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
            ]"
        >
            <div class="max-w-4xl mx-auto">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-xs mb-1">
                    <button
                        @click="$emit('navigate', 'community')"
                        :class="[dark ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-700']"
                    >Community</button>
                    <span :class="dark ? 'text-gray-600' : 'text-gray-400'">/</span>
                    <button
                        v-if="categoryName"
                        @click="$emit('navigate', 'community', { categoryId: topic?.category_id })"
                        :class="[dark ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-700']"
                    >{{ categoryName }}</button>
                    <span v-if="categoryName" :class="dark ? 'text-gray-600' : 'text-gray-400'">/</span>
                    <span :class="dark ? 'text-gray-400' : 'text-gray-500'">Topic</span>
                </div>

                <!-- Moderation toolbar (admin only) -->
                <div
                    v-if="isAdminOrAgent && topic"
                    :class="[
                        'flex items-center gap-2 mt-2 p-2 rounded-lg border',
                        dark ? 'bg-gray-700/50 border-gray-600' : 'bg-gray-50 border-gray-200',
                    ]"
                >
                    <span :class="['text-[10px] font-semibold uppercase tracking-wider mr-1', dark ? 'text-gray-500' : 'text-gray-400']">
                        Moderation
                    </span>

                    <!-- Pin/Unpin -->
                    <button
                        @click="handlePin"
                        :class="modBtnClass"
                        :title="topic.is_pinned ? 'Unpin topic' : 'Pin topic'"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        {{ topic.is_pinned ? 'Unpin' : 'Pin' }}
                    </button>

                    <!-- Lock/Unlock -->
                    <button
                        @click="handleLock"
                        :class="modBtnClass"
                        :title="topic.is_locked ? 'Unlock topic' : 'Lock topic'"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path v-if="topic.is_locked" fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            <path v-else fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                        </svg>
                        {{ topic.is_locked ? 'Unlock' : 'Lock' }}
                    </button>

                    <!-- Move to Category -->
                    <div class="relative">
                        <button
                            @click="showMoveMenu = !showMoveMenu"
                            :class="modBtnClass"
                            title="Move to category"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                            </svg>
                            Move
                        </button>
                        <!-- Move dropdown -->
                        <div
                            v-if="showMoveMenu"
                            :class="[
                                'absolute top-full left-0 mt-1 w-48 rounded-lg border shadow-lg z-20 py-1',
                                dark ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-200',
                            ]"
                        >
                            <button
                                v-for="cat in availableCategories"
                                :key="cat.id"
                                @click="handleMove(cat.id)"
                                :class="[
                                    'block w-full text-left px-3 py-1.5 text-sm transition-colors',
                                    cat.id === topic?.category_id
                                        ? (dark ? 'bg-gray-600 text-gray-300' : 'bg-gray-100 text-gray-400')
                                        : (dark ? 'text-gray-200 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-50'),
                                ]"
                                :disabled="cat.id === topic?.category_id"
                            >
                                {{ cat.name }}
                                <span v-if="cat.id === topic?.category_id" class="text-xs ml-1">(current)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Delete -->
                    <button
                        @click="handleDeleteTopic"
                        :class="[
                            'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors',
                            dark ? 'text-red-400 hover:bg-red-900/30' : 'text-red-600 hover:bg-red-50',
                        ]"
                        title="Delete topic"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Delete
                    </button>

                    <span class="flex-1"></span>

                    <!-- Convert to Ticket -->
                    <button
                        @click="handleConvertToTicket"
                        :class="[
                            'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors',
                            dark ? 'text-indigo-400 hover:bg-indigo-900/30' : 'text-indigo-600 hover:bg-indigo-50',
                        ]"
                        title="Convert to ticket"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                        </svg>
                        Convert to Ticket
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- Topic Content                                                     -->
        <!-- ================================================================ -->
        <div class="max-w-4xl mx-auto px-6 py-6">
            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-16">
                <svg class="w-8 h-8 animate-spin" :class="dark ? 'text-gray-500' : 'text-gray-400'" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
            </div>

            <template v-if="!loading && topic">
                <!-- Topic header + original post -->
                <div
                    :class="[
                        'rounded-xl border p-6',
                        dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <!-- Vote column -->
                        <div class="flex flex-col items-center gap-0.5 shrink-0 pt-1">
                            <button
                                @click="handleVote('topic', topic.id, 'up')"
                                :class="[
                                    'p-1.5 rounded-lg transition-colors',
                                    getUserVoteDir('topic', topic.id) === 'up'
                                        ? (dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-50 text-blue-600')
                                        : (dark ? 'text-gray-500 hover:text-gray-300 hover:bg-gray-700' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'),
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>
                            </button>
                            <span
                                :class="[
                                    'text-lg font-bold tabular-nums',
                                    (topic.vote_count || 0) > 0
                                        ? (dark ? 'text-blue-400' : 'text-blue-600')
                                        : (topic.vote_count || 0) < 0
                                            ? 'text-red-500'
                                            : (dark ? 'text-gray-500' : 'text-gray-400'),
                                ]"
                            >{{ topic.vote_count || 0 }}</span>
                            <button
                                @click="handleVote('topic', topic.id, 'down')"
                                :class="[
                                    'p-1.5 rounded-lg transition-colors',
                                    getUserVoteDir('topic', topic.id) === 'down'
                                        ? (dark ? 'bg-red-600/20 text-red-400' : 'bg-red-50 text-red-600')
                                        : (dark ? 'text-gray-500 hover:text-gray-300 hover:bg-gray-700' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'),
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </div>

                        <!-- Topic body -->
                        <div class="flex-1 min-w-0">
                            <!-- Title -->
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <h1 :class="['text-xl font-bold', dark ? 'text-gray-100' : 'text-gray-900']">
                                    {{ topic.title }}
                                </h1>
                                <span
                                    v-if="topic.is_pinned"
                                    :class="[
                                        'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-yellow-600/20 text-yellow-400' : 'bg-yellow-100 text-yellow-700',
                                    ]"
                                >Pinned</span>
                                <span
                                    v-if="topic.is_locked"
                                    :class="[
                                        'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-red-600/20 text-red-400' : 'bg-red-100 text-red-600',
                                    ]"
                                >Locked</span>
                                <span
                                    v-if="topic.is_answered"
                                    :class="[
                                        'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded',
                                        dark ? 'bg-green-600/20 text-green-400' : 'bg-green-100 text-green-700',
                                    ]"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Answered
                                </span>
                            </div>

                            <!-- Author meta -->
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        :class="[
                                            'w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold',
                                            topic.author_type === 'agent' || topic.author_type === 'admin'
                                                ? (dark ? 'bg-blue-600/30 text-blue-400' : 'bg-blue-100 text-blue-700')
                                                : (dark ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-600'),
                                        ]"
                                    >{{ getInitials(topic.author_name) }}</div>
                                    <span :class="['text-sm font-medium', dark ? 'text-gray-200' : 'text-gray-700']">
                                        {{ topic.author_name || 'Anonymous' }}
                                    </span>
                                    <span
                                        v-if="topic.author_type === 'agent' || topic.author_type === 'admin'"
                                        :class="[
                                            'text-[9px] font-medium px-1 py-0.5 rounded',
                                            dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-100 text-blue-600',
                                        ]"
                                    >{{ topic.author_type === 'admin' ? 'Admin' : 'Agent' }}</span>
                                </div>

                                <span :class="['text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    {{ formatTime(topic.created_at) }}
                                </span>

                                <span :class="['inline-flex items-center gap-1 text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ topic.view_count || 0 }} views
                                </span>
                            </div>

                            <!-- Body content -->
                            <div :class="['text-sm leading-relaxed whitespace-pre-wrap', dark ? 'text-gray-300' : 'text-gray-700']">
                                {{ topic.body }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- Replies Section                                           -->
                <!-- ======================================================== -->
                <div class="mt-6">
                    <div class="flex items-center gap-2 mb-4">
                        <h2 :class="['text-base font-semibold', dark ? 'text-gray-200' : 'text-gray-800']">
                            {{ posts.length }} {{ posts.length === 1 ? 'Reply' : 'Replies' }}
                        </h2>
                    </div>

                    <!-- Reply cards -->
                    <div class="space-y-3">
                        <div
                            v-for="post in posts"
                            :key="post.id"
                            :class="[
                                'flex items-start gap-4 rounded-xl border p-5 transition-colors',
                                post.is_answer
                                    ? (dark ? 'bg-green-900/10 border-green-700/40' : 'bg-green-50 border-green-200')
                                    : (dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'),
                            ]"
                        >
                            <!-- Vote column -->
                            <div class="flex flex-col items-center gap-0.5 shrink-0 pt-1">
                                <button
                                    @click="handleVote('post', post.id, 'up')"
                                    :class="[
                                        'p-1 rounded transition-colors',
                                        getUserVoteDir('post', post.id) === 'up'
                                            ? (dark ? 'text-blue-400' : 'text-blue-600')
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
                                        (post.vote_count || 0) > 0
                                            ? (dark ? 'text-blue-400' : 'text-blue-600')
                                            : (post.vote_count || 0) < 0
                                                ? 'text-red-500'
                                                : (dark ? 'text-gray-500' : 'text-gray-400'),
                                    ]"
                                >{{ post.vote_count || 0 }}</span>
                                <button
                                    @click="handleVote('post', post.id, 'down')"
                                    :class="[
                                        'p-1 rounded transition-colors',
                                        getUserVoteDir('post', post.id) === 'down'
                                            ? (dark ? 'text-red-400' : 'text-red-600')
                                            : (dark ? 'text-gray-500 hover:text-gray-300' : 'text-gray-400 hover:text-gray-600'),
                                    ]"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Reply content -->
                            <div class="flex-1 min-w-0">
                                <!-- Answer badge -->
                                <div
                                    v-if="post.is_answer"
                                    class="flex items-center gap-1.5 mb-2"
                                >
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs font-semibold text-green-600 dark:text-green-400" :class="dark ? 'text-green-400' : 'text-green-600'">
                                        Accepted Answer
                                    </span>
                                </div>

                                <!-- Author line -->
                                <div class="flex items-center gap-2 mb-2">
                                    <div
                                        :class="[
                                            'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-semibold',
                                            post.author_type === 'agent' || post.author_type === 'admin'
                                                ? (dark ? 'bg-blue-600/30 text-blue-400' : 'bg-blue-100 text-blue-700')
                                                : (dark ? 'bg-gray-700 text-gray-300' : 'bg-gray-200 text-gray-600'),
                                        ]"
                                    >{{ getInitials(post.author_name) }}</div>
                                    <span :class="['text-sm font-medium', dark ? 'text-gray-200' : 'text-gray-700']">
                                        {{ post.author_name || 'Anonymous' }}
                                    </span>
                                    <span
                                        v-if="post.author_type === 'agent' || post.author_type === 'admin'"
                                        :class="[
                                            'text-[9px] font-medium px-1 py-0.5 rounded',
                                            dark ? 'bg-blue-600/20 text-blue-400' : 'bg-blue-100 text-blue-600',
                                        ]"
                                    >{{ post.author_type === 'admin' ? 'Admin' : 'Agent' }}</span>
                                    <span :class="['text-xs', dark ? 'text-gray-500' : 'text-gray-400']">
                                        {{ formatTime(post.created_at) }}
                                    </span>
                                </div>

                                <!-- Body -->
                                <div :class="['text-sm leading-relaxed whitespace-pre-wrap', dark ? 'text-gray-300' : 'text-gray-700']">
                                    {{ post.body }}
                                </div>

                                <!-- Mark as answer button (for agent/admin) -->
                                <div v-if="isAdminOrAgent" class="mt-3">
                                    <button
                                        v-if="!post.is_answer"
                                        @click="handleMarkAnswer(post.id)"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors',
                                            dark
                                                ? 'text-green-400 hover:bg-green-900/30 border border-green-700/30'
                                                : 'text-green-600 hover:bg-green-50 border border-green-200',
                                        ]"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Mark as Answer
                                    </button>
                                    <button
                                        v-else
                                        @click="handleUnmarkAnswer"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors',
                                            dark
                                                ? 'text-yellow-400 hover:bg-yellow-900/30 border border-yellow-700/30'
                                                : 'text-yellow-600 hover:bg-yellow-50 border border-yellow-200',
                                        ]"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Unmark Answer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty replies -->
                    <div
                        v-if="!loading && posts.length === 0"
                        :class="[
                            'text-center py-10 rounded-xl border',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                        ]"
                    >
                        <p :class="['text-sm', dark ? 'text-gray-400' : 'text-gray-500']">
                            No replies yet. Be the first to respond!
                        </p>
                    </div>

                    <!-- ================================================== -->
                    <!-- Reply Input                                         -->
                    <!-- ================================================== -->
                    <div
                        v-if="!topic.is_locked"
                        :class="[
                            'mt-6 rounded-xl border p-5',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                        ]"
                    >
                        <h3 :class="['text-sm font-semibold mb-3', dark ? 'text-gray-200' : 'text-gray-800']">
                            Post a Reply
                        </h3>
                        <textarea
                            v-model="replyText"
                            rows="4"
                            placeholder="Write your reply..."
                            :class="[
                                'w-full px-3 py-2 text-sm rounded-lg border resize-none transition-colors',
                                dark
                                    ? 'bg-gray-900 border-gray-600 text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
                            ]"
                        ></textarea>
                        <div class="flex items-center justify-end mt-3">
                            <button
                                @click="handleSubmitReply"
                                :disabled="!replyText.trim() || submitting"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors disabled:opacity-40"
                            >
                                <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Submit Reply
                            </button>
                        </div>
                    </div>

                    <!-- Locked notice -->
                    <div
                        v-else
                        :class="[
                            'mt-6 text-center py-6 rounded-xl border',
                            dark ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200',
                        ]"
                    >
                        <svg class="w-6 h-6 mx-auto mb-2" :class="dark ? 'text-gray-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <p :class="['text-sm font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                            This topic is locked
                        </p>
                        <p :class="['text-xs mt-1', dark ? 'text-gray-500' : 'text-gray-400']">
                            Replies are no longer accepted.
                        </p>
                    </div>
                </div>
            </template>

            <!-- Not found state -->
            <div
                v-if="!loading && !topic"
                class="flex flex-col items-center justify-center py-16"
            >
                <p :class="['text-base font-medium', dark ? 'text-gray-400' : 'text-gray-500']">
                    Topic not found
                </p>
                <button
                    @click="$emit('navigate', 'community')"
                    class="mt-3 text-sm text-blue-600 hover:text-blue-700 font-medium"
                >
                    Back to Community
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue';

const dark = inject('esc-dark', false);
const communityService = inject('community', null);

// ---------------------------------------------------------------------------
// Props & Emits
// ---------------------------------------------------------------------------

const props = defineProps({
    topicId: { type: String, default: null },
    pluginSlug: { type: String, default: 'community' },
});

const emit = defineEmits(['navigate']);

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const topic = ref(null);
const posts = ref([]);
const loading = ref(false);
const submitting = ref(false);
const replyText = ref('');
const showMoveMenu = ref(false);
const availableCategories = ref([]);
const categoryName = ref('');

// Assume agent/admin for moderation -- in real app, this comes from auth context
const isAdminOrAgent = ref(true);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
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
    if (diffDays < 365) return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function getUserVoteDir(targetType, targetId) {
    if (communityService?.getUserVote) {
        return communityService.getUserVote(targetType, targetId);
    }
    return null;
}

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

const modBtnClass = computed(() => [
    'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded transition-colors',
    dark
        ? 'text-gray-300 hover:bg-gray-600'
        : 'text-gray-600 hover:bg-gray-200',
]);

// ---------------------------------------------------------------------------
// Data loading
// ---------------------------------------------------------------------------

async function loadTopic() {
    if (!props.topicId) return;

    loading.value = true;
    try {
        if (communityService?.fetchTopic) {
            topic.value = await communityService.fetchTopic(props.topicId);
            await communityService.fetchPosts(props.topicId);
            posts.value = communityService.state.currentPosts || [];

            // Load category name
            if (topic.value?.category_id && communityService.state.categories?.length) {
                const cat = communityService.state.categories.find((c) => c.id === topic.value.category_id);
                categoryName.value = cat?.name || '';
            }

            // Load all categories for move menu
            availableCategories.value = communityService.state.categories || [];
        } else {
            // Demo data
            loadDemoData();
        }
    } catch (err) {
        console.error('[community] Failed to load topic:', err);
        loadDemoData();
    } finally {
        loading.value = false;
    }
}

function loadDemoData() {
    const now = Date.now();
    topic.value = {
        id: props.topicId || 'top_demo',
        category_id: 'cat_1',
        title: 'How to set up multi-channel support?',
        body: 'I want to configure email, chat, and social channels together. What is the recommended approach for setting up all three channels while maintaining a unified inbox?\n\nCurrently I have email set up, but I would like to add live chat and connect our Twitter account. Are there any gotchas I should be aware of?\n\nThanks in advance for any help!',
        author_name: 'Sarah Chen',
        author_type: 'customer',
        author_id: 'user_1',
        vote_count: 15,
        reply_count: 3,
        view_count: 234,
        is_pinned: false,
        is_locked: false,
        is_answered: true,
        answer_post_id: 'post_2',
        created_at: new Date(now - 86400000 * 3).toISOString(),
        updated_at: new Date(now - 3600000).toISOString(),
    };

    posts.value = [
        {
            id: 'post_1',
            topic_id: props.topicId || 'top_demo',
            body: 'Great question! I went through this setup last month. The key thing is to start with the unified inbox settings before connecting individual channels. Go to Settings > Channels and enable each one.\n\nMake sure your routing rules are set up first so tickets from each channel go to the right team.',
            author_name: 'Mike Johnson',
            author_type: 'customer',
            vote_count: 5,
            is_answer: false,
            created_at: new Date(now - 86400000 * 2).toISOString(),
        },
        {
            id: 'post_2',
            topic_id: props.topicId || 'top_demo',
            body: 'Hi Sarah! Here is the recommended setup order:\n\n1. Enable unified inbox in Settings > General\n2. Connect your email channel first (it\'s the most straightforward)\n3. Add live chat via the Chat widget settings\n4. Connect social accounts under Settings > Channels > Social\n\nOne important note: make sure to set up SLA policies for each channel separately, as response time expectations differ between email and chat.\n\nLet me know if you run into any issues!',
            author_name: 'Alex Support',
            author_type: 'agent',
            vote_count: 12,
            is_answer: true,
            created_at: new Date(now - 86400000 * 1.5).toISOString(),
        },
        {
            id: 'post_3',
            topic_id: props.topicId || 'top_demo',
            body: 'Alex\'s answer is spot on. I just want to add that for Twitter specifically, you\'ll need to create a Twitter Developer account first and get API credentials. The setup wizard in the Social channel settings walks you through it.',
            author_name: 'Emma Wilson',
            author_type: 'customer',
            vote_count: 3,
            is_answer: false,
            created_at: new Date(now - 86400000).toISOString(),
        },
    ];

    categoryName.value = 'General Discussion';
    availableCategories.value = [
        { id: 'cat_1', name: 'General Discussion' },
        { id: 'cat_2', name: 'Feature Requests' },
        { id: 'cat_3', name: 'Bug Reports' },
    ];
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

async function handleVote(targetType, targetId, direction) {
    if (communityService?.vote) {
        try {
            await communityService.vote(targetType, targetId, direction);
            // Refresh local state from service
            if (targetType === 'topic' && communityService.state.currentTopic) {
                topic.value = { ...communityService.state.currentTopic };
            }
            posts.value = [...(communityService.state.currentPosts || posts.value)];
        } catch (err) {
            console.error('[community] Vote failed:', err);
        }
    }
}

async function handleSubmitReply() {
    if (!replyText.value.trim() || submitting.value) return;

    submitting.value = true;
    try {
        if (communityService?.savePost) {
            await communityService.savePost({
                topic_id: topic.value?.id,
                body: replyText.value,
            });
            posts.value = communityService.state.currentPosts || [];
            topic.value = communityService.state.currentTopic || topic.value;
        } else {
            // Demo: add reply locally
            posts.value.push({
                id: `post_${Date.now()}`,
                topic_id: topic.value?.id,
                body: replyText.value,
                author_name: 'You',
                author_type: 'agent',
                vote_count: 0,
                is_answer: false,
                created_at: new Date().toISOString(),
            });
        }
        replyText.value = '';
    } catch (err) {
        console.error('[community] Failed to submit reply:', err);
    } finally {
        submitting.value = false;
    }
}

async function handleMarkAnswer(postId) {
    if (communityService?.markAnswer) {
        try {
            await communityService.markAnswer(postId);
            posts.value = [...(communityService.state.currentPosts || posts.value)];
            if (communityService.state.currentTopic) {
                topic.value = { ...communityService.state.currentTopic };
            }
        } catch (err) {
            console.error('[community] Failed to mark answer:', err);
        }
    } else {
        // Demo: toggle locally
        posts.value.forEach((p) => { p.is_answer = (p.id === postId); });
        if (topic.value) {
            topic.value.is_answered = true;
            topic.value.answer_post_id = postId;
        }
    }
}

async function handleUnmarkAnswer() {
    if (communityService?.unmarkAnswer) {
        try {
            await communityService.unmarkAnswer(topic.value?.id);
            posts.value = [...(communityService.state.currentPosts || posts.value)];
            if (communityService.state.currentTopic) {
                topic.value = { ...communityService.state.currentTopic };
            }
        } catch (err) {
            console.error('[community] Failed to unmark answer:', err);
        }
    } else {
        posts.value.forEach((p) => { p.is_answer = false; });
        if (topic.value) {
            topic.value.is_answered = false;
            topic.value.answer_post_id = null;
        }
    }
}

async function handlePin() {
    if (!topic.value) return;
    const newVal = !topic.value.is_pinned;
    if (communityService?.pinTopic) {
        await communityService.pinTopic(topic.value.id, newVal);
        topic.value = communityService.state.currentTopic || { ...topic.value, is_pinned: newVal };
    } else {
        topic.value.is_pinned = newVal;
    }
}

async function handleLock() {
    if (!topic.value) return;
    const newVal = !topic.value.is_locked;
    if (communityService?.lockTopic) {
        await communityService.lockTopic(topic.value.id, newVal);
        topic.value = communityService.state.currentTopic || { ...topic.value, is_locked: newVal };
    } else {
        topic.value.is_locked = newVal;
    }
}

async function handleMove(categoryId) {
    showMoveMenu.value = false;
    if (!topic.value || categoryId === topic.value.category_id) return;

    if (communityService?.moveTopic) {
        await communityService.moveTopic(topic.value.id, categoryId);
        topic.value = communityService.state.currentTopic || { ...topic.value, category_id: categoryId };
    } else {
        topic.value.category_id = categoryId;
    }

    const cat = availableCategories.value.find((c) => c.id === categoryId);
    if (cat) categoryName.value = cat.name;
}

async function handleDeleteTopic() {
    if (!topic.value) return;
    if (!confirm('Are you sure you want to delete this topic and all its replies?')) return;

    if (communityService?.deleteTopic) {
        await communityService.deleteTopic(topic.value.id);
    }
    emit('navigate', 'community');
}

async function handleConvertToTicket() {
    if (!topic.value) return;
    if (communityService?.convertTopicToTicket) {
        try {
            await communityService.convertTopicToTicket(topic.value.id);
        } catch (err) {
            console.error('[community] Failed to convert to ticket:', err);
        }
    }
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(() => {
    loadTopic();
});

watch(() => props.topicId, () => {
    loadTopic();
});

// Close move menu on click outside
function handleClickOutside(e) {
    if (showMoveMenu.value) {
        showMoveMenu.value = false;
    }
}
</script>
