<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import DeleteConfirmDialog from '@/components/tasks/DeleteConfirmDialog.vue';
import TaskCard from '@/components/tasks/TaskCard.vue';
import TaskFormModal from '@/components/tasks/TaskFormModal.vue';
import { Button } from '@/components/ui/button';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, Paginated, Task, TaskFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    tasks: Paginated<Task>;
    categories: Category[];
    filters: TaskFilters;
}>();

// --- Local filter state (initialised from server-provided filters) ---
const selectedCategory = ref(props.filters.category ?? 'all');
const selectedPriority = ref(props.filters.priority ?? 'all');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const showCompletedOnly = ref(props.filters.completed === 'true' || props.filters.completed === true);
const search = ref(props.filters.search ?? '');

// Based on what the server actually applied, not on what's typed in the inputs
const hasActiveFilters = computed(() =>
    Boolean(
        props.filters.category ||
            props.filters.priority ||
            props.filters.completed ||
            props.filters.search ||
            props.filters.date_from ||
            props.filters.date_to,
    ),
);

const isDateRangeInvalid = computed(() => {
    return Boolean(dateFrom.value && dateTo.value && dateFrom.value > dateTo.value);
});

// --- Modal state ---
const isFormModalOpen = ref(false);
const editingTask = ref<Task | null>(null);
const deletingTask = ref<Task | null>(null);
const isDeleteDialogOpen = ref(false);

function openDeleteDialog(task: Task) {
    deletingTask.value = task;
    isDeleteDialogOpen.value = true;
}

function openCreateModal() {
    editingTask.value = null;
    isFormModalOpen.value = true;
}

function openEditModal(task: Task) {
    editingTask.value = task;
    isFormModalOpen.value = true;
}

// --- Filters push new query strings through Inertia ---
function applyFilters() {
    if (isDateRangeInvalid.value) {
        return;
    }

    router.get(
        route('tasks.index'),
        {
            category: selectedCategory.value === 'all' ? undefined : selectedCategory.value,
            priority: selectedPriority.value === 'all' ? undefined : selectedPriority.value,
            completed: showCompletedOnly.value ? 'true' : undefined,
            search: search.value.trim() || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['tasks', 'filters'],
        },
    );
}

// Wait 300ms after the last change before sending a request
let filterTimer: ReturnType<typeof setTimeout>;

watch([selectedCategory, selectedPriority, showCompletedOnly, search, dateFrom, dateTo], () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(applyFilters, 300);
});

function clearFilters() {
    selectedCategory.value = 'all';
    selectedPriority.value = 'all';
    showCompletedOnly.value = false;
    search.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

// --- Keyboard shortcuts ---
const searchInput = ref<InstanceType<typeof Input> | null>(null);

function focusSearch() {
    searchInput.value?.$el.focus();
    searchInput.value?.$el.select();
}

// Esc clears the search first; pressing it again leaves the box
function onSearchEscape() {
    if (search.value) {
        search.value = '';
    } else {
        searchInput.value?.$el.blur();
    }
}

useKeyboardShortcut('n', openCreateModal);
useKeyboardShortcut('/', focusSearch);

onBeforeUnmount(() => clearTimeout(filterTimer));
</script>

<template>
    <Head title="Tasks" />

    <div class="w-full p-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Tasks</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage your personal to-do list.</p>
            </div>
            <Button @click="openCreateModal">
                <Plus class="mr-2 h-4 w-4" />
                New Task
                <kbd class="ml-2 hidden rounded bg-primary-foreground/20 px-1.5 font-mono text-[10px] sm:inline">N</kbd>
            </Button>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-border bg-card p-4">
            <div class="flex flex-col gap-1.5">
                <Label for="filter-category">Category</Label>
                <select id="filter-category" v-model="selectedCategory" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="all">All categories</option>
                    <option v-for="c in categories" :key="c.id" :value="String(c.id)">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-priority">Priority</Label>
                <select id="filter-priority" v-model="selectedPriority" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="all">All priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-date-from">Due from</Label>
                <Input id="filter-date-from" v-model="dateFrom" type="date" class="h-9" :class="{ 'border-destructive': isDateRangeInvalid }" />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-date-to">Due to</Label>
                <Input id="filter-date-to" v-model="dateTo" type="date" class="h-9" :class="{ 'border-destructive': isDateRangeInvalid }" />
            </div>

            <div class="flex h-9 items-center gap-2 text-sm">
                <Checkbox id="filter-completed" v-model:checked="showCompletedOnly" class="rounded-full" />
                <Label for="filter-completed">Completed only</Label>
            </div>

            <div class="relative ml-auto w-full sm:w-64">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input ref="searchInput" v-model="search" type="search" placeholder="Search tasks…" class="pl-9" @keydown.esc="onSearchEscape" />
                <kbd
                    v-if="!search"
                    class="pointer-events-none absolute right-2.5 top-2 hidden h-5 items-center rounded border border-border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground sm:flex"
                >
                    /
                </kbd>
            </div>

            <div v-if="isDateRangeInvalid" class="basis-full text-center text-sm text-destructive">
                The "Due from" date cannot be later than the "Due to" date.
            </div>
        </div>

        <div v-if="tasks.data.length === 0" class="rounded-lg border border-dashed border-border p-12 text-center">
            <template v-if="hasActiveFilters">
                <p class="font-medium">No tasks match your filters</p>
                <p class="mt-1 text-sm text-muted-foreground">Try a different search, or clear the filters to see everything.</p>
                <Button variant="outline" class="mt-4" @click="clearFilters">Clear filters</Button>
            </template>
            <template v-else>
                <p class="font-medium">No tasks yet</p>
                <p class="my-3 text-sm text-muted-foreground">Create your first task to get started.</p>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    New Task
                    <kbd class="ml-2 hidden rounded bg-primary-foreground/20 px-1.5 font-mono text-[10px] sm:inline">N</kbd>
                </Button>
            </template>
        </div>

        <div v-else class="space-y-3">
            <TaskCard v-for="task in tasks.data" :key="task.id" :task="task" @edit="openEditModal(task)" @delete="openDeleteDialog(task)" />
        </div>

        <Pagination :pagination="tasks" />
    </div>

    <TaskFormModal v-model:open="isFormModalOpen" :task="editingTask" :categories="categories" />
    <DeleteConfirmDialog v-model:open="isDeleteDialogOpen" :task="deletingTask" />
</template>
