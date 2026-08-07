<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/axios'

/*
|--------------------------------------------------------------------------
| ESTRUCTURA EXISTENTE
|--------------------------------------------------------------------------
| App.vue ya manda:
|
| :usuario="usuarioActivo"
| @cerrar-sesion="cerrarSesion"
|
| No cambiamos esa estructura.
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

const filtroEstatus = ref('TODOS')
const terminoBusqueda = ref('')

const solicitudSeleccionada = ref(null)
const observaciones = ref('')
const guardandoDictamen = ref(false)

const modalLogout = ref(false)
const mensajeExito = ref('')


/*
|--------------------------------------------------------------------------
| DATOS DEL JEFE
|--------------------------------------------------------------------------
*/

const nombreJefe = computed(() => {
    return (
        props.usuario?.name ||
        'Jefe de Carrera'
    )
})

const primerNombre = computed(() => {
    return String(nombreJefe.value)
        .trim()
        .split(' ')
        .filter(Boolean)[0] || 'Jefe'
})

const iniciales = computed(() => {
    return String(nombreJefe.value)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(nombre =>
            nombre.charAt(0).toUpperCase()
        )
        .join('') || 'JC'
})

const carreraJefe = computed(() => {
    return (
        props.usuario?.carrera?.nombre ||
        props.usuario?.carrera_nombre ||
        'Carrera asignada'
    )
})


/*
|--------------------------------------------------------------------------
| NORMALIZAR SOLICITUDES
|--------------------------------------------------------------------------
*/

function normalizarEstado(estado) {

    return String(
        estado || 'PENDIENTE'
    )
        .trim()
        .toUpperCase()
}

function textoEstado(estado) {

    const valor =
        normalizarEstado(estado)

    const estados = {
        PENDIENTE: 'Pendiente',
        EN_REVISION: 'En revisión',
        ACEPTADA: 'Aceptada',
        RECHAZADA: 'Rechazada',
        DOCUMENTACION_INCOMPLETA:
            'Documentación incompleta',
    }

    return estados[valor] || valor
}

function claseEstado(estado) {

    const valor =
        normalizarEstado(estado)

    const clases = {
        PENDIENTE: 'warning',
        EN_REVISION: 'info',
        ACEPTADA: 'success',
        RECHAZADA: 'danger',
        DOCUMENTACION_INCOMPLETA:
            'purple',
    }

    return clases[valor] || 'neutral'
}


/*
|--------------------------------------------------------------------------
| LISTADO FILTRADO
|--------------------------------------------------------------------------
*/

const solicitudesFiltradas = computed(() => {

    const termino =
        terminoBusqueda.value
            .trim()
            .toLowerCase()

    return solicitudes.value.filter(
        solicitud => {

            const estado =
                normalizarEstado(
                    solicitud.estado ||
                    solicitud.estatus
                )

            const coincideEstado =
                filtroEstatus.value === 'TODOS'
                ||
                estado === filtroEstatus.value


            const alumno =
                solicitud.user ||
                solicitud.alumno ||
                {}

            const textoBusqueda = [
                alumno.name,
                alumno.matricula,
                solicitud.grupo,
                solicitud.id,
                solicitud.convocatoria?.nombre,
                solicitud.convocatoria
                    ?.periodo?.nombre,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()


            const coincideBusqueda =
                !termino
                ||
                textoBusqueda.includes(
                    termino
                )


            return (
                coincideEstado &&
                coincideBusqueda
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
                e => e === 'PENDIENTE'
            ).length,

        revision:
            estados.filter(
                e => e === 'EN_REVISION'
            ).length,

        aceptadas:
            estados.filter(
                e => e === 'ACEPTADA'
            ).length,

        rechazadas:
            estados.filter(
                e => e === 'RECHAZADA'
            ).length,

        incompletas:
            estados.filter(
                e =>
                    e ===
                    'DOCUMENTACION_INCOMPLETA'
            ).length,
    }
})


const porcentajeAtendidas = computed(() => {

    if (
        estadisticas.value.total === 0
    ) {
        return 0
    }

    const atendidas =
        estadisticas.value.aceptadas +
        estadisticas.value.rechazadas

    return Math.round(
        (
            atendidas /
            estadisticas.value.total
        ) * 100
    )
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
                '/admin/solicitudes'
            )

        /*
        |--------------------------------------------------------------------------
        | Tu backend actual devuelve:
        |
        | {
        |   status: "success",
        |   data: [...]
        | }
        |--------------------------------------------------------------------------
        */

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
            'Error cargando solicitudes:',
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
| MODAL DE REVISIÓN
|--------------------------------------------------------------------------
*/

function abrirRevision(solicitud) {

    solicitudSeleccionada.value =
        solicitud

    observaciones.value =
        solicitud.observaciones ||
        solicitud.comentario_revision ||
        ''
}

function cerrarRevision() {

    solicitudSeleccionada.value =
        null

    observaciones.value = ''
}


/*
|--------------------------------------------------------------------------
| GUARDAR DICTAMEN
|--------------------------------------------------------------------------
|
| Tu backend actual espera:
|
| estado
| observaciones
|
| en:
|
| PATCH /admin/solicitudes/{id}/dictamen
|--------------------------------------------------------------------------
*/

async function guardarDictamen(
    nuevoEstado
) {

    if (
        !solicitudSeleccionada.value
    ) {
        return
    }

    if (
        nuevoEstado === 'RECHAZADA' &&
        !observaciones.value.trim()
    ) {

        alert(
            'Escribe una observación para indicar por qué se rechaza la solicitud.'
        )

        return
    }

    guardandoDictamen.value = true

    try {

        const { data } =
            await api.patch(
                `/admin/solicitudes/${solicitudSeleccionada.value.id}/dictamen`,
                {
                    estado:
                        nuevoEstado,

                    observaciones:
                        observaciones.value.trim()
                        || null,
                }
            )

        /*
        |--------------------------------------------------------------------------
        | Actualizamos localmente.
        |--------------------------------------------------------------------------
        */

        const actualizado =
            data?.data

        if (actualizado) {

            const indice =
                solicitudes.value.findIndex(
                    s =>
                        s.id ===
                        actualizado.id
                )

            if (indice !== -1) {

                solicitudes.value[
                    indice
                ] = actualizado
            }

        } else {

            await cargarSolicitudes()
        }

        mensajeExito.value =
            data?.message ||
            'Solicitud actualizada correctamente.'

        cerrarRevision()

        setTimeout(() => {
            mensajeExito.value = ''
        }, 3500)

    } catch (err) {

        console.error(
            'Error guardando dictamen:',
            err
        )

        alert(
            err?.response?.data?.message ||
            'No fue posible guardar el dictamen.'
        )

    } finally {

        guardandoDictamen.value = false
    }
}


/*
|--------------------------------------------------------------------------
| CAMBIAR A EN REVISIÓN
|--------------------------------------------------------------------------
*/

async function marcarEnRevision(
    solicitud
) {

    try {

        await api.patch(
            `/admin/solicitudes/${solicitud.id}/estatus`,
            {
                estado:
                    'EN_REVISION',
            }
        )

        await cargarSolicitudes()

    } catch (err) {

        alert(
            err?.response?.data?.message ||
            'No fue posible cambiar el estado.'
        )
    }
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
        []

    return Array.isArray(documentos)
        ? documentos
        : []
}

function obtenerUrlDocumento(
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

    return `http://127.0.0.1:8000/storage/${ruta}`
}


/*
|--------------------------------------------------------------------------
| DATOS DEL ALUMNO
|--------------------------------------------------------------------------
*/

function alumnoDe(solicitud) {

    return (
        solicitud?.user ||
        solicitud?.alumno ||
        {}
    )
}

function periodoDe(solicitud) {

    return (
        solicitud
            ?.convocatoria
            ?.periodo
            ?.nombre
        ||
        solicitud
            ?.convocatoria
            ?.periodo
        ||
        'Sin periodo'
    )
}

function nombreConvocatoria(
    solicitud
) {

    return (
        solicitud
            ?.convocatoria
            ?.nombre
        ||
        'Convocatoria'
    )
}

function folioDe(solicitud) {

    if (!solicitud) {
        return 'Sin folio'
    }

    return (
        solicitud.folio ||
        `BEC-${String(
            solicitud.id
        ).padStart(5, '0')}`
    )
}

function formatearFecha(fecha) {

    if (!fecha) {
        return 'Sin fecha'
    }

    try {

        return new Intl.DateTimeFormat(
            'es-MX',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }
        ).format(
            new Date(fecha)
        )

    } catch {

        return fecha
    }
}


/*
|--------------------------------------------------------------------------
| CERRAR SESIÓN
|--------------------------------------------------------------------------
|
| NO borramos localStorage aquí.
| NO hacemos window.location.href.
|
| App.vue ya controla correctamente
| el cierre de sesión.
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

    <div class="jefe-dashboard">

        <!-- ============================================================
             HEADER
        ============================================================= -->

        <header class="topbar">

            <div class="topbar-inner">

                <div class="brand">

                    <div class="brand-mark">
                        <span class="green">
                            UP
                        </span>

                        <span class="red">
                            T
                        </span>

                        <span class="gray">
                            ex
                        </span>
                    </div>


                    <div class="brand-copy">

                        <strong>
                            Sistema de Becas
                        </strong>

                        <span>
                            Gestión académica
                        </span>

                    </div>

                </div>


                <nav class="nav">

                    <button
                        type="button"
                        class="nav-item active"
                    >
                        Resumen
                    </button>

                    <button
                        type="button"
                        class="nav-item"
                        @click="
                            document
                                .getElementById(
                                    'solicitudes'
                                )
                                ?.scrollIntoView({
                                    behavior:
                                        'smooth'
                                })
                        "
                    >
                        Solicitudes
                    </button>

                </nav>


                <div class="profile">

                    <div class="profile-copy">

                        <strong>
                            {{ primerNombre }}
                        </strong>

                        <span>
                            Jefe de Carrera
                        </span>

                    </div>


                    <div class="avatar">
                        {{ iniciales }}
                    </div>


                    <button
                        type="button"
                        class="logout-button"
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

        <main class="main-content">


            <!-- MENSAJE ÉXITO -->

            <div
                v-if="mensajeExito"
                class="success-alert"
            >

                <div class="check-mini">
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
                        PANEL ACADÉMICO
                    </span>

                    <h1>
                        Gestión de solicitudes
                    </h1>

                    <p>
                        Revisa y dictamina
                        las solicitudes de beca
                        correspondientes a tu carrera.
                    </p>

                </div>


                <div class="career-card">

                    <span>
                        CARRERA ASIGNADA
                    </span>

                    <strong>
                        {{ carreraJefe }}
                    </strong>

                </div>

            </section>


            <!-- ========================================================
                 ESTADÍSTICAS
            ========================================================= -->

            <section class="stats-grid">

                <article
                    class="stat-card"
                >

                    <div
                        class="
                            stat-icon
                            dark
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
                                d="M6 2h9l5 5v15H6z"
                            />
                            <path
                                d="M14 2v6h6"
                            />
                        </svg>
                    </div>

                    <div>
                        <span>
                            Total
                        </span>

                        <strong>
                            {{
                                estadisticas.total
                            }}
                        </strong>

                        <small>
                            Solicitudes
                        </small>
                    </div>

                </article>


                <article
                    class="stat-card"
                >

                    <div
                        class="
                            stat-icon
                            amber
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
                            Por atender
                        </small>
                    </div>

                </article>


                <article
                    class="stat-card"
                >

                    <div
                        class="
                            stat-icon
                            blue
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
                            En revisión
                        </span>

                        <strong>
                            {{
                                estadisticas
                                    .revision
                            }}
                        </strong>

                        <small>
                            En proceso
                        </small>
                    </div>

                </article>


                <article
                    class="stat-card"
                >

                    <div
                        class="
                            stat-icon
                            green
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
                            Dictamen positivo
                        </small>
                    </div>

                </article>


                <article
                    class="stat-card"
                >

                    <div
                        class="
                            stat-icon
                            burgundy
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
                            Dictamen negativo
                        </small>
                    </div>

                </article>

            </section>


            <!-- ========================================================
                 RESUMEN ATENCIÓN
            ========================================================= -->

            <section class="summary-strip">

                <div>

                    <span class="eyebrow">
                        AVANCE DE REVISIÓN
                    </span>

                    <strong>
                        {{
                            porcentajeAtendidas
                        }}%
                    </strong>

                    <p>
                        de las solicitudes
                        ya tienen dictamen final.
                    </p>

                </div>


                <div class="summary-progress">

                    <div
                        class="summary-progress-fill"
                        :style="{
                            width:
                                porcentajeAtendidas
                                + '%'
                        }"
                    ></div>

                </div>


                <div class="summary-numbers">

                    <span>
                        {{
                            estadisticas
                                .aceptadas
                            +
                            estadisticas
                                .rechazadas
                        }}
                        atendidas
                    </span>

                    <span>
                        {{
                            estadisticas.total
                        }}
                        totales
                    </span>

                </div>

            </section>


            <!-- ========================================================
                 SOLICITUDES
            ========================================================= -->

            <section
                id="solicitudes"
                class="requests-card"
            >

                <div class="requests-heading">

                    <div>

                        <span class="eyebrow">
                            SOLICITUDES
                        </span>

                        <h2>
                            Alumnos de mi carrera
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
                        class="refresh-button"
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

                    <div class="search-field">

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
                                Buscar por alumno,
                                matrícula, folio...
                            "
                        />

                    </div>


                    <select
                        v-model="
                            filtroEstatus
                        "
                        class="filter-select"
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
                    class="loading-box"
                >

                    <div class="spinner"></div>

                    <strong>
                        Consultando solicitudes
                    </strong>

                    <span>
                        Espera un momento...
                    </span>

                </div>


                <!-- ERROR -->

                <div
                    v-else-if="error"
                    class="empty-box"
                >

                    <div
                        class="
                            empty-icon
                            error
                        "
                    >
                        !
                    </div>

                    <strong>
                        No se pudieron
                        cargar las solicitudes
                    </strong>

                    <span>
                        {{ error }}
                    </span>

                    <button
                        type="button"
                        class="primary-button"
                        @click="
                            cargarSolicitudes
                        "
                    >
                        Intentar nuevamente
                    </button>

                </div>


                <!-- VACÍO -->

                <div
                    v-else-if="
                        solicitudesFiltradas
                            .length === 0
                    "
                    class="empty-box"
                >

                    <div class="empty-icon">

                        <svg
                            viewBox="0 0 24 24"
                            width="24"
                            height="24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M6 2h9l5 5v15H6z"
                            />
                            <path
                                d="M14 2v6h6"
                            />
                        </svg>

                    </div>

                    <strong>
                        No encontramos solicitudes
                    </strong>

                    <span>
                        Cambia los filtros
                        o vuelve a intentarlo.
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
                                        text-right
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

                                    <div class="student-cell">

                                        <div
                                            class="
                                                student-avatar
                                            "
                                        >
                                            {{
                                                (
                                                    alumnoDe(
                                                        solicitud
                                                    )
                                                    .name
                                                    ||
                                                    'A'
                                                )
                                                .charAt(0)
                                                .toUpperCase()
                                            }}
                                        </div>


                                        <div>

                                            <strong>
                                                {{
                                                    alumnoDe(
                                                        solicitud
                                                    )
                                                    .name
                                                    ||
                                                    'Alumno'
                                                }}
                                            </strong>

                                            <span>
                                                {{
                                                    folioDe(
                                                        solicitud
                                                    )
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    {{
                                        alumnoDe(
                                            solicitud
                                        )
                                        .matricula
                                        ||
                                        '—'
                                    }}

                                </td>


                                <td>

                                    {{
                                        solicitud.grupo
                                        ||
                                        alumnoDe(
                                            solicitud
                                        ).grupo
                                        ||
                                        '—'
                                    }}

                                </td>


                                <td>

                                    {{
                                        periodoDe(
                                            solicitud
                                        )
                                    }}

                                </td>


                                <td>

                                    <div
                                        class="
                                            docs-count
                                        "
                                    >

                                        {{
                                            obtenerDocumentos(
                                                solicitud
                                            ).length
                                        }}

                                        <span>
                                            archivo(s)
                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="
                                            status-badge
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


                                <td
                                    class="
                                        text-right
                                    "
                                >

                                    <button
                                        type="button"
                                        class="
                                            review-button
                                        "
                                        @click="
                                            abrirRevision(
                                                solicitud
                                            )
                                        "
                                    >
                                        Revisar
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>


        <!-- ============================================================
             MODAL DE REVISIÓN
        ============================================================= -->

        <div
            v-if="
                solicitudSeleccionada
            "
            class="modal-overlay"
            @click.self="
                cerrarRevision
            "
        >

            <div class="modal review-modal">

                <button
                    type="button"
                    class="modal-close"
                    @click="
                        cerrarRevision
                    "
                >
                    ×
                </button>


                <div class="modal-heading">

                    <span class="eyebrow">
                        REVISIÓN DE SOLICITUD
                    </span>

                    <h2>
                        {{
                            alumnoDe(
                                solicitudSeleccionada
                            ).name
                        }}
                    </h2>

                    <p>
                        {{
                            folioDe(
                                solicitudSeleccionada
                            )
                        }}
                    </p>

                </div>


                <!-- DATOS -->

                <div class="applicant-grid">

                    <div>

                        <span>
                            Matrícula
                        </span>

                        <strong>
                            {{
                                alumnoDe(
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
                                alumnoDe(
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
                                periodoDe(
                                    solicitudSeleccionada
                                )
                            }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Estado actual
                        </span>

                        <strong>
                            {{
                                textoEstado(
                                    solicitudSeleccionada
                                        .estado
                                    ||
                                    solicitudSeleccionada
                                        .estatus
                                )
                            }}
                        </strong>

                    </div>

                </div>


                <!-- CONVOCATORIA -->

                <div class="review-section">

                    <span class="section-title">
                        CONVOCATORIA
                    </span>

                    <strong>
                        {{
                            nombreConvocatoria(
                                solicitudSeleccionada
                            )
                        }}
                    </strong>

                </div>


                <!-- DOCUMENTOS -->

                <div class="review-section">

                    <div
                        class="
                            section-header
                        "
                    >

                        <span
                            class="
                                section-title
                            "
                        >
                            DOCUMENTOS
                        </span>

                        <span
                            class="
                                document-total
                            "
                        >
                            {{
                                obtenerDocumentos(
                                    solicitudSeleccionada
                                ).length
                            }}
                        </span>

                    </div>


                    <div
                        v-if="
                            obtenerDocumentos(
                                solicitudSeleccionada
                            ).length === 0
                        "
                        class="no-documents"
                    >
                        No hay documentos registrados.
                    </div>


                    <div
                        v-else
                        class="
                            documents-list
                        "
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
                            class="
                                document-row
                            "
                        >

                            <div
                                class="
                                    document-icon
                                "
                            >
                                PDF
                            </div>


                            <div
                                class="
                                    document-copy
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
                                        'Archivo adjunto'
                                    }}
                                </span>

                            </div>


                            <a
                                v-if="
                                    obtenerUrlDocumento(
                                        documento
                                    )
                                "
                                :href="
                                    obtenerUrlDocumento(
                                        documento
                                    )
                                "
                                target="_blank"
                                rel="
                                    noopener
                                    noreferrer
                                "
                                class="
                                    document-open
                                "
                            >
                                Ver
                            </a>

                        </div>

                    </div>

                </div>


                <!-- OBSERVACIONES -->

                <div class="review-section">

                    <label class="section-title">
                        OBSERVACIONES
                    </label>

                    <textarea
                        v-model="
                            observaciones
                        "
                        rows="4"
                        placeholder="
                            Escribe una observación
                            para el alumno...
                        "
                    ></textarea>

                </div>


                <!-- ACCIONES -->

                <div class="decision-actions">

                    <button
                        type="button"
                        class="
                            decision-button
                            review
                        "
                        :disabled="
                            guardandoDictamen
                        "
                        @click="
                            marcarEnRevision(
                                solicitudSeleccionada
                            );
                            cerrarRevision();
                        "
                    >
                        En revisión
                    </button>


                    <button
                        type="button"
                        class="
                            decision-button
                            incomplete
                        "
                        :disabled="
                            guardandoDictamen
                        "
                        @click="
                            guardarDictamen(
                                'DOCUMENTACION_INCOMPLETA'
                            )
                        "
                    >
                        Documentación incompleta
                    </button>


                    <button
                        type="button"
                        class="
                            decision-button
                            reject
                        "
                        :disabled="
                            guardandoDictamen
                        "
                        @click="
                            guardarDictamen(
                                'RECHAZADA'
                            )
                        "
                    >
                        Rechazar
                    </button>


                    <button
                        type="button"
                        class="
                            decision-button
                            approve
                        "
                        :disabled="
                            guardandoDictamen
                        "
                        @click="
                            guardarDictamen(
                                'ACEPTADA'
                            )
                        "
                    >
                        {{
                            guardandoDictamen
                                ? 'Guardando...'
                                : 'Aceptar'
                        }}
                    </button>

                </div>

            </div>

        </div>


        <!-- ============================================================
             MODAL LOGOUT
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


                <div class="logout-actions">

                    <button
                        type="button"
                        class="
                            secondary-button
                        "
                        @click="
                            modalLogout = false
                        "
                    >
                        Cancelar
                    </button>


                    <button
                        type="button"
                        class="
                            primary-button
                            logout-confirm
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

.jefe-dashboard {
    min-height: 100vh;
    background:
        linear-gradient(
            180deg,
            #f5f7f6 0%,
            #fbfcfb 50%,
            #f4f6f5 100%
        );
    color: #242a27;
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
   HEADER
================================================================ */

.topbar {
    position: sticky;
    top: 0;
    z-index: 40;
    background:
        rgba(
            255,
            255,
            255,
            .95
        );
    backdrop-filter:
        blur(18px);
    border-bottom:
        1px solid #e5e8e6;
}

.topbar-inner {
    width:
        min(
            1220px,
            calc(100% - 40px)
        );
    height: 76px;
    margin: auto;
    display: flex;
    align-items: center;
    gap: 35px;
}

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

.brand-mark .green {
    color: #247548;
}

.brand-mark .red {
    color: #b91f37;
}

.brand-mark .gray {
    color: #737a76;
}

.brand-copy {
    padding-left: 12px;
    border-left:
        1px solid #e1e4e2;
    display: flex;
    flex-direction: column;
}

.brand-copy strong {
    color: #29302c;
    font-size: 12px;
}

.brand-copy span {
    color: #9aa09d;
    font-size: 9px;
    margin-top: 3px;
}

.nav {
    flex: 1;
    display: flex;
    justify-content: center;
    gap: 6px;
}

.nav-item {
    border: 0;
    background: transparent;
    color: #737a76;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.nav-item:hover,
.nav-item.active {
    background: #edf5f0;
    color: #247548;
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-copy {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.profile-copy strong {
    font-size: 11px;
}

.profile-copy span {
    color: #929894;
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
            #358c5a
        );
    color: #fff;
    font-size: 11px;
    font-weight: 850;
}

.logout-button {
    width: 38px;
    height: 38px;
    border: 1px solid #e2e5e3;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: #fff;
    color: #727975;
    cursor: pointer;
}

.logout-button:hover {
    color: #7a1c33;
    border-color: #ebd5db;
    background: #fff5f7;
}


/* ================================================================
   MAIN
================================================================ */

.main-content {
    width:
        min(
            1220px,
            calc(100% - 40px)
        );
    margin: auto;
    padding:
        44px
        0
        80px;
}

.success-alert {
    margin-bottom: 18px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border:
        1px solid #d9eadd;
    border-radius: 13px;
    background: #edf7f0;
    color: #25623d;
    font-size: 11px;
    font-weight: 700;
}

.check-mini {
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #247548;
    color: #fff;
    font-size: 11px;
}


/* WELCOME */

.welcome {
    display: flex;
    align-items: flex-end;
    justify-content:
        space-between;
    gap: 25px;
    margin-bottom: 26px;
}

.eyebrow {
    display: block;
    color: #8a918d;
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
    color: #7e8581;
    font-size: 13px;
}

.career-card {
    min-width: 270px;
    padding:
        14px
        17px;
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

.career-card span {
    display: block;
    color: #9ca29f;
    font-size: 8px;
    font-weight: 850;
    letter-spacing: .11em;
}

.career-card strong {
    display: block;
    margin-top: 5px;
    color: #39413d;
    font-size: 11px;
}


/* ================================================================
   STATS
================================================================ */

.stats-grid {
    display: grid;
    grid-template-columns:
        repeat(
            5,
            1fr
        );
    gap: 13px;
    margin-bottom: 18px;
}

.stat-card {
    min-height: 110px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding:
        17px
        16px;
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

.stat-icon.dark {
    color: #424b46;
    background: #f0f2f1;
}

.stat-icon.amber {
    color: #9a6817;
    background: #fff6dd;
}

.stat-icon.blue {
    color: #32688f;
    background: #eaf3fa;
}

.stat-icon.green {
    color: #247548;
    background: #eaf5ee;
}

.stat-icon.burgundy {
    color: #85243d;
    background: #faedf1;
}

.stat-card div:last-child {
    display: flex;
    flex-direction: column;
}

.stat-card span {
    color: #969c98;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.stat-card strong {
    margin-top: 2px;
    color: #2d3430;
    font-size: 24px;
    line-height: 1;
}

.stat-card small {
    margin-top: 5px;
    color: #a3a8a5;
    font-size: 8px;
}


/* ================================================================
   SUMMARY
================================================================ */

.summary-strip {
    margin-bottom: 23px;
    padding:
        18px
        22px;
    display: grid;
    grid-template-columns:
        210px
        1fr
        auto;
    align-items: center;
    gap: 25px;
    border:
        1px solid #e3e7e4;
    border-radius: 17px;
    background: #fff;
}

.summary-strip strong {
    display: inline-block;
    margin-top: 4px;
    color: #247548;
    font-size: 21px;
}

.summary-strip p {
    display: inline;
    margin-left: 7px;
    color: #7d8580;
    font-size: 10px;
}

.summary-progress {
    height: 6px;
    overflow: hidden;
    border-radius: 99px;
    background: #edf0ee;
}

.summary-progress-fill {
    height: 100%;
    border-radius: 99px;
    background:
        linear-gradient(
            90deg,
            #247548,
            #45a16c
        );
    transition: width .3s ease;
}

.summary-numbers {
    display: flex;
    gap: 14px;
}

.summary-numbers span {
    color: #8c938f;
    font-size: 9px;
    font-weight: 700;
}


/* ================================================================
   REQUESTS
================================================================ */

.requests-card {
    border:
        1px solid #e3e7e4;
    border-radius: 21px;
    background: #fff;
    overflow: hidden;
    box-shadow:
        0 13px 38px
        rgba(
            28,
            40,
            33,
            .05
        );
}

.requests-heading {
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

.requests-heading h2 {
    margin:
        6px
        0
        3px;
    font-size: 19px;
}

.requests-heading p {
    margin: 0;
    color: #989e9b;
    font-size: 9px;
}

.refresh-button {
    height: 37px;
    padding:
        0
        13px;
    display: flex;
    align-items: center;
    gap: 7px;
    border:
        1px solid #e1e5e2;
    border-radius: 10px;
    background: #fff;
    color: #626a66;
    font-size: 9px;
    font-weight: 800;
    cursor: pointer;
}

.refresh-button:hover {
    background: #f6f8f7;
}


/* FILTERS */

.filters {
    padding:
        15px
        24px;
    display: grid;
    grid-template-columns:
        1fr
        235px;
    gap: 11px;
    background: #fbfcfb;
    border-bottom:
        1px solid #edf0ee;
}

.search-field {
    position: relative;
    display: flex;
    align-items: center;
}

.search-field svg {
    position: absolute;
    left: 13px;
    color: #9da39f;
}

.search-field input {
    width: 100%;
    height: 40px;
    border:
        1px solid #e1e5e2;
    border-radius: 11px;
    padding:
        0
        13px
        0
        40px;
    background: #fff;
    color: #404743;
    outline: 0;
    font-size: 10px;
}

.search-field input:focus {
    border-color: #6ca583;
    box-shadow:
        0 0 0 3px
        #edf6f1;
}

.filter-select {
    height: 40px;
    border:
        1px solid #e1e5e2;
    border-radius: 11px;
    padding:
        0
        12px;
    background: #fff;
    color: #505753;
    outline: 0;
    font-size: 10px;
    font-weight: 700;
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
    padding:
        12px
        17px;
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
    padding:
        14px
        17px;
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

.text-right {
    text-align: right;
}

.student-cell {
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

.student-cell div:last-child {
    display: flex;
    flex-direction: column;
}

.student-cell strong {
    color: #333a36;
    font-size: 10px;
}

.student-cell span {
    margin-top: 3px;
    color: #a0a5a2;
    font-size: 8px;
}

.docs-count {
    display: flex;
    flex-direction: column;
    font-weight: 750;
    color: #434b47;
}

.docs-count span {
    margin-top: 2px;
    color: #9fa5a1;
    font-size: 8px;
    font-weight: 500;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding:
        6px
        9px;
    border-radius: 99px;
    font-size: 8px;
    font-weight: 800;
    white-space: nowrap;
}

.status-badge.warning {
    color: #90611b;
    background: #fff4d7;
}

.status-badge.info {
    color: #32688f;
    background: #e9f3fa;
}

.status-badge.success {
    color: #216841;
    background: #e8f5ed;
}

.status-badge.danger {
    color: #86253d;
    background: #faeaf0;
}

.status-badge.purple {
    color: #6e4992;
    background: #f2ebf8;
}

.status-badge.neutral {
    color: #707773;
    background: #f0f2f1;
}

.review-button {
    height: 32px;
    padding:
        0
        12px;
    border:
        1px solid #dbe5de;
    border-radius: 9px;
    background: #f4f8f5;
    color: #267348;
    font-size: 8px;
    font-weight: 850;
    cursor: pointer;
}

.review-button:hover {
    background: #eaf4ed;
}


/* EMPTY / LOADING */

.loading-box,
.empty-box {
    min-height: 320px;
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.loading-box strong,
.empty-box strong {
    margin-top: 13px;
    color: #454d49;
    font-size: 12px;
}

.loading-box span,
.empty-box span {
    margin-top: 5px;
    color: #969d99;
    font-size: 9px;
}

.spinner {
    width: 35px;
    height: 35px;
    border:
        3px solid #e8edea;
    border-top-color: #247548;
    border-radius: 50%;
    animation:
        spin .8s linear infinite;
}

@keyframes spin {
    to {
        transform:
            rotate(360deg);
    }
}

.empty-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    color: #76807a;
    background: #f0f3f1;
}

.empty-icon.error {
    color: #84253c;
    background: #faeaf0;
    font-size: 17px;
    font-weight: 850;
}


/* ================================================================
   MODALS
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
            650px,
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

.modal-heading h2 {
    margin:
        7px
        0
        3px;
    color: #29302c;
    font-size: 21px;
}

.modal-heading p {
    margin: 0;
    color: #9ba19e;
    font-size: 9px;
}


/* APPLICANT DATA */

.applicant-grid {
    margin-top: 21px;
    display: grid;
    grid-template-columns:
        repeat(
            4,
            1fr
        );
    gap: 9px;
}

.applicant-grid > div {
    padding: 12px;
    border-radius: 11px;
    background: #f6f8f7;
}

.applicant-grid span {
    display: block;
    color: #9da39f;
    font-size: 7px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.applicant-grid strong {
    display: block;
    margin-top: 4px;
    color: #454d48;
    font-size: 9px;
}


/* REVIEW SECTIONS */

.review-section {
    margin-top: 20px;
    padding-top: 18px;
    border-top:
        1px solid #ecefed;
}

.section-title {
    display: block;
    margin-bottom: 10px;
    color: #8b928e;
    font-size: 8px;
    font-weight: 850;
    letter-spacing: .12em;
}

.review-section > strong {
    color: #444c47;
    font-size: 11px;
}

.section-header {
    display: flex;
    justify-content:
        space-between;
    align-items: center;
}

.document-total {
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

.no-documents {
    padding: 16px;
    border-radius: 11px;
    background: #f7f8f8;
    color: #999f9c;
    font-size: 9px;
    text-align: center;
}

.documents-list {
    display: grid;
    gap: 7px;
}

.document-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding:
        10px
        11px;
    border:
        1px solid #e8ebe9;
    border-radius: 11px;
}

.document-icon {
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

.document-copy {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.document-copy strong {
    color: #414944;
    font-size: 9px;
}

.document-copy span {
    margin-top: 3px;
    color: #9ba19e;
    font-size: 8px;
}

.document-open {
    padding:
        7px
        10px;
    border-radius: 8px;
    background: #edf5f0;
    color: #267348;
    font-size: 8px;
    font-weight: 850;
    text-decoration: none;
}

.review-section textarea {
    width: 100%;
    resize: vertical;
    min-height: 90px;
    border:
        1px solid #e0e4e1;
    border-radius: 12px;
    padding: 12px;
    outline: 0;
    color: #454c48;
    font-family: inherit;
    font-size: 10px;
    line-height: 1.5;
}

.review-section textarea:focus {
    border-color: #6ca583;
    box-shadow:
        0 0 0 3px #edf6f1;
}


/* DECISION BUTTONS */

.decision-actions {
    margin-top: 22px;
    display: grid;
    grid-template-columns:
        repeat(
            4,
            1fr
        );
    gap: 8px;
}

.decision-button {
    min-height: 41px;
    border: 0;
    border-radius: 10px;
    font-size: 8px;
    font-weight: 850;
    cursor: pointer;
}

.decision-button.review {
    background: #eaf3fa;
    color: #32688f;
}

.decision-button.incomplete {
    background: #f1eaf8;
    color: #724896;
}

.decision-button.reject {
    background: #faeaf0;
    color: #86253d;
}

.decision-button.approve {
    background: #247548;
    color: #fff;
}

.decision-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}


/* LOGOUT */

.logout-modal {
    width:
        min(
            405px,
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

.primary-button,
.secondary-button {
    min-height: 40px;
    padding:
        0
        15px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 850;
    cursor: pointer;
}

.primary-button {
    border: 0;
    background: #247548;
    color: #fff;
}

.secondary-button {
    flex: 1;
    border:
        1px solid #e0e4e1;
    background: #fff;
    color: #666e69;
}

.logout-confirm {
    flex: 1;
    background: #7a1c33;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (
    max-width: 1050px
) {

    .stats-grid {
        grid-template-columns:
            repeat(
                3,
                1fr
            );
    }

    .summary-strip {
        grid-template-columns:
            180px
            1fr;
    }

    .summary-numbers {
        grid-column:
            1 / -1;
        justify-content:
            flex-end;
    }

}

@media (
    max-width: 800px
) {

    .nav {
        display: none;
    }

    .brand {
        flex: 1;
    }

    .profile-copy {
        display: none;
    }

    .welcome {
        flex-direction: column;
        align-items:
            flex-start;
    }

    .career-card {
        width: 100%;
    }

    .stats-grid {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

    .summary-strip {
        grid-template-columns:
            1fr;
    }

    .summary-numbers {
        justify-content:
            flex-start;
    }

    .filters {
        grid-template-columns:
            1fr;
    }

    .applicant-grid {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

    .decision-actions {
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

    .brand-copy span {
        display: none;
    }

    .main-content {
        width:
            calc(
                100% - 24px
            );
        padding-top: 30px;
    }

    .stats-grid {
        grid-template-columns:
            1fr
            1fr;
        gap: 9px;
    }

    .stat-card {
        min-height: 100px;
        gap: 10px;
        padding: 13px;
    }

    .stat-icon {
        width: 35px;
        height: 35px;
    }

    .stat-card strong {
        font-size: 20px;
    }

    .requests-heading {
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

    .applicant-grid {
        grid-template-columns:
            1fr
            1fr;
    }

    .decision-actions {
        grid-template-columns:
            1fr;
    }

}

</style>