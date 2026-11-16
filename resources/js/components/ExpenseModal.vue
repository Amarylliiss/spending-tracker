<script setup>
import { ref } from 'vue';
import { api, fieldError } from '../api';
import { centsToInput, today } from '../format';

const props = defineProps({
    expense: { type: Object, default: null },
    categories: { type: Array, required: true },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    amount: centsToInput(props.expense?.amount),
    spent_on: props.expense?.spent_on ?? today(),
    merchant: props.expense?.merchant ?? '',
    description: props.expense?.description ?? '',
    category_id: props.expense?.categorized_by === 'manual' ? props.expense.category.id : '',
});
const error = ref(null);
const pending = ref(false);

async function submit() {
    pending.value = true;
    error.value = null;

    try {
        const body = { ...form.value, category_id: form.value.category_id || null };
        const saved = props.expense
            ? await api(`/expenses/${props.expense.id}`, { method: 'PUT', body })
            : await api('/expenses', { method: 'POST', body });
        emit('saved', saved);
    } catch (caught) {
        error.value = caught;
    } finally {
        pending.value = false;
    }
}

async function remove() {
    if (!confirm('Usunąć ten wydatek?')) {
        return;
    }

    await api(`/expenses/${props.expense.id}`, { method: 'DELETE' });
    emit('saved', null);
}
</script>

<template>
    <div class="overlay" @click.self="emit('close')">
        <form class="modal" @submit.prevent="submit">
            <h2>{{ expense ? 'Edytuj wydatek' : 'Nowy wydatek' }}</h2>
            <div class="form-grid">
                <label class="field">
                    Kwota (zł)
                    <input v-model="form.amount" inputmode="decimal" placeholder="0,00" autofocus required>
                    <span v-if="fieldError(error, 'amount')" class="error">{{ fieldError(error, 'amount') }}</span>
                </label>
                <label class="field">
                    Data
                    <input v-model="form.spent_on" type="date" :max="today()" required>
                    <span v-if="fieldError(error, 'spent_on')" class="error">{{ fieldError(error, 'spent_on') }}</span>
                </label>
                <label class="field wide">
                    Sprzedawca
                    <input v-model="form.merchant" placeholder="np. Biedronka" maxlength="120">
                </label>
                <label class="field wide">
                    Opis
                    <input v-model="form.description" placeholder="np. Zakupy na tydzień" maxlength="255" required>
                    <span v-if="fieldError(error, 'description')" class="error">{{ fieldError(error, 'description') }}</span>
                </label>
                <label class="field wide">
                    Kategoria
                    <select v-model="form.category_id">
                        <option value="">Automatycznie, według reguł</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
            </div>

            <p v-if="error && !Object.keys(error.errors).length" class="error">{{ error.message }}</p>

            <div style="display: flex; gap: 10px; margin-top: 22px;">
                <button v-if="expense" class="btn danger" type="button" @click="remove">Usuń</button>
                <span style="flex: 1;" />
                <button class="btn ghost" type="button" @click="emit('close')">Anuluj</button>
                <button class="btn" type="submit" :disabled="pending">{{ pending ? 'Zapisuję…' : 'Zapisz' }}</button>
            </div>
        </form>
    </div>
</template>
