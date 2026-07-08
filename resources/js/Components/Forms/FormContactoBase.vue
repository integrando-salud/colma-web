<script setup>
import { toRef } from 'vue';
import { MODALIDAD, MOTIVO_CONTACTO } from '@/Constants/contacto';
import { vMaska } from 'maska/vue';
import {TIPO_ALERTA} from "@/Constants/contacto.js";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    motivosContacto: {
        type: Array,
        required: true,
    },
    alerta: {
        type: Object,
        default: null,
    },
    entrenamientoActual: {
        type: Object,
        default: null,
    },
    verFechaHoraContacto: {
        type: Boolean,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
<div class="col-12 mb-2">
    <div class="row">
        <label for="contacto_motivo" class="col-12 col-sm-4 col-lg-3 col-form-label">
            Motivo de contacto
        </label>
        <div class="col-12 col-sm-8 col-lg-9">
            <select
                name="contacto_motivo"
                id="contacto_motivo"
                class="form-select"
                :class="{ 'is-invalid': formContacto.errors?.motivo }"
                v-model="formContacto.motivo"
                :disabled="alerta && alerta.tipo !== TIPO_ALERTA.CALLS"
            >
                <option value="">Seleccionar motivo</option>
                <option
                    v-for="motivo in motivosContacto"
                    :key="motivo"
                    :value="motivo"
                    :disabled="motivo === MOTIVO_CONTACTO.AGENDAR_CITA_ENTRENAMIENTO && entrenamientoActual"
                >{{ motivo }}</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.motivo">
                {{ formContacto.errors.motivo }}
            </div>
        </div>
    </div>
</div>

<div class="col-12 mb-2">
    <div class="row">
        <label for="contacto_resultado" class="col-12 col-sm-4 col-lg-3 col-form-label">
            Resultado del contacto
        </label>
        <div class="col-12 col-sm-8 col-lg-9">
            <select
                name="contacto_resultado"
                id="contacto_resultado"
                class="form-select"
                :class="{ 'is-invalid': formContacto.errors?.contactado }"
                v-model.number="formContacto.contactado"
            >
                <option value="">Seleccionar resultado</option>
                <option value="1">Contactado</option>
                <option value="0">No contactado</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.contactado">
                {{ formContacto.errors.contactado }}
            </div>
        </div>
    </div>
</div>

<template v-if="verFechaHoraContacto">
    <div class="col-12 mb-2">
        <div class="row">
            <label for="contacto_fecha" class="col-12 col-sm-4 col-lg-3 col-form-label">
                Fecha de contacto
            </label>
            <div class="col-7 col-sm-5 col-md-4">
                <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.fecha }">
                    <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.fecha }">
                        <i class="fa fa-calendar-alt"></i>
                    </span>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="contacto_fecha"
                        id="contacto_fecha"
                        class="form-control"
                        :class="{ 'is-invalid': formContacto.errors?.fecha }"
                        v-model="formContacto.fecha"
                        v-maska="'##/##/####'"
                        data-maska-eager
                        placeholder="DD/MM/AAAA"
                    />
                </div>
                <div class="invalid-feedback" v-if="formContacto.errors?.fecha">
                    {{ formContacto.errors.fecha }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-2">
        <div class="row">
            <label for="contacto_hora" class="col-12 col-sm-4 col-lg-3 col-form-label">
                Hora de contacto
            </label>
            <div class="col-12 col-sm-8 col-lg-9">
                <div class="row gx-2">
                    <div class="col-5 col-md-4">
                        <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.hora }">
                            <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.hora }">
                                <i class="fa fa-clock"></i>
                            </span>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_hora"
                                id="contacto_hora"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.hora }"
                                v-model="formContacto.hora_display"
                                v-maska="'##:##'"
                                data-maska-eager
                                placeholder="HH:MM"
                            />
                        </div>
                        <div class="invalid-feedback d-block" v-if="formContacto.errors?.hora">
                            {{ formContacto.errors.hora }}
                        </div>
                    </div>
                    <div class="col-5 col-sm-4 col-md-3 col-xl-2">
                        <select
                            class="form-select"
                            v-model="formContacto.hora_meridiano"
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

<div class="col-12 mb-2">
    <div class="row">
        <label for="contacto_modalidad" class="col-12 col-sm-4 col-lg-3 col-form-label">
            Medio de contacto
        </label>
        <div class="col-12 col-sm-8 col-lg-9">
            <select
                name="contacto_modalidad"
                id="contacto_modalidad"
                class="form-select"
                :class="{ 'is-invalid': formContacto.errors?.modalidad }"
                v-model="formContacto.modalidad"
            >
                <option value="">Seleccionar modalidad</option>
                <option
                    v-for="modalidad in MODALIDAD"
                    :key="modalidad"
                    :value="modalidad"
                >{{ modalidad }}</option>
            </select>
            <div class="invalid-feedback" v-if="formContacto.errors?.modalidad">
                {{ formContacto.errors.modalidad }}
            </div>
        </div>
    </div>
</div>
</template>
