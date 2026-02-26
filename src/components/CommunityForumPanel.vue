<template>
    <div class="community-panel">
        <div class="community-panel-header">
            <h3>Community Forums</h3>
        </div>
        <div class="community-panel-body">
            <p class="community-placeholder">Community forums are coming soon.</p>
            <p class="community-info">
                Build a customer community with discussion forums, topics, replies, upvoting, and moderation tools.
                Enable peer-to-peer support and surface common issues.
            </p>
            <div class="community-stats">
                <div class="community-stat">
                    <span class="stat-value">--</span>
                    <span class="stat-label">Topics</span>
                </div>
                <div class="community-stat">
                    <span class="stat-value">--</span>
                    <span class="stat-label">Replies</span>
                </div>
                <div class="community-stat">
                    <span class="stat-value">--</span>
                    <span class="stat-label">Members</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const topicCount = ref(0);
const replyCount = ref(0);
const memberCount = ref(0);
</script>

<style scoped>
.community-panel {
    background: var(--escalated-bg-secondary, #1e1e2e);
    border: 1px solid var(--escalated-border, #2e2e3e);
    border-radius: 8px;
    padding: 16px;
    color: var(--escalated-text, #e0e0e0);
}

.community-panel-header {
    margin-bottom: 12px;
}

.community-panel-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.community-placeholder {
    font-size: 13px;
    color: var(--escalated-text-muted, #888);
    margin-bottom: 8px;
}

.community-info {
    font-size: 12px;
    color: var(--escalated-text-muted, #666);
    line-height: 1.5;
    margin-bottom: 16px;
}

.community-stats {
    display: flex;
    gap: 16px;
}

.community-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    padding: 12px;
    background: var(--escalated-bg-tertiary, #2e2e3e);
    border-radius: 6px;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--escalated-text, #e0e0e0);
}

.stat-label {
    font-size: 11px;
    color: var(--escalated-text-muted, #888);
    margin-top: 2px;
}
</style>
