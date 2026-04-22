import { onMounted, onUnmounted } from 'vue';

/**
 * When Echo is unavailable, periodically run pollFn (e.g. light Inertia partial reload).
 *
 * @param {() => void} pollFn
 * @param {{ intervalMs?: number }} options
 */
export function useRealtimeOrPoll(pollFn, { intervalMs = 60000 } = {}) {
    let intervalId = null;

    onMounted(() => {
        if (typeof window === 'undefined' || window.Echo) {
            return;
        }
        intervalId = window.setInterval(pollFn, intervalMs);
    });

    onUnmounted(() => {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
        }
    });
}
