<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import AppShell from '../components/AppShell.vue';
import ExpenseModal from '../components/ExpenseModal.vue';
import MonthPicker from '../components/MonthPicker.vue';
import { api } from '../api';
import { currentMonth, formatDay, formatMoney, monthLabel } from '../format';

const shell = ref(null);
const month = ref(currentMonth());
const summary = ref(null);
const recent = ref([]);
const categories = ref([]);
const adding = ref(false);

const total = computed(() => summary.value?.total);
const change = computed(() => {
    if (!total.value?.previous) {
        return null;
    }

    return Math.round((total.value.spent / total.value.previous - 1) * 100);
});

function width(line) {
    return `${Math.min(line.percent ?? 0, 100)}%`;
}

function barColor(line) {
    if (line.level === 'exceeded') {
        return 'var(--color-red)';
    }

    return line.level === 'warning' ? 'var(--color-amber)' : line.color;
}

async function load() {
    const [budget, expenses] = await Promise.all([
        api(`/budget?month=${month.value}`),
        api(`/expenses?month=${month.value}&per_page=6`),
    ]);

    summary.value = budget;
    recent.value = expenses.data;
}

async function saved() {
    adding.value = false;
    await load();
    shell.value?.loadAlerts();
}

watch(month, load);

onMounted(async () => {
    load();
    const payload = await api('/categories');
    categories.value = payload.data ?? payload;
});
</script>

<template>
    <AppShell ref="shell" title="Pulpit">
        <template #actions>
            <MonthPicker v-model="month" />
            <button class="btn" type="button" @click="adding = true">+ Dodaj wydatek</button>
        </template>

        <template v-if="summary">
            <section class="hero">
                <article class="card total" :class="total.level">
                    <div class="label">Wydano w miesiącu {{ monthLabel(month).toLowerCase() }}</div>
                    <div class="amount">{{ formatMoney(total.spent) }}</div>
                    <template v-if="total.limit">
                        <div class="bar"><i :style="{ width: width(total) }" /></div>
                        <div class="total-meta">
                            <span>{{ total.percent }}% limitu {{ formatMoney(total.limit) }}</span>
                            <span v-if="total.limit > total.spent">Zostało {{ formatMoney(total.limit - total.spent) }}</span>
                            <span v-else>Ponad limit o {{ formatMoney(total.spent - total.limit) }}</span>
                        </div>
                    </template>
                    <RouterLink v-else to="/categories" style="color: white; opacity: 0.85;">Ustaw miesięczny limit →</RouterLink>
                </article>

                <article class="card facts">
                    <div class="fact">
                        <span class="muted">Liczba wydatków</span>
                        <b>{{ total.count }}</b>
                    </div>
                    <div class="fact">
                        <span class="muted">Poprzedni miesiąc</span>
                        <b>{{ formatMoney(total.previous) }}</b>
                    </div>
                    <div class="fact">
                        <span class="muted">Zmiana</span>
                        <b :style="{ color: change > 0 ? 'var(--color-red)' : 'var(--color-forest)' }">
                            {{ change === null ? '—' : `${change > 0 ? '+' : ''}${change}%` }}
                        </b>
                    </div>
                    <div class="fact">
                        <span class="muted">Bez kategorii</span>
                        <b>{{ formatMoney(summary.uncategorized) }}</b>
                    </div>
                </article>
            </section>

            <section class="columns">
                <article class="card">
                    <h2>Kategorie</h2>
                    <div class="cat-grid">
                        <div v-for="line in summary.categories" :key="line.id" class="cat">
                            <div class="cat-head">
                                <span style="display: flex; align-items: center; gap: 8px;"><i class="dot" :style="{ background: line.color }" />{{ line.name }}</span>
                                <span v-if="line.level" class="tag" :class="line.level">{{ line.level === 'exceeded' ? 'Ponad limit' : '80%' }}</span>
                            </div>
                            <div class="cat-amount">
                                <b>{{ formatMoney(line.spent) }}</b>
                                <span class="muted">{{ line.limit ? `z ${formatMoney(line.limit)}` : 'bez limitu' }}</span>
                            </div>
                            <div v-if="line.limit" class="bar"><i :style="{ width: width(line), background: barColor(line) }" /></div>
                        </div>
                    </div>
                </article>

                <article class="card">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <h2>Ostatnie wydatki</h2>
                        <RouterLink to="/expenses" style="font-size: 13px;">Wszystkie</RouterLink>
                    </div>
                    <div class="list">
                        <div v-for="expense in recent" :key="expense.id" class="list-row">
                            <i class="dot" :style="{ background: expense.category?.color ?? '#cbd5e1' }" />
                            <div class="grow">
                                <div class="title">{{ expense.merchant || expense.description }}</div>
                                <div class="sub">{{ formatDay(expense.spent_on) }} · {{ expense.category?.name ?? 'Bez kategorii' }}</div>
                            </div>
                            <b class="num">{{ formatMoney(expense.amount) }}</b>
                        </div>
                        <p v-if="!recent.length" class="muted" style="margin: 0;">Brak wydatków w tym miesiącu.</p>
                    </div>
                </article>
            </section>
        </template>

        <ExpenseModal v-if="adding" :categories="categories" @close="adding = false" @saved="saved" />
    </AppShell>
</template>
