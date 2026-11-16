<script setup>
import { onMounted, ref } from 'vue';
import AppShell from '../components/AppShell.vue';
import { api, fieldError } from '../api';
import { centsToInput } from '../format';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const shell = ref(null);
const rows = ref([]);
const errors = ref({});
const saved = ref({});
const totalLimit = ref(centsToInput(auth.user?.monthly_limit));
const totalError = ref(null);
const totalSaved = ref(false);
const draft = ref({ name: '', color: '#3a7ca5', monthly_limit: '' });
const draftError = ref(null);

async function load() {
    const payload = await api('/categories');
    rows.value = (payload.data ?? payload).map((category) => ({
        ...category,
        monthly_limit: centsToInput(category.monthly_limit),
    }));
}

function flash(target, key) {
    target.value = { ...target.value, [key]: true };
    setTimeout(() => (target.value = { ...target.value, [key]: false }), 1800);
}

async function saveTotal() {
    totalError.value = null;

    try {
        auth.user = await api('/budget/limit', { method: 'PUT', body: { monthly_limit: totalLimit.value || null } });
        totalSaved.value = true;
        setTimeout(() => (totalSaved.value = false), 1800);
        shell.value?.loadAlerts();
    } catch (caught) {
        totalError.value = caught;
    }
}

async function save(row) {
    errors.value = { ...errors.value, [row.id]: null };

    try {
        await api(`/categories/${row.id}`, {
            method: 'PUT',
            body: { name: row.name, color: row.color, monthly_limit: row.monthly_limit || null },
        });
        flash(saved, row.id);
        shell.value?.loadAlerts();
    } catch (caught) {
        errors.value = { ...errors.value, [row.id]: caught };
    }
}

async function remove(row) {
    if (!confirm(`Usunąć kategorię „${row.name}”? Wydatki zostaną bez kategorii, a jej reguły znikną.`)) {
        return;
    }

    await api(`/categories/${row.id}`, { method: 'DELETE' });
    await load();
}

async function create() {
    draftError.value = null;

    try {
        await api('/categories', { method: 'POST', body: { ...draft.value, monthly_limit: draft.value.monthly_limit || null } });
        draft.value = { name: '', color: '#3a7ca5', monthly_limit: '' };
        await load();
    } catch (caught) {
        draftError.value = caught;
    }
}

onMounted(load);
</script>

<template>
    <AppShell ref="shell" title="Kategorie i limity">
        <section class="card" style="margin-bottom: 20px;">
            <h2>Limit na cały miesiąc</h2>
            <form style="display: flex; gap: 10px; align-items: flex-start; max-width: 460px;" @submit.prevent="saveTotal">
                <div style="flex: 1;">
                    <input v-model="totalLimit" inputmode="decimal" placeholder="Bez limitu, np. 4000">
                    <span v-if="totalError" class="error">{{ fieldError(totalError, 'monthly_limit') || totalError.message }}</span>
                </div>
                <button class="btn" type="submit">{{ totalSaved ? 'Zapisano ✓' : 'Zapisz' }}</button>
            </form>
            <p class="muted" style="margin: 10px 0 0; font-size: 14px;">Alert przychodzi przy 80% i po przekroczeniu limitu, raz na miesiąc dla każdego progu.</p>
        </section>

        <section class="card" style="padding: 14px 8px;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Kolor</th>
                        <th>Nazwa</th>
                        <th style="width: 200px;">Limit miesięczny (zł)</th>
                        <th style="width: 150px;">Wydatki / reguły</th>
                        <th />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id">
                        <td><input v-model="row.color" type="color"></td>
                        <td>
                            <input v-model="row.name" maxlength="60">
                            <span v-if="errors[row.id]" class="error">{{ fieldError(errors[row.id], 'name') || fieldError(errors[row.id], 'monthly_limit') || errors[row.id].message }}</span>
                        </td>
                        <td><input v-model="row.monthly_limit" inputmode="decimal" placeholder="bez limitu"></td>
                        <td class="muted">{{ row.expenses_count }} / {{ row.rules_count }}</td>
                        <td class="num">
                            <button class="btn ghost small" type="button" style="margin-right: 6px;" @click="save(row)">{{ saved[row.id] ? 'Zapisano ✓' : 'Zapisz' }}</button>
                            <button class="btn danger small" type="button" @click="remove(row)">Usuń</button>
                        </td>
                    </tr>
                    <tr>
                        <td><input v-model="draft.color" type="color"></td>
                        <td>
                            <input v-model="draft.name" placeholder="Nowa kategoria" maxlength="60">
                            <span v-if="draftError" class="error">{{ fieldError(draftError, 'name') || fieldError(draftError, 'monthly_limit') || draftError.message }}</span>
                        </td>
                        <td><input v-model="draft.monthly_limit" inputmode="decimal" placeholder="bez limitu"></td>
                        <td />
                        <td class="num"><button class="btn small" type="button" :disabled="!draft.name.trim()" @click="create">Dodaj</button></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AppShell>
</template>
