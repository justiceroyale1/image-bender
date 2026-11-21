<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 42" :class="className" v-bind="$attrs">
        <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"
            d="M6 7C14 7 19 10 26 7C28.5 6 31 7.8 31 10.5C31 17 35 21 35 28V35C35 37.8 32.8 40 30 40H7C4.2 40 2 37.8 2 35V11C2 8.8 3.8 7 6 7ZM6.5 9.8C14 9.8 19 12.8 26 9.8C27.3 9.3 28.2 10.1 28.2 11.2C28.2 18 32.2 21.8 32.2 28V34.5C32.2 36 31 37.2 29.5 37.2H7.5C6 37.2 4.8 36 4.8 34.5V11.5C4.8 10.5 5.5 9.8 6.5 9.8ZM11.5 14.2A2.3 2.3 0 1 0 11.5 18.8A2.3 2.3 0 1 0 11.5 14.2ZM6.5 29.5C12 23.5 17 31.5 23 25.5C26 22.5 28.5 24 30.5 26.5V30.2C28.5 27.7 26 26.2 23 29.2C17 35.2 12 27.2 6.5 33.2ZM34 1.2L35.2 4.8L38.8 6L35.2 7.2L34 10.8L32.8 7.2L29.2 6L32.8 4.8Z" />
    </svg>
</template>
