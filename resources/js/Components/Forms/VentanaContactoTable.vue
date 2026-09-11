<script setup>
import { toRef } from 'vue';
import { VENTANA_CONTACTO_LABELS } from '@/Constants/ventanaContacto';

const props = defineProps({
    ventanaContacto: {
        type: Object,
        required: true,
    },
    colorFechaEntrenamiento: {
        type: String,
        required: true,
    },
    ventanaContactoKeys: {
        type: Array,
        required: true,
    },
});

const ventanaContacto = toRef(props, 'ventanaContacto');
</script>

<template>
<div class="col-12 col-lg-5">
    <div class="table-responsive">
        <table class="table table-bordered bg-body">
            <thead>
                <tr>
                    <th class="bg-body-secondary"></th>
                    <th class="bg-body-secondary text-black text-center">Inicio de contacto</th>
                    <th class="bg-body-secondary text-black text-center">Fin de contacto</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Fecha de entrenamiento</td>
                    <td class="text-center" :class="colorFechaEntrenamiento">
                        {{ ventanaContacto.fechaEntrenamiento }}
                    </td>
                    <td></td>
                </tr>

                <tr
                    v-for="key in ventanaContactoKeys"
                    :key="key"
                >
                    <td>{{ VENTANA_CONTACTO_LABELS[key] }}</td>
                    <td class="text-center" :class="ventanaContacto[key].color">
                        {{ ventanaContacto[key].inicio }}
                    </td>
                    <td class="text-center" :class="ventanaContacto[key].color">
                        {{ ventanaContacto[key].fin }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>
