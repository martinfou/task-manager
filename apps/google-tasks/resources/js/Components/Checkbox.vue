<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        v-model="proxyChecked"
        :value="value"
        class="rounded border-gt-border-strong text-gt-accent shadow-sm focus:ring-gt-accent-ring dark:bg-gt-field"
    />
</template>
