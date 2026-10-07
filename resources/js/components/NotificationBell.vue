<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { AppNotification, SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, BellOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const page = usePage<SharedData>();

const unreadCount = computed(() => page.props.notifications?.unread_count ?? 0);
const items = computed(() => page.props.notifications?.items ?? []);
const badgeLabel = computed(() => (unreadCount.value > 9 ? '9+' : String(unreadCount.value)));

const isOpen = ref(false);

// A new notification arrived while the app was open: let the user know
watch(unreadCount, (current, previous) => {
    if (current > previous) {
        toast.info(items.value[0]?.message ?? 'You have a new notification');
    }
});

function openItem(item: AppNotification) {
    isOpen.value = false;

    // Already read: just go to the page it points to
    if (item.read) {
        if (item.url) router.visit(item.url);
        return;
    }

    // Unread: mark it as read first, then navigate once that request has finished
    router.post(
        route('notifications.read', item.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (item.url) router.visit(item.url);
            },
        },
    );
}

function markAllAsRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9"
                :aria-label="unreadCount > 0 ? `Notifications, ${unreadCount} unread` : 'Notifications'"
            >
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                >
                    {{ badgeLabel }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <h2 class="text-sm font-semibold">Notifications</h2>
                <Button v-if="unreadCount > 0" variant="ghost" size="sm" class="h-7 px-2 text-xs" @click="markAllAsRead">Mark all as read</Button>
            </div>

            <ul v-if="items.length > 0" class="max-h-96 divide-y divide-border overflow-y-auto">
                <li v-for="item in items" :key="item.id">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-accent"
                        @click="openItem(item)"
                    >
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="item.read ? 'bg-transparent' : 'bg-primary'" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm" :class="item.read ? 'text-muted-foreground' : 'font-medium'">{{ item.title }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ item.message }}</span>
                            <span class="mt-1 block text-[11px] text-muted-foreground/80">{{ item.created_at }}</span>
                        </span>
                    </button>
                </li>
            </ul>

            <div v-else class="flex flex-col items-center px-4 py-10 text-center">
                <BellOff class="h-6 w-6 text-muted-foreground" />
                <p class="mt-2 text-sm font-medium">You're all caught up</p>
                <p class="mt-1 text-xs text-muted-foreground">Reminders about your tasks will show up here.</p>
            </div>
        </PopoverContent>
    </Popover>
</template>
