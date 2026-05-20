#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SESSION="${SVCI_TMUX_SESSION:-svci}"
WITH_REVERB=false

for arg in "$@"; do
    case "$arg" in
        --reverb)
            WITH_REVERB=true
            ;;
        -h|--help)
            echo "Usage: scripts/dev-tmux.sh [--reverb]"
            echo
            echo "Starts the SVCI local dev stack in a tmux session."
            echo "Set SVCI_TMUX_SESSION=name to override the session name."
            exit 0
            ;;
        *)
            echo "Unknown option: $arg" >&2
            echo "Usage: scripts/dev-tmux.sh [--reverb]" >&2
            exit 1
            ;;
    esac
done

if ! command -v tmux >/dev/null 2>&1; then
    echo "tmux is not installed or not on PATH." >&2
    exit 1
fi

if tmux has-session -t "$SESSION" 2>/dev/null; then
    tmux attach-session -t "$SESSION"
    exit 0
fi

tmux new-session -d -s "$SESSION" -c "$APP_DIR" -n opencode
tmux send-keys -t "$SESSION:opencode" "opencode" Enter
tmux new-window -t "$SESSION" -c "$APP_DIR" -n lazygit "lazygit"
tmux new-window -t "$SESSION" -c "$APP_DIR" -n server "php artisan serve"
tmux new-window -t "$SESSION" -c "$APP_DIR" -n vite "npm run dev"
tmux new-window -t "$SESSION" -c "$APP_DIR" -n queue "php artisan queue:listen --tries=1"
tmux new-window -t "$SESSION" -c "$APP_DIR" -n logs "php artisan pail --timeout=0"

if [ "$WITH_REVERB" = true ]; then
    tmux new-window -t "$SESSION" -c "$APP_DIR" -n reverb "php artisan reverb:start"
fi

tmux select-window -t "$SESSION:opencode"
tmux attach-session -t "$SESSION"
