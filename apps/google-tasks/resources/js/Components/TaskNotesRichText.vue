<script setup>
import { computed } from 'vue';
import { linkifyNotes } from '@/utils/linkifyNotes';

const props = defineProps({
    text: { type: String, default: '' },
});

const segments = computed(() => linkifyNotes(props.text));
</script>

<template>
    <span class="block whitespace-pre-wrap break-words">
        <template v-for="(seg, i) in segments" :key="i">
            <a
                v-if="seg.type === 'link'"
                :href="seg.href"
                class="text-indigo-600 underline decoration-indigo-600/30 underline-offset-2 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                target="_blank"
                rel="noopener noreferrer"
                @click.stop
            >
                {{ seg.value }}
            </a>
            <span v-else>{{ seg.value }}</span>
        </template>
    </span>
</template>
