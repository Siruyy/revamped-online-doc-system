import { onMounted, onUnmounted } from 'vue';

/**
 * Subscribe to a Laravel private channel (Echo.private).
 *
 * @param {() => string | null} channelResolver - returns channel name without "private-" prefix (e.g. user.1, role.admin)
 * @param {Record<string, (payload: unknown) => void>} listeners - Pusher event name => handler (Laravel default: class basename, e.g. RequestSubmitted)
 */
export function useEchoPrivateChannel(channelResolver, listeners) {
    let activeName = null;

    onMounted(() => {
        const name = channelResolver();
        if (!name || typeof window === 'undefined' || !window.Echo) {
            return;
        }
        activeName = name;
        const channel = window.Echo.private(name);
        for (const [event, handler] of Object.entries(listeners)) {
            channel.listen(event, handler);
        }
    });

    onUnmounted(() => {
        if (!activeName || typeof window === 'undefined' || !window.Echo) {
            return;
        }
        // User-private channels are often shared across components (bell + detail pages).
        if (activeName.startsWith('user.')) {
            return;
        }
        window.Echo.leave(`private-${activeName}`);
    });
}
