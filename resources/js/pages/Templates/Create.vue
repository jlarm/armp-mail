<script setup lang="ts">
import { index as templatesRoute } from '@/routes/templates';
import Builder from './Builder.vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Templates', href: templatesRoute() }],
    },
});
</script>

<template>
    <Builder />
</template>
