<script setup>

</script>

<template>
    <div class="table-responsive">
        <table class="table table-striped table-borderless border text-center align-middle">
            <thead v-if="$slots.header">
                <slot name="header" />
            </thead>
            <tbody>
                <slot />
            </tbody>
            <tfoot v-if="$slots.footer">
                <slot name="footer" />
            </tfoot>
        </table>
    </div>
</template>

<style scoped>

</style>
