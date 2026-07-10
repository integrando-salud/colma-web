<script setup>
import { toRef } from 'vue';
import { MOTIVO_CONTACTO } from '@/Constants/contacto';
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
