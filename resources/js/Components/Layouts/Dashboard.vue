<script setup>
import Main from '@/Components/Layouts/Main.vue';

defineProps({
    title: String,
    subtitle: {
        type: String,
        default: null,
    }
})
</script>

<template>
    <Main>
        <div class="py-4">
            <div class="mb-4 text-primary-emphasis">
                <h3 v-if="title">{{ title }}</h3>
                <h6 v-if="subtitle">{{ subtitle }}</h6>
            </div>

            <slot />
        </div>
    </Main>
</template>

<style scoped>

</style>
