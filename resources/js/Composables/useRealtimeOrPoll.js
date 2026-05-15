import { onMounted, onUnmounted } from 'vue';

/**
 * When Echo is unavailable or disconnected, periodically run pollFn (e.g. light Inertia partial reload).
 *
 * @param {() => void} pollFn
 * @param {{ intervalMs?: number, disconnectedGraceMs?: number, monitorMs?: number }} options
 */
export function useRealtimeOrPoll(pollFn, { intervalMs = 60000, disconnectedGraceMs = 60000, monitorMs = 30000 } = {}) {
    let intervalId = null;
    let graceTimeoutId = null;
    let monitorIntervalId = null;
    let connection = null;
    let boundConnection = null;

    const stopPolling = () => {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    };

    const clearGraceTimeout = () => {
        if (graceTimeoutId !== null) {
            window.clearTimeout(graceTimeoutId);
            graceTimeoutId = null;
        }
    };

    const connectionState = () => {
        if (typeof window === 'undefined') {
            return 'unavailable';
        }

        const echo = window.Echo;
        connection =
            echo?.connector?.pusher?.connection ?? echo?.connector?.connection ?? echo?.connector?.socket ?? null;

        if (!echo || !connection) {
            return 'unavailable';
        }

        if (typeof connection.state === 'string') {
            return connection.state;
        }

        if (typeof connection.readyState === 'number') {
            return connection.readyState === window.WebSocket?.OPEN ? 'connected' : 'disconnected';
        }

        return 'unavailable';
    };

    const startPolling = () => {
        clearGraceTimeout();

        if (intervalId !== null || connectionState() === 'connected') {
            return;
        }

        pollFn();
        intervalId = window.setInterval(() => {
            if (connectionState() === 'connected') {
                stopPolling();
                return;
            }

            pollFn();
        }, intervalMs);
    };

    const scheduleFallback = () => {
        if (graceTimeoutId !== null || intervalId !== null) {
            return;
        }

        graceTimeoutId = window.setTimeout(startPolling, disconnectedGraceMs);
    };

    const syncFallback = () => {
        if (connectionState() === 'connected') {
            bindConnectionEvents();
            clearGraceTimeout();
            stopPolling();
            return;
        }

        bindConnectionEvents();
        scheduleFallback();
    };

    const bindConnectionEvents = () => {
        if (!connection || connection === boundConnection || typeof connection.bind !== 'function') {
            return;
        }

        unbindConnectionEvents();

        connection.bind('connected', syncFallback);
        connection.bind('state_change', syncFallback);
        connection.bind('disconnected', syncFallback);
        connection.bind('unavailable', syncFallback);
        connection.bind('failed', syncFallback);
        boundConnection = connection;
    };

    const unbindConnectionEvents = () => {
        if (!boundConnection || typeof boundConnection.unbind !== 'function') {
            return;
        }

        boundConnection.unbind('connected', syncFallback);
        boundConnection.unbind('state_change', syncFallback);
        boundConnection.unbind('disconnected', syncFallback);
        boundConnection.unbind('unavailable', syncFallback);
        boundConnection.unbind('failed', syncFallback);
        boundConnection = null;
    };

    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        syncFallback();
        bindConnectionEvents();
        monitorIntervalId = window.setInterval(syncFallback, monitorMs);
    });

    onUnmounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        unbindConnectionEvents();
        clearGraceTimeout();
        stopPolling();

        if (monitorIntervalId !== null) {
            window.clearInterval(monitorIntervalId);
            monitorIntervalId = null;
        }
    });
}
