<script setup lang="ts">
import { index as listsRoute } from '@/routes/lists';
import Builder from './Builder.vue';

type Option = { value: number; label: string };

defineProps<{
    list: { name: string; slug: string };
    options: {
        tags: string[];
        attributes: string[];
        campaigns: Option[];
        automationMails: Option[];
        lists: Option[];
    };
    preview?: {
        total: number;
        subscribers: { id: number; email: string; name: string | null }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Lists', href: listsRoute() }],
    },
});
</script>

<template>
    <Builder :list="list" :options="options" :preview="preview" />
</template>
