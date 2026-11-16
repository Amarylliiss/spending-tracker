<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppShell from '../components/AppShell.vue';
import ExpenseModal from '../components/ExpenseModal.vue';
import MonthPicker from '../components/MonthPicker.vue';
import { api, download } from '../api';
import { currentMonth, formatDay, formatMoney } from '../format';

const shell = ref(null);
const month = ref(currentMonth());
const category = ref('');
const query = ref('');
const expenses = ref([]);
const categories = ref([]);
const editing = ref(undefined);
const loading = ref(false);

const sum = computed(() => expenses.value.reduce((total, expense) => total + expense.amount, 0));

let timer;

async function load() {
    loading.value = true;
    const params = new URLSearchParams({ month: month.value, per_page: 200 });

    if (category.value) {
        params.set('category_id', category.value);
    }

    if (query.value.trim()) {
        params.set('q', query.value.trim());
    }

    expenses.value = (await api(`/expenses?${params}`)).data;
    loading.value = false;
}

function exportCsv() {
    const params = new URLSearchParams({ month: month.value });

    if (category.value && category.value !== 'none') {
        params.set('category_id', category.value);
    }

    download(`/expenses/export?${params}`);
}

async function saved() {
    editing.value = undefined;
    await load();
    shell.value?.loadAlerts();
}

watch([month, category], load);
watch(query, () => {
    clearTimeout(timer);
    timer = setTimeout(load, 250);
});

onMounted(async () => {
    load();
    const payload = await api('/categories');
    categories.value = payload.data ?? payload;
});
</script>

<template>
    <AppShell ref="shell" title="Wydatki">
        <template #actions>
            <button class="btn ghost" type="button" @click="exportCsv">Eksport CSV</button>
            <button class="btn" type="button" @click="editing = null">+ Dodaj wydatek</button>
        </template>

        <div class="toolbar">
            <MonthPicker v-model="month" />
            <select v-model="category">
                <option value="">Wszystkie kategorie</option>
                <option value="none">Bez kategorii</option>
                <option v-for="item in categories" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
            </select>
            <input v-model="query" type="search" placeholder="Szukaj w opisie lub sprzedawcy" style="min-width: 260px;">
            <span style="flex: 1;" />
            <span class="muted">{{ expenses.length }} pozycji · razem <b style="color: var(--color-ink);">{{ formatMoney(sum) }}</b></span>
        </div>

        <section class="card" style="padding: 14px 8px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Wydatek</th>
                        <th>Kategoria</th>
                        <th class="num">Kwota</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="expense in expenses" :key="expense.id" class="clickable" @click="editing = expense">
                        <td class="muted" style="width: 90px;">{{ formatDay(expense.spent_on) }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ expense.merchant || expense.description }}</div>
                            <div v-if="expense.merchant" class="muted" style="font-size: 13px;">{{ expense.description }}</div>
                        </td>
                        <td>
                            <span class="chip">
                                <i class="dot" :style="{ background: expense.category?.color ?? '#cbd5e1' }" />
                                {{ expense.category?.name ?? 'Bez kategorii' }}
                            </span>
                            <span v-if="expense.categorized_by !== 'none'" class="tag" :class="expense.categorized_by" style="margin-left: 6px;">
                                {{ expense.categorized_by === 'rule' ? 'reguła' : 'ręcznie' }}
                            </span>
                        </td>
                        <td class="num" style="font-weight: 600;">{{ formatMoney(expense.amount) }}</td>
                    </tr>
                    <tr v-if="!expenses.length && !loading">
                        <td colspan="4" class="empty">Nic tu nie ma. Dodaj pierwszy wydatek albo zmień filtry.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <ExpenseModal v-if="editing !== undefined" :expense="editing" :categories="categories" @close="editing = undefined" @saved="saved" />
    </AppShell>
</template>
