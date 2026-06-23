<script setup>
import { toRef } from 'vue';
import { DIFICULTADES_PROBLEMAS_CONEXION, MOTIVO_CONTACTO, OPCION_NO } from '@/Constants/contacto';
import { vMaska } from 'maska/vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.PROBLEMAS_CONEXION">
        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_problemas_conexion_pregunta_a" class="col-12 col-sm-4 col-xl-3 col-form-label">
                    ¿Qué dificultades tuvo el/la participante con las sesiones?
                    Selecciona todas las que aplique
                </label>
                <div class="col-12 col-sm-8 col-xl-9">
                    <div
                        v-for="dificultad in DIFICULTADES_PROBLEMAS_CONEXION"
                        :key="dificultad"
                        class="form-check mb-2"
                    >
                        <input
                            class="form-check-input border-primary"
                            type="checkbox"
                            :id="'contacto_problemas_conexion_pregunta_a' + dificultad"
                            :value="dificultad"
                            v-model="formContacto.problemas_conexion_pregunta_a"
                        />
                        <label
                            class="form-check-label"
                            :for="'contacto_problemas_conexion_pregunta_a' + dificultad"
                        >
                            {{ dificultad }}
                        </label>
                    </div>
                    <div class="invalid-feedback d-block" v-if="formContacto.errors?.problemas_conexion_pregunta_a">
                        {{ formContacto.errors.problemas_conexion_pregunta_a }}
                    </div>
                    <input
                        type="text"
                        id="contacto_problemas_conexion_otro"
                        name="contacto_problemas_conexion_otro"
                        class="form-control"
                        :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_otro }"
                        v-model="formContacto.problemas_conexion_otro"
                        :disabled="!formContacto.problemas_conexion_pregunta_a.includes(DIFICULTADES_PROBLEMAS_CONEXION.OTRO)"
                    />
                    <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_otro">
                        {{ formContacto.errors.problemas_conexion_otro }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <label for="contacto_problemas_conexion_pregunta_b" class="form-label me-2">
                ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
            </label>
            <select
                name="contacto_problemas_conexion_pregunta_b"
                id="contacto_problemas_conexion_pregunta_b"
                v-model="formContacto.problemas_conexion_pregunta_b"
                class="form-select w-auto d-inline-block"
                :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_pregunta_b }"
            >
                <option value=""></option>
                <option value="Si">Si</option>
                <option value="No">No</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_pregunta_b">
                {{ formContacto.errors.problemas_conexion_pregunta_b }}
            </div>
        </div>

        <div
            v-if="formContacto.problemas_conexion_pregunta_b === OPCION_NO"
            class="col-12 mb-2"
        >
            <div class="row">
                <label for="contacto_problemas_conexion_pregunta_c" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Agenda una visita domiciliaria o un re-entrenamiento
                </label>
                <div class="col-7 col-sm-5 col-md-4">
                    <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_pregunta_c }">
                        <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.problemas_conexion_pregunta_c }">
                            <i class="fa fa-calendar-alt"></i>
                        </span>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="contacto_problemas_conexion_pregunta_c"
                            id="contacto_problemas_conexion_pregunta_c"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.problemas_conexion_pregunta_c }"
                            v-model="formContacto.problemas_conexion_pregunta_c"
                            v-maska="'##/##/####'"
                            data-maska-eager
                            placeholder="DD/MM/AAAA"
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.problemas_conexion_pregunta_c">
                            {{ formContacto.errors.problemas_conexion_pregunta_c }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
