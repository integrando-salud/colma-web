<script setup>
import { computed, toRef } from 'vue';
import { LUGAR_CITA, MOTIVO_CONTACTO } from '@/Constants/contacto';
import { vMaska } from 'maska/vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');

const fechaProgramadaCitaTitle = computed(() => {
    let title = 'Fecha programada para cita';

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO) {
        title += ' de entrenamiento';
    }

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_FINAL) {
        title += ' final';
    }

    return title;
});

const lugarAcordadoCitaTitle = computed(() => {
    let title = 'Lugar acordado para cita';

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO) {
        title += ' de entrenamiento';
    }

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_FINAL) {
        title += ' final';
    }

    return title;
});

const horaProgramadaCitaTitle = computed(() => {
    let title = 'Hora para la cita';

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO) {
        title += ' de entrenamiento';
    }

    if (formContacto.value.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_FINAL) {
        title += ' final';
    }

    return title;
});
</script>

<template>
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO || formContacto.motivo === MOTIVO_CONTACTO.AGENDAR_CITA_FINAL">
        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_fecha_cita" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    {{ fechaProgramadaCitaTitle }}
                </label>
                <div class="col-7 col-sm-5 col-md-4">
                    <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.fecha_cita }">
                        <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.fecha_cita }">
                            <i class="fa fa-calendar-alt"></i>
                        </span>
                        <input
                            type="text"
                            inputmode="numeric"
                            name="contacto_fecha_cita"
                            id="contacto_fecha_cita"
                            class="form-control"
                            :class="{ 'is-invalid': formContacto.errors?.fecha_cita }"
                            v-model="formContacto.fecha_cita"
                            v-maska="'##/##/####'"
                            data-maska-eager
                            placeholder="DD/MM/AAAA"
                        />
                    </div>
                    <div class="invalid-feedback" v-if="formContacto.errors?.fecha_cita">
                        {{ formContacto.errors.fecha_cita }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_lugar_cita" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    {{ lugarAcordadoCitaTitle }}
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
                    class="col-12 col-sm-8 col-xl-7 offset-sm-4 offset-lg-3 mt-2 mt-lg-0"
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
                <label for="hora_cita" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    {{ horaProgramadaCitaTitle }}
                </label>
                <div class="col-12 col-sm-8 col-lg-9">
                    <div class="row gx-2">
                        <div class="col-5 col-md-4">
                            <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.hora_cita }">
                                <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.hora_cita }">
                                    <i class="fa fa-clock"></i>
                                </span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    name="hora_cita"
                                    id="hora_cita"
                                    class="form-control"
                                    :class="{ 'is-invalid': formContacto.errors?.hora_cita }"
                                    v-model="formContacto.hora_cita_display"
                                    v-maska="'##:##'"
                                    data-maska-eager
                                    placeholder="HH:MM"
                                />
                            </div>
                            <div class="invalid-feedback d-block" v-if="formContacto.errors?.hora_cita">
                                {{ formContacto.errors.hora_cita }}
                            </div>
                        </div>
                        <div class="col-5 col-sm-4 col-md-3 col-xl-2">
                            <select
                                class="form-select"
                                v-model="formContacto.hora_cita_meridiano"
                            >
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
