<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

const page = usePage<SharedData>();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            const undoUrl = flash.undo;

            toast.success(
                flash.success,
                undoUrl
                    ? {
                          duration: 8000,
                          action: {
                              label: 'Undo',
                              onClick: () => router.patch(undoUrl, {}, { preserveScroll: true, preserveState: true }),
                          },
                      }
                    : undefined,
            );
        }
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);
</script>

<template>
    <Toaster position="top-right" rich-colors />
</template>
