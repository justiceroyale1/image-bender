<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import { useFormat } from '@/composables/useFormat';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupText,
} from '@/components/ui/input-group';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    AlertCircleIcon,
    ClockIcon,
    DownloadIcon,
    ImageIcon,
    Loader2Icon,
    PlusIcon,
} from '@lucide/vue';
import { create } from '@/routes/generations';

export type GenerationStatus =
    | 'queued'
    | 'processing'
    | 'succeeded'
    | 'failed';

export interface Generation {
    id: string;
    title: string;
    prompt: string;
    quality: string;
    size: string;
    status: GenerationStatus;
    is_in_flight: boolean;
    cost: number | null;
    image_url: string | null;
    is_expired: boolean;
    reference_images: string[];
    created_at: string | null;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'New Generation',
                href: create(),
            }
        ],
    },
    inheritAttrs: false
});

const props = defineProps<{
    generation: Generation;
}>();

const { currencyFormat } = useFormat();

const isProcessing = computed(() => {
    return (
        props.generation.status === 'queued' ||
        props.generation.status === 'processing'
    );
});

const isSucceeded = computed(() => props.generation.status === 'succeeded');
const isFailed = computed(() => props.generation.status === 'failed');

const formattedCreatedAt = computed(() => {
    if (!props.generation.created_at) return null;

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.generation.created_at));
});

const { start, stop } = usePoll(
    3000,
    {
        only: ['generation'],
    },
    {
        autoStart: false,
        mode: 'rest',
        background: 'pause',
    },
);

watch(
    isProcessing,
    (active, wasActive) => {
        if (active) {
            start();
        } else {
            stop();

            // Refresh sidebar history once upon completion without duplicating merged items
            if (wasActive) {
                router.reload({
                    only: ['generationHistory'],
                    reset: ['generationHistory'],
                });
            }
        }
    },
    { immediate: true },
);
</script>

<template>

    <Head :title="generation.title" />

    <div class="flex h-full flex-1 flex-col items-center gap-4 overflow-hidden rounded-xl p-4">
        <!-- TOP DOCKED REQUEST CARD (Matches Create.vue InputGroup style) -->
        <div class="w-full shrink-0 sm:w-md xl:w-4xl">
            <InputGroup>
                <!-- Prompt Display -->
                <div class="max-h-20 w-full overflow-y-auto px-3.5 py-2.5 text-sm leading-relaxed">
                    {{ generation.prompt }}
                </div>

                <!-- Metadata & New Request Action Bar -->
                <InputGroupAddon align="block-end" class="flex flex-col">
                    <div class="flex w-full flex-wrap items-center justify-between gap-3">
                        <!-- Left: Generation Parameters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="capitalize">
                                {{ generation.quality }}
                            </Badge>
                            <Badge variant="outline">
                                {{ generation.size }}
                            </Badge>
                            <Badge v-if="generation.reference_images.length > 0" variant="outline" class="gap-1">
                                <ImageIcon class="size-3" />
                                {{ generation.reference_images.length }} ref
                            </Badge>
                            <InputGroupText v-if="formattedCreatedAt" class="text-xs">
                                {{ formattedCreatedAt }}
                            </InputGroupText>
                        </div>

                        <!-- Right: Cost + Actions -->
                        <div class="ml-auto flex items-center gap-2">
                            <InputGroupText v-if="generation.cost !== null">
                                Cost: {{ currencyFormat(generation.cost) }}
                            </InputGroupText>

                            <!-- Download Button when succeeded & not expired -->
                            <Tooltip v-if="
                                isSucceeded &&
                                generation.image_url &&
                                !generation.is_expired
                            ">
                                <TooltipTrigger as-child>
                                    <InputGroupButton as="a" :href="generation.image_url"
                                        :download="`${generation.id}.png`" variant="outline" size="icon-xs"
                                        class="rounded-full">
                                        <DownloadIcon class="size-3.5" />
                                        <span class="sr-only">
                                            Download image
                                        </span>
                                    </InputGroupButton>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Download image</p>
                                </TooltipContent>
                            </Tooltip>

                            <!-- New Request Button -->
                            <Button as-child size="sm" class="gap-1.5 rounded-full">
                                <Link :href="create()">
                                    <PlusIcon class="size-3.5" />
                                    New Request
                                </Link>
                            </Button>
                        </div>
                    </div>
                </InputGroupAddon>
            </InputGroup>
        </div>

        <!-- MAIN STAGE: Takes up remaining screen height -->
        <div class="flex min-h-0 w-full flex-1 items-center justify-center">
            <!-- Queued / Processing State -->
            <div v-if="isProcessing" class="relative flex h-full w-full max-w-4xl items-center justify-center">
                <Skeleton class="h-full w-full rounded-xl" />
                <div class="absolute flex flex-col items-center gap-2 text-muted-foreground">
                    <Loader2Icon class="size-8 animate-spin text-primary" />
                    <span class="text-sm font-medium">
                        {{
                            generation.status === 'queued'
                                ? 'Queued for generation...'
                                : 'Generating your image...'
                        }}
                    </span>
                </div>
            </div>

            <!-- Failed State -->
            <div v-else-if="isFailed"
                class="flex max-w-md flex-col items-center gap-2 rounded-xl border bg-card p-6 text-center">
                <AlertCircleIcon class="size-8 text-destructive" />
                <h2 class="text-base font-semibold">Generation Failed</h2>
                <p class="text-sm text-muted-foreground">
                    We couldn't generate this image. Click "New Request" above
                    to try again.
                </p>
            </div>

            <!-- Expired Image URL State -->
            <div v-else-if="isSucceeded && generation.is_expired"
                class="flex max-w-md flex-col items-center gap-2 rounded-xl border bg-card p-6 text-center">
                <ClockIcon class="size-8 text-muted-foreground" />
                <h2 class="text-base font-semibold">Image Link Expired</h2>
                <p class="text-sm text-muted-foreground">
                    The temporary link for this generated image has expired.
                </p>
            </div>

            <!-- Completed Image -->
            <div v-else-if="isSucceeded && generation.image_url"
                class="flex h-full min-h-0 w-full items-center justify-center">
                <img :src="generation.image_url" :alt="generation.title"
                    class="max-h-full max-w-full rounded-xl border object-contain shadow-sm" />
            </div>
        </div>
    </div>
</template>
