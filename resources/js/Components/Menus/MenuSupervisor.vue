<script setup>
import Menu from '@/Components/Layouts/Menu.vue';
import MenuItem from "@/Components/Layouts/MenuItem.vue";
</script>

<template>
    <Menu>
        <li class="nav-item me-md-3">
            <MenuItem :link="route('dashboard.pacientes.index')" title="Pacientes" />
        </li>
        <li class="nav-item">
            <MenuItem :link="route('dashboard.usuarios.index')" title="Administración de usuarios" />
        </li>
    </Menu>
</template>

<style scoped>

</style>
