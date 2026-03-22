<script setup>
/**
 * US-031: bidirectional horizontal swipe on touch — LTR only (see docs/MOBILE_SWIPE.md).
 * Scroll vs swipe: if early movement is mostly vertical, the gesture stays scroll (no preventDefault).
 */
import {
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    /** Typically coarse pointer + max-width sm (see Index.vue). */
    enabled: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    /** When true, primary action is “mark incomplete”; emit still toggles via parent. */
    isCompleted: { type: Boolean, default: false },
    /** Parent increments to snap-close rows (e.g. sheet opened). */
    resetSignal: { type: Number, default: 0 },
});

const emit = defineEmits(['complete', 'more']);

const { t } = useI18n();

const ACTION_W = 88;
const SNAP_PX = 40;
const HORIZONTAL_LOCK_DX = 14;
const VERTICAL_LOCK_DY = 12;
const RATIO = 1.25;

const translateXPx = ref(0);
const settling = ref(false);
const paneRef = ref(null);

/** @type {'undecided' | 'horizontal' | 'vertical'} */
let gestureMode = 'undecided';
let startX = 0;
let startY = 0;
/** @type {ReturnType<typeof setTimeout> | null} */
let longPressTimer = null;
/** @type {HTMLElement | null} */
let touchMoveBoundEl = null;

function clamp(n, a, b) {
    return Math.max(a, Math.min(b, n));
}

function clearLongPress() {
    if (longPressTimer != null) {
        clearTimeout(longPressTimer);
        longPressTimer = null;
    }
}

function unbindTouchMove() {
    if (touchMoveBoundEl) {
        touchMoveBoundEl.removeEventListener('touchmove', onTouchMove);
        touchMoveBoundEl = null;
    }
}

function bindTouchMove() {
    unbindTouchMove();
    if (!props.enabled) {
        return;
    }
    const el = paneRef.value;
    if (!el) {
        return;
    }
    el.addEventListener('touchmove', onTouchMove, { passive: false });
    touchMoveBoundEl = el;
}

watch(
    () => props.resetSignal,
    () => {
        gestureMode = 'undecided';
        translateXPx.value = 0;
        settling.value = false;
        clearLongPress();
    },
);

watch(
    () => [props.enabled, props.disabled],
    () => {
        gestureMode = 'undecided';
        translateXPx.value = 0;
        clearLongPress();
        nextTick(bindTouchMove);
    },
);

watch(
    () => props.enabled,
    () => {
        nextTick(bindTouchMove);
    },
);

onMounted(() => {
    nextTick(bindTouchMove);
});

onUnmounted(() => {
    unbindTouchMove();
    clearLongPress();
});

function onTouchStart(e) {
    if (!props.enabled || props.disabled || e.touches.length !== 1) {
        return;
    }
    settling.value = false;
    gestureMode = 'undecided';
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    clearLongPress();
    longPressTimer = setTimeout(() => {
        longPressTimer = null;
        if (gestureMode === 'undecided' && props.enabled && !props.disabled) {
            emit('more');
        }
    }, 520);
}

function onTouchMove(e) {
    if (!props.enabled || props.disabled || e.touches.length !== 1) {
        return;
    }
    const x = e.touches[0].clientX;
    const y = e.touches[0].clientY;
    const dx = x - startX;
    const dy = y - startY;

    if (gestureMode === 'undecided') {
        const adx = Math.abs(dx);
        const ady = Math.abs(dy);
        if (ady > VERTICAL_LOCK_DY && ady > adx * RATIO) {
            gestureMode = 'vertical';
            clearLongPress();
            return;
        }
        if (adx > HORIZONTAL_LOCK_DX && adx > ady * RATIO) {
            gestureMode = 'horizontal';
            clearLongPress();
        } else {
            return;
        }
    }

    if (gestureMode === 'vertical') {
        return;
    }

    e.preventDefault();
    translateXPx.value = Math.round(clamp(dx, -ACTION_W, ACTION_W));
}

function onTouchEnd() {
    clearLongPress();
    if (!props.enabled || props.disabled) {
        gestureMode = 'undecided';
        return;
    }
    if (gestureMode !== 'horizontal') {
        gestureMode = 'undecided';
        return;
    }
    const x = translateXPx.value;
    settling.value = true;
    if (x >= SNAP_PX) {
        emit('complete');
    } else if (x <= -SNAP_PX) {
        emit('more');
    }
    translateXPx.value = 0;
    gestureMode = 'undecided';
    requestAnimationFrame(() => {
        settling.value = false;
    });
}

function onTouchCancel() {
    clearLongPress();
    gestureMode = 'undecided';
    settling.value = true;
    translateXPx.value = 0;
    requestAnimationFrame(() => {
        settling.value = false;
    });
}

function onCompleteZoneClick() {
    if (!props.enabled || props.disabled) {
        return;
    }
    emit('complete');
}

function onMoreZoneClick() {
    if (!props.enabled || props.disabled) {
        return;
    }
    emit('more');
}
</script>

<template>
    <div
        v-if="!enabled"
        class="contents"
    >
        <slot />
    </div>
    <div
        v-else
        class="relative overflow-hidden touch-pan-y"
    >
        <div
            class="absolute inset-y-0 left-0 z-0 flex w-[5.5rem] items-stretch bg-emerald-600 text-white dark:bg-emerald-700"
            :aria-hidden="translateXPx <= 0 ? 'true' : 'false'"
        >
            <button
                type="button"
                class="flex h-full min-h-[3rem] w-full items-center justify-center px-2 text-center text-xs font-semibold leading-tight text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                :aria-label="
                    isCompleted
                        ? t('tasks.swipe.markIncomplete')
                        : t('tasks.swipe.markComplete')
                "
                @click.stop="onCompleteZoneClick"
            >
                {{
                    isCompleted
                        ? t('tasks.swipe.incompleteShort')
                        : t('tasks.swipe.completeShort')
                }}
            </button>
        </div>
        <div
            class="absolute inset-y-0 right-0 z-0 flex w-[5.5rem] items-stretch bg-gt-field-muted dark:bg-gt-raised"
            :aria-hidden="translateXPx >= 0 ? 'true' : 'false'"
        >
            <button
                type="button"
                class="flex h-full min-h-[3rem] w-full items-center justify-center px-2 text-center text-xs font-semibold leading-tight text-gt-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-gt-accent-ring"
                :aria-label="t('tasks.swipe.moreActions')"
                @click.stop="onMoreZoneClick"
            >
                {{ t('tasks.swipe.moreShort') }}
            </button>
        </div>
        <div
            ref="paneRef"
            class="relative z-[1]"
            :class="settling ? 'transition-transform duration-200 ease-out' : ''"
            :style="{
                transform: `translate3d(${translateXPx}px,0,0)`,
            }"
            @touchstart.passive="onTouchStart"
            @touchend="onTouchEnd"
            @touchcancel="onTouchCancel"
        >
            <slot />
        </div>
    </div>
</template>
