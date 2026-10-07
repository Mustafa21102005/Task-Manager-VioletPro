<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps<{
    pagination: Paginated<unknown>;
}>();
</script>

<template>
    <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
        <p class="text-sm text-muted-foreground">Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}</p>

        <div class="flex items-center gap-2">
            <Button v-if="pagination.prev_page_url" variant="outline" size="sm" as-child>
                <Link :href="pagination.prev_page_url" preserve-scroll>
                    <ChevronLeft class="mr-1 h-4 w-4" />
                    Previous
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <ChevronLeft class="mr-1 h-4 w-4" />
                Previous
            </Button>

            <span class="px-2 text-sm">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>

            <Button v-if="pagination.next_page_url" variant="outline" size="sm" as-child>
                <Link :href="pagination.next_page_url" preserve-scroll>
                    Next
                    <ChevronRight class="ml-1 h-4 w-4" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                Next
                <ChevronRight class="ml-1 h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
