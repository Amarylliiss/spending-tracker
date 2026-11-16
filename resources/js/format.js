const money = new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' });

export function formatMoney(cents) {
    return money.format((cents ?? 0) / 100);
}

export function centsToInput(cents) {
    return cents == null ? '' : (cents / 100).toFixed(2).replace('.', ',').replace(',00', '');
}

export function currentMonth() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

export function shiftMonth(key, delta) {
    const [year, month] = key.split('-').map(Number);
    const date = new Date(year, month - 1 + delta, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

export function monthLabel(key) {
    const [year, month] = key.split('-').map(Number);
    const label = new Date(year, month - 1, 1).toLocaleDateString('pl-PL', { month: 'long', year: 'numeric' });

    return label.charAt(0).toUpperCase() + label.slice(1);
}

export function formatDay(isoDate) {
    return new Date(`${isoDate}T00:00:00`).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' });
}

export function today() {
    const now = new Date();

    return `${currentMonth()}-${String(now.getDate()).padStart(2, '0')}`;
}
