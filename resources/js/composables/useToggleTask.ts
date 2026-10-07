import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useToggleTask() {
    const pendingIds = ref<number[]>([]);

    const isToggling = (taskId: number) => pendingIds.value.includes(taskId);

    function toggleTask(taskId: number) {
        if (isToggling(taskId)) return;

        pendingIds.value.push(taskId);

        router.patch(
            route('tasks.toggle-complete', taskId),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    pendingIds.value = pendingIds.value.filter((id) => id !== taskId);
                },
            },
        );
    }

    return { toggleTask, isToggling };
}
