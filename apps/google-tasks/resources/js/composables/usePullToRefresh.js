/**
 * US-044: Pull-to-refresh composable for mobile touch devices.
 *
 * Attaches to a scroll container and triggers a callback when the user
 * pulls down from the top (scroll position 0). Shows a visual indicator
 * during the pull and refresh.
 *
 * Usage:
 *   const { pullIndicatorStyle, isPulling, isRefreshing } = usePullToRefresh(containerRef, onRefresh);
 */
import { ref, onMounted, onUnmounted, computed } from 'vue';

const THRESHOLD = 64; // px to pull before triggering refresh
const MAX_PULL = 100; // max visual displacement

export function usePullToRefresh(containerRef, onRefresh) {
    const pullDistance = ref(0);
    const isPulling = ref(false);
    const isRefreshing = ref(false);

    let startY = 0;
    let tracking = false;

    function onTouchStart(e) {
        if (isRefreshing.value) return;
        const el = containerRef.value;
        if (!el || el.scrollTop > 0) return;
        startY = e.touches[0].clientY;
        tracking = true;
    }

    function onTouchMove(e) {
        if (!tracking || isRefreshing.value) return;
        const el = containerRef.value;
        if (!el || el.scrollTop > 0) {
            tracking = false;
            pullDistance.value = 0;
            isPulling.value = false;
            return;
        }
        const dy = e.touches[0].clientY - startY;
        if (dy <= 0) {
            pullDistance.value = 0;
            isPulling.value = false;
            return;
        }
        // Dampen the pull
        const dampened = Math.min(MAX_PULL, dy * 0.4);
        pullDistance.value = dampened;
        isPulling.value = true;
    }

    async function onTouchEnd() {
        if (!tracking) return;
        tracking = false;
        if (pullDistance.value >= THRESHOLD && !isRefreshing.value) {
            isRefreshing.value = true;
            pullDistance.value = THRESHOLD * 0.6;
            try {
                await onRefresh();
            } finally {
                isRefreshing.value = false;
                pullDistance.value = 0;
                isPulling.value = false;
            }
        } else {
            pullDistance.value = 0;
            isPulling.value = false;
        }
    }

    onMounted(() => {
        const el = containerRef.value;
        if (!el) return;
        el.addEventListener('touchstart', onTouchStart, { passive: true });
        el.addEventListener('touchmove', onTouchMove, { passive: true });
        el.addEventListener('touchend', onTouchEnd, { passive: true });
    });

    onUnmounted(() => {
        const el = containerRef.value;
        if (!el) return;
        el.removeEventListener('touchstart', onTouchStart);
        el.removeEventListener('touchmove', onTouchMove);
        el.removeEventListener('touchend', onTouchEnd);
    });

    const pullIndicatorStyle = computed(() => {
        if (!isPulling.value && !isRefreshing.value) return {};
        return {
            transform: `translateY(${pullDistance.value}px)`,
            transition: isPulling.value ? 'none' : 'transform 0.2s ease-out',
        };
    });

    const pullProgress = computed(() =>
        Math.min(1, pullDistance.value / THRESHOLD),
    );

    return {
        pullIndicatorStyle,
        pullProgress,
        isPulling,
        isRefreshing,
    };
}
