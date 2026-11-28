<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { index } from '@/routes/credits';
import { useFormat } from '@/composables/useFormat';
import { CreditCard, DollarSign, CircleX } from '@lucide/vue';
import { computed } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Credits',
                href: index(),
            },
        ],
    },
    inheritAttrs: false
});

const props = defineProps<{
    'availableCredits': number
}>()

const { currencyFormat } = useFormat()

const form = useForm({
    'amount': '',
    'reference_id': crypto.randomUUID()
})

const min = computed(() => 5)
const max = computed(() => 1000000 - props.availableCredits)

const disableSubmitButton = computed(() => {
    return false;
    // return form.processing || form.amount === '' || Number(form.amount) < min.value || Number(form.amount) > max.value
})

function submit() {
    form.clearErrors()

    form.post('credits', {
        onSuccess: () => {
            form.resetAndClearErrors()
            router.reload({
                only: ['availableCredits']
            })
        }
    })
}
</script>
<template>

    <Head title="Credits"></Head>
    <div class="flex h-full flex-1 flex-col items-center gap-4 overflow-x-auto rounded-xl p-4">
        <div class="w-full max-w-5xl flex flex-col gap-4">
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
                    <!-- Start Date -->
                    <div class="flex flex-col gap-1">
                        <InputGroup class="w-full sm:w-auto" :class="{ 'border-destructive': form.errors.amount }">
                            <InputGroupAddon align="inline-start">
                                <DollarSign class="size-4 text-muted-foreground" />
                                <InputGroupText>Amount</InputGroupText>
                            </InputGroupAddon>
                            <InputGroupInput v-model="form.amount" type="number"
                                :aria-invalid="Boolean(form.errors.amount)" :min="min" :max="max"
                                @change="form.clearErrors('amount')" />
                            <InputGroupAddon v-if="form.amount" align="inline-end">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <InputGroupButton type="button" size="icon-xs"
                                            @click="form.amount = ''; form.clearErrors('amount')">
                                            <CircleX />
                                        </InputGroupButton>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Clear</p>
                                    </TooltipContent>
                                </Tooltip>
                            </InputGroupAddon>
                        </InputGroup>

                        <p v-if="form.errors.amount" class="text-xs font-medium text-destructive">
                            {{ form.errors.amount }}
                        </p>
                        <p v-else-if="form.errors.reference_id" class="text-xs font-medium text-destructive">
                            {{ form.errors.reference_id }}
                        </p>
                        <p v-else-if="$page.props.errors.general" class="text-xs font-medium text-destructive">
                            {{ $page.props.errors.general }}
                        </p>
                    </div>

                    <!-- Submit & Reset Buttons -->
                    <div class="flex items-center gap-2">
                        <Button type="submit" :disabled="disableSubmitButton" class="gap-1.5">
                            <CreditCard class="size-4" />
                            {{ form.processing ? 'Loading...' : 'Buy' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>