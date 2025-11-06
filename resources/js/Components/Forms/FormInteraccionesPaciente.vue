<script setup>
import { useForm } from "@inertiajs/vue3";
import { FORM_CONTACTO } from "@/Constants/paciente";

const formContacto = useForm(structuredClone(FORM_CONTACTO));
</script>

<template>
    <div class="row">
        <div class="col-12">
            <h4 class="fs-5 text-primary-emphasis">Formulario de contacto</h4>
        </div>
        <div class="col-12">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_resultado" class="col-12 col-sm-4 col-xl-4 col-form-label">Resultado del contacto</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select name="contacto_resultado" id="contacto_resultado" class="form-select" :class="{ 'is-invalid': formContacto.errors?.resultado }" v-model="formContacto.resultado">
                                <option value="">Seleccionar resultado</option>
                                <option value="1">Contactado</option>
                                <option value="0">No contactado</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.resultado">
                                {{ formContacto.errors.resultado }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3" v-if="formContacto.resultado !== ''">
                    <div class="row">
                        <label for="contacto_resultado_razon" class="col-12 col-sm-4 col-xl-4 col-form-label">
                            {{ (formContacto.resultado == 1) ? 'Razón de contacto' : 'Razón de no contacto' }}
                        </label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select name="contacto_resultado_razon" id="contacto_resultado_razon" class="form-select" :class="{ 'is-invalid': formContacto.errors?.resultado_razon }" v-model="formContacto.resultado_razon">
                                <option value="">Seleccionar razón</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.resultado_razon">
                                {{ formContacto.errors.resultado_razon }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_fecha" class="col-12 col-sm-4 col-xl-4 col-form-label">Fecha de contacto</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <input type="date" name="contacto_fecha" id="contacto_fecha" class="form-control" :class="{ 'is-invalid': formContacto.errors?.fecha }" v-model="formContacto.fecha" />
                            <div class="invalid-feedback" v-if="formContacto.errors?.fecha">
                                {{ formContacto.errors.fecha }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_hora" class="col-12 col-sm-4 col-xl-4 col-form-label">Hora de contacto</label>
                        <div class="col-12 col-sm-8 col-xl-4">
                            <input type="time" name="contacto_hora" id="contacto_hora" class="form-control" :class="{ 'is-invalid': formContacto.errors?.hora }" v-model="formContacto.hora" />
                            <div class="invalid-feedback" v-if="formContacto.errors?.hora">
                                {{ formContacto.errors.hora }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <div class="col-12 mb-sm-3">
                            <div class="row">
                                <label for="contacto_modalidad" class="col-12 col-sm-4 col-xl-4 col-form-label">Modalidad</label>
                                <div class="col-12 col-sm-8 col-xl-6">
                                    <select name="contacto_modalidad" id="contacto_modalidad" class="form-select" :class="{ 'is-invalid': formContacto.errors?.modalidad }" v-model="formContacto.modalidad">
                                        <option value="">Seleccionar modalidad</option>
                                        <option value="Teléfono">Teléfono</option>
                                        <option value="Visita">Visita</option>
                                        <option value="Mail">Mail</option>
                                        <option value="WhatsApp">WhatsApp</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="formContacto.errors?.modalidad">
                                        {{ formContacto.errors.modalidad }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <label for="contacto_motivo" class="col-12 col-sm-4 col-xl-4 col-form-label">Motivo</label>
                                <div class="col-12 col-sm-8 col-xl-6">
                                    <select name="contacto_motivo" id="contacto_motivo" class="form-select" :class="{ 'is-invalid': formContacto.errors?.motivo }" v-model="formContacto.motivo">
                                        <option value="">Seleccionar motivo</option>
                                        <option value="Entrenamiento inicial">Entrenamiento inicial</option>
                                        <option value="Falta de conexión">Falta de conexión</option>
                                        <option value="Falta de adherencia">Falta de adherencia</option>
                                        <option value="Alerta clínica">Alerta clínica</option>
                                        <option value="Pedido ayuda">Pedido ayuda</option>
                                        <option value="Seguimiento programado">Seguimiento programado</option>
                                        <option value="Reprogramación">Reprogramación</option>
                                    </select>
                                    <div class="invalid-feedback" v-if="formContacto.errors?.motivo">
                                        {{ formContacto.errors.motivo }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 mb-sm-3">
                    <div class="row h-100">
                        <label for="contacto_observaciones" class="col-12 col-sm-4 col-md-2 col-form-label">Observaciones</label>
                        <div class="col-12 col-sm-8 col-md-10 col-xl-6">
                            <textarea id="contacto_observaciones" name="contacto_observaciones" class="form-control h-100" :class="{ 'is-invalid': formContacto.errors?.observaciones }" v-model="formContacto.observaciones"></textarea>
                            <div class="invalid-feedback" v-if="formContacto.errors?.observaciones">
                                {{ formContacto.errors.observaciones }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" v-if="formContacto.motivo === 'Alerta clínica'">
                <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                    <div class="row">
                        <label for="contacto_accion" class="col-12 col-sm-4 col-xl-4 col-form-label">Acción</label>
                        <div class="col-12 col-sm-8 col-xl-6">
                            <select name="contacto_accion" id="contacto_accion" class="form-select" :class="{ 'is-invalid': formContacto.errors?.accion }" v-model="formContacto.accion">
                                <option value="">Seleccionar acción</option>
                                <option value="Derivado">Derivado</option>
                                <option value="Consejo">Consejo</option>
                                <option value="Reagendado">Reagendado</option>
                            </select>
                            <div class="invalid-feedback" v-if="formContacto.errors?.accion">
                                {{ formContacto.errors.accion }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="formContacto.motivo === 'Reprogramación'">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <label for="contacto_fecha_reprogramacion" class="col-12 col-sm-4 col-xl-4 col-form-label">Nueva fecha de contacto</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <input type="date" name="contacto_fecha_reprogramacion" id="contacto_fecha_reprogramacion" class="form-control" :class="{ 'is-invalid': formContacto.errors?.contacto_fecha_reprogramacion }" v-model="formContacto.contacto_fecha_reprogramacion" />
                                <div class="invalid-feedback" v-if="formContacto.errors?.contacto_fecha_reprogramacion">
                                    {{ formContacto.errors.contacto_fecha_reprogramacion }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-sm-3">
                        <div class="row">
                            <label for="contacto_motivo_reprogramacion" class="col-12 col-sm-4 col-xl-4 col-form-label">Motivo reprogramación</label>
                            <div class="col-12 col-sm-8 col-xl-6">
                                <select name="contacto_motivo_reprogramacion" id="contacto_motivo_reprogramacion" class="form-select" :class="{ 'is-invalid': formContacto.errors?.motivo_reprogramacion }" v-model="formContacto.motivo_reprogramacion">
                                    <option value="">Seleccionar motivo</option>
                                </select>
                                <div class="invalid-feedback" v-if="formContacto.errors?.motivo_reprogramacion">
                                    {{ formContacto.errors.motivo_reprogramacion }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>

</style>
