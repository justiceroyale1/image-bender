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
    CheckCircle2Icon,
    ClockIcon,
    DownloadIcon,
    ImageIcon,
    Loader2Icon,
    PlusIcon,
    SparklesIcon,
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
    estimated_cost: number | null;
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
            },
        ],
    },
    inheritAttrs: false,
});

const props = defineProps<{
    generation: Generation;
}>();

const { currencyFormat } = useFormat();

const isProcessing = computed(() =>
    ['queued', 'processing'].includes(props.generation.status)
);

const isSucceeded = computed(
    () => props.generation.status === 'succeeded'
);

const isFailed = computed(
    () => props.generation.status === 'failed'
);

// The active step is determined by the actual backend status.
// No artificial percentage is displayed.
const currentStep = computed(() => {
    switch (props.generation.status) {
        case 'queued':
            return 0;
        case 'processing':
            return 1;
        case 'succeeded':
            return 2;
        default:
            return -1;
    }
});

const progressSteps = [
    {
        title: 'Request received',
        description: 'Your generation request has been accepted.',
    },
    {
        title: 'Creating your image',
        description: 'AI is working on your image.',
    },
    {
        title: 'Generation complete',
        description: 'Your image is ready to view and download.',
    },
];

const statusMessage = computed(() => {
    if (props.generation.status === 'queued') {
        return {
            title: "You're in the queue",
            description:
                'Your request has been received. We will start generating your image as soon as a processing slot becomes available.',
        };
    }

    if (props.generation.status === 'processing') {
        return {
            title: 'Creating your image',
            description:
                'Your request is being processed. This usually takes a little while. You can stay on this page while we work.',
        };
    }

    return null;
});

const formattedCreatedAt = computed(() => {
    if (!props.generation.created_at) return null;

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.generation.created_at));
});

// Poll while the generation is queued or processing.
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
            return;
        }

        stop();

        // Refresh generation history once the generation finishes.
        if (wasActive) {
            router.reload({
                only: ['generationHistory'],
                reset: ['generationHistory'],
            });
        }
    },
    { immediate: true },
);
</script>

<template>

    <Head :title="generation.title" />

    <div class="flex h-full flex-1 flex-col items-center gap-4 overflow-hidden rounded-xl p-4">
        <!-- Generation request information -->
        <div class="w-full shrink-0 sm:w-md xl:w-4xl">
            <InputGroup>
                <div class="max-h-20 w-full overflow-y-auto px-3.5 py-2.5 text-sm leading-relaxed">
                    {{ generation.prompt }}
                </div>

                <InputGroupAddon align="block-end" class="flex flex-col">
                    <div class="flex w-full flex-wrap items-center justify-between gap-3">
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

                            <Badge v-if="isProcessing" variant="outline" class="gap-1.5">
                                <Loader2Icon class="size-3 animate-spin" />
                                {{ generation.status === 'queued'
                                    ? 'Queued'
                                    : 'Generating' }}
                            </Badge>

                            <Badge v-else-if="isSucceeded" variant="secondary"
                                class="gap-1.5 text-green-700 dark:text-green-400">
                                <CheckCircle2Icon class="size-3" />
                                Completed
                            </Badge>

                            <Badge v-else-if="isFailed" variant="destructive">
                                Failed
                            </Badge>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <!-- Cost breakdown: Estimated & Actual -->
                            <div v-if="generation.estimated_cost !== null || generation.cost !== null"
                                class="flex items-center gap-2 text-xs">
                                <InputGroupText v-if="generation.estimated_cost !== null" class="text-muted-foreground">
                                    Est: {{ currencyFormat(generation.estimated_cost) }}
                                </InputGroupText>

                                <span v-if="generation.estimated_cost !== null && generation.cost !== null"
                                    class="text-muted-foreground/50">
                                    •
                                </span>

                                <InputGroupText v-if="generation.cost !== null" class="font-medium text-foreground">
                                    Actual: {{ currencyFormat(generation.cost) }}
                                </InputGroupText>
                            </div>

                            <!-- Download completed image -->
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

                            <!-- New request -->
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

        <!-- Main generation stage -->
        <div class="flex min-h-0 w-full flex-1 items-center justify-center" aria-live="polite">
            <Transition name="stage" mode="out-in">
                <!-- QUEUED / PROCESSING -->
                <div v-if="isProcessing" key="processing"
                    class="flex h-full w-full max-w-4xl flex-col items-center justify-center gap-8">
                    <!-- Progress card -->
                    <div class="w-full max-w-xl rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
                        <!-- Header -->
                        <div class="mb-8 flex flex-col items-center text-center">
                            <div
                                class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <SparklesIcon class="size-7" />
                            </div>

                            <h2 class="text-lg font-semibold tracking-tight">
                                {{ statusMessage?.title }}
                            </h2>

                            <p class="mt-2 max-w-sm text-sm leading-relaxed text-muted-foreground">
                                {{ statusMessage?.description }}
                            </p>
                        </div>

                        <!-- Progress steps -->
                        <div class="space-y-6">
                            <div v-for="(step, index) in progressSteps" :key="step.title" class="relative flex gap-4">
                                <!-- Connecting line -->
                                <div v-if="index < progressSteps.length - 1"
                                    class="absolute left-[15px] top-9 h-[calc(100%+12px)] w-px" :class="index < currentStep
                                        ? 'bg-primary'
                                        : 'bg-border'
                                        " />

                                <!-- Step icon -->
                                <div class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border transition-colors duration-300"
                                    :class="{
                                        'border-primary bg-primary text-primary-foreground':
                                            index < currentStep,
                                        'border-primary bg-primary/10 text-primary':
                                            index === currentStep,
                                        'border-border bg-muted text-muted-foreground':
                                            index > currentStep,
                                    }">
                                    <CheckCircle2Icon v-if="index < currentStep" class="size-4" />

                                    <Loader2Icon v-else-if="index === currentStep" class="size-4 animate-spin" />

                                    <span v-else class="text-xs font-medium">
                                        {{ index + 1 }}
                                    </span>
                                </div>

                                <!-- Step description -->
                                <div class="min-w-0 flex-1 pb-1">
                                    <p class="text-sm font-medium transition-colors" :class="index <= currentStep
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                        ">
                                        {{ step.title }}
                                    </p>

                                    <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                        {{ step.description }}
                                    </p>

                                    <p v-if="index === currentStep" class="mt-2 text-xs font-medium text-primary">
                                        {{ generation.status === 'queued'
                                            ? 'Waiting for processing...'
                                            : 'In progress...' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Processing animation -->
                        <div class="mt-8 overflow-hidden rounded-full bg-muted">
                            <div class="h-1.5 rounded-full bg-primary transition-all duration-700" :class="generation.status === 'queued'
                                ? 'w-1/3'
                                : 'w-2/3'
                                " />
                        </div>

                        <p class="mt-4 text-center text-xs text-muted-foreground">
                            This page updates automatically. You don't need to
                            refresh it.
                        </p>
                    </div>

                    <!-- Image preview placeholder -->
                    <div
                        class="relative hidden aspect-video w-full max-w-2xl overflow-hidden rounded-xl border bg-muted/30 sm:block">
                        <Skeleton class="absolute inset-0 h-full w-full" />

                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-3">
                            <div
                                class="flex size-12 items-center justify-center rounded-xl border bg-background/80 shadow-sm">
                                <ImageIcon class="size-6 text-muted-foreground" />
                            </div>

                            <p class="text-xs text-muted-foreground">
                                Your image will appear here
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FAILED -->
                <div v-else-if="isFailed" key="failed"
                    class="flex max-w-md flex-col items-center gap-3 rounded-xl border bg-card p-8 text-center shadow-sm">
                    <div class="flex size-14 items-center justify-center rounded-full bg-destructive/10">
                        <AlertCircleIcon class="size-7 text-destructive" />
                    </div>

                    <h2 class="text-base font-semibold">
                        Generation Failed
                    </h2>

                    <p class="text-sm leading-relaxed text-muted-foreground">
                        We couldn't generate this image. Your generation
                        request was unsuccessful. Click "New Request" above
                        to try again.
                    </p>
                </div>

                <!-- EXPIRED IMAGE -->
                <div v-else-if="isSucceeded && generation.is_expired" key="expired"
                    class="flex max-w-md flex-col items-center gap-3 rounded-xl border bg-card p-8 text-center">
                    <div class="flex size-14 items-center justify-center rounded-full bg-muted">
                        <ClockIcon class="size-7 text-muted-foreground" />
                    </div>

                    <h2 class="text-base font-semibold">
                        Image Link Expired
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        The temporary link for this generated image has
                        expired.
                    </p>
                </div>

                <!-- SUCCEEDED: EXISTING IMAGE VIEWER -->
                <div v-else-if="isSucceeded && generation.image_url" key="succeeded"
                    class="flex h-full min-h-0 w-full flex-col items-center justify-center gap-4">
                    <!-- Completion indicator -->
                    <div
                        class="flex items-center gap-2 rounded-full border bg-card px-4 py-2 text-sm font-medium shadow-sm">
                        <CheckCircle2Icon class="size-4 text-green-600 dark:text-green-400" />

                        Your image is ready
                    </div>

                    <!-- Maintained image viewing interface -->
                    <div class="flex min-h-0 w-full flex-1 items-center justify-center">
                        <img :src="generation.image_url" :alt="generation.title"
                            class="max-h-full max-w-full rounded-xl border object-contain shadow-sm" />
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.stage-enter-active,
.stage-leave-active {
    transition:
        opacity 250ms ease,
        transform 250ms ease;
}

.stage-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.stage-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
