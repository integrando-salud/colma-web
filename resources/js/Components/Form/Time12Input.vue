<script setup>
const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: null,
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const getTimeParts = () => {
    const [hours = '', minutes = ''] = (props.modelValue || '').split(':');

    return { hours, minutes };
};

const sanitizeTimePart = (value, max) => {
    const digits = value.replace(/\D/g, '').slice(0, 2);

    if (!digits) {
        return '';
    }

    const numberValue = parseInt(digits, 10);

    if (Number.isNaN(numberValue)) {
        return '';
    }

    return Math.min(numberValue, max).toString().padStart(digits.length, '0');
};

const emitTime = (hours, minutes) => {
    emit('update:modelValue', hours || minutes ? `${hours}:${minutes}` : null);
};

const handleHoursInput = (event) => {
    const { minutes } = getTimeParts();
    const hours = sanitizeTimePart(event.target.value, 12);

    emitTime(hours, minutes);
};

const handleMinutesInput = (event) => {
    const { hours } = getTimeParts();
    const minutes = sanitizeTimePart(event.target.value, 59);

    emitTime(hours, minutes);
};

const handleBlur = () => {
    const { hours, minutes } = getTimeParts();
    const normalizedHours = hours && parseInt(hours, 10) === 0 ? '1' : hours;

    emitTime(
        normalizedHours ? normalizedHours.padStart(2, '0') : '',
        minutes ? minutes.padStart(2, '0') : '',
    );
};
</script>

<template>
    <div class="input-group time-12-input" :class="{ 'is-invalid': error }">
        <span class="input-group-text" :class="{ 'border-danger': error }">
            <i class="fa fa-clock"></i>
        </span>
        <input
            type="text"
            inputmode="numeric"
            :name="`${name}_hora`"
            :id="`${id}_hora`"
            class="form-control text-center"
            :class="{ 'is-invalid': error }"
            :value="getTimeParts().hours"
            maxlength="2"
            min="1"
            max="12"
            placeholder="HH"
            aria-label="Hora"
            @input="handleHoursInput"
            @blur="handleBlur"
        />
        <span class="input-group-text px-2" :class="{ 'border-danger': error }">:</span>
        <input
            type="text"
            inputmode="numeric"
            :name="`${name}_minutos`"
            :id="`${id}_minutos`"
            class="form-control text-center"
            :class="{ 'is-invalid': error }"
            :value="getTimeParts().minutes"
            maxlength="2"
            min="0"
            max="59"
            placeholder="MM"
            aria-label="Minutos"
            @input="handleMinutesInput"
            @blur="handleBlur"
        />
    </div>
</template>

<style scoped>
.time-12-input .form-control {
    min-width: 3.25rem;
    max-width: 100px;
}
</style>
