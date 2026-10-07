<script setup lang="ts">
import DeleteConfirmDialog from '@/components/tasks/DeleteConfirmDialog.vue';
import TaskCard from '@/components/tasks/TaskCard.vue';
import TaskFormModal from '@/components/tasks/TaskFormModal.vue';
import { Button } from '@/components/ui/button';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import AppLayout from '@/layouts/AppLayout.vue';
import { priorityBarClasses } from '@/lib/priority';
import type { CalendarDay, Category, Task } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    month: string;
    prevMonth: string;
    nextMonth: string;
    today: string;
    days: CalendarDay[];
    tasks: Task[];
    categories: Category[];
}>();

const MAX_CHIPS = 3;

// --- Labels (formatted in the user's language) ---
const monthLabel = computed(() => new Date(`${props.month}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));

// The first 7 days of the grid are Monday to Sunday, so they give us the header labels
const weekdayHeaders = computed(() =>
    props.days.slice(0, 7).map((day) => new Date(`${day.date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short' })),
);

// --- Tasks grouped by their due date ---
const tasksByDate = computed(() => {
    const map = new Map<string, Task[]>();

    for (const task of props.tasks) {
        if (!task.due_date) continue;
        map.set(task.due_date, [...(map.get(task.due_date) ?? []), task]);
    }

    return map;
});

function dayTasks(date: string) {
    return tasksByDate.value.get(date) ?? [];
}

// --- Selected day ---
function defaultSelectedDate() {
    const inMonth = props.days.filter((day) => day.in_month);

    return inMonth.find((day) => day.date === props.today)?.date ?? inMonth[0]?.date ?? props.today;
}

const selectedDate = ref(defaultSelectedDate());

// A different month was loaded: pick today (if it is in that month) or the 1st
watch(
    () => props.month,
    () => {
        selectedDate.value = defaultSelectedDate();
    },
);

const selectedDay = computed(() => props.days.find((day) => day.date === selectedDate.value));
const selectedTasks = computed(() => dayTasks(selectedDate.value));
const selectedLabel = computed(() =>
    new Date(`${selectedDate.value}T00:00:00`).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }),
);

// --- Month navigation (a partial reload: categories are not sent again) ---
function goToMonth(month?: string) {
    router.get(route('calendar.index'), month ? { month } : {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['month', 'prevMonth', 'nextMonth', 'today', 'days', 'tasks'],
    });
}

function goToToday() {
    if (props.days.some((day) => day.in_month && day.date === props.today)) {
        selectedDate.value = props.today;
    } else {
        goToMonth();
    }
}

// --- Create / edit / delete ---
const isFormOpen = ref(false);
const editingTask = ref<Task | null>(null);
const createForDate = ref<string | null>(null);

const isDeleteOpen = ref(false);
const deletingTask = ref<Task | null>(null);

function openCreate(date: string) {
    const day = props.days.find((item) => item.date === date);
    if (!day || day.is_past) return;

    selectedDate.value = date;
    createForDate.value = date;
    editingTask.value = null;
    isFormOpen.value = true;
}

function openEdit(task: Task) {
    createForDate.value = null;
    editingTask.value = task;
    isFormOpen.value = true;
}

function openDelete(task: Task) {
    deletingTask.value = task;
    isDeleteOpen.value = true;
}

useKeyboardShortcut('n', () => openCreate(selectedDate.value));
</script>

<template>
    <Head title="Calendar" />

    <div class="w-full p-8">
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Calendar</h1>
                <p class="mt-1 text-sm text-muted-foreground">Plan your tasks day by day.</p>
            </div>
            <Button :disabled="selectedDay?.is_past" @click="openCreate(selectedDate)">
                <Plus class="mr-2 h-4 w-4" />
                New Task
                <kbd class="ml-2 hidden rounded bg-primary-foreground/20 px-1.5 font-mono text-[10px] sm:inline">N</kbd>
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <!-- Month grid -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ monthLabel }}</h2>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="icon" class="h-8 w-8" aria-label="Previous month" @click="goToMonth(prevMonth)">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" @click="goToToday">Today</Button>
                        <Button variant="outline" size="icon" class="h-8 w-8" aria-label="Next month" @click="goToMonth(nextMonth)">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-border">
                    <div class="grid grid-cols-7 gap-px bg-border">
                        <div
                            v-for="label in weekdayHeaders"
                            :key="label"
                            class="bg-muted px-2 py-2 text-center text-xs font-medium text-muted-foreground"
                        >
                            {{ label }}
                        </div>

                        <button
                            v-for="day in days"
                            :key="day.date"
                            type="button"
                            class="flex min-h-14 flex-col items-start gap-1 p-1.5 text-left transition duration-150 hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring active:scale-95 motion-reduce:active:scale-100 sm:min-h-28"
                            :class="[
                                day.in_month ? 'bg-card' : 'bg-muted text-muted-foreground',
                                selectedDate === day.date ? 'day-selected relative z-10 ring-2 ring-inset ring-primary' : '',
                            ]"
                            :aria-pressed="selectedDate === day.date"
                            :aria-label="`${day.date}: ${dayTasks(day.date).length} tasks`"
                            @click="selectedDate = day.date"
                            @dblclick="openCreate(day.date)"
                        >
                            <span
                                class="day-number flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                :class="
                                    day.is_today
                                        ? 'bg-primary text-primary-foreground'
                                        : selectedDate === day.date
                                          ? 'bg-primary/15 text-primary'
                                          : ''
                                "
                            >
                                {{ day.day }}
                            </span>

                            <!-- Phones: just a count -->
                            <span
                                v-if="dayTasks(day.date).length > 0"
                                class="rounded-full bg-primary/15 px-1.5 text-[10px] font-medium text-primary sm:hidden"
                            >
                                {{ dayTasks(day.date).length }}
                            </span>

                            <!-- Larger screens: task chips -->
                            <span class="hidden w-full flex-col gap-1 sm:flex">
                                <span
                                    v-for="task in dayTasks(day.date).slice(0, MAX_CHIPS)"
                                    :key="task.id"
                                    class="flex items-center gap-1.5 rounded bg-secondary px-1.5 py-0.5 text-[11px] text-secondary-foreground"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="priorityBarClasses[task.priority]" />
                                    <span class="truncate" :class="{ 'line-through opacity-60': task.is_completed }">{{ task.title }}</span>
                                </span>
                                <span v-if="dayTasks(day.date).length > MAX_CHIPS" class="px-1 text-[11px] text-muted-foreground">
                                    +{{ dayTasks(day.date).length - MAX_CHIPS }} more
                                </span>
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Selected day -->
            <aside class="h-fit overflow-hidden rounded-xl border border-border bg-card">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <Transition name="day-swap" mode="out-in">
                        <div :key="selectedDate" aria-live="polite">
                            <h2 class="font-semibold">{{ selectedLabel }}</h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    selectedTasks.length === 0
                                        ? 'Nothing scheduled'
                                        : `${selectedTasks.length} ${selectedTasks.length === 1 ? 'task' : 'tasks'}`
                                }}
                            </p>
                        </div>
                    </Transition>
                    <Button
                        size="sm"
                        :disabled="selectedDay?.is_past"
                        :title="selectedDay?.is_past ? 'You cannot add tasks to past days' : undefined"
                        @click="openCreate(selectedDate)"
                    >
                        <Plus class="mr-1 h-4 w-4" />
                        Add
                    </Button>
                </div>

                <Transition name="day-swap" mode="out-in">
                    <div :key="selectedDate">
                        <div v-if="selectedTasks.length > 0" class="space-y-3 p-4">
                            <TaskCard
                                v-for="(task, index) in selectedTasks"
                                :key="task.id"
                                class="day-card-in"
                                :style="{ animationDelay: `${index * 50}ms` }"
                                :task="task"
                                @edit="openEdit(task)"
                                @delete="openDelete(task)"
                            />
                        </div>
                        <p v-else class="px-5 py-10 text-center text-sm text-muted-foreground">No tasks on this day.</p>
                    </div>
                </Transition>
            </aside>
        </div>
    </div>

    <TaskFormModal v-model:open="isFormOpen" :task="editingTask" :categories="categories" :default-due-date="createForDate" />
    <DeleteConfirmDialog v-model:open="isDeleteOpen" :task="deletingTask" />
</template>

<style scoped>
@keyframes day-pulse {
    from {
        box-shadow: 0 0 0 0 hsl(var(--primary) / 0.45);
    }
    to {
        box-shadow: 0 0 0 12px hsl(var(--primary) / 0);
    }
}

@keyframes day-pop {
    0% {
        transform: scale(0.6);
    }
    60% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes card-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

.day-selected {
    animation: day-pulse 0.6s ease-out;
}

.day-selected .day-number {
    animation: day-pop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.day-card-in {
    animation: card-in 0.3s ease-out both;
}

.day-swap-enter-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.day-swap-leave-active {
    transition: opacity 0.1s ease;
}

.day-swap-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.day-swap-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .day-selected,
    .day-selected .day-number,
    .day-card-in {
        animation: none;
    }

    .day-swap-enter-active,
    .day-swap-leave-active {
        transition: none;
    }
}
</style>
