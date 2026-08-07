<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/axios'

/*
|--------------------------------------------------------------------------
| PROPS Y EVENTOS
|--------------------------------------------------------------------------
| NO cambiamos la estructura que App.vue ya utiliza.
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

const loading = ref(true)
const error = ref('')

const usuarioActual = ref(
    props.usuario || null
)

const convocatoria = ref(null)
const solicitud = ref(null)
const solicitudes = ref([])

const modalConvocatoria = ref(false)
const modalSolicitud = ref(false)
const modalLogout = ref(false)


/*
|--------------------------------------------------------------------------
| USUARIO
|--------------------------------------------------------------------------
*/

const nombreCompleto = computed(() => {
    return (
        usuarioActual.value?.name ||
        props.usuario?.name ||
        'Alumno'
    )
})

const primerNombre = computed(() => {
    const nombre = String(
        nombreCompleto.value
    )
        .trim()
        .split(' ')
        .filter(Boolean)

    return nombre[0] || 'Alumno'
})

const iniciales = computed(() => {
    return String(nombreCompleto.value)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(item =>
            item.charAt(0).toUpperCase()
        )
        .join('') || 'A'
})

const matricula = computed(() => {
    return (
        usuarioActual.value?.matricula ||
        props.usuario?.matricula ||
        'Sin matrícula'
    )
})

const grupo = computed(() => {
    return (
        usuarioActual.value?.grupo ||
        props.usuario?.grupo ||
        'Sin grupo'
    )
})


/*
|--------------------------------------------------------------------------
| CONVOCATORIA
|--------------------------------------------------------------------------
*/

const nombreConvocatoria = computed(() => {
    return (
        convocatoria.value?.nombre ||
        convocatoria.value?.titulo ||
        'Convocatoria de becas'
    )
})

const descripcionConvocatoria = computed(() => {
    return (
        convocatoria.value?.descripcion ||
        'Consulta la convocatoria vigente y revisa los requisitos para participar.'
    )
})

const periodoConvocatoria = computed(() => {
    return (
        convocatoria.value?.periodo?.nombre ||
        convocatoria.value?.periodo_nombre ||
        'Periodo vigente'
    )
})

const fechaInicio = computed(() => {
    return formatearFecha(
        convocatoria.value?.fecha_inicio
    )
})

const fechaCierre = computed(() => {
    return formatearFecha(
        convocatoria.value?.fecha_cierre ||
        convocatoria.value?.fecha_fin
    )
})

const convocatoriaDisponible = computed(() => {
    return Boolean(
        convocatoria.value
    )
})

const pdfConvocatoria = computed(() => {

    if (!convocatoria.value) {
        return null
    }

    const archivo =
        convocatoria.value.archivo_url ||
        convocatoria.value.pdf_url ||
        convocatoria.value.url_pdf ||
        convocatoria.value.archivo

    if (!archivo) {
        return null
    }

    if (
        String(archivo).startsWith('http')
    ) {
        return archivo
    }

    if (
        String(archivo).startsWith('/')
    ) {
        return archivo
    }

    return `http://127.0.0.1:8000/storage/${archivo}`
})


/*
|--------------------------------------------------------------------------
| SOLICITUD
|--------------------------------------------------------------------------
*/

const estatusSolicitudRaw = computed(() => {
    return String(
        solicitud.value?.estatus ||
        solicitud.value?.estado ||
        ''
    )
        .trim()
        .toLowerCase()
})

const estadoSolicitud = computed(() => {

    if (!solicitud.value) {
        return {
            texto: 'Sin solicitud',
            clase: 'neutral',
        }
    }

    const estado =
        estatusSolicitudRaw.value

    if (
        estado.includes('acept') ||
        estado.includes('aprob')
    ) {
        return {
            texto: 'Aceptada',
            clase: 'success',
        }
    }

    if (
        estado.includes('rechaz') ||
        estado.includes('deneg')
    ) {
        return {
            texto: 'Rechazada',
            clase: 'danger',
        }
    }

    if (
        estado.includes('revision') ||
        estado.includes('revisión')
    ) {
        return {
            texto: 'En revisión',
            clase: 'info',
        }
    }

    return {
        texto: 'Pendiente',
        clase: 'warning',
    }
})

const folioSolicitud = computed(() => {
    if (!solicitud.value) {
        return 'Sin folio'
    }

    return (
        solicitud.value.folio ||
        `BEC-${String(
            solicitud.value.id || ''
        ).padStart(5, '0')}`
    )
})

const fechaSolicitud = computed(() => {
    return formatearFecha(
        solicitud.value?.created_at ||
        solicitud.value?.fecha_solicitud
    )
})


/*
|--------------------------------------------------------------------------
| PROGRESO
|--------------------------------------------------------------------------
| Se calcula con información que ya existe.
| No agregamos rutas ni modificamos la base.
|--------------------------------------------------------------------------
*/

const progreso = computed(() => {

    const hayConvocatoria =
        Boolean(convocatoria.value)

    const haySolicitud =
        Boolean(solicitud.value)

    const documentos =
        solicitud.value?.documentos ||
        solicitud.value?.documentos_solicitud ||
        []

    const tieneDocumentos =
        Array.isArray(documentos)
            ? documentos.length > 0
            : Boolean(documentos)

    const estado =
        estatusSolicitudRaw.value

    const enRevision =
        haySolicitud &&
        (
            estado.includes('revision') ||
            estado.includes('revisión') ||
            estado.includes('pendiente') ||
            estado.includes('acept') ||
            estado.includes('aprob') ||
            estado.includes('rechaz')
        )

    const tieneResultado =
        estado.includes('acept') ||
        estado.includes('aprob') ||
        estado.includes('rechaz') ||
        estado.includes('deneg')

    return [
        {
            numero: 1,
            titulo: 'Convocatoria',
            descripcion:
                'Convocatoria disponible',
            completado:
                hayConvocatoria,
            activo:
                hayConvocatoria &&
                !haySolicitud,
        },
        {
            numero: 2,
            titulo: 'Solicitud',
            descripcion:
                'Solicitud registrada',
            completado:
                haySolicitud,
            activo:
                haySolicitud &&
                !tieneDocumentos,
        },
        {
            numero: 3,
            titulo: 'Documentación',
            descripcion:
                'Archivos registrados',
            completado:
                tieneDocumentos,
            activo:
                haySolicitud &&
                !enRevision,
        },
        {
            numero: 4,
            titulo: 'Revisión',
            descripcion:
                'Validación administrativa',
            completado:
                tieneResultado,
            activo:
                enRevision &&
                !tieneResultado,
        },
        {
            numero: 5,
            titulo: tieneResultado
                ? estadoSolicitud.value.texto
                : 'Resultado',
            descripcion:
                tieneResultado
                    ? 'Proceso concluido'
                    : 'Pendiente de dictamen',
            completado:
                tieneResultado,
            activo: false,
        },
    ]
})

const porcentajeProgreso = computed(() => {

    const completados =
        progreso.value.filter(
            paso => paso.completado
        ).length

    return Math.round(
        (
            completados /
            progreso.value.length
        ) * 100
    )
})


/*
|--------------------------------------------------------------------------
| CARGAR INFORMACIÓN
|--------------------------------------------------------------------------
*/

async function cargarDatos() {

    loading.value = true
    error.value = ''

    try {

        /*
        |--------------------------------------------------------------------------
        | No pedimos /user obligatoriamente porque App.vue YA nos manda usuario.
        | Sólo consultamos información del dashboard.
        |--------------------------------------------------------------------------
        */

        const respuestas =
            await Promise.allSettled([
                api.get(
                    '/alumno/convocatoria-actual'
                ),
                api.get(
                    '/alumno/mi-solicitud-activa'
                ),
                api.get(
                    '/alumno/mis-solicitudes'
                ),
            ])


        /*
        |--------------------------------------------------------------------------
        | CONVOCATORIA
        |--------------------------------------------------------------------------
        */

        if (
            respuestas[0].status ===
            'fulfilled'
        ) {

            const data =
                respuestas[0].value.data

            convocatoria.value =
                data?.data ||
                data?.convocatoria ||
                (
                    Array.isArray(data)
                        ? data[0] || null
                        : data
                )
        }


        /*
        |--------------------------------------------------------------------------
        | SOLICITUD ACTIVA
        |--------------------------------------------------------------------------
        */

        if (
            respuestas[1].status ===
            'fulfilled'
        ) {

            const data =
                respuestas[1].value.data

            solicitud.value =
                data?.data ||
                data?.solicitud ||
                (
                    Array.isArray(data)
                        ? data[0] || null
                        : data
                )
        }


        /*
        |--------------------------------------------------------------------------
        | HISTORIAL
        |--------------------------------------------------------------------------
        */

        if (
            respuestas[2].status ===
            'fulfilled'
        ) {

            const data =
                respuestas[2].value.data

            solicitudes.value =
                data?.data ||
                data?.solicitudes ||
                (
                    Array.isArray(data)
                        ? data
                        : []
                )
        }


    } catch (err) {

        console.error(
            'Error cargando dashboard:',
            err
        )

        error.value =
            err?.response?.data?.message ||
            'No fue posible cargar la información.'

    } finally {

        loading.value = false
    }
}


/*
|--------------------------------------------------------------------------
| ACCIONES
|--------------------------------------------------------------------------
*/

function verConvocatoria() {

    if (!convocatoria.value) {
        return
    }

    /*
    |--------------------------------------------------------------------------
    | Si tiene PDF, lo abrimos en pestaña nueva.
    | Esto NO cambia la vista principal ni destruye el login.
    |--------------------------------------------------------------------------
    */

    if (pdfConvocatoria.value) {

        window.open(
            pdfConvocatoria.value,
            '_blank',
            'noopener,noreferrer'
        )

        return
    }

    /*
    |--------------------------------------------------------------------------
    | Si todavía no tiene PDF, mostramos información dentro del dashboard.
    |--------------------------------------------------------------------------
    */

    modalConvocatoria.value = true
}


function verSolicitud() {

    if (!solicitud.value) {
        return
    }

    modalSolicitud.value = true
}


function solicitarBeca() {

    /*
    |--------------------------------------------------------------------------
    | No cambiamos rutas ni usamos window.location.href.
    | Tu estructura actual todavía no tiene navegación interna formal
    | para esta vista, así que mantenemos al alumno en el dashboard.
    |--------------------------------------------------------------------------
    */

    modalConvocatoria.value = true
}


function confirmarCerrarSesion() {
    modalLogout.value = true
}

function cerrarSesion() {

    modalLogout.value = false

    /*
    |--------------------------------------------------------------------------
    | MUY IMPORTANTE:
    | App.vue es quien controla el logout.
    | Solamente emitimos el evento que YA espera tu estructura.
    |--------------------------------------------------------------------------
    */

    emit('cerrar-sesion')
}


/*
|--------------------------------------------------------------------------
| UTILIDADES
|--------------------------------------------------------------------------
*/

function formatearFecha(fecha) {

    if (!fecha) {
        return 'Por definir'
    }

    try {

        const fechaObj =
            new Date(fecha)

        if (
            Number.isNaN(
                fechaObj.getTime()
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
        ).format(fechaObj)

    } catch {

        return fecha
    }
}


onMounted(() => {

    /*
    |--------------------------------------------------------------------------
    | NO hacemos redirects.
    | App.vue ya controla si debe mostrarse este componente.
    |--------------------------------------------------------------------------
    */

    cargarDatos()
})
</script>


<template>

    <div class="student-dashboard">

        <!-- ============================================================
             HEADER
        ============================================================= -->

        <header class="header">

            <div class="header-inner">

                <div class="identity">

                    <div class="university-symbol">
                        <span class="symbol-u">UP</span>
                        <span class="symbol-t">T</span>
                        <span class="symbol-ex">ex</span>
                    </div>

                    <div class="identity-copy">
                        <strong>
                            Sistema de Becas
                        </strong>

                        <span>
                            Universidad Politécnica
                            de Texcoco
                        </span>
                    </div>

                </div>


                <nav class="desktop-nav">

                    <button
                        class="nav-link active"
                        type="button"
                    >
                        Inicio
                    </button>

                    <button
                        class="nav-link"
                        type="button"
                        @click="verConvocatoria"
                    >
                        Convocatoria
                    </button>

                    <button
                        class="nav-link"
                        type="button"
                        :disabled="!solicitud"
                        @click="verSolicitud"
                    >
                        Mi solicitud
                    </button>

                </nav>


                <div class="account">

                    <div class="account-text">

                        <strong>
                            {{ primerNombre }}
                        </strong>

                        <span>
                            Alumno
                        </span>

                    </div>


                    <div class="avatar">
                        {{ iniciales }}
                    </div>


                    <button
                        class="logout"
                        type="button"
                        title="Cerrar sesión"
                        @click="
                            confirmarCerrarSesion
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            width="19"
                            height="19"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
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

        <main class="content">


            <!-- LOADING -->

            <section
                v-if="loading"
                class="state-panel"
            >

                <div class="spinner"></div>

                <h2>
                    Preparando tu portal
                </h2>

                <p>
                    Estamos consultando tu
                    información de beca.
                </p>

            </section>


            <!-- ERROR -->

            <section
                v-else-if="error"
                class="state-panel"
            >

                <div
                    class="
                        state-icon
                        error
                    "
                >
                    !
                </div>

                <h2>
                    No pudimos cargar
                    tu información
                </h2>

                <p>
                    {{ error }}
                </p>

                <button
                    class="primary-button"
                    type="button"
                    @click="cargarDatos"
                >
                    Intentar nuevamente
                </button>

            </section>


            <!-- DASHBOARD -->

            <template v-else>


                <!-- ====================================================
                     BIENVENIDA
                ===================================================== -->

                <section class="welcome">

                    <div>

                        <span class="label">
                            PORTAL DEL ALUMNO
                        </span>

                        <h1>
                            Hola,
                            {{ primerNombre }}
                        </h1>

                        <p>
                            Aquí puedes consultar
                            el avance de tu solicitud
                            de beca.
                        </p>

                    </div>


                    <div class="student-chip">

                        <div>
                            <span>
                                MATRÍCULA
                            </span>

                            <strong>
                                {{ matricula }}
                            </strong>
                        </div>

                        <div class="chip-divider"></div>

                        <div>
                            <span>
                                GRUPO
                            </span>

                            <strong>
                                {{ grupo }}
                            </strong>
                        </div>

                    </div>

                </section>


                <!-- ====================================================
                     PROGRESO
                ===================================================== -->

                <section class="progress-card">

                    <div class="progress-header">

                        <div>

                            <span class="label">
                                SEGUIMIENTO
                            </span>

                            <h2>
                                Progreso de mi beca
                            </h2>

                            <p>
                                Consulta en qué etapa
                                se encuentra tu solicitud.
                            </p>

                        </div>


                        <div class="progress-percent">

                            <strong>
                                {{ porcentajeProgreso }}%
                            </strong>

                            <span>
                                completado
                            </span>

                        </div>

                    </div>


                    <div class="progress-bar">

                        <div
                            class="progress-fill"
                            :style="{
                                width:
                                    porcentajeProgreso
                                    + '%'
                            }"
                        ></div>

                    </div>


                    <div class="steps">

                        <div
                            v-for="
                                (
                                    paso,
                                    index
                                ) in progreso
                            "
                            :key="paso.numero"
                            class="step"
                        >

                            <div
                                v-if="
                                    index <
                                    progreso.length - 1
                                "
                                class="step-line"
                                :class="{
                                    completed:
                                        progreso[
                                            index + 1
                                        ].completado ||
                                        progreso[
                                            index + 1
                                        ].activo
                                }"
                            ></div>


                            <div
                                class="step-circle"
                                :class="{
                                    completed:
                                        paso.completado,
                                    active:
                                        paso.activo
                                }"
                            >

                                <svg
                                    v-if="
                                        paso.completado
                                    "
                                    viewBox="0 0 24 24"
                                    width="20"
                                    height="20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                >
                                    <path
                                        d="M5 12l4 4L19 6"
                                    />
                                </svg>

                                <span v-else>
                                    {{ paso.numero }}
                                </span>

                            </div>


                            <div class="step-copy">

                                <strong>
                                    {{ paso.titulo }}
                                </strong>

                                <span>
                                    {{
                                        paso.descripcion
                                    }}
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- ====================================================
                     GRID
                ===================================================== -->

                <section class="dashboard-grid">


                    <!-- CONVOCATORIA -->

                    <article
                        class="
                            card
                            convocatoria-card
                        "
                    >

                        <div class="card-header">

                            <div
                                class="
                                    icon-box
                                    green
                                "
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    width="22"
                                    height="22"
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
                                    <path
                                        d="M9 13h6"
                                    />
                                    <path
                                        d="M9 17h6"
                                    />
                                </svg>
                            </div>


                            <span
                                class="
                                    badge
                                    success
                                "
                                v-if="
                                    convocatoriaDisponible
                                "
                            >
                                Vigente
                            </span>

                            <span
                                class="
                                    badge
                                    neutral
                                "
                                v-else
                            >
                                Sin convocatoria
                            </span>

                        </div>


                        <div class="card-body">

                            <span class="label">
                                CONVOCATORIA
                            </span>

                            <h3>
                                {{
                                    nombreConvocatoria
                                }}
                            </h3>

                            <p>
                                {{
                                    descripcionConvocatoria
                                }}
                            </p>


                            <div
                                v-if="
                                    convocatoriaDisponible
                                "
                                class="
                                    convocatoria-info
                                "
                            >

                                <div>

                                    <span>
                                        Periodo
                                    </span>

                                    <strong>
                                        {{
                                            periodoConvocatoria
                                        }}
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Apertura
                                    </span>

                                    <strong>
                                        {{
                                            fechaInicio
                                        }}
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Cierre
                                    </span>

                                    <strong>
                                        {{
                                            fechaCierre
                                        }}
                                    </strong>

                                </div>

                            </div>

                        </div>


                        <button
                            class="
                                card-action
                                green
                            "
                            type="button"
                            :disabled="
                                !convocatoriaDisponible
                            "
                            @click="
                                verConvocatoria
                            "
                        >

                            <span>
                                Ver convocatoria
                            </span>

                            <span>
                                →
                            </span>

                        </button>

                    </article>


                    <!-- SOLICITUD -->

                    <article
                        class="
                            card
                            solicitud-card
                        "
                    >

                        <div class="card-header">

                            <div
                                class="
                                    icon-box
                                    burgundy
                                "
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    width="22"
                                    height="22"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M9 11l3 3L22 4"
                                    />
                                    <path
                                        d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                                    />
                                </svg>

                            </div>


                            <span
                                class="badge"
                                :class="
                                    estadoSolicitud.clase
                                "
                            >
                                {{
                                    estadoSolicitud.texto
                                }}
                            </span>

                        </div>


                        <div class="card-body">

                            <span class="label">
                                MI SOLICITUD
                            </span>


                            <template v-if="solicitud">

                                <h3>
                                    {{
                                        folioSolicitud
                                    }}
                                </h3>

                                <p>
                                    Consulta los datos
                                    y el estatus actual
                                    de tu solicitud.
                                </p>


                                <div
                                    class="
                                        solicitud-summary
                                    "
                                >

                                    <div>
                                        <span>
                                            Registro
                                        </span>

                                        <strong>
                                            {{
                                                fechaSolicitud
                                            }}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>
                                            Estado
                                        </span>

                                        <strong>
                                            {{
                                                estadoSolicitud
                                                    .texto
                                            }}
                                        </strong>
                                    </div>

                                </div>

                            </template>


                            <template v-else>

                                <h3>
                                    Aún no tienes
                                    una solicitud
                                </h3>

                                <p>
                                    Cuando esté abierta
                                    la convocatoria podrás
                                    iniciar tu proceso.
                                </p>

                            </template>

                        </div>


                        <button
                            v-if="solicitud"
                            class="
                                card-action
                                burgundy
                            "
                            type="button"
                            @click="verSolicitud"
                        >

                            <span>
                                Ver mi solicitud
                            </span>

                            <span>
                                →
                            </span>

                        </button>


                        <button
                            v-else
                            class="
                                card-action
                                burgundy
                            "
                            type="button"
                            :disabled="
                                !convocatoriaDisponible
                            "
                            @click="solicitarBeca"
                        >

                            <span>
                                Iniciar solicitud
                            </span>

                            <span>
                                →
                            </span>

                        </button>

                    </article>

                </section>


                <!-- ====================================================
                     INFORMACIÓN INFERIOR
                ===================================================== -->

                <section class="info-strip">

                    <div class="info-icon">

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
                            Mantente pendiente
                            de tu portal
                        </strong>

                        <p>
                            Los cambios de estatus
                            de tu solicitud se
                            reflejarán en este
                            seguimiento.
                        </p>

                    </div>

                </section>

            </template>

        </main>


        <!-- ============================================================
             MODAL CONVOCATORIA
        ============================================================= -->

        <div
            v-if="modalConvocatoria"
            class="modal-overlay"
            @click.self="
                modalConvocatoria = false
            "
        >

            <div class="modal">

                <button
                    class="modal-close"
                    type="button"
                    @click="
                        modalConvocatoria = false
                    "
                >
                    ×
                </button>


                <span class="label">
                    CONVOCATORIA
                </span>

                <h2>
                    {{ nombreConvocatoria }}
                </h2>


                <p class="modal-description">
                    {{
                        descripcionConvocatoria
                    }}
                </p>


                <div
                    v-if="
                        convocatoria?.requisitos
                    "
                    class="requirements"
                >

                    <span>
                        Requisitos
                    </span>

                    <p>
                        {{
                            convocatoria.requisitos
                        }}
                    </p>

                </div>


                <div class="modal-info-grid">

                    <div>
                        <span>
                            Inicio
                        </span>

                        <strong>
                            {{ fechaInicio }}
                        </strong>
                    </div>

                    <div>
                        <span>
                            Cierre
                        </span>

                        <strong>
                            {{ fechaCierre }}
                        </strong>
                    </div>

                </div>


                <div class="modal-actions">

                    <button
                        class="secondary-button"
                        type="button"
                        @click="
                            modalConvocatoria = false
                        "
                    >
                        Cerrar
                    </button>


                    <a
                        v-if="pdfConvocatoria"
                        class="primary-button link-button"
                        :href="pdfConvocatoria"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Abrir PDF
                    </a>

                </div>

            </div>

        </div>


        <!-- ============================================================
             MODAL SOLICITUD
        ============================================================= -->

        <div
            v-if="modalSolicitud"
            class="modal-overlay"
            @click.self="
                modalSolicitud = false
            "
        >

            <div class="modal">

                <button
                    class="modal-close"
                    type="button"
                    @click="
                        modalSolicitud = false
                    "
                >
                    ×
                </button>


                <span class="label">
                    MI SOLICITUD
                </span>

                <h2>
                    {{ folioSolicitud }}
                </h2>


                <div
                    class="
                        modal-status
                    "
                    :class="
                        estadoSolicitud.clase
                    "
                >
                    {{
                        estadoSolicitud.texto
                    }}
                </div>


                <div class="detail-list">

                    <div>

                        <span>
                            Fecha de registro
                        </span>

                        <strong>
                            {{
                                fechaSolicitud
                            }}
                        </strong>

                    </div>


                    <div>

                        <span>
                            Grupo
                        </span>

                        <strong>
                            {{
                                solicitud?.grupo ||
                                grupo
                            }}
                        </strong>

                    </div>


                    <div
                        v-if="
                            solicitud
                                ?.comentario_revision
                        "
                    >

                        <span>
                            Observaciones
                        </span>

                        <strong>
                            {{
                                solicitud
                                    .comentario_revision
                            }}
                        </strong>

                    </div>

                </div>


                <button
                    class="
                        primary-button
                        full
                    "
                    type="button"
                    @click="
                        modalSolicitud = false
                    "
                >
                    Entendido
                </button>

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
                    small
                "
            >

                <div class="logout-modal-icon">

                    <svg
                        viewBox="0 0 24 24"
                        width="26"
                        height="26"
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

                <p class="modal-description">
                    Regresarás a la pantalla
                    de inicio del sistema.
                </p>


                <div class="modal-actions">

                    <button
                        class="secondary-button"
                        type="button"
                        @click="
                            modalLogout = false
                        "
                    >
                        Cancelar
                    </button>

                    <button
                        class="
                            primary-button
                            burgundy-button
                        "
                        type="button"
                        @click="cerrarSesion"
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
   GENERAL
================================================================ */

* {
    box-sizing: border-box;
}

.student-dashboard {
    min-height: 100vh;
    background:
        linear-gradient(
            180deg,
            #f5f7f8 0%,
            #fbfcfc 50%,
            #f5f7f8 100%
        );
    color: #20252b;
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

.header {
    position: sticky;
    top: 0;
    z-index: 50;
    background:
        rgba(
            255,
            255,
            255,
            .94
        );
    backdrop-filter:
        blur(18px);
    border-bottom:
        1px solid #e7e9ea;
}

.header-inner {
    width:
        min(
            1180px,
            calc(100% - 40px)
        );
    height: 76px;
    margin: auto;
    display: flex;
    align-items: center;
    gap: 38px;
}

.identity {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 280px;
}

.university-symbol {
    font-size: 21px;
    font-weight: 900;
    letter-spacing: -2px;
    white-space: nowrap;
}

.symbol-u {
    color: #267441;
}

.symbol-t {
    color: #c3182c;
}

.symbol-ex {
    color: #73777b;
}

.identity-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    padding-left: 12px;
    border-left:
        1px solid #e3e5e6;
}

.identity-copy strong {
    font-size: 13px;
    color: #1f2529;
}

.identity-copy span {
    font-size: 9px;
    color: #8b9297;
    margin-top: 3px;
}


/* NAV */

.desktop-nav {
    flex: 1;
    display: flex;
    justify-content: center;
    gap: 6px;
}

.nav-link {
    border: 0;
    background: transparent;
    color: #71787e;
    font-size: 12px;
    font-weight: 650;
    padding: 10px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition:
        background .2s ease,
        color .2s ease;
}

.nav-link:hover {
    background: #f1f4f2;
    color: #17623a;
}

.nav-link.active {
    background: #edf5f0;
    color: #17623a;
}

.nav-link:disabled {
    opacity: .45;
    cursor: not-allowed;
}


/* ACCOUNT */

.account {
    display: flex;
    align-items: center;
    gap: 10px;
}

.account-text {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.account-text strong {
    font-size: 12px;
}

.account-text span {
    font-size: 10px;
    color: #92999e;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background:
        linear-gradient(
            145deg,
            #17623a,
            #248153
        );
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    box-shadow:
        0 4px 12px
        rgba(
            23,
            98,
            58,
            .16
        );
}

.logout {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid #e3e7e5;
    background: #fff;
    color: #747c80;
    border-radius: 10px;
    cursor: pointer;
    transition:
        background .2s ease,
        color .2s ease,
        border .2s ease;
}

.logout:hover {
    color: #7a1c33;
    background: #fff5f7;
    border-color: #eed5dc;
}


/* ================================================================
   CONTENT
================================================================ */

.content {
    width:
        min(
            1180px,
            calc(100% - 40px)
        );
    margin: auto;
    padding:
        48px
        0
        80px;
}


/* WELCOME */

.welcome {
    display: flex;
    align-items: flex-end;
    justify-content:
        space-between;
    gap: 25px;
    margin-bottom: 28px;
}

.label {
    display: block;
    color: #7b8388;
    font-size: 9px;
    font-weight: 850;
    letter-spacing: .17em;
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
            42px
        );
    font-weight: 760;
    letter-spacing: -.045em;
    line-height: 1.05;
}

.welcome p {
    margin: 0;
    color: #747c81;
    font-size: 14px;
}

.student-chip {
    min-width: 280px;
    display: flex;
    align-items: center;
    justify-content:
        space-around;
    gap: 20px;
    padding:
        13px
        18px;
    border:
        1px solid #e4e7e6;
    background: #fff;
    border-radius: 16px;
    box-shadow:
        0 6px 18px
        rgba(
            30,
            40,
            35,
            .04
        );
}

.student-chip div {
    display: flex;
    flex-direction: column;
}

.student-chip span {
    color: #9aa09d;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .12em;
}

.student-chip strong {
    margin-top: 4px;
    font-size: 12px;
    color: #38413d;
}

.student-chip .chip-divider {
    display: block;
    width: 1px;
    height: 27px;
    background: #e6e8e7;
}


/* ================================================================
   PROGRESS
================================================================ */

.progress-card {
    padding:
        32px
        34px
        35px;
    border-radius: 24px;
    border:
        1px solid #e4e8e5;
    background: #fff;
    box-shadow:
        0 14px 40px
        rgba(
            32,
            45,
            38,
            .06
        );
    margin-bottom: 26px;
}

.progress-header {
    display: flex;
    justify-content:
        space-between;
    align-items: flex-start;
    margin-bottom: 22px;
}

.progress-header h2 {
    margin:
        7px
        0
        5px;
    font-size: 22px;
    letter-spacing: -.025em;
}

.progress-header p {
    margin: 0;
    color: #89918d;
    font-size: 12px;
}

.progress-percent {
    min-width: 100px;
    text-align: right;
}

.progress-percent strong {
    display: block;
    color: #17623a;
    font-size: 27px;
    line-height: 1;
}

.progress-percent span {
    display: block;
    color: #9aa09d;
    margin-top: 5px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.progress-bar {
    height: 5px;
    background: #edf0ee;
    border-radius: 99px;
    margin-bottom: 30px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background:
        linear-gradient(
            90deg,
            #247548,
            #3d9764
        );
    border-radius: 99px;
    transition: width .4s ease;
}


/* STEPS */

.steps {
    display: grid;
    grid-template-columns:
        repeat(
            5,
            1fr
        );
}

.step {
    position: relative;
    text-align: center;
    min-width: 0;
}

.step-circle {
    position: relative;
    z-index: 2;
    width: 42px;
    height: 42px;
    margin:
        0
        auto
        12px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #fff;
    border:
        2px solid
        #dfe3e1;
    color: #acb2af;
    font-size: 12px;
    font-weight: 850;
    transition: .25s ease;
}

.step-circle.completed {
    background: #247548;
    border-color: #247548;
    color: #fff;
    box-shadow:
        0 0 0 5px
        #edf6f1;
}

.step-circle.active {
    background: #fff;
    border-color: #247548;
    color: #247548;
    box-shadow:
        0 0 0 5px
        #edf6f1;
}

.step-line {
    position: absolute;
    z-index: 1;
    top: 20px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e1e5e2;
}

.step-line.completed {
    background: #49936a;
}

.step-copy {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding:
        0
        8px;
}

.step-copy strong {
    color: #3c4440;
    font-size: 11px;
}

.step-copy span {
    color: #979d9a;
    font-size: 9px;
    line-height: 1.3;
}


/* ================================================================
   GRID / CARDS
================================================================ */

.dashboard-grid {
    display: grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );
    gap: 22px;
}

.card {
    min-height: 350px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border:
        1px solid #e3e7e5;
    border-radius: 22px;
    background: #fff;
    box-shadow:
        0 12px 32px
        rgba(
            29,
            40,
            34,
            .05
        );
}

.card-header {
    min-height: 75px;
    display: flex;
    justify-content:
        space-between;
    align-items: center;
    padding:
        22px
        24px
        10px;
}

.icon-box {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    border-radius: 13px;
}

.icon-box.green {
    background: #edf6f1;
    color: #247548;
}

.icon-box.burgundy {
    background: #faf0f3;
    color: #7a1c33;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding:
        6px
        10px;
    border-radius: 99px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .03em;
}

.badge.success,
.modal-status.success {
    color: #17623a;
    background: #e9f5ee;
}

.badge.warning,
.modal-status.warning {
    color: #946211;
    background: #fff6db;
}

.badge.info,
.modal-status.info {
    color: #265c87;
    background: #eaf3fa;
}

.badge.danger,
.modal-status.danger {
    color: #8a233a;
    background: #faeaf0;
}

.badge.neutral,
.modal-status.neutral {
    color: #6f7773;
    background: #f1f3f2;
}

.card-body {
    flex: 1;
    padding:
        10px
        24px
        26px;
}

.card-body h3 {
    margin:
        8px
        0
        9px;
    color: #27302b;
    font-size: 20px;
    letter-spacing: -.025em;
}

.card-body > p {
    margin: 0;
    min-height: 40px;
    color: #7b837f;
    font-size: 12px;
    line-height: 1.6;
}

.convocatoria-info,
.solicitud-summary {
    margin-top: 23px;
    padding:
        16px
        0
        0;
    border-top:
        1px solid #edf0ee;
    display: grid;
    grid-template-columns:
        repeat(
            3,
            1fr
        );
    gap: 12px;
}

.solicitud-summary {
    grid-template-columns:
        repeat(
            2,
            1fr
        );
}

.convocatoria-info div,
.solicitud-summary div {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.convocatoria-info span,
.solicitud-summary span {
    color: #a0a6a3;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.convocatoria-info strong,
.solicitud-summary strong {
    color: #444c48;
    font-size: 10px;
}

.card-action {
    width: 100%;
    border: 0;
    padding:
        15px
        24px;
    display: flex;
    align-items: center;
    justify-content:
        space-between;
    cursor: pointer;
    font-size: 11px;
    font-weight: 800;
    transition: .2s ease;
}

.card-action.green {
    background: #f0f7f3;
    color: #216c44;
    border-top:
        1px solid #dfebe4;
}

.card-action.green:hover {
    background: #e5f2ea;
}

.card-action.burgundy {
    background: #faf2f4;
    color: #7a1c33;
    border-top:
        1px solid #eedee3;
}

.card-action.burgundy:hover {
    background: #f5e7eb;
}

.card-action:disabled {
    cursor: not-allowed;
    opacity: .45;
}


/* ================================================================
   INFO STRIP
================================================================ */

.info-strip {
    margin-top: 22px;
    padding:
        18px
        20px;
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border:
        1px solid #e5e8e6;
    border-radius: 16px;
}

.info-icon {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: #247548;
    background: #edf6f1;
    border-radius: 11px;
}

.info-strip strong {
    display: block;
    color: #444c48;
    font-size: 11px;
}

.info-strip p {
    margin:
        4px
        0
        0;
    color: #8b928e;
    font-size: 10px;
}


/* ================================================================
   LOADING / ERROR
================================================================ */

.state-panel {
    min-height: 480px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border:
        3px solid
        #e6ece8;
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

.state-panel h2 {
    margin:
        18px
        0
        6px;
    font-size: 19px;
}

.state-panel p {
    margin:
        0
        0
        18px;
    color: #8c9390;
    font-size: 12px;
}

.state-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    font-size: 20px;
    font-weight: 800;
}

.state-icon.error {
    color: #8a233a;
    background: #faeaf0;
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
            20,
            27,
            23,
            .44
        );
    backdrop-filter:
        blur(4px);
}

.modal {
    position: relative;
    width:
        min(
            520px,
            100%
        );
    max-height:
        min(
            750px,
            90vh
        );
    overflow-y: auto;
    padding: 30px;
    background: #fff;
    border-radius: 23px;
    box-shadow:
        0 28px 80px
        rgba(
            17,
            26,
            21,
            .2
        );
}

.modal.small {
    width:
        min(
            410px,
            100%
        );
    text-align: center;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 16px;
    width: 34px;
    height: 34px;
    border: 0;
    display: grid;
    place-items: center;
    color: #848b87;
    background: #f4f6f5;
    border-radius: 10px;
    cursor: pointer;
    font-size: 19px;
}

.modal h2 {
    margin:
        8px
        0
        10px;
    color: #28302c;
    font-size: 22px;
}

.modal-description {
    color: #7d8581;
    font-size: 12px;
    line-height: 1.65;
}

.requirements {
    margin-top: 20px;
    padding: 16px;
    background: #f7f9f8;
    border-radius: 14px;
}

.requirements span {
    display: block;
    margin-bottom: 7px;
    color: #777f7b;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
}

.requirements p {
    margin: 0;
    color: #4a524e;
    font-size: 11px;
    line-height: 1.6;
    white-space: pre-line;
}

.modal-info-grid {
    display: grid;
    grid-template-columns:
        1fr
        1fr;
    gap: 12px;
    margin-top: 20px;
}

.modal-info-grid > div,
.detail-list > div {
    padding: 13px;
    background: #f7f9f8;
    border-radius: 12px;
}

.modal-info-grid span,
.detail-list span {
    display: block;
    color: #989f9b;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.modal-info-grid strong,
.detail-list strong {
    display: block;
    margin-top: 5px;
    color: #424a46;
    font-size: 11px;
}

.modal-status {
    display: inline-flex;
    padding:
        7px
        11px;
    margin:
        5px
        0
        17px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
}

.detail-list {
    display: grid;
    gap: 9px;
}

.modal-actions {
    margin-top: 25px;
    display: flex;
    justify-content: flex-end;
    gap: 9px;
}

.logout-modal-icon {
    width: 52px;
    height: 52px;
    margin:
        0
        auto
        15px;
    display: grid;
    place-items: center;
    color: #7a1c33;
    background: #faf0f3;
    border-radius: 16px;
}


/* BUTTONS */

.primary-button,
.secondary-button {
    min-height: 42px;
    border-radius: 11px;
    padding:
        0
        17px;
    font-size: 10px;
    font-weight: 800;
    cursor: pointer;
}

.primary-button {
    border: 1px solid #247548;
    background: #247548;
    color: #fff;
}

.primary-button:hover {
    background: #1d633c;
}

.primary-button.full {
    width: 100%;
    margin-top: 22px;
}

.burgundy-button {
    background: #7a1c33;
    border-color: #7a1c33;
}

.burgundy-button:hover {
    background: #65162a;
}

.secondary-button {
    border:
        1px solid
        #e0e4e2;
    background: #fff;
    color: #666e6a;
}

.secondary-button:hover {
    background: #f7f8f8;
}

.link-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (
    max-width: 900px
) {

    .desktop-nav {
        display: none;
    }

    .header-inner {
        gap: 15px;
    }

    .identity {
        flex: 1;
        min-width: 0;
    }

    .account-text {
        display: none;
    }

    .welcome {
        align-items:
            flex-start;
        flex-direction:
            column;
    }

    .student-chip {
        width: 100%;
    }

    .dashboard-grid {
        grid-template-columns:
            1fr;
    }

    .steps {
        overflow-x: auto;
        grid-template-columns:
            repeat(
                5,
                145px
            );
        padding-bottom: 10px;
    }

}


@media (
    max-width: 600px
) {

    .header-inner {
        width:
            calc(
                100% - 24px
            );
        height: 68px;
    }

    .identity-copy span {
        display: none;
    }

    .identity-copy {
        padding-left: 9px;
    }

    .identity {
        gap: 7px;
    }

    .university-symbol {
        font-size: 18px;
    }

    .logout {
        width: 34px;
        height: 34px;
    }

    .avatar {
        width: 34px;
        height: 34px;
    }

    .content {
        width:
            calc(
                100% - 24px
            );
        padding-top: 30px;
    }

    .progress-card {
        padding:
            24px
            18px;
    }

    .progress-header {
        gap: 12px;
    }

    .progress-header h2 {
        font-size: 19px;
    }

    .progress-percent strong {
        font-size: 22px;
    }

    .card {
        min-height: 330px;
    }

    .convocatoria-info {
        grid-template-columns:
            1fr;
    }

    .modal {
        padding:
            25px
            20px;
    }

}

</style>