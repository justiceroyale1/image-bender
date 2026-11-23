<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { create } from '@/routes/generations';
import { Button } from '@/components/ui/button';
import { CircleX, Trash, ArrowUpIcon, PlusIcon } from '@lucide/vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput, InputGroupText, InputGroupTextarea } from '@/components/ui/input-group'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { computed, onMounted, ref, watch } from 'vue';
import { useCurrency } from '@/composables/useCurrency';

const { currencyFormat } = useCurrency();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'New Generation',
                href: create(),
            },
        ],
    },
});

const pricing = {
    "object": "pricing",
    "currency": "usd",
    "unit": "per_1m_tokens",
    "rates": {
        "input_text_tokens": "5.00",
        "input_image_tokens": "10.00",
        "output_tokens": "40.00",
        "reasoning_tokens": "12.50"
    },
    "tokens": {
        "characters_per_text_token": 4,
        "text_token_variance": 0.15,
        "per_reference_image": 765,
        "output": {
            "low": {
                "1024x1024": 280,
                "1536x1024": 420,
                "1024x1536": 420
            },
            "medium": {
                "1024x1024": 1100,
                "1536x1024": 1650,
                "1024x1536": 1650
            },
            "high": {
                "1024x1024": 4300,
                "1536x1024": 6450,
                "1024x1536": 6450
            }
        },
        "reasoning": {
            "low": { "min": 0, "max": 500 },
            "medium": { "min": 200, "max": 2000 },
            "high": { "min": 1000, "max": 8000 }
        }
    }
}

type OutputMap = typeof pricing.tokens.output
type Quality = keyof OutputMap
type Size = keyof OutputMap[Quality]

type ReasoningMap = typeof pricing.tokens.reasoning
type ReasoningQuality = keyof ReasoningMap
type ReasoningLevel = keyof ReasoningMap[ReasoningQuality]

interface GenerationForm {
    prompt: string;
    size: Size;
    quality: Quality;
    referenceImages: { id: string, value: string }[];
}

const sizeOptions: { label: string, value: Size }[] = [
    {
        label: '1024x1024',
        value: '1024x1024'
    },
    {
        label: '1536x1024',
        value: '1536x1024'
    },
    {
        label: '1024x1536',
        value: '1024x1536'
    },
]

const qualityOptions: { label: string, value: Quality }[] = [
    {
        label: 'Low',
        value: 'low'
    },
    {
        label: 'Medium',
        value: 'medium'
    },
    {
        label: 'High',
        value: 'high'
    },
]

const estimatedCost = ref(0);

const costUnits = 1000000

const form = useForm<GenerationForm>({
    prompt: '', //max 4000 chars
    size: '1024x1024',
    quality: 'medium',
    referenceImages: []
})

const disableSubmitButton = computed(() => form.prompt.trim() === '')

function getOutputTokens(quality: Quality, size: Size): number {
    return pricing.tokens.output[quality][size];
}

function getReasoningTokens(quality: ReasoningQuality, size: ReasoningLevel): number {
    return pricing.tokens.reasoning[quality][size]
}

const estimateCost = () => {
    const trimmedPrompt = form.prompt.trim()
    if (trimmedPrompt === '') {
        estimatedCost.value = 0

        return
    }

    // Estimate text costs
    let textCount = Math.ceil(trimmedPrompt.length / pricing.tokens.characters_per_text_token) * (1 + pricing.tokens.text_token_variance)
    let textCost = (textCount * Number(pricing.rates.input_text_tokens));

    // Estimate output cost
    let outputCount = getOutputTokens(form.quality, form.size);
    let outputCost = (outputCount * Number(pricing.rates.output_tokens));

    // Estimate reference images cost
    let referenceImagesCount = form.referenceImages.filter((item) => item.value.trim() !== '').length * pricing.tokens.per_reference_image
    let referenceImagesCost = (referenceImagesCount * Number(pricing.rates.input_image_tokens));

    // Calculate max reasoning price
    let reasoningCount = getReasoningTokens('high', 'max');
    let reasoningCost = (reasoningCount * Number(pricing.rates.reasoning_tokens));

    estimatedCost.value = (textCost + outputCost + referenceImagesCost + reasoningCost) / costUnits;
}

const addReferenceImage = () => {
    form.referenceImages.push({
        id: crypto.randomUUID(),
        value: ''
    })
}

const clearReferencImage = (index: number) => {
    form.referenceImages[index].value = ''

    estimateCost()
}

const removeReferenceImage = (index: number) => {
    form.referenceImages.splice(index, 1);

    estimateCost()
}

const submit = () => {
    if (form.prompt === '') {
        return;
    }

    form.transform((data) => ({
        ...data,
        reference_images: data.referenceImages
            .map((item) => item.value.trim())
            .filter((url) => url !== ''),
        idempotency_key: crypto.randomUUID(),
        referenceImages: undefined,
    })).post('/generations');
};


watch(() => form.size, (value) => estimateCost())
watch(() => form.quality, (value) => estimateCost())

onMounted(() => {
    estimateCost()
})


</script>

<template>

    <Head title="New Generation" />

    <div class="flex h-full items-center justify-center flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

        <div class="text-2xl font-semibold">Wanna create an image?</div>
        <div class="w-full sm:w-md xl:w-2xl">
            <InputGroup>
                <InputGroupTextarea v-model="form.prompt" placeholder="Describe the image..." @input="estimateCost"
                    maxlength="4000" />
                <InputGroupAddon align="block-end" class="flex flex-col">

                    <div class="w-full flex gap-2 items-center">
                        <Tooltip>
                            <TooltipTrigger>
                                <InputGroupButton variant="outline" class="rounded-full" size="icon-xs"
                                    @click="addReferenceImage">
                                    <PlusIcon class="size-4" />
                                </InputGroupButton>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Add reference image</p>
                            </TooltipContent>
                        </Tooltip>
                        <Select v-model="form.quality">
                            <SelectTrigger>
                                <SelectValue placeholder="Quality" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="qualityOption in qualityOptions" :key="qualityOption.label"
                                    :value="qualityOption.value">
                                    {{ qualityOption.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select v-model="form.size">
                            <SelectTrigger>
                                <SelectValue placeholder="Size" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="sizeOption in sizeOptions" :key="sizeOption.label"
                                    :value="sizeOption.value">
                                    {{ sizeOption.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputGroupText class="ml-auto">
                            Est. cost: {{ currencyFormat(estimatedCost) }}
                        </InputGroupText>
                        <Separator orientation="vertical" class="!h-4" />
                        <InputGroupButton variant="default" class="rounded-full bg-green-500" size="icon-xs"
                            :disabled="disableSubmitButton" @click="submit">
                            <ArrowUpIcon class="size-4" />
                            <span class="sr-only">Send</span>
                        </InputGroupButton>
                    </div>
                </InputGroupAddon>
            </InputGroup>
        </div>

        <div v-if="form.referenceImages.length > 0"
            class="w-full sm:w-md xl:w-2xl flex flex-col items-center justify-between">
            <Separator class="my-4" />

            <div class="w-full flex flex-col gap-4 items-center">
                <div v-for="(referenceImage, index) in form.referenceImages" :key="referenceImage.id"
                    class="w-full flex gap-2">
                    <InputGroup>
                        <InputGroupInput v-model="referenceImage.value" placeholder="Reference image URL"
                            @input="estimateCost" />
                        <InputGroupAddon v-if="referenceImage.value" align="inline-end">
                            <Tooltip>
                                <TooltipTrigger>
                                    <InputGroupButton size="icon-xs" @click="clearReferencImage(index)">
                                        <CircleX />
                                    </InputGroupButton>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Clear</p>
                                </TooltipContent>
                            </Tooltip>
                        </InputGroupAddon>
                    </InputGroup>
                    <Tooltip>
                        <TooltipTrigger>
                            <Button variant="destructive" size="icon-sm" @click="removeReferenceImage(index)">
                                <Trash />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Remove</p>
                        </TooltipContent>
                    </Tooltip>
                </div>
            </div>
        </div>
    </div>
</template>
