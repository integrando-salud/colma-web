<script setup>
import { toRef } from 'vue';
import { MOTIVO_CONTACTO, OPCION_NO, OPCION_SI } from '@/Constants/contacto';
import { vMaska } from 'maska/vue';
import MeridiemSelect from '@/Components/Form/MeridiemSelect.vue';
import Time12Input from '@/Components/Form/Time12Input.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_DOS_SEMANAS || formContacto.motivo === MOTIVO_CONTACTO.SEGUIMIENTO_MENSUAL">
        <div class="col-12 mb-2">
            <label for="contacto_seguimiento_pregunta_a" class="form-label me-2">
                ¿El/la participante tuvo dificultades utilizando el aplicativo desde su último contacto?
            </label>
            <select
                name="contacto_seguimiento_pregunta_a"
                id="contacto_seguimiento_pregunta_a"
                class="form-select w-auto d-inline-block"
                v-model="formContacto.seguimiento_pregunta_a"
                :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_a }"
            >
                <option value=""></option>
                <option value="Si">Si</option>
                <option value="No">No</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_a">
                {{ formContacto.errors.seguimiento_pregunta_a }}
            </div>
        </div>

        <template v-if="formContacto.seguimiento_pregunta_a === OPCION_SI">
            <div class="col-12 mb-2">
                <label for="contacto_seguimiento_pregunta_b" class="form-label me-2">
                    En caso si, ¿qué dificultades tuvo?
                </label>
                <input
                    type="text"
                    name="contacto_seguimiento_pregunta_b"
                    id="contacto_seguimiento_pregunta_b"
                    class="form-control"
                    v-model="formContacto.seguimiento_pregunta_b"
                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_b }"
                >
                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_b">
                    {{ formContacto.errors.seguimiento_pregunta_b }}
                </div>
            </div>

            <div class="col-12 mb-2">
                <label for="contacto_seguimiento_pregunta_c" class="form-label me-2">
                    ¿El/la participante pudo resolver estas dificultades?
                </label>
                <select
                    name="contacto_seguimiento_pregunta_c"
                    id="contacto_seguimiento_pregunta_c"
                    class="form-select w-auto d-inline-block"
                    v-model="formContacto.seguimiento_pregunta_c"
                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_c }"
                >
                    <option value=""></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_c">
                    {{ formContacto.errors.seguimiento_pregunta_c }}
                </div>
            </div>
        </template>

        <template v-if="formContacto.seguimiento_pregunta_c === OPCION_SI">
            <div class="col-12 mb-2">
                <label for="contacto_seguimiento_pregunta_d" class="form-label me-2">
                    En caso si, ¿cómo resolvió estas dificultades?
                </label>
                <input
                    type="text"
                    name="contacto_seguimiento_pregunta_d"
                    id="contacto_seguimiento_pregunta_d"
                    class="form-control"
                    v-model="formContacto.seguimiento_pregunta_d"
                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_d }"
                >
                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_d">
                    {{ formContacto.errors.seguimiento_pregunta_d }}
                </div>
            </div>
        </template>

        <template v-if="formContacto.seguimiento_pregunta_c === OPCION_NO">
            <div class="col-12 mb-2">
                <label for="contacto_seguimiento_pregunta_e" class="form-label me-2">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                </label>
                <select
                    name="contacto_seguimiento_pregunta_e"
                    id="contacto_seguimiento_pregunta_e"
                    class="form-select w-auto d-inline-block"
                    v-model="formContacto.seguimiento_pregunta_e"
                    :class="{ 'is-invalid': formContacto.errors?.seguimiento_pregunta_e }"
                >
                    <option value=""></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.seguimiento_pregunta_e">
                    {{ formContacto.errors.seguimiento_pregunta_e }}
                </div>
            </div>
        </template>

        <template v-if="formContacto.seguimiento_pregunta_e === OPCION_NO">
            <div class="col-12 mb-2">
                <div class="form-label">
                    Agenda un re-entrenamiento
                </div>
            </div>

            <div class="col-12 mb-2">
                <div class="row">
                    <label for="contacto_fecha_reentrenamiento" class="col-12 col-sm-4 col-lg-3 col-form-label">
                        Fecha re-entrenamiento
                    </label>
                    <div class="col-7 col-sm-5 col-md-4">
                        <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.fecha_reentrenamiento }">
                            <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.fecha_reentrenamiento }">
                                <i class="fa fa-calendar-alt"></i>
                            </span>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_fecha_reentrenamiento"
                                id="contacto_fecha_reentrenamiento"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.fecha_reentrenamiento }"
                                v-model="formContacto.fecha_reentrenamiento"
                                v-maska="'##/##/####'"
                                data-maska-eager
                                placeholder="DD/MM/AAAA"
                            />
                        </div>
                        <div class="invalid-feedback" v-if="formContacto.errors?.fecha_reentrenamiento">
                            {{ formContacto.errors.fecha_reentrenamiento }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-2">
                <div class="row">
                    <label for="contacto_hora_reentrenamiento" class="col-12 col-sm-4 col-lg-3 col-form-label">
                        Hora re-entrenamiento
                    </label>
                    <div class="col-12 col-sm-8 col-lg-9">
                        <div class="row gx-2">
                            <div class="col-12 d-flex">
                                <div class="me-2">
                                    <Time12Input
                                        id="contacto_hora_reentrenamiento"
                                        name="contacto_hora_reentrenamiento"
                                        v-model="formContacto.hora_reentrenamiento_display"
                                        :error="!!formContacto.errors?.hora_reentrenamiento"
                                    />
                                    <div class="invalid-feedback d-block" v-if="formContacto.errors?.hora_reentrenamiento">
                                        {{ formContacto.errors.hora_reentrenamiento }}
                                    </div>
                                </div>

                                <div>
                                    <MeridiemSelect
                                        id="contacto_hora_reentrenamiento_meridiano"
                                        name="contacto_hora_reentrenamiento_meridiano"
                                        v-model="formContacto.hora_reentrenamiento_meridiano"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-2">
                <label for="contacto_lugar_reentrenamiento" class="form-label me-2">
                    Lugar re-entrenamiento
                </label>
                <input
                    type="text"
                    name="contacto_lugar_reentrenamiento"
                    id="contacto_lugar_reentrenamiento"
                    class="form-control"
                    v-model="formContacto.lugar_reentrenamiento"
                    :class="{ 'is-invalid': formContacto.errors?.lugar_reentrenamiento }"
                >
                <div class="invalid-feedback" v-if="formContacto.errors?.lugar_reentrenamiento">
                    {{ formContacto.errors.lugar_reentrenamiento }}
                </div>
            </div>
        </template>
    </template>
</template>
