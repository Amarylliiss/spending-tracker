<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import GuestLayout from '../components/GuestLayout.vue';
import { fieldError } from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const form = ref({ email: '', password: '' });
const error = ref(null);
const pending = ref(false);

async function submit() {
    pending.value = true;
    error.value = null;

    try {
        await auth.login('/auth/login', form.value);
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
            <h2>Zaloguj się</h2>
            <p class="muted" style="margin: -6px 0 8px;">Konto demo: demo@example.com / password</p>
            <label class="field">
                E-mail
                <input v-model="form.email" type="email" autocomplete="username" required>
                <span v-if="fieldError(error, 'email')" class="error">{{ fieldError(error, 'email') }}</span>
            </label>
            <label class="field">
                Hasło
                <input v-model="form.password" type="password" autocomplete="current-password" required>
            </label>
            <p v-if="error && !fieldError(error, 'email')" class="error">{{ error.message }}</p>
            <button class="btn" type="submit" :disabled="pending">{{ pending ? 'Loguję…' : 'Zaloguj' }}</button>
            <p class="muted">Nie masz konta? <RouterLink to="/register">Załóż je</RouterLink></p>
        </form>
    </GuestLayout>
</template>
