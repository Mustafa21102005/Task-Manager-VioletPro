<script setup lang="ts">
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue';
import Pagination from '@/components/Pagination.vue';
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
import { Button } from '@/components/ui/button';
import { useKeyboardShortcut } from '@/composables/useKeyboardShortcut';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category, CategoryWithCount, Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

defineProps<{
    categories: Paginated<CategoryWithCount>;
}>();

// --- Create / edit modal ---
const isFormOpen = ref(false);
const editing = ref<Category | null>(null);

function openCreate() {
    editing.value = null;
    isFormOpen.value = true;
}

function openEdit(category: Category) {
    editing.value = category;
    isFormOpen.value = true;
}

// --- Delete dialog ---
const isDeleteOpen = ref(false);
const deleting = ref<CategoryWithCount | null>(null);

function openDelete(category: CategoryWithCount) {
    deleting.value = category;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deleting.value) return;

    router.delete(route('categories.destroy', deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (isDeleteOpen.value = false),
    });
}

useKeyboardShortcut('n', openCreate);
</script>

<template>
    <Head title="Categories" />

    <div class="w-full px-4 py-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Categories</h1>
                <p class="mt-1 text-sm text-muted-foreground">Organise your tasks into groups.</p>
            </div>
            <Button @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                New Category
                <kbd class="ml-2 hidden rounded bg-primary-foreground/20 px-1.5 font-mono text-[10px] sm:inline">N</kbd>
            </Button>
        </div>

        <div v-if="categories.data.length === 0" class="rounded-lg border border-dashed border-border p-12 text-center">
            <p class="text-muted-foreground">No categories yet.</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="category in categories.data"
                :key="category.id"
                class="flex items-center justify-between rounded-lg border border-border bg-card p-4 transition-colors hover:border-primary/40"
            >
                <div>
                    <p class="font-medium">{{ category.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ category.tasks_count }} {{ category.tasks_count === 1 ? 'task' : 'tasks' }}</p>
                </div>

                <div class="flex items-center gap-1">
                    <Button variant="ghost" size="icon" @click="openEdit(category)">
                        <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :disabled="category.tasks_count > 0"
                        :title="category.tasks_count > 0 ? 'Move or delete its tasks first' : 'Delete category'"
                        @click="openDelete(category)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <Pagination :pagination="categories" />
    </div>

    <CategoryFormModal v-model:open="isFormOpen" :category="editing" />

    <AlertDialog v-model:open="isDeleteOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete "{{ deleting?.name }}"?</AlertDialogTitle>
                <AlertDialogDescription>This can't be undone.</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click.prevent="confirmDelete">Delete</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
