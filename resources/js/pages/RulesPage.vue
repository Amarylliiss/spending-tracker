<script setup>
import { onMounted, ref } from 'vue';
import AppShell from '../components/AppShell.vue';
import { api, fieldError } from '../api';

const shell = ref(null);
const rules = ref([]);
const categories = ref([]);
const error = ref(null);
const notice = ref('');
const editingId = ref(null);

const blank = () => ({ field: 'any', operator: 'contains', pattern: '', category_id: '', priority: 100 });
const form = ref(blank());

const fields = [
    ['any', 'Sprzedawca lub opis'],
    ['merchant', 'Sprzedawca'],
    ['description', 'Opis'],
];

const operators = [
    ['contains', 'zawiera'],
    ['starts_with', 'zaczyna się od'],
    ['equals', 'jest równe'],
];

async function load() {
    const [rulePayload, categoryPayload] = await Promise.all([api('/rules'), api('/categories')]);
    rules.value = rulePayload.data ?? rulePayload;
    categories.value = categoryPayload.data ?? categoryPayload;
}

function edit(rule) {
    editingId.value = rule.id;
    form.value = {
        field: rule.field.value,
        operator: rule.operator.value,
        pattern: rule.pattern,
        category_id: rule.category.id,
        priority: rule.priority,
    };
}

function cancel() {
    editingId.value = null;
    form.value = blank();
    error.value = null;
}

async function save() {
    error.value = null;

    try {
        if (editingId.value) {
            await api(`/rules/${editingId.value}`, { method: 'PUT', body: form.value });
        } else {
            await api('/rules', { method: 'POST', body: form.value });
        }

        cancel();
        await load();
    } catch (caught) {
        error.value = caught;
    }
}

async function remove(rule) {
    await api(`/rules/${rule.id}`, { method: 'DELETE' });
    await load();
}

async function apply() {
    const result = await api('/rules/apply', { method: 'POST' });
    notice.value = result.changed
        ? `Zmieniono kategorię ${result.changed} ${result.changed === 1 ? 'wydatku' : 'wydatków'}.`
        : 'Wszystkie wydatki mają już kategorie zgodne z regułami.';
    shell.value?.loadAlerts();
}

onMounted(load);
</script>

<template>
    <AppShell ref="shell" title="Reguły kategorii">
        <template #actions>
            <button class="btn ghost" type="button" @click="apply">Zastosuj do istniejących wydatków</button>
        </template>

        <p v-if="notice" class="notice">{{ notice }}</p>

        <section class="card" style="margin-bottom: 20px;">
            <h2>{{ editingId ? 'Edytuj regułę' : 'Nowa reguła' }}</h2>
            <p class="muted" style="margin: -6px 0 14px; font-size: 14px;">
                Wydatek bez ręcznie wybranej kategorii dostaje kategorię z pierwszej pasującej reguły. Niższy priorytet sprawdzany jest wcześniej.
            </p>
            <form class="rule-form" @submit.prevent="save">
                <label class="field">
                    Jeśli
                    <select v-model="form.field">
                        <option v-for="[value, label] in fields" :key="value" :value="value">{{ label }}</option>
                    </select>
                </label>
                <label class="field">
                    &nbsp;
                    <select v-model="form.operator">
                        <option v-for="[value, label] in operators" :key="value" :value="value">{{ label }}</option>
                    </select>
                </label>
                <label class="field">
                    Tekst
                    <input v-model="form.pattern" placeholder="np. orlen" required>
                </label>
                <label class="field">
                    to kategoria
                    <select v-model="form.category_id" required>
                        <option value="" disabled>Wybierz</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label class="field">
                    Priorytet
                    <input v-model.number="form.priority" type="number" min="1" max="999">
                </label>
                <div style="display: flex; gap: 8px;">
                    <button v-if="editingId" class="btn ghost" type="button" @click="cancel">Anuluj</button>
                    <button class="btn" type="submit">{{ editingId ? 'Zapisz' : 'Dodaj' }}</button>
                </div>
            </form>
            <p v-if="error" class="error" style="margin-bottom: 0;">
                {{ fieldError(error, 'pattern') || fieldError(error, 'category_id') || error.message }}
            </p>
        </section>

        <section class="card" style="padding: 14px 8px;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Priorytet</th>
                        <th>Warunek</th>
                        <th>Kategoria</th>
                        <th />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rule in rules" :key="rule.id">
                        <td class="muted">{{ rule.priority }}</td>
                        <td class="sentence">{{ rule.field.label }} {{ rule.operator.label }} <code>{{ rule.pattern }}</code></td>
                        <td><span class="chip"><i class="dot" :style="{ background: rule.category.color }" />{{ rule.category.name }}</span></td>
                        <td class="num">
                            <button class="btn ghost small" type="button" style="margin-right: 6px;" @click="edit(rule)">Edytuj</button>
                            <button class="btn danger small" type="button" @click="remove(rule)">Usuń</button>
                        </td>
                    </tr>
                    <tr v-if="!rules.length">
                        <td colspan="4" class="empty">Nie masz jeszcze reguł.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AppShell>
</template>
