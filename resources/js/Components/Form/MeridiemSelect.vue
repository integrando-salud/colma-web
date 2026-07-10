<script setup>
const model = defineModel({
    type: String,
    default: 'AM',
});
</script>

<template>
    <select
        class="form-select meridiem-select"
        v-model="model"
    >
        <option value="AM">AM</option>
        <option value="PM">PM</option>
    </select>
</template>

<style scoped>
.meridiem-select {
    max-width: 100px;
}
</style>
