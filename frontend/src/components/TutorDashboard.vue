<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/axios'

/*
|--------------------------------------------------------------------------
| PROPS Y EVENTOS
|--------------------------------------------------------------------------
| No cambiamos la estructura del programa.
|--------------------------------------------------------------------------
*/

const props = defineProps({
    usuario: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits([
    'cerrar-sesion',
])


/*
|--------------------------------------------------------------------------
| ESTADO
|--------------------------------------------------------------------------
*/

const solicitudes = ref([])
const cargando = ref(true)
const error = ref('')

const terminoBusqueda = ref('')
const filtroEstado = ref('TODOS')

const solicitudSeleccionada = ref(null)

const actualizando = ref(false)
const mensajeExito = ref('')

const modalLogout = ref(false)


/*
|--------------------------------------------------------------------------
| DATOS DEL TUTOR
|--------------------------------------------------------------------------
*/

const nombreTutor = computed(() => {
    return (
        props.usuario?.name ||
        'Profesor Tutor'
    )
})

const primerNombre = computed(() => {
    return String(nombreTutor.value)
        .trim()
        .split(' ')
        .filter(Boolean)[0] || 'Tutor'
})

const iniciales = computed(() => {
    return String(nombreTutor.value)
        .trim()
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(
            palabra =>
                palabra.charAt(0)
                    .toUpperCase()
        )
        .join('') || 'PT'
})

const carreraTutor = computed(() => {
    return (
        props.usuario?.carrera?.nombre ||
        props.usuario?.carrera_nombre ||
        'Carrera asignada'
    )
})


/*
|--------------------------------------------------------------------------
| ESTADOS
|--------------------------------------------------------------------------
*/

function normalizarEstado(estado) {

    return String(
        estado ||
        'PENDIENTE'
    )
        .trim()
        .toUpperCase()
}


function textoEstado(estado) {

    const valor =
        normalizarEstado(estado)

    const estados = {
        PENDIENTE:
            'Pendiente',

        EN_REVISION:
            'En revisión',

        ACEPTADA:
            'Aceptada',

        RECHAZADA:
            'Rechazada',

        DOCUMENTACION_INCOMPLETA:
            'Documentación incompleta',
    }

    return estados[valor] || valor
}


function claseEstado(estado) {

    const valor =
        normalizarEstado(estado)

    const clases = {
        PENDIENTE:
            'warning',

        EN_REVISION:
            'info',

        ACEPTADA:
            'success',

        RECHAZADA:
            'danger',

        DOCUMENTACION_INCOMPLETA:
            'purple',
    }

    return clases[valor] || 'neutral'
}


/*
|--------------------------------------------------------------------------
| SOLICITUDES FILTRADAS
|--------------------------------------------------------------------------
*/

const solicitudesFiltradas = computed(() => {

    const busqueda =
        terminoBusqueda.value
            .trim()
            .toLowerCase()

    return solicitudes.value.filter(
        solicitud => {

            const alumno =
                obtenerAlumno(
                    solicitud
                )

            const estado =
                normalizarEstado(
                    solicitud.estado ||
                    solicitud.estatus
                )

            const coincideEstado =
                filtroEstado.value ===
                    'TODOS'
                ||
                estado ===
                    filtroEstado.value

            const texto = [
                alumno.name,
                alumno.matricula,
                alumno.grupo,
                solicitud.grupo,
                solicitud.id,
                solicitud.folio,
                solicitud
                    ?.convocatoria
                    ?.nombre,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()

            const coincideTexto =
                busqueda === ''
                ||
                texto.includes(busqueda)

            return (
                coincideEstado &&
                coincideTexto
            )
        }
    )
})


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

const estadisticas = computed(() => {

    const estados =
        solicitudes.value.map(
            solicitud =>
                normalizarEstado(
                    solicitud.estado ||
                    solicitud.estatus
                )
        )

    return {
        total:
            solicitudes.value.length,

        pendientes:
            estados.filter(
                e =>
                    e ===
                    'PENDIENTE'
            ).length,

        revision:
            estados.filter(
                e =>
                    e ===
                    'EN_REVISION'
            ).length,

        aceptadas:
            estados.filter(
                e =>
                    e ===
                    'ACEPTADA'
            ).length,

        rechazadas:
            estados.filter(
                e =>
                    e ===
                    'RECHAZADA'
            ).length,

        incompletas:
            estados.filter(
                e =>
                    e ===
                    'DOCUMENTACION_INCOMPLETA'
            ).length,
    }
})


/*
|--------------------------------------------------------------------------
| CARGAR SOLICITUDES
|--------------------------------------------------------------------------
*/

async function cargarSolicitudes() {

    cargando.value = true
    error.value = ''

    try {

        const { data } =
            await api.get(
                '/profesor/solicitudes'
            )

        solicitudes.value =
            data?.data ||
            data?.solicitudes ||
            (
                Array.isArray(data)
                    ? data
                    : []
            )

    } catch (err) {

        console.error(
            'Error cargando solicitudes del tutor:',
            err
        )

        error.value =
            err?.response?.data?.message ||
            'No fue posible cargar las solicitudes.'

    } finally {

        cargando.value = false
    }
}


/*
|--------------------------------------------------------------------------
| ALUMNO
|--------------------------------------------------------------------------
*/

function obtenerAlumno(solicitud) {

    return (
        solicitud?.user ||
        solicitud?.alumno ||
        {}
    )
}


function obtenerInicialAlumno(
    solicitud
) {

    const nombre =
        obtenerAlumno(
            solicitud
        )?.name ||
        'A'

    return String(nombre)
        .charAt(0)
        .toUpperCase()
}


function folioSolicitud(
    solicitud
) {

    if (!solicitud) {
        return 'Sin folio'
    }

    return (
        solicitud.folio ||
        `BEC-${String(
            solicitud.id || ''
        ).padStart(5, '0')}`
    )
}


function periodoSolicitud(
    solicitud
) {

    return (
        solicitud
            ?.convocatoria
            ?.periodo
            ?.nombre
        ||
        solicitud
            ?.periodo
            ?.nombre
        ||
        'Sin periodo'
    )
}


/*
|--------------------------------------------------------------------------
| DOCUMENTOS
|--------------------------------------------------------------------------
*/

function obtenerDocumentos(
    solicitud
) {

    const documentos =
        solicitud?.documentos ||
        solicitud?.documentos_solicitud ||
        []

    return Array.isArray(
        documentos
    )
        ? documentos
        : []
}


function urlDocumento(
    documento
) {

    const ruta =
        documento?.archivo_url ||
        documento?.url ||
        documento?.ruta_archivo

    if (!ruta) {
        return null
    }

    if (
        String(ruta).startsWith(
            'http'
        )
    ) {
        return ruta
    }

    if (
        String(ruta).startsWith('/')
    ) {
        return ruta
    }

    return (
        'http://127.0.0.1:8000/storage/' +
        ruta
    )
}


/*
|--------------------------------------------------------------------------
| MODAL DE SOLICITUD
|--------------------------------------------------------------------------
*/

function abrirSolicitud(
    solicitud
) {

    solicitudSeleccionada.value =
        solicitud
}


function cerrarSolicitud() {

    solicitudSeleccionada.value =
        null
}


/*
|--------------------------------------------------------------------------
| CAMBIAR ESTATUS
|--------------------------------------------------------------------------
|
| El tutor NO dicta aceptación final.
| Sólo puede apoyar con seguimiento.
|
| Utilizamos la ruta existente:
|
| PATCH /profesor/solicitudes/{id}/estatus
|--------------------------------------------------------------------------
*/

async function cambiarEstado(
    solicitud,
    nuevoEstado
) {

    if (!solicitud) {
        return
    }

    actualizando.value = true

    try {

        const { data } =
            await api.patch(
                `/profesor/solicitudes/${solicitud.id}/estatus`,
                {
                    estado:
                        nuevoEstado,
                }
            )

        const actualizado =
            data?.data ||
            data?.solicitud

        if (actualizado) {

            const index =
                solicitudes.value.findIndex(
                    s =>
                        s.id ===
                        actualizado.id
                )

            if (index !== -1) {

                solicitudes.value[
                    index
                ] = actualizado
            }

        } else {

            await cargarSolicitudes()
        }

        mensajeExito.value =
            data?.message ||
            'El estado de la solicitud fue actualizado.'

        if (
            solicitudSeleccionada.value
        ) {

            solicitudSeleccionada.value =
                actualizado ||
                solicitudes.value.find(
                    s =>
                        s.id ===
                        solicitud.id
                ) ||
                null
        }

        setTimeout(() => {
            mensajeExito.value = ''
        }, 3500)

    } catch (err) {

        console.error(
            'Error cambiando estado:',
            err
        )

        alert(
            err?.response?.data?.message ||
            'No fue posible actualizar el estado.'
        )

    } finally {

        actualizando.value = false
    }
}


/*
|--------------------------------------------------------------------------
| FECHAS
|--------------------------------------------------------------------------
*/

function formatearFecha(
    fecha
) {

    if (!fecha) {
        return 'Sin fecha'
    }

    try {

        const date =
            new Date(fecha)

        if (
            Number.isNaN(
                date.getTime()
            )
        ) {
            return fecha
        }

        return new Intl.DateTimeFormat(
            'es-MX',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }
        ).format(date)

    } catch {

        return fecha
    }
}


/*
|--------------------------------------------------------------------------
| CERRAR SESIÓN
|--------------------------------------------------------------------------
|
| No borramos token.
| No redirigimos.
|
| App.vue lo hace.
|--------------------------------------------------------------------------
*/

function confirmarCerrarSesion() {

    modalLogout.value = true
}


function cerrarSesion() {

    modalLogout.value = false

    emit('cerrar-sesion')
}


/*
|--------------------------------------------------------------------------
| MOUNT
|--------------------------------------------------------------------------
*/

onMounted(() => {

    cargarSolicitudes()
})
</script>


<template>

    <div class="tutor-dashboard">


        <!-- ============================================================
             HEADER
        ============================================================= -->

        <header class="topbar">

            <div class="topbar-inner">


                <!-- MARCA -->

                <div class="brand">

                    <div class="brand-mark">

                        <span class="up">
                            UP
                        </span>

                        <span class="t">
                            T
                        </span>

                        <span class="ex">
                            ex
                        </span>

                    </div>


                    <div class="brand-info">

                        <strong>
                            Sistema de Becas
                        </strong>

                        <span>
                            Seguimiento académico
                        </span>

                    </div>

                </div>


                <!-- NAVEGACIÓN -->

                <nav class="navigation">

                    <button
                        type="button"
                        class="
                            navigation-button
                            active
                        "
                    >
                        Resumen
                    </button>


                    <button
                        type="button"
                        class="
                            navigation-button
                        "
                        @click="
                            document
                                .getElementById(
                                    'lista-solicitudes'
                                )
                                ?.scrollIntoView({
                                    behavior:
                                        'smooth'
                                })
                        "
                    >
                        Alumnos
                    </button>

                </nav>


                <!-- PERFIL -->

                <div class="profile">

                    <div class="profile-data">

                        <strong>
                            {{ primerNombre }}
                        </strong>

                        <span>
                            Tutor académico
                        </span>

                    </div>


                    <div class="avatar">
                        {{ iniciales }}
                    </div>


                    <button
                        type="button"
                        class="logout"
                        title="Cerrar sesión"
                        @click="
                            confirmarCerrarSesion
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="18"
                            height="18"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M10 17l5-5-5-5"
                            />

                            <path
                                d="M15 12H3"
                            />

                            <path
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                            />

                        </svg>

                    </button>

                </div>

            </div>

        </header>


        <!-- ============================================================
             MAIN
        ============================================================= -->

        <main class="main">


            <!-- ÉXITO -->

            <div
                v-if="mensajeExito"
                class="success-message"
            >

                <div class="success-icon">
                    ✓
                </div>

                {{ mensajeExito }}

            </div>


            <!-- ========================================================
                 BIENVENIDA
            ========================================================= -->

            <section class="welcome">

                <div>

                    <span class="eyebrow">
                        PORTAL DEL TUTOR
                    </span>

                    <h1>
                        Hola, {{ primerNombre }}
                    </h1>

                    <p>
                        Consulta el seguimiento
                        académico de los alumnos
                        que solicitaron una beca.
                    </p>

                </div>


                <div class="career-box">

                    <span>
                        CARRERA ASIGNADA
                    </span>

                    <strong>
                        {{ carreraTutor }}
                    </strong>

                </div>

            </section>


            <!-- ========================================================
                 TARJETAS
            ========================================================= -->

            <section class="stats">

                <article class="stat">

                    <div
                        class="
                            stat-icon
                            neutral
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="21"
                            height="21"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />

                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            />

                            <path
                                d="M22 21v-2a4 4 0 0 0-3-3.87"
                            />

                        </svg>

                    </div>


                    <div>

                        <span>
                            Alumnos
                        </span>

                        <strong>
                            {{
                                estadisticas.total
                            }}
                        </strong>

                        <small>
                            Con solicitud
                        </small>

                    </div>

                </article>


                <article class="stat">

                    <div
                        class="
                            stat-icon
                            warning
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="21"
                            height="21"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            />

                            <path
                                d="M12 7v5l3 2"
                            />

                        </svg>

                    </div>


                    <div>

                        <span>
                            Pendientes
                        </span>

                        <strong>
                            {{
                                estadisticas
                                    .pendientes
                            }}
                        </strong>

                        <small>
                            Sin revisar
                        </small>

                    </div>

                </article>


                <article class="stat">

                    <div
                        class="
                            stat-icon
                            info
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="21"
                            height="21"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M4 19V5"
                            />

                            <path
                                d="M4 19h16"
                            />

                            <path
                                d="M8 15l3-4 3 2 4-6"
                            />

                        </svg>

                    </div>


                    <div>

                        <span>
                            Seguimiento
                        </span>

                        <strong>
                            {{
                                estadisticas
                                    .revision
                            }}
                        </strong>

                        <small>
                            En revisión
                        </small>

                    </div>

                </article>


                <article class="stat">

                    <div
                        class="
                            stat-icon
                            success
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="21"
                            height="21"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M5 12l4 4L19 6"
                            />

                        </svg>

                    </div>


                    <div>

                        <span>
                            Aceptadas
                        </span>

                        <strong>
                            {{
                                estadisticas
                                    .aceptadas
                            }}
                        </strong>

                        <small>
                            Dictaminadas
                        </small>

                    </div>

                </article>


                <article class="stat">

                    <div
                        class="
                            stat-icon
                            danger
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="21"
                            height="21"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M6 6l12 12"
                            />

                            <path
                                d="M18 6L6 18"
                            />

                        </svg>

                    </div>


                    <div>

                        <span>
                            Rechazadas
                        </span>

                        <strong>
                            {{
                                estadisticas
                                    .rechazadas
                            }}
                        </strong>

                        <small>
                            No aprobadas
                        </small>

                    </div>

                </article>

            </section>


            <!-- ========================================================
                 INFORMACIÓN
            ========================================================= -->

            <section class="notice">

                <div class="notice-icon">

                    <svg
                        viewBox="0 0 24 24"
                        width="22"
                        height="22"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="10"
                        />

                        <path
                            d="M12 16v-4"
                        />

                        <path
                            d="M12 8h.01"
                        />

                    </svg>

                </div>


                <div>

                    <strong>
                        Seguimiento del tutor
                    </strong>

                    <p>
                        Desde este panel puedes
                        consultar alumnos,
                        documentación y marcar
                        solicitudes para seguimiento.
                        El dictamen final corresponde
                        al área administrativa.
                    </p>

                </div>

            </section>


            <!-- ========================================================
                 SOLICITUDES
            ========================================================= -->

            <section
                id="lista-solicitudes"
                class="requests"
            >


                <!-- CABECERA -->

                <div class="requests-header">

                    <div>

                        <span class="eyebrow">
                            SEGUIMIENTO
                        </span>

                        <h2>
                            Solicitudes de alumnos
                        </h2>

                        <p>
                            {{
                                solicitudesFiltradas
                                    .length
                            }}
                            resultado(s)
                        </p>

                    </div>


                    <button
                        type="button"
                        class="refresh"
                        @click="
                            cargarSolicitudes
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="16"
                            height="16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M20 11a8 8 0 1 0-2 5.3"
                            />

                            <path
                                d="M20 4v7h-7"
                            />

                        </svg>

                        Actualizar

                    </button>

                </div>


                <!-- FILTROS -->

                <div class="filters">

                    <div class="search">

                        <svg
                            viewBox="0 0 24 24"
                            width="17"
                            height="17"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                            />

                            <path
                                d="M20 20l-4-4"
                            />

                        </svg>


                        <input
                            v-model="
                                terminoBusqueda
                            "
                            type="text"
                            placeholder="
                                Buscar alumno,
                                matrícula o folio
                            "
                        />

                    </div>


                    <select
                        v-model="
                            filtroEstado
                        "
                    >

                        <option value="TODOS">
                            Todos los estados
                        </option>

                        <option
                            value="PENDIENTE"
                        >
                            Pendiente
                        </option>

                        <option
                            value="EN_REVISION"
                        >
                            En revisión
                        </option>

                        <option
                            value="ACEPTADA"
                        >
                            Aceptada
                        </option>

                        <option
                            value="RECHAZADA"
                        >
                            Rechazada
                        </option>

                        <option
                            value="
                                DOCUMENTACION_INCOMPLETA
                            "
                        >
                            Documentación incompleta
                        </option>

                    </select>

                </div>


                <!-- LOADING -->

                <div
                    v-if="cargando"
                    class="state"
                >

                    <div class="spinner"></div>

                    <strong>
                        Cargando alumnos
                    </strong>

                    <span>
                        Consultando información...
                    </span>

                </div>


                <!-- ERROR -->

                <div
                    v-else-if="error"
                    class="state"
                >

                    <div
                        class="
                            state-icon
                            error
                        "
                    >
                        !
                    </div>

                    <strong>
                        No pudimos cargar
                        la información
                    </strong>

                    <span>
                        {{ error }}
                    </span>


                    <button
                        type="button"
                        class="primary"
                        @click="
                            cargarSolicitudes
                        "
                    >
                        Intentar nuevamente
                    </button>

                </div>


                <!-- SIN DATOS -->

                <div
                    v-else-if="
                        solicitudesFiltradas
                            .length === 0
                    "
                    class="state"
                >

                    <div class="state-icon">

                        <svg
                            viewBox="0 0 24 24"
                            width="23"
                            height="23"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                            />

                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            />

                        </svg>

                    </div>

                    <strong>
                        No hay solicitudes
                    </strong>

                    <span>
                        No encontramos alumnos
                        con estos filtros.
                    </span>

                </div>


                <!-- TABLA -->

                <div
                    v-else
                    class="table-wrapper"
                >

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Alumno
                                </th>

                                <th>
                                    Matrícula
                                </th>

                                <th>
                                    Grupo
                                </th>

                                <th>
                                    Periodo
                                </th>

                                <th>
                                    Documentos
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th
                                    class="
                                        right
                                    "
                                >
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <tr
                                v-for="
                                    solicitud
                                    in
                                    solicitudesFiltradas
                                "
                                :key="
                                    solicitud.id
                                "
                            >

                                <td>

                                    <div
                                        class="
                                            student
                                        "
                                    >

                                        <div
                                            class="
                                                student-avatar
                                            "
                                        >
                                            {{
                                                obtenerInicialAlumno(
                                                    solicitud
                                                )
                                            }}
                                        </div>


                                        <div>

                                            <strong>
                                                {{
                                                    obtenerAlumno(
                                                        solicitud
                                                    )
                                                        .name
                                                    ||
                                                    'Alumno'
                                                }}
                                            </strong>

                                            <span>
                                                {{
                                                    folioSolicitud(
                                                        solicitud
                                                    )
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    {{
                                        obtenerAlumno(
                                            solicitud
                                        )
                                            .matricula
                                        ||
                                        '—'
                                    }}

                                </td>


                                <td>

                                    {{
                                        solicitud
                                            .grupo
                                        ||
                                        obtenerAlumno(
                                            solicitud
                                        )
                                            .grupo
                                        ||
                                        '—'
                                    }}

                                </td>


                                <td>

                                    {{
                                        periodoSolicitud(
                                            solicitud
                                        )
                                    }}

                                </td>


                                <td>

                                    <div
                                        class="
                                            document-count
                                        "
                                    >

                                        <strong>
                                            {{
                                                obtenerDocumentos(
                                                    solicitud
                                                ).length
                                            }}
                                        </strong>

                                        <span>
                                            archivo(s)
                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="
                                            status
                                        "
                                        :class="
                                            claseEstado(
                                                solicitud.estado
                                                ||
                                                solicitud.estatus
                                            )
                                        "
                                    >
                                        {{
                                            textoEstado(
                                                solicitud.estado
                                                ||
                                                solicitud.estatus
                                            )
                                        }}
                                    </span>

                                </td>


                                <td class="right">

                                    <button
                                        type="button"
                                        class="
                                            view-button
                                        "
                                        @click="
                                            abrirSolicitud(
                                                solicitud
                                            )
                                        "
                                    >
                                        Ver seguimiento
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>


        <!-- ============================================================
             MODAL SOLICITUD
        ============================================================= -->

        <div
            v-if="
                solicitudSeleccionada
            "
            class="modal-overlay"
            @click.self="
                cerrarSolicitud
            "
        >

            <div class="modal">

                <button
                    type="button"
                    class="modal-close"
                    @click="
                        cerrarSolicitud
                    "
                >
                    ×
                </button>


                <div class="modal-title">

                    <span class="eyebrow">
                        SEGUIMIENTO ACADÉMICO
                    </span>

                    <h2>
                        {{
                            obtenerAlumno(
                                solicitudSeleccionada
                            ).name
                            ||
                            'Alumno'
                        }}
                    </h2>

                    <p>
                        {{
                            folioSolicitud(
                                solicitudSeleccionada
                            )
                        }}
                    </p>

                </div>


                <!-- DATOS -->

                <div class="detail-grid">

                    <div>

                        <span>
                            Matrícula
                        </span>

                        <strong>
                            {{
                                obtenerAlumno(
                                    solicitudSeleccionada
                                ).matricula
                                ||
                                'Sin matrícula'
                            }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Grupo
                        </span>

                        <strong>
                            {{
                                solicitudSeleccionada
                                    .grupo
                                ||
                                obtenerAlumno(
                                    solicitudSeleccionada
                                ).grupo
                                ||
                                'Sin grupo'
                            }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Periodo
                        </span>

                        <strong>
                            {{
                                periodoSolicitud(
                                    solicitudSeleccionada
                                )
                            }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Registro
                        </span>

                        <strong>
                            {{
                                formatearFecha(
                                    solicitudSeleccionada
                                        .created_at
                                )
                            }}
                        </strong>

                    </div>

                </div>


                <!-- ESTADO -->

                <section class="modal-section">

                    <div class="section-header">

                        <span>
                            ESTADO DE SOLICITUD
                        </span>


                        <span
                            class="status"
                            :class="
                                claseEstado(
                                    solicitudSeleccionada
                                        .estado
                                    ||
                                    solicitudSeleccionada
                                        .estatus
                                )
                            "
                        >
                            {{
                                textoEstado(
                                    solicitudSeleccionada
                                        .estado
                                    ||
                                    solicitudSeleccionada
                                        .estatus
                                )
                            }}
                        </span>

                    </div>


                    <p class="section-description">
                        El tutor puede marcar
                        la solicitud como
                        "En revisión" para indicar
                        que está realizando
                        seguimiento académico.
                    </p>


                    <button
                        v-if="
                            normalizarEstado(
                                solicitudSeleccionada
                                    .estado
                                ||
                                solicitudSeleccionada
                                    .estatus
                            )
                            ===
                            'PENDIENTE'
                        "
                        type="button"
                        class="
                            review-status-button
                        "
                        :disabled="
                            actualizando
                        "
                        @click="
                            cambiarEstado(
                                solicitudSeleccionada,
                                'EN_REVISION'
                            )
                        "
                    >
                        {{
                            actualizando
                                ? 'Actualizando...'
                                : 'Marcar como En revisión'
                        }}
                    </button>

                </section>


                <!-- DOCUMENTOS -->

                <section class="modal-section">

                    <div class="section-header">

                        <span>
                            DOCUMENTOS ADJUNTOS
                        </span>

                        <small>
                            {{
                                obtenerDocumentos(
                                    solicitudSeleccionada
                                ).length
                            }}
                        </small>

                    </div>


                    <div
                        v-if="
                            obtenerDocumentos(
                                solicitudSeleccionada
                            ).length === 0
                        "
                        class="no-docs"
                    >
                        Este alumno todavía
                        no tiene documentos
                        registrados.
                    </div>


                    <div
                        v-else
                        class="documents"
                    >

                        <div
                            v-for="
                                documento
                                in
                                obtenerDocumentos(
                                    solicitudSeleccionada
                                )
                            "
                            :key="
                                documento.id
                                ||
                                documento.ruta_archivo
                            "
                            class="document"
                        >

                            <div class="pdf">
                                PDF
                            </div>


                            <div
                                class="
                                    document-info
                                "
                            >

                                <strong>
                                    {{
                                        documento
                                            .nombre_original
                                        ||
                                        documento
                                            .tipo_documento
                                        ||
                                        'Documento'
                                    }}
                                </strong>

                                <span>
                                    {{
                                        documento
                                            .tipo_documento
                                        ||
                                        'Archivo del alumno'
                                    }}
                                </span>

                            </div>


                            <a
                                v-if="
                                    urlDocumento(
                                        documento
                                    )
                                "
                                :href="
                                    urlDocumento(
                                        documento
                                    )
                                "
                                target="_blank"
                                rel="
                                    noopener
                                    noreferrer
                                "
                                class="
                                    open-document
                                "
                            >
                                Abrir
                            </a>

                        </div>

                    </div>

                </section>


                <!-- OBSERVACIONES ADMIN -->

                <section
                    v-if="
                        solicitudSeleccionada
                            .observaciones
                        ||
                        solicitudSeleccionada
                            .comentario_revision
                    "
                    class="modal-section"
                >

                    <span
                        class="
                            section-label
                        "
                    >
                        OBSERVACIONES
                    </span>

                    <div
                        class="
                            observation
                        "
                    >
                        {{
                            solicitudSeleccionada
                                .observaciones
                            ||
                            solicitudSeleccionada
                                .comentario_revision
                        }}
                    </div>

                </section>


                <button
                    type="button"
                    class="
                        primary
                        full
                    "
                    @click="
                        cerrarSolicitud
                    "
                >
                    Cerrar
                </button>

            </div>

        </div>


        <!-- ============================================================
             LOGOUT
        ============================================================= -->

        <div
            v-if="modalLogout"
            class="modal-overlay"
            @click.self="
                modalLogout = false
            "
        >

            <div
                class="
                    modal
                    logout-modal
                "
            >

                <div
                    class="
                        logout-modal-icon
                    "
                >

                    <svg
                        viewBox="0 0 24 24"
                        width="25"
                        height="25"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <path
                            d="M10 17l5-5-5-5"
                        />

                        <path
                            d="M15 12H3"
                        />

                        <path
                            d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                        />

                    </svg>

                </div>


                <h2>
                    ¿Cerrar sesión?
                </h2>

                <p>
                    Volverás a la pantalla
                    principal del sistema.
                </p>


                <div
                    class="
                        logout-actions
                    "
                >

                    <button
                        type="button"
                        class="secondary"
                        @click="
                            modalLogout = false
                        "
                    >
                        Cancelar
                    </button>


                    <button
                        type="button"
                        class="
                            primary
                            close-session
                        "
                        @click="
                            cerrarSesion
                        "
                    >
                        Cerrar sesión
                    </button>

                </div>

            </div>

        </div>

    </div>

</template>


<style scoped>

/* ================================================================
   BASE
================================================================ */

* {
    box-sizing: border-box;
}

.tutor-dashboard {
    min-height: 100vh;
    background:
        linear-gradient(
            180deg,
            #f5f7f6 0%,
            #fcfdfc 55%,
            #f4f6f5 100%
        );
    color: #272e2a;
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}


/* ================================================================
   TOPBAR
================================================================ */

.topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    border-bottom:
        1px solid #e4e8e5;
    background:
        rgba(
            255,
            255,
            255,
            .95
        );
    backdrop-filter:
        blur(18px);
}

.topbar-inner {
    width:
        min(
            1200px,
            calc(100% - 40px)
        );
    height: 76px;
    margin: auto;
    display: flex;
    align-items: center;
    gap: 34px;
}


/* BRAND */

.brand {
    min-width: 260px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-mark {
    font-size: 21px;
    font-weight: 900;
    letter-spacing: -2px;
}

.brand-mark .up {
    color: #247548;
}

.brand-mark .t {
    color: #bb1f38;
}

.brand-mark .ex {
    color: #727975;
}

.brand-info {
    padding-left: 12px;
    border-left:
        1px solid #e2e5e3;
    display: flex;
    flex-direction: column;
}

.brand-info strong {
    font-size: 12px;
}

.brand-info span {
    margin-top: 3px;
    color: #999f9b;
    font-size: 9px;
}


/* NAVIGATION */

.navigation {
    flex: 1;
    display: flex;
    justify-content: center;
    gap: 6px;
}

.navigation-button {
    padding: 10px 14px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #747b77;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.navigation-button:hover,
.navigation-button.active {
    background: #edf5f0;
    color: #247548;
}


/* PROFILE */

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-data {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.profile-data strong {
    font-size: 11px;
}

.profile-data span {
    color: #999f9b;
    font-size: 9px;
}

.avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background:
        linear-gradient(
            145deg,
            #247548,
            #409063
        );
    color: #fff;
    font-size: 11px;
    font-weight: 850;
}

.logout {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border:
        1px solid #e2e5e3;
    border-radius: 10px;
    background: #fff;
    color: #737a76;
    cursor: pointer;
}

.logout:hover {
    background: #fff5f7;
    border-color: #ecd4db;
    color: #7a1c33;
}


/* ================================================================
   MAIN
================================================================ */

.main {
    width:
        min(
            1200px,
            calc(100% - 40px)
        );
    margin: auto;
    padding:
        44px
        0
        80px;
}

.success-message {
    margin-bottom: 18px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    gap: 9px;
    border:
        1px solid #d9eadf;
    border-radius: 13px;
    background: #edf7f1;
    color: #256541;
    font-size: 10px;
    font-weight: 750;
}

.success-icon {
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #247548;
    color: #fff;
}


/* ================================================================
   WELCOME
================================================================ */

.welcome {
    margin-bottom: 25px;
    display: flex;
    align-items: flex-end;
    justify-content:
        space-between;
    gap: 25px;
}

.eyebrow {
    display: block;
    color: #8d9490;
    font-size: 8px;
    font-weight: 850;
    letter-spacing: .16em;
}

.welcome h1 {
    margin:
        7px
        0
        5px;
    font-size:
        clamp(
            28px,
            4vw,
            40px
        );
    line-height: 1.05;
    letter-spacing: -.04em;
}

.welcome p {
    margin: 0;
    color: #7c847f;
    font-size: 13px;
}

.career-box {
    min-width: 270px;
    padding: 14px 17px;
    border:
        1px solid #e2e6e3;
    border-radius: 15px;
    background: #fff;
    box-shadow:
        0 6px 18px
        rgba(
            30,
            40,
            34,
            .04
        );
}

.career-box span {
    display: block;
    color: #9ca29f;
    font-size: 8px;
    font-weight: 850;
    letter-spacing: .11em;
}

.career-box strong {
    display: block;
    margin-top: 5px;
    color: #39413d;
    font-size: 11px;
}


/* ================================================================
   STATS
================================================================ */

.stats {
    margin-bottom: 18px;
    display: grid;
    grid-template-columns:
        repeat(
            5,
            1fr
        );
    gap: 13px;
}

.stat {
    min-height: 110px;
    padding: 17px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    border:
        1px solid #e4e7e5;
    border-radius: 17px;
    background: #fff;
    box-shadow:
        0 8px 24px
        rgba(
            27,
            39,
            32,
            .04
        );
}

.stat-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 12px;
}

.stat-icon.neutral {
    color: #4d5551;
    background: #f0f2f1;
}

.stat-icon.warning {
    color: #956516;
    background: #fff5da;
}

.stat-icon.info {
    color: #356b91;
    background: #eaf3fa;
}

.stat-icon.success {
    color: #247548;
    background: #eaf5ee;
}

.stat-icon.danger {
    color: #85243d;
    background: #faedf1;
}

.stat > div:last-child {
    display: flex;
    flex-direction: column;
}

.stat span {
    color: #969c98;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
}

.stat strong {
    margin-top: 2px;
    font-size: 24px;
    line-height: 1;
}

.stat small {
    margin-top: 5px;
    color: #a3a8a5;
    font-size: 8px;
}


/* ================================================================
   NOTICE
================================================================ */

.notice {
    margin-bottom: 22px;
    padding: 17px 19px;
    display: flex;
    gap: 13px;
    align-items: center;
    border:
        1px solid #dde8e1;
    border-radius: 15px;
    background: #f5faf7;
}

.notice-icon {
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: #e6f2ea;
    color: #247548;
}

.notice strong {
    color: #3e4742;
    font-size: 10px;
}

.notice p {
    margin:
        4px
        0
        0;
    max-width: 760px;
    color: #838b86;
    font-size: 9px;
    line-height: 1.5;
}


/* ================================================================
   REQUESTS
================================================================ */

.requests {
    overflow: hidden;
    border:
        1px solid #e3e7e4;
    border-radius: 21px;
    background: #fff;
    box-shadow:
        0 13px 38px
        rgba(
            28,
            40,
            33,
            .05
        );
}

.requests-header {
    padding:
        24px
        25px
        18px;
    display: flex;
    align-items: center;
    justify-content:
        space-between;
    border-bottom:
        1px solid #edf0ee;
}

.requests-header h2 {
    margin:
        6px
        0
        3px;
    font-size: 19px;
}

.requests-header p {
    margin: 0;
    color: #989e9b;
    font-size: 9px;
}

.refresh {
    height: 37px;
    padding: 0 13px;
    display: flex;
    gap: 7px;
    align-items: center;
    border:
        1px solid #e1e5e2;
    border-radius: 10px;
    background: #fff;
    color: #626a66;
    font-size: 9px;
    font-weight: 800;
    cursor: pointer;
}


/* FILTERS */

.filters {
    padding: 15px 24px;
    display: grid;
    grid-template-columns:
        1fr 235px;
    gap: 11px;
    background: #fbfcfb;
    border-bottom:
        1px solid #edf0ee;
}

.search {
    position: relative;
    display: flex;
    align-items: center;
}

.search svg {
    position: absolute;
    left: 13px;
    color: #9da39f;
}

.search input,
.filters select {
    height: 40px;
    border:
        1px solid #e1e5e2;
    border-radius: 11px;
    background: #fff;
    color: #505753;
    outline: none;
    font-size: 10px;
}

.search input {
    width: 100%;
    padding:
        0 13px
        0 40px;
}

.filters select {
    padding:
        0 12px;
}


/* TABLE */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

thead th {
    padding: 12px 17px;
    background: #fafbfa;
    color: #969c98;
    border-bottom:
        1px solid #ecefec;
    text-align: left;
    font-size: 8px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .08em;
}

tbody td {
    padding: 14px 17px;
    border-bottom:
        1px solid #f0f2f1;
    color: #59605c;
    font-size: 10px;
}

tbody tr:last-child td {
    border-bottom: 0;
}

tbody tr:hover {
    background: #fcfdfc;
}

.right {
    text-align: right;
}

.student {
    display: flex;
    align-items: center;
    gap: 10px;
}

.student-avatar {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: #edf5f0;
    color: #247548;
    font-size: 11px;
    font-weight: 850;
}

.student > div:last-child {
    display: flex;
    flex-direction: column;
}

.student strong {
    color: #333a36;
    font-size: 10px;
}

.student span {
    margin-top: 3px;
    color: #a0a5a2;
    font-size: 8px;
}

.document-count {
    display: flex;
    flex-direction: column;
}

.document-count strong {
    color: #434b47;
    font-size: 10px;
}

.document-count span {
    color: #9fa5a1;
    font-size: 8px;
}

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 9px;
    border-radius: 99px;
    font-size: 8px;
    font-weight: 800;
    white-space: nowrap;
}

.status.warning {
    color: #90611b;
    background: #fff4d7;
}

.status.info {
    color: #32688f;
    background: #e9f3fa;
}

.status.success {
    color: #216841;
    background: #e8f5ed;
}

.status.danger {
    color: #86253d;
    background: #faeaf0;
}

.status.purple {
    color: #6e4992;
    background: #f2ebf8;
}

.status.neutral {
    color: #707773;
    background: #f0f2f1;
}

.view-button {
    height: 32px;
    padding: 0 12px;
    border:
        1px solid #dbe5de;
    border-radius: 9px;
    background: #f4f8f5;
    color: #267348;
    font-size: 8px;
    font-weight: 850;
    cursor: pointer;
}


/* ================================================================
   STATE
================================================================ */

.state {
    min-height: 310px;
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.spinner {
    width: 35px;
    height: 35px;
    border:
        3px solid #e8edea;
    border-top-color: #247548;
    border-radius: 50%;
    animation:
        spin .8s linear
        infinite;
}

@keyframes spin {
    to {
        transform:
            rotate(360deg);
    }
}

.state strong {
    margin-top: 13px;
    color: #454d49;
    font-size: 12px;
}

.state span {
    margin-top: 5px;
    color: #969d99;
    font-size: 9px;
}

.state-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    color: #76807a;
    background: #f0f3f1;
}

.state-icon.error {
    color: #84253c;
    background: #faeaf0;
    font-weight: 850;
}


/* ================================================================
   MODAL
================================================================ */

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    padding: 20px;
    display: grid;
    place-items: center;
    background:
        rgba(
            19,
            27,
            22,
            .45
        );
    backdrop-filter:
        blur(5px);
}

.modal {
    position: relative;
    width:
        min(
            620px,
            100%
        );
    max-height: 90vh;
    overflow-y: auto;
    padding: 29px;
    border-radius: 22px;
    background: #fff;
    box-shadow:
        0 30px 85px
        rgba(
            18,
            27,
            22,
            .23
        );
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 16px;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: #f4f6f5;
    color: #818884;
    cursor: pointer;
    font-size: 19px;
}

.modal-title h2 {
    margin:
        7px
        0
        3px;
    font-size: 21px;
}

.modal-title p {
    margin: 0;
    color: #9ba19e;
    font-size: 9px;
}

.detail-grid {
    margin-top: 21px;
    display: grid;
    grid-template-columns:
        repeat(
            4,
            1fr
        );
    gap: 9px;
}

.detail-grid > div {
    padding: 12px;
    border-radius: 11px;
    background: #f6f8f7;
}

.detail-grid span {
    display: block;
    color: #9da39f;
    font-size: 7px;
    font-weight: 850;
    text-transform: uppercase;
}

.detail-grid strong {
    display: block;
    margin-top: 4px;
    color: #454d48;
    font-size: 9px;
}

.modal-section {
    margin-top: 20px;
    padding-top: 18px;
    border-top:
        1px solid #ecefed;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content:
        space-between;
}

.section-header > span:first-child,
.section-label {
    color: #8b928e;
    font-size: 8px;
    font-weight: 850;
    letter-spacing: .12em;
}

.section-header small {
    min-width: 23px;
    height: 23px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    background: #edf5f0;
    color: #267348;
    font-size: 8px;
    font-weight: 850;
}

.section-description {
    margin:
        12px
        0;
    color: #878e8a;
    font-size: 9px;
    line-height: 1.55;
}

.review-status-button {
    min-height: 38px;
    padding: 0 14px;
    border: 0;
    border-radius: 10px;
    background: #eaf3fa;
    color: #32688f;
    font-size: 8px;
    font-weight: 850;
    cursor: pointer;
}

.documents {
    margin-top: 10px;
    display: grid;
    gap: 7px;
}

.document {
    padding: 10px 11px;
    display: flex;
    align-items: center;
    gap: 10px;
    border:
        1px solid #e8ebe9;
    border-radius: 11px;
}

.pdf {
    width: 35px;
    height: 35px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 9px;
    background: #faedf1;
    color: #81263d;
    font-size: 8px;
    font-weight: 850;
}

.document-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.document-info strong {
    color: #414944;
    font-size: 9px;
}

.document-info span {
    margin-top: 3px;
    color: #9ba19e;
    font-size: 8px;
}

.open-document {
    padding: 7px 10px;
    border-radius: 8px;
    background: #edf5f0;
    color: #267348;
    font-size: 8px;
    font-weight: 850;
    text-decoration: none;
}

.no-docs {
    margin-top: 10px;
    padding: 15px;
    border-radius: 11px;
    background: #f7f8f8;
    color: #999f9c;
    font-size: 9px;
    text-align: center;
}

.observation {
    margin-top: 9px;
    padding: 14px;
    border-radius: 11px;
    background: #f7f8f8;
    color: #555d58;
    font-size: 9px;
    line-height: 1.55;
}


/* ================================================================
   BUTTONS
================================================================ */

.primary,
.secondary {
    min-height: 40px;
    padding: 0 15px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 850;
    cursor: pointer;
}

.primary {
    border: 0;
    background: #247548;
    color: #fff;
}

.primary.full {
    width: 100%;
    margin-top: 22px;
}

.secondary {
    border:
        1px solid #e0e4e1;
    background: #fff;
    color: #666e69;
}


/* ================================================================
   LOGOUT
================================================================ */

.logout-modal {
    width:
        min(
            400px,
            100%
        );
    text-align: center;
}

.logout-modal-icon {
    width: 50px;
    height: 50px;
    margin:
        0
        auto
        13px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: #84253d;
    background: #faedf1;
}

.logout-modal h2 {
    margin:
        0
        0
        6px;
    font-size: 18px;
}

.logout-modal p {
    margin: 0;
    color: #8a918d;
    font-size: 10px;
}

.logout-actions {
    margin-top: 22px;
    display: flex;
    gap: 9px;
}

.logout-actions button {
    flex: 1;
}

.close-session {
    background: #7a1c33;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (
    max-width: 1000px
) {

    .stats {
        grid-template-columns:
            repeat(
                3,
                1fr
            );
    }

}

@media (
    max-width: 800px
) {

    .navigation {
        display: none;
    }

    .brand {
        flex: 1;
    }

    .profile-data {
        display: none;
    }

    .welcome {
        flex-direction: column;
        align-items: flex-start;
    }

    .career-box {
        width: 100%;
    }

    .stats {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

    .filters {
        grid-template-columns:
            1fr;
    }

    .detail-grid {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

}

@media (
    max-width: 520px
) {

    .topbar-inner {
        width:
            calc(
                100% - 24px
            );
        height: 68px;
    }

    .brand-info span {
        display: none;
    }

    .main {
        width:
            calc(
                100% - 24px
            );
        padding-top: 30px;
    }

    .stats {
        gap: 9px;
    }

    .stat {
        min-height: 100px;
        padding: 13px;
        gap: 9px;
    }

    .stat-icon {
        width: 35px;
        height: 35px;
    }

    .stat strong {
        font-size: 20px;
    }

    .requests-header {
        padding:
            19px
            17px
            15px;
    }

    .filters {
        padding:
            12px
            16px;
    }

    .modal {
        padding:
            25px
            18px;
    }

    .detail-grid {
        grid-template-columns:
            1fr
            1fr;
    }

}

</style>