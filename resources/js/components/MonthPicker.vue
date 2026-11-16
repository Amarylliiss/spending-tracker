<script setup>
import { computed } from 'vue';
import { currentMonth, monthLabel, shiftMonth } from '../format';

const month = defineModel({ type: String, required: true });
const isCurrent = computed(() => month.value >= currentMonth());
</script>

<template>
    <div class="month-picker">
        <button type="button" aria-label="Poprzedni miesiąc" @click="month = shiftMonth(month, -1)">‹</button>
        <span>{{ monthLabel(month) }}</span>
        <button type="button" aria-label="Następny miesiąc" :disabled="isCurrent" @click="month = shiftMonth(month, 1)">›</button>
    </div>
</template>
