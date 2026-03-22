<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border border-gt-border-strong bg-gt-field text-gt-ink shadow-sm placeholder:text-gt-muted focus:border-gt-accent focus:ring-gt-accent-ring dark:focus:ring-gt-accent-ring"
        v-model="model"
        ref="input"
    />
</template>
