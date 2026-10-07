<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Flag, Layers, ListChecks, Moon } from 'lucide-vue-next';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const page = usePage<SharedData>();

const features = [
    {
        icon: ListChecks,
        title: 'Track every task',
        description: 'Create, edit, and complete tasks with a clean, focused interface.',
    },
    {
        icon: Flag,
        title: 'Prioritise what matters',
        description: 'Sort work by Low, Medium, High, or Urgent priority — at a glance.',
    },
    {
        icon: Layers,
        title: 'Organise with categories',
        description: 'Group tasks under Work, Personal, Meetings, or your own custom labels.',
    },
    {
        icon: Calendar,
        title: 'Never miss a deadline',
        description: 'Due dates highlight today and overdue tasks so nothing slips through.',
    },
];
</script>

<template>
    <Head title="Task Manager">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header class="border-b border-border">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <div class="flex items-center gap-2">
                    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                        <ListChecks class="size-5" />
                    </div>
                    <span class="text-sm font-semibold">Task Manager</span>
                </div>

                <nav class="flex items-center gap-2">
                    <template v-if="page.props.auth.user">
                        <Button as-child>
                            <Link :href="route('dashboard')">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child v-if="canLogin">
                            <Link :href="route('login')">Log in</Link>
                        </Button>
                        <Button as-child v-if="canRegister">
                            <Link :href="route('register')">Get started</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
            <div class="grid items-center gap-16 lg:grid-cols-2">
                <div>
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-muted-foreground"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        A simple, focused to-do app
                    </span>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                        Get things done,<br />
                        <span class="text-primary">one task at a time.</span>
                    </h1>

                    <p class="mt-6 max-w-lg text-base leading-relaxed text-muted-foreground">
                        Task Manager helps you organise your day with priorities, categories, and due-date awareness — all in a clean interface that
                        stays out of your way.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Button size="lg" as-child>
                            <Link :href="page.props.auth.user ? route('tasks.index') : route('register')">
                                {{ page.props.auth.user ? 'Open my tasks' : 'Start organising' }}
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="route('login')">Sign in</Link>
                        </Button>
                    </div>

                    <div class="mt-10 flex items-center gap-6 text-xs text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <CheckCircle2 class="h-4 w-4 text-primary" />
                            Free to use
                        </div>
                        <div class="flex items-center gap-2">
                            <Moon class="h-4 w-4 text-accent-foreground" />
                            Dark mode ready
                        </div>
                        <div class="flex items-center gap-2">
                            <Layers class="h-4 w-4 text-primary" />
                            Custom categories
                        </div>
                    </div>
                </div>

                <!-- Hero mockup: a fake task list -->
                <div class="relative">
                    <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-primary/10 via-accent/10 to-transparent blur-2xl" />
                    <div class="relative rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="h-2.5 w-2.5 rounded-full bg-primary" />
                                <span class="text-xs font-medium text-muted-foreground">Today</span>
                            </div>
                            <span class="text-xs text-muted-foreground">3 of 5 done</span>
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-start gap-3 rounded-lg border border-border p-3">
                                <div class="mt-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <CheckCircle2 class="h-3 w-3" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-muted-foreground line-through">Review pull requests</p>
                                    <div class="mt-1 flex items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-accent px-2 py-0.5 text-accent-foreground">Work</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-lg border border-primary/40 p-3">
                                <div class="mt-0.5 h-4 w-4 rounded-full border border-input" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Ship internship demo</p>
                                    <div class="mt-1 flex items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-primary/15 px-2 py-0.5 text-primary">High</span>
                                        <span class="rounded-full border border-border px-2 py-0.5 text-muted-foreground">Work</span>
                                        <span class="ml-auto text-primary">Due today</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-lg border border-border p-3">
                                <div class="mt-0.5 h-4 w-4 rounded-full border border-input" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Read Inertia docs</p>
                                    <div class="mt-1 flex items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-secondary px-2 py-0.5 text-secondary-foreground">Medium</span>
                                        <span class="rounded-full border border-border px-2 py-0.5 text-muted-foreground">Personal</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-lg border border-destructive/40 bg-destructive/5 p-3">
                                <div class="mt-0.5 h-4 w-4 rounded-full border border-input" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Send weekly report</p>
                                    <div class="mt-1 flex items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-destructive px-2 py-0.5 text-destructive-foreground">Urgent</span>
                                        <span class="ml-auto text-destructive">Overdue</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="border-t border-border bg-card/50">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="mb-12 max-w-2xl">
                    <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Everything you need, nothing you don't.</h2>
                    <p class="mt-3 text-muted-foreground">Task Manager stays out of the way so you can focus on getting things done.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="feature in features" :key="feature.title" class="rounded-xl border border-border bg-card p-5">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <h3 class="text-sm font-semibold">{{ feature.title }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="border-t border-border">
            <div class="mx-auto flex max-w-6xl flex-col items-center px-6 py-20 text-center">
                <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <ListChecks class="size-5" />
                </div>
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Ready to organise your day?</h2>
                <p class="mt-3 max-w-md text-muted-foreground">Create your free account and start managing your tasks in under a minute.</p>
                <Button size="lg" class="mt-8" as-child>
                    <Link :href="page.props.auth.user ? route('tasks.index') : route('register')">
                        {{ page.props.auth.user ? 'Open my tasks' : "Get started — it's free" }}
                    </Link>
                </Button>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-xs text-muted-foreground sm:flex-row">
                <div class="flex items-center gap-2">
                    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                        <ListChecks class="size-5" />
                    </div>
                    <span>Task Manager</span>
                </div>
                <p>Built with Laravel, Inertia &amp; Vue</p>
            </div>
        </footer>
    </div>
</template>
