<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { useFormat } from '@/composables/useFormat';
import { dashboard } from '@/routes';
import usage from '@/routes/usage';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface DailyUsage {
    date: string;
    generations: number;
    input_text_tokens: number;
    input_image_tokens: number;
    output_tokens: number;
    reasoning_tokens: number;
    billed_usd: string;
}

interface UsageSummary {
    period?: {
        start_date: string;
        end_date: string;
    };
    total_generations?: number;
    total_tokens?: number;
    total_billed_usd?: string;
    today_generations?: number;
    today_billed_usd?: string;
    recent_days?: DailyUsage[];
    error: string | null;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    usageSummary?: UsageSummary;
}>();

const { currencyFormat, numberFormat } = useFormat();
</script>

<template>

    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <Deferred data="usageSummary">
            <!-- Loading Skeleton matching the 3-card + main-panel layout -->
            <template #fallback>
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card v-for="i in 3" :key="i">
                        <CardHeader class="pb-2">
                            <Skeleton class="h-4 w-28" />
                            <Skeleton class="mt-2 h-8 w-36" />
                            <Skeleton class="mt-1 h-3 w-24" />
                        </CardHeader>
                    </Card>
                </div>

                <Card class="flex-1">
                    <CardHeader>
                        <Skeleton class="h-5 w-40" />
                        <Skeleton class="h-4 w-64" />
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Skeleton v-for="i in 5" :key="i" class="h-10 w-full" />
                    </CardContent>
                </Card>
            </template>

            <!-- Error State -->
            <Alert v-if="usageSummary?.error" variant="destructive">
                <AlertTitle>Unable to load usage metrics</AlertTitle>
                <AlertDescription>{{ usageSummary.error }}</AlertDescription>
            </Alert>

            <template v-else-if="usageSummary">
                <!-- Top 3 Metric Cards (matching your 3-column grid) -->
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>30-Day Generations</CardDescription>
                            <CardTitle class="text-2xl font-semibold">
                                {{ numberFormat(usageSummary.total_generations ?? 0) }}
                            </CardTitle>
                            <p class="text-xs text-muted-foreground">
                                {{ numberFormat(usageSummary.today_generations ?? 0) }} today (UTC)
                            </p>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>30-Day Tokens</CardDescription>
                            <CardTitle class="text-2xl font-semibold">
                                {{ numberFormat(usageSummary.total_tokens ?? 0) }}
                            </CardTitle>
                            <p class="text-xs text-muted-foreground">
                                Input, output & reasoning combined
                            </p>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>30-Day Spend</CardDescription>
                            <CardTitle class="text-2xl font-semibold">
                                {{ currencyFormat(Number(usageSummary.total_billed_usd ?? 0)) }}
                            </CardTitle>
                            <p class="text-xs text-muted-foreground">
                                {{ currencyFormat(Number(usageSummary.today_billed_usd ?? 0)) }} today (UTC)
                            </p>
                        </CardHeader>
                    </Card>
                </div>

                <!-- Bottom Panel: Last 7 Days Breakdown + Link to Full Usage Page -->
                <Card class="flex-1">
                    <CardHeader class="flex flex-row items-center justify-between gap-4">
                        <div>
                            <CardTitle>Recent Daily Usage (Last 7 Days)</CardTitle>
                            <CardDescription v-if="usageSummary.period">
                                30-day UTC window: {{ usageSummary.period.start_date }} to {{
                                usageSummary.period.end_date }}
                            </CardDescription>
                        </div>

                        <Button variant="outline" size="sm" as-child>
                            <Link :href="usage.index()">
                                Full report
                                <ArrowUpRight class="ml-1 size-4" />
                            </Link>
                        </Button>
                    </CardHeader>

                    <CardContent>
                        <Table container-class="max-h-[24rem] rounded-md border">
                            <TableHeader class="sticky top-0 z-10 bg-background shadow-[0_1px_0_0_hsl(var(--border))]">
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
                                <TableRow v-for="day in usageSummary.recent_days" :key="day.date">
                                    <TableCell class="font-medium">{{ day.date }}</TableCell>
                                    <TableCell class="text-right">
                                        {{ numberFormat(day.generations) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ numberFormat(day.input_text_tokens + day.input_image_tokens) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ numberFormat(day.output_tokens) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ numberFormat(day.reasoning_tokens) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ currencyFormat(Number(day.billed_usd)) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>

                            <TableFooter class="sticky bottom-0 z-10 bg-muted shadow-[0_-1px_0_0_hsl(var(--border))]">
                                <TableRow class="font-semibold hover:bg-muted">
                                    <TableCell>30-Day Total</TableCell>
                                    <TableCell class="text-right">
                                        {{ numberFormat(usageSummary.total_generations ?? 0) }}
                                    </TableCell>
                                    <TableCell class="text-right" colspan="3">
                                        {{ numberFormat(usageSummary.total_tokens ?? 0) }} total tokens
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ currencyFormat(Number(usageSummary.total_billed_usd ?? 0)) }}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>
            </template>
        </Deferred>
    </div>
</template>
