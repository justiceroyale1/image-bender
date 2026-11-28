<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { index } from '@/routes/credits';
import { useFormat } from '@/composables/useFormat';
import { CreditCard, DollarSign, CircleX, Receipt } from '@lucide/vue';
import { computed } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';

interface CreditPurchaseItem {
    id: number;
    reference_id: string;
    amount: number;
    created_at: string;
}

interface PaginatedPurchases {
    data: CreditPurchaseItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Credits',
                href: index(),
            },
        ],
    },
    inheritAttrs: false,
});

const props = defineProps<{
    availableCredits: number;
    purchases: PaginatedPurchases;
}>();

const { currencyFormat } = useFormat();

const form = useForm<{
    amount: number | string;
    reference_id: string;
    general?: string;
}>({
    amount: '',
    reference_id: crypto.randomUUID(),
});

const min = computed(() => 5);
const max = computed(() => 1_000_000 - props.availableCredits);

const amountError = computed(() => {
    return form.errors.amount || form.errors.reference_id || form.errors.general || null;
});

const disableSubmitButton = computed(() => {
    return (
        form.processing ||
        form.amount === '' ||
        Number(form.amount) < min.value ||
        Number(form.amount) > max.value
    );
});

function formatDate(isoDate: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoDate));
}

function goToPage(page: number) {
    router.get(
        index().url,
        { page },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['purchases'],
        },
    );
}

function submit() {
    form.clearErrors();

    form.post('/credits', {
        onSuccess: () => {
            form.resetAndClearErrors();
            form.reference_id = crypto.randomUUID();

            router.reload({
                only: ['availableCredits', 'purchases'],
            });
        },
    });
}
</script>

<template>

    <Head title="Credits" />
    <div class="flex h-full flex-1 flex-col items-center gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex w-full max-w-5xl flex-col gap-6">
            <!-- Balance & Purchase Form -->
            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
                <div>
                    <div class="text-2xl font-semibold">
                        {{ currencyFormat(availableCredits) }}
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Available credits
                    </p>
                </div>

                <form class="flex flex-col gap-3 sm:flex-row sm:items-start" @submit.prevent="submit">
                    <div class="flex flex-col gap-1">
                        <InputGroup class="w-full sm:w-auto" :class="{ 'border-destructive': Boolean(amountError) }">
                            <InputGroupAddon align="inline-start">
                                <DollarSign class="size-4 text-muted-foreground" />
                                <InputGroupText>Amount</InputGroupText>
                            </InputGroupAddon>
                            <InputGroupInput v-model="form.amount" type="number" :aria-invalid="Boolean(amountError)"
                                :min="min" :max="max" @change="form.clearErrors()" />
                            <InputGroupAddon v-if="form.amount" align="inline-end">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <InputGroupButton type="button" size="icon-xs"
                                            @click="form.amount = ''; form.clearErrors()">
                                            <CircleX />
                                        </InputGroupButton>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Clear</p>
                                    </TooltipContent>
                                </Tooltip>
                            </InputGroupAddon>
                        </InputGroup>

                        <p v-if="amountError" class="text-xs font-medium text-destructive">
                            {{ amountError }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button type="submit" :disabled="disableSubmitButton" class="gap-1.5">
                            <CreditCard class="size-4" />
                            {{ form.processing ? 'Loading...' : 'Buy' }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Credit Purchase History Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Purchase History</CardTitle>
                    <CardDescription>
                        A record of all your credit top-ups.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <div v-if="purchases.data.length === 0"
                        class="flex flex-col items-center justify-center gap-2 py-10 text-center">
                        <Receipt class="size-8 text-muted-foreground/60" />
                        <p class="text-sm font-medium">No purchases yet</p>
                        <p class="text-xs text-muted-foreground">
                            Buy credits above to get started.
                        </p>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Reference ID</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="purchase in purchases.data" :key="purchase.id">
                                <TableCell class="whitespace-nowrap text-muted-foreground">
                                    {{ formatDate(purchase.created_at) }}
                                </TableCell>
                                <TableCell class="font-mono text-xs text-muted-foreground">
                                    {{ purchase.reference_id }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-right font-medium">
                                    {{ currencyFormat(purchase.amount) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>

                <CardFooter v-if="purchases.meta.last_page > 1"
                    class="flex flex-col items-center justify-between gap-4 border-t pt-4 sm:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Showing <span class="font-medium text-foreground">{{ purchases.meta.from }}</span> to
                        <span class="font-medium text-foreground">{{ purchases.meta.to }}</span> of
                        <span class="font-medium text-foreground">{{ purchases.meta.total }}</span> results
                    </p>

                    <Pagination v-slot="{ page }" :items-per-page="purchases.meta.per_page"
                        :total="purchases.meta.total" :default-page="purchases.meta.current_page" :sibling-count="1"
                        show-edges class="mx-0 w-auto" @update:page="goToPage">
                        <PaginationContent v-slot="{ items }">
                            <PaginationPrevious />

                            <template v-for="(item, idx) in items" :key="idx">
                                <PaginationItem v-if="item.type === 'page'" :value="item.value"
                                    :is-active="item.value === page">
                                    {{ item.value }}
                                </PaginationItem>
                                <PaginationEllipsis v-else :index="idx" />
                            </template>

                            <PaginationNext />
                        </PaginationContent>
                    </Pagination>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
