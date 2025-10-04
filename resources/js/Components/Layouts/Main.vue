<script setup>

</script>

<template>
    <div class="container-fluid">
        <div id="top-bar" class="row position-fixed w-100">
            <div class="col-12 d-flex justify-content-between justify-content-sm-start align-items-center">
                <span class="fs-4 me-sm-4 fw-bold ms-md-5 text-primary-emphasis">{{ $page.props.app_name }}</span>
            </div>
        </div>

        <div id="content">
            <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
