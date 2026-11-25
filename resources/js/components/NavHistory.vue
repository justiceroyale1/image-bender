<script setup lang="ts">
import { computed } from 'vue';
import { InfiniteScroll, Link, usePage } from '@inertiajs/vue3';
import { AlertCircle, Loader2 } from '@lucide/vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type {
    GenerationHistoryItem,
    GenerationStatus,
    ScrollPaginated,
} from '@/types';

const props = defineProps<{
    items?: ScrollPaginated<GenerationHistoryItem>;
}>();

const page = usePage();

const history = computed<ScrollPaginated<GenerationHistoryItem>>(() => {
    return (
        props.items ??
        page.props.generationHistory ?? {
            data: [],
        }
    );
});

const normalizePath = (url: string): string => {
    try {
        return new URL(url, window.location.origin).pathname.replace(/\/$/, '');
    } catch {
        return url.split('?')[0].replace(/\/$/, '');
    }
};

const currentPath = computed(() => normalizePath(page.url));

const isCurrentItem = (itemUrl: string): boolean => {
    return currentPath.value === normalizePath(itemUrl);
};

const isActive = (status: GenerationStatus): boolean => {
    return status === 'queued' || status === 'processing';
};

const isFailed = (status: GenerationStatus): boolean => {
    return status === 'failed';
};
</script>

<template>
    <SidebarGroup v-if="history.data.length > 0" class="group-data-[collapsible=icon]:hidden">
        <SidebarGroupLabel>History</SidebarGroupLabel>
        <SidebarGroupContent>
            <InfiniteScroll data="generationHistory" items-element="#generation-history-list" :buffer="200" preserve-url
                only-next>
                <SidebarMenu id="generation-history-list">
                    <SidebarMenuItem v-for="item in history.data" :key="item.id">
                        <SidebarMenuButton as-child :is-active="isCurrentItem(item.url)" :tooltip="item.title">
                            <Link :href="item.url" prefetch class="flex items-center justify-between gap-2">
                                <span class="truncate" :class="{
                                    'text-muted-foreground': isActive(
                                        item.status,
                                    ),
                                    'text-destructive': isFailed(
                                        item.status,
                                    ),
                                }">
                                    {{ item.title }}
                                </span>

                                <Loader2 v-if="isActive(item.status)"
                                    class="size-3.5 shrink-0 animate-spin text-muted-foreground" :aria-label="item.status === 'queued'
                                        ? 'Queued'
                                        : 'Processing'
                                        " />
                                <AlertCircle v-else-if="isFailed(item.status)"
                                    class="size-3.5 shrink-0 text-destructive" aria-label="Failed" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <template #loading>
                    <div class="px-2 py-2 text-xs text-muted-foreground">
                        Loading older items...
                    </div>
                </template>
            </InfiniteScroll>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
