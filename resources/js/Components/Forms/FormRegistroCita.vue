<script setup>
import { toRef } from 'vue';
import { LUGAR_CITA, MOTIVO_CONTACTO } from '@/Constants/contacto';
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
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.CITA_ENTRENAMIENTO || formContacto.motivo === MOTIVO_CONTACTO.CITA_FINAL || formContacto.motivo === MOTIVO_CONTACTO.REENTRENAMIENTO">
        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_fecha_cita_final" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Fecha
                </label>
                <div class="col-7 col-sm-5 col-md-4">
                    <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.fecha_cita_final }">
                        <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.fecha_cita_final }">
                            <i class="fa fa-calendar-alt"></i>
                        </span>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="contacto_fecha_cita_final"
                            id="contacto_fecha_cita_final"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.fecha_cita_final }"
                            v-model="formContacto.fecha_cita_final"
                            v-maska="'##/##/####'"
                            data-maska-eager
                            placeholder="DD/MM/AAAA"
                        />
                        <div class="invalid-feedback" v-if="formContacto.errors?.fecha_cita_final">
                            {{ formContacto.errors.fecha_cita_final }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_lugar_cita" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Lugar
                </label>
                <div class="col-12 col-sm-8 col-lg-9">
                    <select
                        name="contacto_lugar_cita"
                        id="contacto_lugar_cita"
                        class="form-select"
                        :class="{ 'is-invalid': formContacto.errors?.lugar_cita }"
                        v-model="formContacto.lugar_cita"
                    >
                        <option value="">Seleccionar lugar</option>
                        <option
                            v-for="lugar in LUGAR_CITA"
                            :key="lugar"
                            :value="lugar"
                        >
                            {{ lugar }}
                        </option>
                    </select>
                    <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita">
                        {{ formContacto.errors.lugar_cita }}
                    </div>
                </div>
                <div
                    v-if="formContacto.lugar_cita === 'Otro'"
                    class="col-12 col-sm-8 col-lg-9 offset-sm-4 offset-lg-3 mt-2"
                >
                    <input
                        type="text"
                        name="contacto_lugar_cita_otro"
                        id="contacto_lugar_cita_otro"
                        class="form-control"
                        placeholder="Escriba el lugar de la cita"
                        :class="{ 'is-invalid': formContacto.errors?.lugar_cita_otro }"
                        v-model="formContacto.lugar_cita_otro"
                    />
                    <div class="invalid-feedback" v-if="formContacto.errors?.lugar_cita_otro">
                        {{ formContacto.errors.lugar_cita_otro }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_hora_cita_final" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Hora de inicio
                </label>
                <div class="col-12 col-sm-8 col-lg-9">
                    <div class="row gx-2">
                        <div class="col-12 d-flex">
                            <div class="me-2">
                                <Time12Input
                                    id="contacto_hora_cita_final"
                                    name="contacto_hora_cita_final"
                                    v-model="formContacto.hora_cita_final_display"
                                    :error="!!formContacto.errors?.hora_cita_final"
                                />
                                <div class="invalid-feedback d-block" v-if="formContacto.errors?.hora_cita_final">
                                    {{ formContacto.errors.hora_cita_final }}
                                </div>
                            </div>

                            <div>
                                <MeridiemSelect
                                    id="contacto_hora_cita_final_meridiano"
                                    name="contacto_hora_cita_final_meridiano"
                                    v-model="formContacto.hora_cita_final_meridiano"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
