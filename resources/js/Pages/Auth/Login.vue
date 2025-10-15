<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Main from "@/Components/Layouts/Main.vue";

const form = useForm({
    email: null,
    password: null
});

const login = () => {
    form.post(route('dashboard.login'), {
        onFinish: () => form.reset('password')
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <Main>
        <div class="row">
            <div class="col-12 vh-100 d-flex flex-column justify-content-center align-items-center">
                <div class="w-100 mb-1" style="max-width: 300px;">
                    <div class="mb-5 text-center">
                        <img :src="$page.props.app.logo" class="img-fluid" :alt="$page.props.app.nombre">
                    </div>
                    <div>
                        <form @submit.prevent="login">
                            <div class="alert alert-danger" v-if="form.errors.login">{{ form.errors.login }}</div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico:</label>
                                <input type="email" id="email" name="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" v-model="form.email">
                                <div class="invalid-feedback" v-if="form.errors.email">{{ form.errors.email }}</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña:</label>
                                <input type="password" id="password" name="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" v-model="form.password">
                                <div class="invalid-feedback" v-if="form.errors.password">{{ form.errors.password }}</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 text-white mb-3">Iniciar sesión</button>
                        </form>
                    </div>
                </div>

                <a href="#" class="text-secondary text-decoration-none">Recuperar contraseña</a>
            </div>
        </div>
    </Main>
</template>

<style scoped>

</style>
