<script setup>
import { toRef } from 'vue';
import { MOTIVO_CONTACTO, RAZON_CONTACTO_PARTICIPANTE } from '@/Constants/contacto';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const formContacto = toRef(props, 'form');
</script>

<template>
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.INICIADO_POR_PACIENTE">
        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_razon_contacto_participante" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Razón de contacto del participante
                </label>
                <div class="col-12 col-sm-8 col-xl-7">
                    <select
                        name="contacto_razon_contacto_participante"
                        id="contacto_razon_contacto_participante"
                        class="form-select"
                        v-model="formContacto.razon_contacto_participante"
                        :class="{ 'is-invalid': formContacto.errors?.razon_contacto_participante }"
                    >
                        <option value=""></option>
                        <option
                            v-for="razonContacto in RAZON_CONTACTO_PARTICIPANTE"
                            :key="razonContacto"
                            :value="razonContacto"
                        >
                            {{ razonContacto }}
                        </option>
                    </select>
                    <div class="invalid-feedback" v-if="formContacto.errors?.razon_contacto_participante">
                        {{ formContacto.errors.razon_contacto_participante }}
                    </div>
                </div>
                <div
                    v-if="formContacto.razon_contacto_participante === RAZON_CONTACTO_PARTICIPANTE.OTRO"
                    class="col-12 col-sm-8 col-xl-7 offset-sm-4 offset-lg-3 mt-2 mt-lg-0"
                >
                    <input
                        type="text"
                        name="contacto_razon_contacto_participante_otro"
                        id="contacto_razon_contacto_participante_otro"
                        class="form-control"
                        placeholder="Escriba el motivo de contacto"
                        :class="{ 'is-invalid': formContacto.errors?.razon_contacto_participante_otro }"
                        v-model="formContacto.razon_contacto_participante_otro"
                    />
                    <div class="invalid-feedback" v-if="formContacto.errors?.razon_contacto_participante_otro">
                        {{ formContacto.errors.razon_contacto_participante_otro }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_contacto_participante_acciones" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    Escribe las acciones realizadas
                </label>
                <div class="col-12 col-sm-8 col-lg-9">
                    <textarea
                        id="contacto_contacto_participante_acciones"
                        name="contacto_contacto_participante_acciones"
                        class="form-control"
                        :class="{ 'is-invalid': formContacto.errors?.contacto_participante_acciones }"
                        v-model="formContacto.contacto_participante_acciones"
                        rows="4"
                    ></textarea>
                    <div class="invalid-feedback" v-if="formContacto.errors?.contacto_participante_acciones">
                        {{ formContacto.errors.contacto_participante_acciones }}
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
