<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useToggleTask } from '@/composables/useToggleTask';
import AppLayout from '@/layouts/AppLayout.vue';
import { priorityBadgeClasses, priorityBarClasses } from '@/lib/priority';
import type {
    ActivityDay,
    ActivityStats,
    CategoryBreakdown,
    DashboardProgress,
    DashboardStats,
    FocusTask,
    PriorityBreakdown,
    SharedData,
} from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Calendar, CheckCircle2, Clock, Flame, ListChecks } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    stats: DashboardStats;
    focusTasks: FocusTask[];
    byPriority: PriorityBreakdown[];
    byCategory: CategoryBreakdown[];
    activity: ActivityStats;
    progress: DashboardProgress;
}>();

const { toggleTask, isToggling } = useToggleTask();

// --- Progress card: tasks due today / this week / this month ---
const periods = [
    { key: 'today', label: 'Today', noun: 'today' },
    { key: 'week', label: 'This week', noun: 'this week' },
    { key: 'month', label: 'This month', noun: 'this month' },
] as const;

const period = ref<(typeof periods)[number]['key']>('today');

const activePeriod = computed(() => periods.find((item) => item.key === period.value) ?? periods[0]);
const currentProgress = computed(() => props.progress[period.value]);

const page = usePage<SharedData>();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

// --- Greeting ---
const hour = new Date().getHours();
const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
const todayLabel = new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });

function plural(count: number, word: string) {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

const summary = computed(() => {
    const { total, open, overdue, due_today } = props.stats;

    if (total === 0) return 'Create your first task to get started.';
    if (open === 0) return 'Everything is done. Nice work!';
    if (overdue > 0) return `${plural(overdue, 'task')} overdue. Let's start there.`;
    if (due_today > 0) return `${plural(due_today, 'task')} due today.`;
    return `You have ${plural(open, 'open task')}, and nothing is due today.`;
});

const statCards = computed(() => [
    {
        key: 'due_today',
        label: 'Due today',
        value: props.stats.due_today,
        icon: Calendar,
        tone: 'bg-primary/10 text-primary',
    },
    {
        key: 'overdue',
        label: 'Overdue',
        value: props.stats.overdue,
        icon: Clock,
        tone: props.stats.overdue > 0 ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' : 'bg-muted text-muted-foreground',
    },
    {
        key: 'open',
        label: 'Open tasks',
        value: props.stats.open,
        icon: ListChecks,
        tone: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    },
]);

// --- Streak + weekly progress ---
const maxDayCount = computed(() => Math.max(1, ...props.activity.week.map((day) => day.count)));

// Empty days get a small stub so the chart still reads as a row of bars
function barHeight(count: number) {
    return count === 0 ? 4 : Math.max(15, Math.round((count / maxDayCount.value) * 100));
}

function barClasses(day: ActivityDay) {
    if (day.count === 0) return 'bg-muted';
    return day.is_today ? 'bg-primary' : 'bg-primary/60';
}

function weekdayLabel(date: string) {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short' });
}

const weekComparison = computed(() => {
    const { this_week, last_week } = props.activity;

    if (this_week === 0 && last_week === 0) return null;

    const diff = this_week - last_week;
    if (diff === 0) return 'Same as last week';

    return `${diff > 0 ? '+' : '-'}${Math.abs(diff)} vs last week`;
});

const streakMessage = computed(() => {
    const { current_streak, completed_today } = props.activity;

    if (current_streak === 0) return 'Complete a task today to start a streak.';
    if (completed_today === 0) return 'Complete a task today to keep it going!';
    return current_streak === 1 ? 'Streak started. Come back tomorrow!' : 'Keep it going!';
});

// --- Breakdowns ---
// The controller sends priorities low → urgent; show the most important first
const priorityRows = computed(() => [...props.byPriority].reverse());

function percentOfOpen(count: number) {
    return props.stats.open > 0 ? Math.round((count / props.stats.open) * 100) : 0;
}

function formatDate(date: string) {
    // Add a time so the browser doesn't treat "2026-10-05" as UTC and shift the day
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="w-full space-y-8 p-8">
        <!-- Greeting -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">{{ todayLabel }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ greeting }}, {{ firstName }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ summary }}</p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="route('tasks.index')">
                    <ListChecks class="mr-2 h-4 w-4" />
                    All tasks
                </Link>
            </Button>
        </div>

        <!-- Stat cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="card in statCards" :key="card.key" class="rounded-xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="card.tone">
                        <component :is="card.icon" class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-semibold tracking-tight">{{ card.value }}</p>
            </div>

            <div class="rounded-xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">Progress</p>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                    </div>
                </div>

                <div class="mt-3 flex items-baseline justify-between">
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ currentProgress.completed }}<span class="text-base font-normal text-muted-foreground"> / {{ currentProgress.total }}</span>
                    </p>
                    <p class="text-lg font-semibold">{{ currentProgress.percent }}%</p>
                </div>

                <div class="mt-3 flex rounded-lg bg-muted p-1">
                    <button
                        v-for="item in periods"
                        :key="item.key"
                        type="button"
                        class="flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
                        :class="period === item.key ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="period = item.key"
                    >
                        {{ item.label }}
                    </button>
                </div>

                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="currentProgress.percent === 100 ? 'bg-emerald-500' : 'bg-primary'"
                        :style="{ width: `${currentProgress.percent}%` }"
                    />
                </div>
                <p class="mt-1.5 text-xs text-muted-foreground">
                    {{ currentProgress.total === 0 ? `No tasks due ${activePeriod.noun}` : `Tasks due ${activePeriod.noun}` }}
                </p>
            </div>
        </div>

        <!-- Streak + weekly progress -->
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">Current streak</p>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg"
                        :class="
                            activity.current_streak > 0
                                ? 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-300'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <Flame class="h-4 w-4" />
                    </div>
                </div>
                <p class="mt-3 text-3xl font-semibold tracking-tight">
                    {{ activity.current_streak }}
                    <span class="text-base font-normal text-muted-foreground">{{ activity.current_streak === 1 ? 'day' : 'days' }}</span>
                </p>
                <p class="mt-1.5 text-xs text-muted-foreground">{{ streakMessage }}</p>
                <p class="mt-3 text-xs text-muted-foreground">Best: {{ activity.best_streak }} {{ activity.best_streak === 1 ? 'day' : 'days' }}</p>
            </section>

            <section class="rounded-xl border border-border bg-card p-5 lg:col-span-2">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold">This week</h2>
                        <p class="text-xs text-muted-foreground">Tasks completed per day</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-semibold tracking-tight">{{ activity.this_week }}</p>
                        <p v-if="weekComparison" class="text-xs text-muted-foreground">{{ weekComparison }}</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-7 gap-2">
                    <div v-for="day in activity.week" :key="day.date" class="flex flex-col items-center gap-1.5">
                        <div class="flex h-24 w-full items-end justify-center">
                            <div
                                class="w-full max-w-9 rounded-md transition-all"
                                :class="barClasses(day)"
                                :style="{ height: `${barHeight(day.count)}%` }"
                                :title="`${day.count} completed`"
                            />
                        </div>
                        <span class="text-xs" :class="day.is_today ? 'font-semibold text-foreground' : 'text-muted-foreground'">
                            {{ weekdayLabel(day.date) }}
                        </span>
                        <span class="text-[11px] text-muted-foreground">{{ day.is_future ? '–' : day.count }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Focus now -->
            <section class="rounded-xl border border-border bg-card lg:col-span-2">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <h2 class="font-semibold">Focus now</h2>
                        <p class="text-xs text-muted-foreground">Overdue and due today first, then by priority.</p>
                    </div>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('tasks.index')">
                            View all
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Link>
                    </Button>
                </div>

                <ul v-if="focusTasks.length > 0" class="divide-y divide-border">
                    <li v-for="task in focusTasks" :key="task.id" class="flex items-center gap-3 px-5 py-3.5">
                        <Checkbox
                            :checked="false"
                            :disabled="isToggling(task.id)"
                            class="h-5 w-5 shrink-0 rounded-full"
                            :aria-label="`Mark ${task.title} as completed`"
                            @update:checked="toggleTask(task.id)"
                        />

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ task.title }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full px-2 py-0.5" :class="priorityBadgeClasses[task.priority]">
                                    {{ task.priority_label }}
                                </span>
                                <span v-if="task.category_name" class="rounded-full border border-border px-2 py-0.5 text-muted-foreground">
                                    {{ task.category_name }}
                                </span>
                            </div>
                        </div>

                        <span v-if="task.due_status === 'overdue'" class="shrink-0 text-xs font-medium text-red-600 dark:text-red-400">Overdue</span>
                        <span v-else-if="task.due_status === 'today'" class="shrink-0 text-xs font-medium text-primary">Due today</span>
                        <span v-else-if="task.due_date" class="shrink-0 text-xs text-muted-foreground">{{ formatDate(task.due_date) }}</span>
                    </li>
                </ul>

                <div v-else class="flex flex-col items-center px-5 py-12 text-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <template v-if="stats.total === 0">
                        <p class="mt-3 font-medium">No tasks yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">Add your first task and it will show up here.</p>
                        <Button class="mt-4" as-child>
                            <Link :href="route('tasks.index')">Go to tasks</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <p class="mt-3 font-medium">You're all caught up</p>
                        <p class="mt-1 text-sm text-muted-foreground">No open tasks right now.</p>
                    </template>
                </div>
            </section>

            <!-- Breakdowns -->
            <div class="space-y-6">
                <section class="rounded-xl border border-border bg-card p-5">
                    <h2 class="font-semibold">Open by priority</h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="item in priorityRows" :key="item.priority">
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span>{{ item.label }}</span>
                                <span class="text-muted-foreground">{{ item.count }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="priorityBarClasses[item.priority]"
                                    :style="{ width: `${percentOfOpen(item.count)}%` }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border border-border bg-card p-5">
                    <h2 class="font-semibold">Open by category</h2>
                    <ul v-if="byCategory.length > 0" class="mt-4 space-y-3">
                        <li v-for="item in byCategory" :key="item.id">
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="truncate">{{ item.name }}</span>
                                <span class="text-muted-foreground">{{ item.count }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${percentOfOpen(item.count)}%` }" />
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-muted-foreground">No open tasks.</p>
                </section>
            </div>
        </div>
    </div>
</template>
