<script setup>
import { ref } from 'vue';
import Password from '@/Components/Form/Password.vue';

const props = defineProps({
    pacienteId: { type: Number, required: true },
    disabled: { type: Boolean, default: false },
});

const password = ref('');
const guardando = ref(false);
const error = ref('');
const mensaje = ref('');

const guardar = async () => {
    if (guardando.value || props.disabled) return;

    guardando.value = true;
    error.value = '';
    mensaje.value = '';

    try {
        const response = await axios.put(route('dashboard.pacientes.updatePassword', props.pacienteId), {
            password: password.value,
        });
        password.value = '';
        mensaje.value = response.data.message;
    } catch (exception) {
        error.value = exception.response?.data.errors?.password?.[0]
            ?? exception.response?.data.message
            ?? 'Error al guardar la contraseña.';
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <form class="row" @submit.prevent="guardar">
        <label for="paciente_nueva_password" class="col-12 col-sm-4 col-xl-2 col-form-label">Nueva contraseña</label>
        <div class="col-12 col-sm-8 col-xl-10">
            <div class="d-flex flex-column flex-sm-row align-items-sm-start gap-2">
                <div class="flex-grow-1">
                    <Password
                        id="paciente_nueva_password"
                        name="paciente_nueva_password"
                        v-model="password"
                        :error="error"
                        :disabled="disabled || guardando"
                        autocomplete="new-password"
                        @update:model-value="mensaje = ''; error = ''"
                    />
                </div>
                <button type="submit" class="btn btn-secondary text-white text-nowrap" :disabled="disabled || guardando">
                    <i v-if="guardando" class="fa-solid fa-spinner fa-spin me-1"></i>
                    {{ guardando ? 'Guardando' : 'Guardar contraseña' }}
                </button>
            </div>
            <div class="form-text">Se guarda por separado de los demás datos del paciente.</div>
            <div v-if="mensaje" class="text-success small mt-1" role="status">{{ mensaje }}</div>
        </div>
    </form>
</template>
