<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";

defineProps({
    id: {
        type: String,
        default: 'modalCancelar',
    },
    title: {
        type: String,
        default: 'Cancelar',
    },
    message: {
        type: String,
        default: '¿Estás seguro/a que quieres cancelar?',
    },
    continueText: {
        type: String,
        default: 'No, continuar',
    },
    cancelText: {
        type: String,
        default: 'Si, cancelar',
    },
});

const emit = defineEmits(['confirm']);
const modal = ref(null);

const showModal = () => {
    modal.value.showModal();
};

const hideModal = () => {
    modal.value.hideModal();
};

defineExpose({
    showModal,
    hideModal,
});
</script>

<template>
    <Modal :id ref="modal" :title>
        <div class="row">
            <div class="col-12">
                <p>
                    {{ message }}
                </p>
            </div>
        </div>

        <template #footer>
            <button type="button" class="btn btn-grey fw-semibold shadow" @click="hideModal">
                {{ continueText }}
            </button>
            <button type="button" class="btn btn-secondary text-white fw-semibold shadow" @click="emit('confirm')">
                {{ cancelText }}
            </button>
        </template>
    </Modal>
</template>
