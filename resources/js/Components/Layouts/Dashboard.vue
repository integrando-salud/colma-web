<script setup>
import Main from '@/Components/Layouts/Main.vue';

defineProps({
    title: String,
})
</script>

<template>
    <Main>
        <div style="padding-top: 55px;">
            <div class="pt-4 pb-2">
                <h3 class="mb-4">{{ title }}</h3>
            </div>

            <slot />
        </div>
    </Main>
</template>

<style scoped>

</style>
