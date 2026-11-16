<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import GuestLayout from '../components/GuestLayout.vue';
import { fieldError } from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref(null);
const pending = ref(false);

async function submit() {
    pending.value = true;
    error.value = null;

    try {
        await auth.login('/auth/register', form.value);
        router.push('/');
    } catch (caught) {
        error.value = caught;
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <GuestLayout>
        <form @submit.prevent="submit">
            <h2>Nowe konto</h2>
            <p class="muted" style="margin: -6px 0 8px;">Dostaniesz gotowe kategorie i podstawowe reguły na start.</p>
            <label class="field">
                Imię
                <input v-model="form.name" autocomplete="name" required>
                <span v-if="fieldError(error, 'name')" class="error">{{ fieldError(error, 'name') }}</span>
            </label>
            <label class="field">
                E-mail
                <input v-model="form.email" type="email" autocomplete="email" required>
                <span v-if="fieldError(error, 'email')" class="error">{{ fieldError(error, 'email') }}</span>
            </label>
            <label class="field">
                Hasło
                <input v-model="form.password" type="password" autocomplete="new-password" required>
                <span v-if="fieldError(error, 'password')" class="error">{{ fieldError(error, 'password') }}</span>
            </label>
            <label class="field">
                Powtórz hasło
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required>
            </label>
            <button class="btn" type="submit" :disabled="pending">{{ pending ? 'Zakładam…' : 'Załóż konto' }}</button>
            <p class="muted">Masz już konto? <RouterLink to="/login">Zaloguj się</RouterLink></p>
        </form>
    </GuestLayout>
</template>
