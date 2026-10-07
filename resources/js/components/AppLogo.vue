<script setup lang="ts">
import { ListChecks } from 'lucide-vue-next';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <ListChecks class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="whitespace-nowrap font-semibold leading-none">Task Manager</span>
    </div>
</template>
