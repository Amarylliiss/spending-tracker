import { defineStore } from 'pinia';
import { api, token } from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        ready: false,
    }),
    actions: {
        async bootstrap() {
            if (this.ready) {
                return;
            }

            if (token.get()) {
                try {
                    this.user = await api('/auth/me');
                } catch {
                    token.clear();
                }
            }

            this.ready = true;
        },
        async login(path, form) {
            const session = await api(path, { method: 'POST', body: form });
            token.set(session.token);
            this.user = session.user;
            this.ready = true;
        },
        async logout() {
            try {
                await api('/auth/logout', { method: 'POST' });
            } catch {
                // The token is dropped locally either way.
            }

            token.clear();
            this.user = null;
        },
    },
});
