<script setup lang="ts">
import { computed } from 'vue'
import { Deferred, Head, useForm } from '@inertiajs/vue3'
import { CalendarIcon, CircleX, FilterIcon, RotateCcw } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group'
import { Separator } from '@/components/ui/separator'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from '@/components/ui/table'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Skeleton } from '@/components/ui/skeleton';
import { useFormat } from '@/composables/useFormat'
import { index } from '@/routes/usage'

interface DailyUsage {
    date: string
    generations: number
    input_text_tokens: number
    input_image_tokens: number
    output_tokens: number
    reasoning_tokens: number
    billed_usd: string
}

interface UsagePayload {
    start_date: string
    end_date: string
    data: DailyUsage[]
    total: Omit<DailyUsage, 'date'>
}

const props = defineProps<{
    deferred?: {
        usage?: UsagePayload,
        error: string | null
    }
    filters: { start_date?: string; end_date?: string }
}>()

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usage',
                href: index(),
            },
        ],
    },
});

const { currencyFormat, numberFormat } = useFormat()

const usage = computed(() => props.deferred?.usage)

const form = useForm({
    start_date: props.filters.start_date ?? usage?.value?.start_date ?? '',
    end_date: props.filters.end_date ?? usage?.value?.end_date ?? '',
})

const totalTokens = computed(() => {
    if (!usage?.value) return 0

    return (
        usage?.value.total.input_text_tokens +
        usage?.value.total.input_image_tokens +
        usage?.value.total.output_tokens +
        usage?.value.total.reasoning_tokens
    )
})

const hasCustomFilters = computed(() => Boolean(props.filters.start_date || props.filters.end_date))

function submit() {
    form
        .transform((data) => ({
            start_date: data.start_date || undefined,
            end_date: data.end_date || undefined,
        }))
        .get('/usage', {
            preserveState: true,
            replace: true,
        })
}

function resetFilters() {
    form.clearErrors()

    form
        .transform(() => ({}))
        .get('/usage', {
            preserveState: true,
            replace: true,
            onSuccess: () => {
                form.start_date = usage?.value?.start_date ?? ''
                form.end_date = usage?.value?.end_date ?? ''
            },
        })
}

</script>

<template>

    <Head title="Usage" />

    <div class="flex h-full flex-1 flex-col items-center gap-4 overflow-x-auto rounded-xl p-4">
        <Deferred data="deferred">
            <template #fallback>
                <div class="w-full max-w-5xl flex flex-col gap-4">
                    <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
                        <div>
                            <Skeleton class="h-8 w-28" />
                            <Skeleton class="mt-2 h-4 w-48" />
                        </div>

                        <form class="flex flex-col gap-3 sm:flex-row sm:items-start" @submit.prevent="submit">
                            <!-- Start Date -->
                            <div class="flex flex-col gap-1">
                                <Skeleton class="h-8 w-36" />
                            </div>

                            <!-- End Date -->
                            <div class="flex flex-col gap-1">
                                <Skeleton class="h-8 w-36" />
                            </div>

                            <!-- Submit & Reset Buttons -->
                            <div class="flex items-center gap-2">
                                <Skeleton class="h-8 w-20" />
                            </div>
                        </form>
                    </div>
                    <Separator />
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Card v-for="i in 3" :key="i">
                            <CardHeader class="pb-2">
                                <Skeleton class="h-4 w-28" />
                                <Skeleton class="mt-2 h-8 w-36" />
                                <Skeleton class="mt-1 h-3 w-24" />
                            </CardHeader>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader class="pb-2">
                            <Skeleton class="h-4 w-28" />
                            <Skeleton class="mt-2 h-8 w-36" />
                            <Skeleton class="mt-1 h-3 w-24" />
                        </CardHeader>
                        <CardContent>
                            <div class="relative max-h-[32rem] overflow-auto rounded-md border">
                                <Table>
                                    <TableHeader
                                        class="sticky top-0 z-10 bg-background shadow-[0_1px_0_0_hsl(var(--border))]">
                                        <TableRow class="hover:bg-transparent">
                                            <TableHead>
                                                <Skeleton class="h-8 w-28" />
                                            </TableHead>
                                            <TableHead class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableHead>
                                            <TableHead class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableHead>
                                            <TableHead class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableHead>
                                            <TableHead class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableHead>
                                            <TableHead class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="i in 6" :key="i">
                                            <TableCell class="font-medium">
                                                <Skeleton class="h-8 w-28" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                    <TableFooter
                                        class="sticky bottom-0 z-10 bg-muted shadow-[0_-1px_0_0_hsl(var(--border))]">
                                        <TableRow class="font-semibold hover:bg-muted">
                                            <TableCell>
                                                <Skeleton class="h-8 w-28" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                <Skeleton class="h-8 w-36" />
                                            </TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <div v-if="deferred" class="w-full max-w-5xl flex flex-col gap-4">
                <!-- Header & Filter Bar -->
                <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
                    <div>
                        <div class="text-2xl font-semibold">Usage</div>
                        <p v-if="usage" class="text-sm text-muted-foreground">
                            UTC period: {{ usage.start_date }} to {{ usage.end_date }}
                        </p>
                    </div>

                    <form class="flex flex-col gap-3 sm:flex-row sm:items-start" @submit.prevent="submit">
                        <!-- Start Date -->
                        <div class="flex flex-col gap-1">
                            <InputGroup class="w-full sm:w-auto"
                                :class="{ 'border-destructive': form.errors.start_date }">
                                <InputGroupAddon align="inline-start">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    <InputGroupText>From</InputGroupText>
                                </InputGroupAddon>
                                <InputGroupInput v-model="form.start_date" type="date"
                                    :aria-invalid="Boolean(form.errors.start_date)"
                                    @change="form.clearErrors('start_date')" />
                                <InputGroupAddon v-if="form.start_date" align="inline-end">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <InputGroupButton type="button" size="icon-xs"
                                                @click="form.start_date = ''; form.clearErrors('start_date')">
                                                <CircleX />
                                            </InputGroupButton>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Clear start date</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </InputGroupAddon>
                            </InputGroup>

                            <p v-if="form.errors.start_date" class="text-xs font-medium text-destructive">
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <!-- End Date -->
                        <div class="flex flex-col gap-1">
                            <InputGroup class="w-full sm:w-auto"
                                :class="{ 'border-destructive': form.errors.end_date }">
                                <InputGroupAddon align="inline-start">
                                    <CalendarIcon class="size-4 text-muted-foreground" />
                                    <InputGroupText>To</InputGroupText>
                                </InputGroupAddon>
                                <InputGroupInput v-model="form.end_date" type="date"
                                    :aria-invalid="Boolean(form.errors.end_date)"
                                    @change="form.clearErrors('end_date')" />
                                <InputGroupAddon v-if="form.end_date" align="inline-end">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <InputGroupButton type="button" size="icon-xs"
                                                @click="form.end_date = ''; form.clearErrors('end_date')">
                                                <CircleX />
                                            </InputGroupButton>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Clear end date</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </InputGroupAddon>
                            </InputGroup>

                            <p v-if="form.errors.end_date" class="text-xs font-medium text-destructive">
                                {{ form.errors.end_date }}
                            </p>
                        </div>

                        <!-- Submit & Reset Buttons -->
                        <div class="flex items-center gap-2">
                            <Button type="submit" :disabled="form.processing" class="gap-1.5">
                                <FilterIcon class="size-4" />
                                {{ form.processing ? 'Loading...' : 'Filter' }}
                            </Button>

                            <Tooltip v-if="hasCustomFilters">
                                <TooltipTrigger as-child>
                                    <Button type="button" variant="outline" size="icon" :disabled="form.processing"
                                        @click="resetFilters">
                                        <RotateCcw class="size-4" />
                                        <span class="sr-only">Reset filters</span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Reset to last 30 days</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </form>

                </div>

                <!-- API / Provider Error -->
                <Alert v-if="deferred?.error" variant="destructive">
                    <AlertTitle>Unable to load usage</AlertTitle>
                    <AlertDescription>{{ deferred?.error }}</AlertDescription>
                </Alert>

                <template v-if="usage">
                    <Separator />

                    <!-- Summary Cards -->
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Total Generations</CardDescription>
                                <CardTitle class="text-2xl font-semibold">
                                    {{ numberFormat(usage.total.generations) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Total Tokens</CardDescription>
                                <CardTitle class="text-2xl font-semibold">
                                    {{ numberFormat(totalTokens) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>

                        <Card>
                            <CardHeader class="pb-2">
                                <CardDescription>Total Cost</CardDescription>
                                <CardTitle class="text-2xl font-semibold">
                                    {{ currencyFormat(usage.total.billed_usd) }}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <!-- Daily Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Breakdown</CardTitle>
                            <CardDescription>
                                Billed on the UTC date each generation completed.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="relative max-h-[32rem] overflow-auto rounded-md border">
                                <Table>
                                    <TableHeader
                                        class="sticky top-0 z-10 bg-background shadow-[0_1px_0_0_hsl(var(--border))]">
                                        <TableRow class="hover:bg-transparent">
                                            <TableHead>Date (UTC)</TableHead>
                                            <TableHead class="text-right">Generations</TableHead>
                                            <TableHead class="text-right">Input Tokens</TableHead>
                                            <TableHead class="text-right">Output Tokens</TableHead>
                                            <TableHead class="text-right">Reasoning Tokens</TableHead>
                                            <TableHead class="text-right">Cost</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="day in usage.data" :key="day.date">
                                            <TableCell class="font-medium">{{ day.date }}</TableCell>
                                            <TableCell class="text-right">{{ numberFormat(day.generations) }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ numberFormat(day.input_text_tokens + day.input_image_tokens) }}
                                            </TableCell>
                                            <TableCell class="text-right">{{ numberFormat(day.output_tokens) }}
                                            </TableCell>
                                            <TableCell class="text-right">{{ numberFormat(day.reasoning_tokens) }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                {{ currencyFormat(day.billed_usd) }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                    <TableFooter
                                        class="sticky bottom-0 z-10 bg-muted shadow-[0_-1px_0_0_hsl(var(--border))]">
                                        <TableRow class="font-semibold hover:bg-muted">
                                            <TableCell>Total</TableCell>
                                            <TableCell class="text-right">
                                                {{ numberFormat(usage.total.generations) }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ numberFormat(usage.total.input_text_tokens +
                                                    usage.total.input_image_tokens)
                                                }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ numberFormat(usage.total.output_tokens) }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                {{ numberFormat(usage.total.reasoning_tokens) }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono">
                                                {{ currencyFormat(usage.total.billed_usd) }}
                                            </TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </template>
            </div>
        </Deferred>

    </div>

</template>
