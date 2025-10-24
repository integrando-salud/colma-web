<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    prevUrl: {
        type: String,
        default: '',
    },
    nextUrl: {
        type: String,
        default: '',
    }
});
</script>

<template>
    <div class="d-flex justify-content-center pt-3 pt-md-0">
        <Link :href="prevUrl ?? ''" class="btn btn-primary text-white me-2" :class="{ disabled: !prevUrl }">
            <i class="fa-solid fa-chevron-left"></i> Anterior
        </Link>
        <Link :href="nextUrl ?? ''" class="btn btn-primary text-white" :class="{ disabled: !nextUrl }">
            <i class="fa-solid fa-chevron-right"></i> Siguiente
        </Link>
    </div>
</template>

<style scoped>

</style>
