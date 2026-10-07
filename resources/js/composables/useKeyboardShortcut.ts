import { onBeforeUnmount, onMounted } from 'vue';

function isTypingTarget(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) return false;

    return target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
}

function isDialogOpen(): boolean {
    return document.querySelector('[role="dialog"], [role="alertdialog"]') !== null;
}

export function useKeyboardShortcut(key: string, handler: (event: KeyboardEvent) => void) {
    function onKeydown(event: KeyboardEvent) {
        if (event.key.toLowerCase() !== key.toLowerCase()) return;
        if (event.ctrlKey || event.metaKey || event.altKey) return;
        if (event.repeat) return;
        if (isTypingTarget(event.target)) return;
        if (isDialogOpen()) return;

        event.preventDefault();
        handler(event);
    }

    onMounted(() => window.addEventListener('keydown', onKeydown));
    onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
}
