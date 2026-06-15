<script setup lang="ts">
import { index as templatesRoute } from '@/routes/templates';
import Builder from './Builder.vue';

defineProps<{
    template: { id: number; name: string; html: string };
    lists: { value: number; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Templates', href: templatesRoute() }],
    },
});
</script>

<template>
    <Builder :template="template" :lists="lists" />
</template>
