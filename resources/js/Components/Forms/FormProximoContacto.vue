<script setup>
import { computed, toRef } from 'vue';
import {
    MODALIDAD,
    MOTIVO_REPROGRAMACION_MODALIDAD_TELEFONO,
    MOTIVO_REPROGRAMACION_MODALIDAD_PRESENCIAL
} from '@/Constants/contacto';
import { vMaska } from 'maska/vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    }
});

const formContacto = toRef(props, 'form');

const motivosReprogramacion = computed(() => {
    if (formContacto.value.modalidad === MODALIDAD.TELEFONO) {
        return MOTIVO_REPROGRAMACION_MODALIDAD_TELEFONO;
    } else if (formContacto.value.modalidad === MODALIDAD.PRESENCIAL) {
        return MOTIVO_REPROGRAMACION_MODALIDAD_PRESENCIAL;
    }

    return [];
});
</script>

<template>
    <div
        v-if="formContacto.contactado === 0"
        class="col-12 mb-2"
    >
        <div class="row">
            <label for="contacto_motivo_reprogramacion" class="col-12 col-sm-4 col-lg-3 col-form-label">
                Motivo de reprogramación
            </label>
            <div class="col-12 col-sm-8 col-xl-7">
                <select
                    name="contacto_motivo_reprogramacion"
                    id="contacto_motivo_reprogramacion"
                    class="form-select"
                    :class="{ 'is-invalid': formContacto.errors?.motivo_reprogramacion }"
                    v-model="formContacto.motivo_reprogramacion"
                >
                    <option value="">Seleccionar motivo</option>
                    <option
                        v-for="motivo in motivosReprogramacion"
                        :key="motivo"
                        :value="motivo"
                    >{{ motivo }}</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.motivo_reprogramacion">
                    {{ formContacto.errors.motivo_reprogramacion }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-2">
        <div class="row">
            <label for="contacto_nueva_fecha_contacto" class="col-12 col-sm-4 col-lg-3 col-form-label">
                Nueva fecha de contacto
            </label>
            <div class="col-7 col-sm-5 col-md-4">
                <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.nueva_fecha_contacto }">
                    <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.nueva_fecha_contacto }">
                        <i class="fa fa-calendar-alt"></i>
                    </span>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="contacto_nueva_fecha_contacto"
                        id="contacto_nueva_fecha_contacto"
                        class="form-control"
                        :class="{ 'is-invalid': formContacto.errors?.nueva_fecha_contacto }"
                        v-model="formContacto.nueva_fecha_contacto"
                        v-maska="'##/##/####'"
                        data-maska-eager
                        placeholder="DD/MM/AAAA"
                    />
                </div>
                <div class="invalid-feedback" v-if="formContacto.errors?.nueva_fecha_contacto">
                    {{ formContacto.errors.nueva_fecha_contacto }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
