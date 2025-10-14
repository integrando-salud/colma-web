<script setup>
import { Modal } from 'bootstrap';
import { onMounted, ref } from 'vue';

const emit = defineEmits(['modal-closed']);

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    title: String,
    size: {
        type: String,
        default: null, // sm, lg, xl
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const modal = ref(null);

const showModal = () => {
    modal.value.show();
};

const hideModal = () => {
    modal.value.hide();
};

defineExpose({
    showModal,
    hideModal,
});

onMounted(() => {
    const modalElement = document.getElementById(props.id);
    modal.value = new Modal(modalElement);

    modalElement.addEventListener('hidden.bs.modal', () => {
        emit('modal-closed');
    });
});
</script>

<template>
    <div class="modal fade" :id data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" :class="{ ['modal-' + size]: size }">
            <div class="modal-content bg-body-tertiary rounded-0 border-3 border-dark-subtle">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-primary-emphasis" v-if="title">
                        {{ title }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                </div>
                <div class="modal-body">
                    <slot />
                </div>
                <div class="modal-footer border-0" v-if="$slots.footer">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
