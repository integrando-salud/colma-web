<script setup>
import Main from '@/Components/Layouts/Main.vue';

defineProps({
    title: String,
})
</script>

<template>
    <Main>
        <div class="py-4">
            <h3 class="mb-4" v-if="title">{{ title }}</h3>

            <slot />
        </div>
    </Main>
</template>

<style scoped>

</style>
