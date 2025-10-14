<script setup>
import { Toast } from "bootstrap";
import { watch, ref, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    color: {
        type: String,
        default: 'primary',
    },
    message: {
        type: String,
        default: '',
    },
});

const page = usePage();
const emit = defineEmits(['update:message']);

const toastEl = ref(null);
let toastInstance = null;

watch(() => props.message, async (newMessage) => {
    if (!newMessage) return;

    await nextTick();

    if (toastEl.value) {
        toastInstance?.dispose?.();

        toastInstance = new Toast(toastEl.value, {
            autohide: true,
            delay: 5000,
        });

        toastEl.value.addEventListener('hidden.bs.toast', () => {
            emit('update:message', '');
            page.props.success = null;
            page.props.error = null;
        });

        toastInstance.show();
    }
});
</script>

<template>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast" :class="`bg-${color} text-white`" ref="toastEl">
            <div class="d-flex">
                <div class="toast-body">
                    {{ message }}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
