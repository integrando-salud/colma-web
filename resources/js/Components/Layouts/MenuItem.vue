<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    link: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    }
});
</script>

<template>
    <Link :href="link" class="nav-link fs-4 fw-semibold text-primary-emphasis">
        {{ title }}
    </Link>
</template>

<style scoped>

</style>
