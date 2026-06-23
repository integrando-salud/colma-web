<script setup>
import { toRef } from 'vue';
import { DIFICULTADES_PEDIDO_AYUDA, MOTIVO_CONTACTO, OPCION_NO } from '@/Constants/contacto';
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
    <template v-if="formContacto.motivo === MOTIVO_CONTACTO.PEDIDO_AYUDA">
        <div class="col-12 mb-2">
            <div class="row">
                <label for="contacto_pedido_ayuda_pregunta_a" class="col-12 col-sm-4 col-lg-3 col-form-label">
                    ¿Qué dificultades tuvo el/la participante?
                    Selecciona todas las que aplique
                </label>
                <div class="col-12 col-sm-8 col-lg-9">
                    <div
                        v-for="dificultad in DIFICULTADES_PEDIDO_AYUDA"
                        :key="dificultad"
                        class="form-check mb-2"
                    >
                        <input
                            class="form-check-input border-primary"
                            type="checkbox"
                            :id="'contacto_pedido_ayuda_pregunta_a' + dificultad"
                            :value="dificultad"
                            v-model="formContacto.pedido_ayuda_pregunta_a"
                        />
                        <label
                            class="form-check-label"
                            :for="'contacto_pedido_ayuda_pregunta_a' + dificultad"
                        >
                            {{ dificultad }}
                        </label>
                    </div>
                    <div class="invalid-feedback d-block" v-if="formContacto.errors?.pedido_ayuda_pregunta_a">
                        {{ formContacto.errors.pedido_ayuda_pregunta_a }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="formContacto.pedido_ayuda_pregunta_a.includes(DIFICULTADES_PEDIDO_AYUDA.NO_QUIERE_PARTICIPAR)"
            class="col-12 mb-2"
        >
            <div class="alert alert-danger">
                Informar al supervisor o supervisora que el participante no desea seguir en el proyecto.
            </div>
        </div>

        <template v-else>
            <div class="col-12 mb-2">
                <label for="contacto_pedido_ayuda_pregunta_b" class="form-label me-2">
                    Describe las dificultades que tuvo el/la participante
                </label>
                <input
                    type="text"
                    name="contacto_pedido_ayuda_pregunta_b"
                    id="contacto_pedido_ayuda_pregunta_b"
                    class="form-control"
                    :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_b }"
                    v-model="formContacto.pedido_ayuda_pregunta_b"
                >
                <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_b">
                    {{ formContacto.errors.pedido_ayuda_pregunta_b }}
                </div>
            </div>

            <div class="col-12 mb-2">
                <label for="contacto_pedido_ayuda_pregunta_c" class="form-label me-2">
                    ¿Pudiste ayudar al participante a resolver estas dificultades durante la llamada/visita?
                </label>
                <select
                    name="contacto_pedido_ayuda_pregunta_c"
                    id="contacto_pedido_ayuda_pregunta_c"
                    class="form-select w-auto d-inline-block"
                    :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_c }"
                    v-model="formContacto.pedido_ayuda_pregunta_c"
                >
                    <option value=""></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
                <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_c">
                    {{ formContacto.errors.pedido_ayuda_pregunta_c }}
                </div>
            </div>

            <div
                v-if="formContacto.pedido_ayuda_pregunta_c === OPCION_NO"
                class="col-12 mb-2"
            >
                <div class="row">
                    <label for="contacto_pedido_ayuda_pregunta_d" class="col-12 col-sm-4 col-lg-3 col-form-label">
                        Agenda una visita domiciliaria o un re-entrenamiento
                    </label>
                    <div class="col-7 col-sm-5 col-md-4">
                        <div class="input-group" :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_d }">
                            <span class="input-group-text" :class="{ 'border-danger': formContacto.errors?.pedido_ayuda_pregunta_d }">
                                <i class="fa fa-calendar-alt"></i>
                            </span>
                            <input
                                type="text"
                                inputmode="numeric"
                                name="contacto_pedido_ayuda_pregunta_d"
                                id="contacto_pedido_ayuda_pregunta_d"
                                class="form-control"
                                :class="{ 'is-invalid': formContacto.errors?.pedido_ayuda_pregunta_d }"
                                v-model="formContacto.pedido_ayuda_pregunta_d"
                                v-maska="'##/##/####'"
                                data-maska-eager
                                placeholder="DD/MM/AAAA"
                            />
                            <div class="invalid-feedback" v-if="formContacto.errors?.pedido_ayuda_pregunta_d">
                                {{ formContacto.errors.pedido_ayuda_pregunta_d }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </template>
</template>
