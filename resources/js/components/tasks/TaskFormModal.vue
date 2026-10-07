<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category, RecurrenceUnit, Task, TaskRecurrence } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    task: Task | null;
    categories: Category[];
    defaultDueDate?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const weekdays = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];

const unitSuffix = computed(() => (Number(form.recurrence_interval) === 1 ? '' : 's'));

function toggleDay(day: number) {
    form.recurrence_days = form.recurrence_days.includes(day)
        ? form.recurrence_days.filter((d) => d !== day)
        : [...form.recurrence_days, day].sort((a, b) => a - b);
}

const form = useForm({
    title: '',
    description: '',
    due_date: '',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    category_id: '' as string | number,
    recurrence: '' as '' | TaskRecurrence,
    recurrence_interval: 1 as number | string,
    recurrence_unit: 'week' as RecurrenceUnit,
    recurrence_days: [] as number[],
});

// Populate the form whenever the modal opens or the task prop changes
watch(
    () => [props.open, props.task, props.defaultDueDate] as const,
    ([isOpen, task]) => {
        if (!isOpen) return;

        form.clearErrors();
        form.title = task?.title ?? '';
        form.description = task?.description ?? '';
        form.due_date = task?.due_date ?? props.defaultDueDate ?? '';
        form.priority = task?.priority ?? 'medium';
        form.category_id = task?.category_id ?? props.categories[0]?.id ?? '';
        form.recurrence = task?.recurrence ?? '';
        form.recurrence_interval = task?.recurrence_interval ?? 1;
        form.recurrence_unit = task?.recurrence_unit ?? 'week';
        form.recurrence_days = task?.recurrence_days ?? [];
    },
    { immediate: true },
);

function submit() {
    if (props.task) {
        form.put(route('tasks.update', props.task.id), {
            preserveScroll: true,
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post(route('tasks.store'), {
            preserveScroll: true,
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ task ? 'Edit task' : 'New task' }}</DialogTitle>
                <DialogDescription>
                    {{ task ? 'Update the details of this task.' : 'Add a new task to your list.' }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="task-title">Title</Label>
                    <Input id="task-title" v-model="form.title" placeholder="e.g. Finish onboarding docs" />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-1.5">
                    <Label for="task-description">Description</Label>
                    <textarea
                        id="task-description"
                        v-model="form.description"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        placeholder="Optional notes…"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="task-priority">Priority</Label>
                        <select
                            id="task-priority"
                            v-model="form.priority"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <InputError :message="form.errors.priority" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-due-date">Due date</Label>
                        <Input id="task-due-date" v-model="form.due_date" type="date" />
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>

                <p v-if="categories.length === 0" class="text-sm text-muted-foreground">
                    You need a category before you can add tasks.
                    <Link :href="route('categories.index')" class="underline underline-offset-4">Create one</Link>
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="task-category">Category</Label>
                        <select
                            id="task-category"
                            v-model="form.category_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option v-for="c in categories" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-recurrence">Repeat</Label>
                        <select
                            id="task-recurrence"
                            v-model="form.recurrence"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Doesn't repeat</option>
                            <option value="daily">Every day</option>
                            <option value="weekly">Every week</option>
                            <option value="monthly">Every month</option>
                            <option value="custom">Custom…</option>
                        </select>
                        <InputError :message="form.errors.recurrence" />
                    </div>
                </div>

                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="form.recurrence === 'custom'" class="space-y-3 rounded-lg border border-border bg-muted/30 p-3">
                        <div class="flex items-center gap-2 text-sm">
                            <span>Every</span>

                            <input
                                v-model.number="form.recurrence_interval"
                                type="number"
                                min="1"
                                max="365"
                                class="flex h-10 w-20 rounded-md border border-input bg-background px-3 text-sm"
                            />

                            <select v-model="form.recurrence_unit" class="flex h-10 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="day">day{{ unitSuffix }}</option>
                                <option value="week">week{{ unitSuffix }}</option>
                                <option value="month">month{{ unitSuffix }}</option>
                            </select>
                        </div>

                        <InputError :message="form.errors.recurrence_interval || form.errors.recurrence_unit" />

                        <div v-if="form.recurrence_unit === 'week'" class="space-y-1.5">
                            <p class="text-xs text-muted-foreground">On these days (leave empty to use the due date's weekday)</p>

                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="day in weekdays"
                                    :key="day.value"
                                    type="button"
                                    :aria-pressed="form.recurrence_days.includes(day.value)"
                                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                    :class="
                                        form.recurrence_days.includes(day.value)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-input hover:bg-accent'
                                    "
                                    @click="toggleDay(day.value)"
                                >
                                    {{ day.label }}
                                </button>
                            </div>

                            <InputError :message="form.errors.recurrence_days" />
                        </div>
                    </div>
                </Transition>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="emit('update:open', false)"> Cancel </Button>
                    <Button type="submit" :disabled="form.processing || categories.length === 0">
                        {{ task ? 'Save changes' : 'Create task' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
