<script setup>
import { reactive, ref, useTemplateRef } from "vue";
import { router } from "@inertiajs/vue3";
import { useNotificationStore } from "@/Stores/notification";
import Table from "@/Components/Table.vue";
import Modal from "@/Components/Modal.vue";
import Password from "@/Components/Form/Password.vue";

const props = defineProps({
    usuarios: Object,
    roles: {
        type: Array,
        default: () => [],
    },
    paises: {
        type: Array,
        default: () => [],
    },
    centrosSalud: {
        type: Array,
        default: () => [],
    },
    supervisores: {
        type: Array,
        default: () => [],
    },
    filtros: Object,
    urlBase: String,
});

const formUsuario = reactive({
    id: null,
    rol: '',
    email: '',
    password: '',
    nombre: '',
    pais_id: '',
    centro_salud_id: '',
    supervisor_id: '',
    errors: {},
});

const formFiltros = reactive({
    rol: props.filtros.rol || '',
    pais: props.filtros.pais || '',
    nombre: props.filtros.nombre || '',
    orderBy: props.filtros.orderBy || null,
    orderDir: props.filtros.orderDir || null,
});

const formEditarPassword = reactive({
    password: '',
    errors: {},
});

const notification = useNotificationStore();
const modalUsuario = useTemplateRef('modalUsuario');
const titleModalUsuario = ref('Nuevo usuario');
const modalEliminar = useTemplateRef('modalEliminar');
const userSelected = ref(null);
const guardando = ref(false);
const eliminando = ref(false);
const modalEditarPassword = useTemplateRef('modalEditarPassword');
const guardandoPassword = ref(false);

const showModalUsuario = () => {
    modalUsuario.value.showModal();
};

const hideModalUsuario = () => {
    modalUsuario.value.hideModal();
};

const handleCloseModalUsuario = () => {
    if (userSelected.value) {
        userSelected.value = null;
    }

    resetFormUsuario();
};

const showModalEliminar = (usuario) => {
    userSelected.value = usuario;
    modalEliminar.value.showModal();
};

const hideModalEliminar = () => {
    modalEliminar.value.hideModal();
};

const handleCloseModalEliminar = () => {
    userSelected.value = null;
};

const showModalEditarPassword = (usuario) => {
    userSelected.value = usuario;
    modalEditarPassword.value.showModal();
};

const hideModalEditarPassword = () => {
    modalEditarPassword.value.hideModal();
};

const handleCloseModalEditarPassword = () => {
    userSelected.value = null;
    formEditarPassword.password = '';
    formEditarPassword.errors = {};
};

const resetFormUsuario = () => {
    titleModalUsuario.value = 'Nuevo usuario';
    formUsuario.id = null;
    formUsuario.rol = '';
    formUsuario.email = '';
    formUsuario.password = '';
    formUsuario.nombre = '';
    formUsuario.pais_id = '';
    formUsuario.centro_salud_id = '';
    formUsuario.supervisor_id = '';
    resetErroresFormUsuario();
};

const resetErroresFormUsuario = () => {
    formUsuario.errors = {};
};

const saveUsuario = async () => {
    guardando.value = true;
    resetErroresFormUsuario();

    try {
        await axios.post(route('dashboard.usuarios.store'), formUsuario);
        hideModalUsuario();
        router.get(props.urlBase);
    } catch (error) {
        switch (error.status) {
            case 422:
                const errors = error.response.data.errors;
                for (const key in errors) {
                    formUsuario.errors[key] = errors[key][0];
                }

                notification.errorNotification('El formulario contiene errores.');
                return;
            default:
                notification.errorNotification('Se produjo un error al guardar el usuario.');
                return;
        }
    } finally {
        guardando.value = false;
    }
};

const filterUsuarios = () => {
    router.get(props.urlBase, formFiltros, { preserveState: true, replace: true });
};

const orderBy = (column) => {
    formFiltros.orderDir = (column === formFiltros.orderBy && formFiltros.orderDir === 'asc') ? 'desc' : 'asc';
    formFiltros.orderBy = column;
    filterUsuarios();
}

const deleteUsuario = async () => {
    eliminando.value = true;

    try {
        await axios.delete(route('dashboard.usuarios.delete', userSelected.value.id));
        hideModalEliminar();
        router.get(props.urlBase);
    } catch (error) {
        notification.errorNotification('Se produjo un error al eliminar el usuario.');
    } finally {
        eliminando.value = false;
    }
};

const handleEditarUsuario = (usuario) => {
    userSelected.value = usuario;
    titleModalUsuario.value = 'Editar usuario';
    fillDataFormEditarUsuario();
    showModalUsuario();
};

const fillDataFormEditarUsuario = () => {
    formUsuario.id = userSelected.value.id;
    formUsuario.rol = userSelected.value.rol.nombre;
    formUsuario.email = userSelected.value.email;
    formUsuario.nombre = userSelected.value.nombre;
    formUsuario.pais_id = userSelected.value.pais.id;
    formUsuario.centro_salud_id = userSelected.value.centro_salud?.id;
    formUsuario.supervisor_id = userSelected.value.supervisor?.id;
};

const resetSupervisor = () => {
    formUsuario.supervisor_id = userSelected.value ? userSelected.value.supervisor?.id : '';
};

const updatePassword = async () => {
    guardandoPassword.value = true;
    formEditarPassword.errors = {};

    try {
        await axios.put(route('dashboard.usuarios.updatePassword', userSelected.value.id), formEditarPassword);
        hideModalEditarPassword();
        notification.successNotification('Contraseña actualizada correctamente.');
    } catch (error) {
        switch (error.status) {
            case 422:
                const errors = error.response.data.errors;
                for (const key in errors) {
                    formEditarPassword.errors[key] = errors[key][0];
                }

                notification.errorNotification('El formulario contiene errores.');
                return;
            default:
                notification.errorNotification('Se produjo un error al actualizar la contraseña.');
                return;
        }
    } finally {
        guardandoPassword.value = false;
    }
}
</script>

<template>
    <div class="row">
        <div class="col-12 text-end mb-4">
            <button class="btn btn-secondary text-white fw-semibold" @click="showModalUsuario">
                Agregar usuario
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <form class="row justify-content-md-end align-items-center">
                <div class="col-12 col-md-2 mb-2 mb-md-0 text-md-end">
                    Filtros:
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                    <select name="rol" id="filtroTipo" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.rol" @change="filterUsuarios">
                        <option value="">Tipo</option>
                        <option v-for="rol in roles" :key="rol.id" :value="rol.nombre">{{ rol.titulo }}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                    <select name="pais" id="filtroPais" class="form-select rounded-4 bg-body-secondary" v-model="formFiltros.pais"  @change="filterUsuarios">
                        <option value="">País</option>
                        <option v-for="pais in paises" :key="pais.id" :value="pais.id">{{ pais.nombre }}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <input type="text" name="nombre" id="filtroNombre" placeholder="Buscar por nombre" class="form-control rounded-4 bg-body-secondary" v-model="formFiltros.nombre">
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <Table v-if="usuarios.data.length > 0">
                <template #header>
                    <tr>
                        <th>
                            <span class="me-3">Usuario</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'nombre' || formFiltros.orderBy === 'nombre' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'nombre' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('nombre')"></i>
                        </th>
                        <th>
                            <span class="me-3">Tipo</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'tipo' || formFiltros.orderBy === 'tipo' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'tipo' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('tipo')"></i>
                        </th>
                        <th>
                            <span class="me-3">País</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'pais' || formFiltros.orderBy === 'pais' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'pais' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('pais')"></i>
                        </th>
                        <th>
                            <span class="me-3">Centro de salud</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'centro_salud' || formFiltros.orderBy === 'centro_salud' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'centro_salud' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('centro_salud')"></i>
                        </th>
                        <th>
                            <span class="me-3">Supervisor</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'supervisor' || formFiltros.orderBy === 'supervisor' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'supervisor' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('supervisor')"></i>
                        </th>
                        <th>
                            <span class="me-3">Pacientes asignados</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'pacientes' || formFiltros.orderBy === 'pacientes' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'pacientes' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('pacientes')"></i>
                        </th>
                        <th>
                            <span class="me-3">Último ingreso</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'last_login' || formFiltros.orderBy === 'last_login' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'last_login' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('last_login')"></i>
                        </th>
                        <th>
                            <span class="me-3">Alertas</span>
                            <i class="fa order" :class="{
                                'fa-chevron-down': formFiltros.orderBy !== 'alertas' || formFiltros.orderBy === 'alertas' && formFiltros.orderDir === 'asc',
                                'fa-chevron-up': formFiltros.orderBy === 'alertas' && formFiltros.orderDir === 'desc'
                            }" @click="orderBy('alertas')"></i>
                        </th>
                        <th></th>
                    </tr>
                </template>

                <tr v-for="usuario in usuarios.data" :key="usuario.id">
                    <td>{{ usuario.nombre }}</td>
                    <td>{{ usuario.rol.titulo }}</td>
                    <td>{{ usuario.pais?.nombre }}</td>
                    <td>{{ usuario.centro_salud?.nombre }}</td>
                    <td>{{ usuario.supervisor?.nombre }}</td>
                    <td>{{ usuario.numero_pacientes }}</td>
                    <td>{{ usuario.ultimo_acceso }}</td>
                    <td></td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn btn-link text-body" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#" class="dropdown-item" @click="handleEditarUsuario(usuario)">Editar</a>
                                    <a href="#" class="dropdown-item" @click="showModalEditarPassword(usuario)">Cambiar contraseña</a>
                                    <a href="#" class="dropdown-item text-danger" @click="showModalEliminar(usuario)">Eliminar</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </Table>
            <div class="alert alert-secondary" v-else>
                No se encontraron usuarios.
            </div>
        </div>
    </div>

    <Modal id="modalUsuario" ref="modalUsuario" :title="titleModalUsuario" size="xl" :loading="guardando" @modalClosed="handleCloseModalUsuario">
        <div class="row">
            <div class="cols-12 col-lg-6">
                <div class="row">
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="tipo" class="col-12 col-xl-4 col-form-label">Tipo usuario</label>
                            <div class="col-12 col-xl-8">
                                <select id="tipo" name="tipo" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.rol }" v-model="formUsuario.rol" @change="resetSupervisor">
                                    <option value="">Seleccionar tipo</option>
                                    <option v-for="rol in roles" :key="rol.id" :value="rol.nombre">{{ rol.titulo }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="formUsuario.errors?.rol">
                                    {{ formUsuario.errors.rol }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" v-if="formUsuario.rol === 'promotor'">
                        <div class="row mb-3">
                            <label for="supervisor" class="col-12 col-xl-4 col-form-label">Supervisor</label>
                            <div class="col-12 col-xl-8">
                                <select id="supervisor" name="supervisor" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.supervisor_id }" v-model="formUsuario.supervisor_id">
                                    <option value="">Seleccionar supervisor</option>
                                    <option v-for="supervisor in supervisores" :key="supervisor.id" :value="supervisor.id">{{ supervisor.nombre }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="formUsuario.errors?.supervisor_id">
                                    {{ formUsuario.errors.supervisor_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="email" class="col-12 col-xl-4 col-form-label">Correo electrónico</label>
                            <div class="col-12 col-xl-8">
                                <input type="email" id="email" name="email" class="form-control" :class="{ 'is-invalid': formUsuario.errors?.email }" v-model="formUsuario.email" />
                                <div class="invalid-feedback" v-if="formUsuario.errors?.email">
                                    {{ formUsuario.errors.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" v-if="formUsuario.id === null">
                        <div class="row mb-3">
                            <label for="password" class="col-12 col-xl-4 col-form-label">Contraseña</label>
                            <div class="col-12 col-xl-8">
                                <Password id="password" name="password" :error="formUsuario.errors?.password" v-model="formUsuario.password" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cols-12 col-lg-6">
                <div class="row">
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="nombre" class="col-12 col-xl-4 col-form-label">Nombre usuario</label>
                            <div class="col-12 col-xl-8">
                                <input type="text" id="nombre" name="nombre" class="form-control" :class="{ 'is-invalid': formUsuario.errors?.nombre }" v-model="formUsuario.nombre" />
                                <div class="invalid-feedback" v-if="formUsuario.errors?.nombre">
                                    {{ formUsuario.errors.nombre }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="pais" class="col-12 col-xl-4 col-form-label">País</label>
                            <div class="col-12 col-xl-8">
                                <select id="pais" name="pais" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.pais_id }" v-model="formUsuario.pais_id">
                                    <option value="">Seleccionar país</option>
                                    <option v-for="pais in paises" :key="pais.id" :value="pais.id">{{ pais.nombre }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="formUsuario.errors?.pais_id">
                                    {{ formUsuario.errors.pais_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="centro_salud" class="col-12 col-xl-4 col-form-label">Centro de salud</label>
                            <div class="col-12 col-xl-8">
                                <select id="centro_salud" name="centro_salud" class="form-select" :class="{ 'is-invalid': formUsuario.errors?.centro_salud_id }" v-model="formUsuario.centro_salud_id">
                                    <option value="">Seleccionar centro</option>
                                    <option v-for="centro in centrosSalud" :key="centro.id" :value="centro.id ">{{ centro.nombre }}</option>
                                </select>
                                <div class="invalid-feedback" v-if="formUsuario.errors?.centro_salud_id">
                                    {{ formUsuario.errors.centro_salud_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <template v-if="guardando === false">
                <button type="button" class="btn btn-grey fw-semibold shadow" :disabled="guardando" @click="hideModalUsuario">
                    Cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="guardando" @click="saveUsuario">
                    Guardar
                </button>
            </template>
            <template v-else>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Guardando usuario, espere...
                </button>
            </template>
        </template>
    </Modal>

    <Modal id="modalEliminar" ref="modalEliminar" title="Eliminar usuario" :loading="eliminando" @modalClosed="handleCloseModalEliminar">
        <p>Se eliminará el usuario <strong>{{ userSelected?.nombre }}</strong>.</p>
        <p>¿Desea continuar?</p>

        <template #footer>
            <template v-if="eliminando === false">
                <button type="button" class="btn btn-grey fw-semibold shadow" :disabled="eliminando" @click="hideModalEliminar">
                    Cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="eliminando" @click="deleteUsuario">
                    Confirmar
                </button>
            </template>
            <template v-else>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Eliminando usuario, espere...
                </button>
            </template>
        </template>
    </Modal>

    <Modal id="modalEditarPassword" ref="modalEditarPassword" title="Cambiar contraseña" :loading="guardandoPassword" @modalClosed="handleCloseModalEditarPassword">
        <div class="row">
            <div class="col-12">
                <p>
                    Se cambiará la contraseña del usuario: <span class="fw-bold text-primary">{{ userSelected?.nombre }}</span>.
                </p>
            </div>
            <div class="col-12">
                <label for="newPassword" class="form-label">Nueva contraseña</label>
                <Password id="newPassword" name="new_password" :error="formEditarPassword.errors?.password" v-model="formEditarPassword.password" />
            </div>
        </div>

        <template #footer>
            <template v-if="guardandoPassword === false">
                <button type="button" class="btn btn-grey fw-semibold shadow" :disabled="guardandoPassword" @click="hideModalEditarPassword">
                    Cancelar
                </button>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" :disabled="guardandoPassword" @click="updatePassword">
                    Confirmar
                </button>
            </template>
            <template v-else>
                <button type="button" class="btn btn-secondary text-white fw-semibold shadow" disabled>
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Cambiando contraseña, espere...
                </button>
            </template>
        </template>
    </Modal>
</template>

<style scoped>
.order {
    cursor: pointer;
}
</style>
