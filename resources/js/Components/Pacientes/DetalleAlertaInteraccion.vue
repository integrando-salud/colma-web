<script setup>
defineProps({
    alerta: {
        required: true,
        type: Object,
    }
});
</script>

<template>
    <div class="col-12 mb-3">
        <div class="accordion" id="accordionInfoAlerta">
            <div class="accordion-item border-danger">
                <h3 class="accordion-header">
                    <button class="accordion-button bg-danger text-white" type="button" data-bs-toggle="collapse" data-bs-target="#alertaInfo">
                        <i class="fa fa-warning me-2"></i> {{ alerta.descripcion }}
                    </button>
                </h3>
                <div id="alertaInfo" class="accordion-collapse collapse show" data-bs-parent="#accordionInfoAlerta">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-12 col-md-auto" v-if="alerta.valor">
                                <strong>Valor:</strong> {{ alerta.valor }}
                            </div>
                            <div class="col-12 col-md-auto" v-if="alerta.rango_normal">
                                <strong>Rango normal:</strong> {{ alerta.rango_normal }}
                            </div>
                            <div class="col-12 col-md-auto" v-if="alerta.fecha">
                                <strong>Fecha:</strong> {{ alerta.fecha }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.accordion-button::after {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round'><path d='m2 5 6 6 6-6'/></svg>");
}

.accordion-button:not(.collapsed)::after {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round'><path d='m2 5 6 6 6-6'/></svg>");
}
</style>
