<script setup>
import { toRef } from 'vue';
import { MOTIVO_CONTACTO, OPCION_NO, OPCION_SI } from '@/Constants/contacto';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.ALERTA_CLINICA">
        <div class="col-12 mb-2">
            <label for="contacto_alerta_clinica_pregunta_a" class="form-label me-2">
                ¿El/la participante vio la recomendación de buscar atención en el aplicativo?
            </label>
            <select
                name="contacto_alerta_clinica_pregunta_a"
                id="contacto_alerta_clinica_pregunta_a"
                v-model="formContacto.alerta_clinica_pregunta_a"
                class="form-select w-auto d-inline-block"
                :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_a }"
            >
                <option value=""></option>
                <option value="Si">Si</option>
                <option value="No">No</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_a">
                {{ formContacto.errors.alerta_clinica_pregunta_a }}
            </div>
        </div>

        <template v-if="formContacto.alerta_clinica_pregunta_a === OPCION_NO">
            <div class="alert alert-danger mt-2">
                Recomienda al participante que busque atención en su centro de salud.
            </div>

            <div class="col-12 mb-2">
                <label for="contacto_alerta_clinica_pregunta_b" class="form-label me-2">
                    ¿Recomendaste al participante que busque atención?
                </label>
                <select
                    name="contacto_alerta_clinica_pregunta_b"
                    id="contacto_alerta_clinica_pregunta_b"
                    v-model="formContacto.alerta_clinica_pregunta_b"
                    class="form-select w-auto d-inline-block"
                    :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_b }"
                >
                    <option value=""></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_b">
                    {{ formContacto.errors.alerta_clinica_pregunta_b }}
                </div>
            </div>

            <div
                v-if="formContacto.alerta_clinica_pregunta_b === OPCION_NO"
                class="col-12 mb-2 text-danger"
            >
                Indique las razones en el campo "Observaciones"
            </div>
        </template>

        <template v-if="formContacto.alerta_clinica_pregunta_a === OPCION_SI">
            <div class="col-12 mb-2">
                <label for="contacto_alerta_clinica_pregunta_c" class="form-label me-2">
                    ¿El/la participante siguió la recomendación indicada por el aplicativo?
                </label>
                <select
                    name="contacto_alerta_clinica_pregunta_c"
                    id="contacto_alerta_clinica_pregunta_c"
                    v-model="formContacto.alerta_clinica_pregunta_c"
                    class="form-select w-auto d-inline-block"
                    :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_c }"
                >
                    <option value=""></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_c">
                    {{ formContacto.errors.alerta_clinica_pregunta_c }}
                </div>
            </div>

            <template v-if="formContacto.alerta_clinica_pregunta_c === OPCION_SI">
                <div class="col-12 py-2">
                    <label for="contacto_alerta_clinica_pregunta_e" class="form-label me-2">
                        ¿El/la participante pudo acceder a la atención recomendada?
                    </label>
                    <select
                        name="contacto_alerta_clinica_pregunta_e"
                        id="contacto_alerta_clinica_pregunta_e"
                        v-model="formContacto.alerta_clinica_pregunta_e"
                        class="form-select w-auto d-inline-block"
                        :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_e }"
                    >
                        <option value=""></option>
                        <option value="Si">Si</option>
                        <option value="No">No</option>
                    </select>
                    <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_e">
                        {{ formContacto.errors.alerta_clinica_pregunta_e }}
                    </div>
                </div>
                <div
                    v-if="formContacto.alerta_clinica_pregunta_e === OPCION_NO"
                    class="col-12 mb-2"
                >
                    <label for="contacto_alerta_clinica_pregunta_f" class="form-label me-2">
                        ¿Qué dificultades encontró para acceder a la atención recomendada?
                    </label>
                    <input
                        type="text"
                        name="contacto_alerta_clinica_pregunta_f"
                        id="contacto_alerta_clinica_pregunta_f"
                        class="form-control"
                        v-model="formContacto.alerta_clinica_pregunta_f"
                        :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_f }"
                    >
                    <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_f">
                        {{ formContacto.errors.alerta_clinica_pregunta_f }}
                    </div>
                </div>
            </template>

            <div
                v-if="formContacto.alerta_clinica_pregunta_c === OPCION_NO"
                class="col-12 mb-2"
            >
                <label for="contacto_alerta_clinica_pregunta_d" class="form-label me-2">
                    ¿Por qué motivo(s) el participante no pudo seguir la recomendación del aplicativo?
                </label>
                <input
                    type="text"
                    name="contacto_alerta_clinica_pregunta_d"
                    id="contacto_alerta_clinica_pregunta_d"
                    class="form-control"
                    v-model="formContacto.alerta_clinica_pregunta_d"
                    :class="{ 'is-invalid': formContacto.errors?.alerta_clinica_pregunta_d }"
                >
                <div class="invalid-feedback" v-if="formContacto.errors?.alerta_clinica_pregunta_d">
                    {{ formContacto.errors.alerta_clinica_pregunta_d }}
                </div>
            </div>
        </template>
    </template>
</template>
