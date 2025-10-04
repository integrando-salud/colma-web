<x-layout title="Administración de usuarios">
    <div class="pt-4 pb-2">
        <h3 class="mb-4">Administración de usuarios</h3>

        <div class="row">
            <div class="col-12 text-end mb-4">
                <button class="btn btn-secondary text-white fw-semibold" onclick="showModalUsuario()">
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
                        <select name="tipo" id="filtroTipo" class="form-select rounded-4 bg-body-secondary">
                            <option value="">Tipo</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" @selected($rol->id === (int) request()->tipo)>{{ $rol->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2 mb-2 mb-md-0">
                        <select name="pais" id="filtroPais" class="form-select rounded-4 bg-body-secondary">
                            <option value="">País</option>
                            @foreach($paises as $pais)
                                <option value="{{ $pais->id }}" @selected($pais->id === (int) request()->pais)>{{ $pais->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <input type="text" name="nombre" id="filtroNombre" placeholder="Buscar por nombre" class="form-control rounded-4 bg-body-secondary">
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                @if(count($usuarios))
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>País</th>
                                <th>Centro de salud</th>
                                <th>Supervisor</th>
                                <th>Pacientes asignados</th>
                                <th>Último ingreso</th>
                                <th>Alertas</th>
                                <th></th>
                            </tr>
                        </x-slot:header>

                        @foreach($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario['name'] }}</td>
                                <td>{{ $usuario->roles->first()->title }}</td>
                                <td>{{ $usuario->pais?->name }}</td>
                                <td>{{ $usuario->centroSalud?->name }}</td>
                                <td>{{ $usuario->supervisor?->name }}</td>
                                <td>{{ $usuario->pacientes?->count() }}</td>
                                <td>{{ $usuario->last_login?->format('d/m/Y H:i') }}</td>
                                <td></td>
                                <td>
                                    <a href="#" class="btn btn-link text-body">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @else
                    <div class="alert alert-secondary">
                        No se encontraron usuarios.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-modal id="modalUsuario" title="Nuevo usuario">
        <div class="row">
            <div class="cols-12 col-lg-6">
                <div class="row">
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="tipo" class="col-12 col-xl-4 col-form-label">Tipo usuario</label>
                            <div class="col-12 col-xl-8">
                                <select id="tipo" name="tipo" class="form-select">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}" @selected(old('tipo') === $rol->id)>{{ $rol->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="email" class="col-12 col-xl-4 col-form-label">Correo electrónico</label>
                            <div class="col-12 col-xl-8">
                                <input type="email" id="email" name="email" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="password" class="col-12 col-xl-4 col-form-label">Contraseña</label>
                            <div class="col-12 col-xl-8">
                                <input type="text" id="password" name="password" class="form-control" />
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
                                <input type="text" id="nombre" name="nombre" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="pais" class="col-12 col-xl-4 col-form-label">País</label>
                            <div class="col-12 col-xl-8">
                                <select id="pais" name="pais" class="form-select">
                                    <option value="">Seleccionar país</option>
                                    @foreach($paises as $pais)
                                        <option value="{{ $pais->id }}" @selected(old('pais') === $pais->id)>{{ $pais->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row mb-3">
                            <label for="centro_salud" class="col-12 col-xl-4 col-form-label">Centro de salud</label>
                            <div class="col-12 col-xl-8">
                                <select id="centro_salud" name="centro_salud" class="form-select">
                                    <option value="">Seleccionar centro</option>
                                    @foreach($centrosSalud as $centro)
                                        <option value="{{ $centro->id }}" @selected(old('centro_salud') === $centro->id)>{{ $centro->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <button type="button" class="btn btn-grey fw-semibold shadow" data-bs-dismiss="modal">
                Cancelar
            </button>
            <button type="button" class="btn btn-secondary text-white fw-semibold shadow" onclick="saveUsuario()">
                Guardar
            </button>
        </x-slot>
    </x-modal>

    <x-slot name="scripts">
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalUsuarioEl = document.getElementById('modalUsuario');
                const modalUsuario = new bootstrap.Modal(modalUsuarioEl);

                modalUsuarioEl.addEventListener('hidden.bs.modal', () => {
                    resetFormUsuario();
                });

                window.showModalUsuario = () => {
                    modalUsuario.show();
                };

                const campoTipo = document.getElementById('tipo');
                const campoEmail = document.getElementById('email');
                const campoPassword = document.getElementById('password');
                const campoNombre = document.getElementById('nombre');
                const campoPais = document.getElementById('pais');
                const campoCentroSalud = document.getElementById('centro_salud');

                const resetFormUsuario = () => {
                    campoTipo.value = '';
                    campoEmail.value = '';
                    campoPassword.value = '';
                    campoNombre.value = '';
                    campoPais.value = '';
                    campoCentroSalud.value = '';
                };

                window.saveUsuario = async () => {
                    const data = {
                        tipo: campoTipo.value,
                        email: campoEmail.value,
                        password: campoPassword.value,
                        nombre: campoNombre.value,
                        pais: campoPais.value,
                        centro_salud: campoCentroSalud.value,
                    };

                    try {
                        const response = await axios.post('{{ route('dashboard.usuarios.store') }}', data);

                        switch (response.status) {
                            case 200:
                                // Usuario creado exitosamente
                                modalUsuario.hide();
                                resetFormUsuario();
                                location.reload();
                                break;
                            case 422:
                                // Manejar errores de validación
                                console.log(response.data.errors);
                                break;
                            default:
                                console.error('Error al crear el usuario');
                        }
                    } catch (error) {
                        console.error('Error en la solicitud:', error);
                    }
                };
            });
        </script>
    </x-slot>
</x-layout>
