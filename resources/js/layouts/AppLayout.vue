<script setup lang="ts">
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <TooltipProvider>
            <slot />
        </TooltipProvider>
    </AppLayout>
</template>
