<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    category: Category | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({ name: '' });

// Populate the form each time the modal opens (create = empty, edit = existing name)
watch(open, (isOpen) => {
    if (!isOpen) return;
    form.clearErrors();
    form.name = props.category?.name ?? '';
});

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    if (props.category) {
        form.put(route('categories.update', props.category.id), options);
    } else {
        form.post(route('categories.store'), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ category ? 'Edit category' : 'New category' }}</DialogTitle>
                <DialogDescription>Categories help you group your tasks.</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="category-name">Name</Label>
                    <Input id="category-name" v-model="form.name" placeholder="e.g. Study" autofocus />
                    <InputError :message="form.errors.name" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ category ? 'Save changes' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
