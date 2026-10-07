<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useToggleTask } from '@/composables/useToggleTask';
import { priorityBadgeClasses } from '@/lib/priority';
import type { Task } from '@/types';
import { Pencil, Repeat, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    task: Task;
}>();

const emit = defineEmits<{
    edit: [task: Task];
    delete: [task: Task];
}>();

const { toggleTask, isToggling } = useToggleTask();

// --- Due date styling ---
const dueDateInfo = computed(() => {
    if (!props.task.due_date) return null;

    // "T00:00:00" makes the browser read the date as local time, not UTC
    const due = new Date(`${props.task.due_date}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const diffDays = Math.round((due.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return { label: 'Overdue', className: 'text-destructive font-medium' };
    }
    if (diffDays === 0) {
        return { label: 'Due today', className: 'text-primary font-medium' };
    }
    if (diffDays === 1) {
        return { label: 'Due tomorrow', className: 'text-muted-foreground' };
    }

    const formatted = due.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    return { label: formatted, className: 'text-muted-foreground' };
});
</script>

<template>
    <div
        class="flex items-start gap-4 rounded-lg border border-border bg-card p-4 transition-colors hover:border-primary/40"
        :class="{ 'opacity-60': task.is_completed }"
    >
        <!-- Completion checkbox -->
        <Checkbox
            :checked="task.is_completed"
            :disabled="isToggling(task.id)"
            class="mt-1 rounded-full"
            :aria-label="`Mark ${task.title} as ${task.is_completed ? 'incomplete' : 'completed'}`"
            @update:checked="toggleTask(task.id)"
        />

        <!-- Main content -->
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <h3 class="truncate text-sm font-medium leading-6" :class="{ 'text-muted-foreground line-through': task.is_completed }">
                    {{ task.title }}
                </h3>

                <div class="flex shrink-0 items-center gap-1">
                    <Button variant="ghost" size="icon" class="h-7 w-7" @click="emit('edit', task)">
                        <Pencil class="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="ghost" size="icon" class="h-7 w-7 text-muted-foreground hover:text-destructive" @click="emit('delete', task)">
                        <Trash2 class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>

            <p v-if="task.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                {{ task.description }}
            </p>

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                <!-- Priority badge -->
                <Badge
                    variant="secondary"
                    class="rounded-full border-0 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide"
                    :class="priorityBadgeClasses[task.priority]"
                >
                    {{ task.priority_label }}
                </Badge>

                <!-- Category badge -->
                <Badge v-if="task.category_name" variant="outline" class="rounded-full px-2 py-0.5 text-[10px] font-medium">
                    {{ task.category_name }}
                </Badge>

                <!-- Due date -->
                <span v-if="dueDateInfo" :class="dueDateInfo.className" class="ml-auto text-[11px]">
                    {{ dueDateInfo.label }}
                </span>

                <span v-if="task.recurrence_label" class="flex items-center gap-1 text-[11px] text-muted-foreground">
                    <Repeat class="h-3 w-3" />
                    {{ task.recurrence_label }}
                </span>
            </div>
        </div>
    </div>
</template>
