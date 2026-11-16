<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { api } from '../api';
import { formatMoney, monthLabel } from '../format';
import { useAuthStore } from '../stores/auth';

defineProps({
    title: { type: String, required: true },
});

const auth = useAuthStore();
const router = useRouter();
const alerts = ref([]);
const unread = ref(0);
const open = ref(false);

async function loadAlerts() {
    const payload = await api('/alerts');
    alerts.value = payload.data;
    unread.value = payload.unread;
}

async function readAll() {
    await api('/alerts/read', { method: 'POST' });
    await loadAlerts();
}

async function logout() {
    await auth.logout();
    router.push('/login');
}

function closeOnOutside(event) {
    if (!event.target.closest('.alerts')) {
        open.value = false;
    }
}

onMounted(() => {
    loadAlerts();
    document.addEventListener('click', closeOnOutside);
});

onBeforeUnmount(() => document.removeEventListener('click', closeOnOutside));

defineExpose({ loadAlerts });
</script>

<template>
    <div class="layout">
        <aside class="side">
            <div class="brand">
                <span class="brand-mark">zł</span>
                Spendings
            </div>
            <RouterLink class="side-link" to="/">Pulpit</RouterLink>
            <RouterLink class="side-link" to="/expenses">Wydatki</RouterLink>
            <RouterLink class="side-link" to="/rules">Reguły kategorii</RouterLink>
            <RouterLink class="side-link" to="/categories">Kategorie i limity</RouterLink>

            <div class="side-foot">
                <span class="muted">{{ auth.user?.name }}</span>
                <button class="btn ghost small" type="button" @click="logout">Wyloguj</button>
            </div>
        </aside>

        <main class="content">
            <header class="top">
                <h1>{{ title }}</h1>
                <div class="top-actions">
                    <slot name="actions" />
                    <div class="alerts" style="position: relative;">
                        <button class="icon-btn" type="button" aria-label="Alerty budżetu" @click="open = !open">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" /><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" /></svg>
                            <span v-if="unread" class="count">{{ unread }}</span>
                        </button>
                        <div v-if="open" class="dropdown">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong>Alerty budżetu</strong>
                                <button v-if="unread" class="btn ghost small" type="button" @click="readAll">Oznacz jako przeczytane</button>
                            </div>
                            <div class="stack" style="gap: 8px; max-height: 360px; overflow: auto;">
                                <div v-for="alert in alerts" :key="alert.id" class="alert-row" :class="[alert.level, { read: alert.read_at }]">
                                    <div class="grow">
                                        <div style="font-weight: 600;">{{ alert.target }}: {{ alert.label.toLowerCase() }}</div>
                                        <div class="muted" style="font-size: 13px;">
                                            {{ monthLabel(alert.month) }} · {{ formatMoney(alert.spent) }} z {{ formatMoney(alert.limit) }}
                                        </div>
                                    </div>
                                </div>
                                <p v-if="!alerts.length" class="muted" style="margin: 0;">Na razie bez alertów.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <slot />
        </main>
    </div>
</template>
