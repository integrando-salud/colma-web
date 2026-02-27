<script setup>
import { ref } from "vue";

defineProps({
    id: String,
    name: String,
    error: String,
    readonly: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const model = defineModel();
const type = ref("password");

const toggle = () => {
    type.value = type.value === "password" ? "text" : "password";
};
</script>

<template>
    <div class="input-group" :class="{ 'has-validation': error }">
        <input :type :id :name class="form-control" :class="{ 'is-invalid': error }" v-model="model" :readonly :disabled />
        <button type="button" class="btn" @click="toggle">
            <i class="fa" :class="{
                'fa-eye': type === 'password',
                'fa-eye-slash': type === 'text'
            }"></i>
        </button>
        <div class="invalid-feedback" v-if="error">
            {{ error }}
        </div>
    </div>
</template>

<style scoped>

</style>
