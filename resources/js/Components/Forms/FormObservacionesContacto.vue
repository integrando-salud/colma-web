<script setup>
import { toRef } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
<div class="col-12">
    <div class="row">
        <label for="contacto_observaciones" class="col-12 col-sm-4 col-lg-3 col-form-label">
            Observaciones
        </label>
        <div class="col-12 col-sm-8 col-lg-9">
            <textarea
                id="contacto_observaciones"
                name="contacto_observaciones"
                class="form-control"
                :class="{ 'is-invalid': formContacto.errors?.observaciones }"
                v-model="formContacto.observaciones"
                rows="4"
            ></textarea>
            <div class="invalid-feedback" v-if="formContacto.errors?.observaciones">
                {{ formContacto.errors.observaciones }}
            </div>
        </div>
    </div>
</div>
</template>
