<script setup>
import Menu from '@/Components/Layouts/Menu.vue';
import MenuItem from "@/Components/Layouts/MenuItem.vue";
</script>

<template>
    <Menu>
        <li class="list-inline-item me-4">
            <MenuItem link="#" title="Pacientes" />
        </li>
        <li class="list-inline-item">
            <MenuItem :link="route('dashboard.usuarios.index')" title="Administración de usuarios" />
        </li>
    </Menu>
</template>

<style scoped>

</style>
