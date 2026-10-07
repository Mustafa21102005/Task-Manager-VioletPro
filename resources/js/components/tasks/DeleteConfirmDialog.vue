<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Task } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    open: boolean;
    task: Task | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

function confirm() {
    if (!props.task) return;

    processing.value = true;

    router.delete(route('tasks.destroy', props.task.id), {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <AlertDialog :open="open" @update:open="(val) => emit('update:open', val)">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete this task?</AlertDialogTitle>
                <AlertDialogDescription>
                    <span v-if="task" class="font-medium text-foreground">"{{ task.title }}"</span>
                    will be permanently deleted. This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel :disabled="processing">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    :disabled="processing"
                    @click.prevent="confirm"
                >
                    {{ processing ? 'Deleting…' : 'Delete' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
