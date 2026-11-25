import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

// resources/js/types/index.d.ts

export type GenerationStatus =
    | 'dispatched'
    | 'queued'
    | 'processing'
    | 'succeeded'
    | 'failed'
    | (string & {});

export interface GenerationHistoryItem {
    id: number | string;
    title: string;
    url: string;
    status: GenerationStatus;
    created_at?: string;
}

export interface ScrollPaginated<T> {
    data: T[];
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    export interface PageProps {
        generationHistory?: ScrollPaginated<GenerationHistoryItem>;
    }
}

