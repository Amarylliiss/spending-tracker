const TOKEN_KEY = 'spendings_token';

export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export function fieldError(error, name) {
    return error?.errors?.[name]?.[0] ?? '';
}

export const token = {
    get: () => localStorage.getItem(TOKEN_KEY),
    set: (value) => localStorage.setItem(TOKEN_KEY, value),
    clear: () => localStorage.removeItem(TOKEN_KEY),
};

function headers(extra = {}) {
    const value = token.get();

    return {
        Accept: 'application/json',
        ...(value ? { Authorization: `Bearer ${value}` } : {}),
        ...extra,
    };
}

export async function api(path, { method = 'GET', body } = {}) {
    const response = await fetch(`/api${path}`, {
        method,
        headers: headers(body === undefined ? {} : { 'Content-Type': 'application/json' }),
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (response.status === 204) {
        return null;
    }

    const payload = (response.headers.get('content-type') || '').includes('application/json')
        ? await response.json()
        : null;

    if (!response.ok) {
        throw new ApiError(payload?.message || 'Coś poszło nie tak.', response.status, payload?.errors || {});
    }

    return payload;
}

export async function download(path) {
    const response = await fetch(`/api${path}`, { headers: headers() });

    if (!response.ok) {
        throw new ApiError('Nie udało się pobrać pliku.', response.status);
    }

    const disposition = response.headers.get('content-disposition') || '';
    const filename = disposition.match(/filename="?([^";]+)"?/)?.[1] ?? 'eksport.csv';
    const url = URL.createObjectURL(await response.blob());
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}
