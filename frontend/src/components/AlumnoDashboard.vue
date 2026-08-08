<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../api/axios'

const props = defineProps({
  usuario: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['cerrar-sesion'])

const seccion = ref('resumen')
const cargando = ref(true)
const guardando = ref(false)
const subiendo = ref(false)
const errorGeneral = ref('')
const toast = ref(null)

const convocatoria = ref(null)
const solicitudActiva = ref(null)
const solicitudes = ref([])
const carreras = ref([])

const formSolicitud = ref({
  modalidad: '',
  carrera_id: '',
  grupo_id: '',
  grupo: '',
})

const formDocumento = ref({
  tipo: '',
  archivo: null,
})

const modalidades = [
  { value: 'DISCAPACIDAD', label: 'Discapacidad' },
  { value: 'EXCELENCIA_ACADEMICA', label: 'Excelencia académica' },
  { value: 'SITUACION_SOCIOECONOMICA', label: 'Situación socioeconómica' },
]

const tabs = [
  { id: 'resumen', label: 'Inicio' },
  { id: 'solicitud', label: 'Mi solicitud' },
  { id: 'documentos', label: 'Documentos' },
  { id: 'historial', label: 'Historial' },
]

function unwrapArray(data) {
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data?.solicitudes)) return data.solicitudes
  if (Array.isArray(data?.carreras)) return data.carreras
  return []
}

function unwrapObject(data, keys = []) {
  if (!data) return null
  for (const key of keys) {
    if (data?.[key] && typeof data[key] === 'object') return data[key]
  }
  if (data?.data && !Array.isArray(data.data) && typeof data.data === 'object') return data.data
  if (typeof data === 'object' && !Array.isArray(data)) return data
  return null
}

function mostrarToast(mensaje, tipo = 'ok') {
  toast.value = { mensaje, tipo }
  window.setTimeout(() => {
    toast.value = null
  }, 3200)
}

function iniciales(nombre) {
  return String(nombre || 'AL')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(x => x.charAt(0).toUpperCase())
    .join('')
}

function fecha(valor) {
  if (!valor) return '—'
  const d = new Date(valor)
  if (Number.isNaN(d.getTime())) return valor
  return new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(d)
}

function nombreEstado(valor) {
  const v = String(valor || '').toUpperCase()
  const mapa = {
    BORRADOR: 'Borrador',
    PENDIENTE: 'Pendiente',
    EN_REVISION: 'En revisión',
    DOCUMENTACION_INCOMPLETA: 'Documentación incompleta',
    ACEPTADA: 'Aceptada',
    APROBADA: 'Aprobada',
    RECHAZADA: 'Rechazada',
    CANCELADA: 'Cancelada',
  }
  return mapa[v] || valor || 'Sin estado'
}

function claseEstado(valor) {
  const v = String(valor || '').toUpperCase()
  if (['ACEPTADA', 'APROBADA'].includes(v)) return 'success'
  if (['RECHAZADA', 'CANCELADA'].includes(v)) return 'danger'
  if (v === 'EN_REVISION') return 'info'
  if (v === 'DOCUMENTACION_INCOMPLETA') return 'purple'
  if (['PENDIENTE', 'BORRADOR'].includes(v)) return 'warning'
  return 'neutral'
}

function folio(s) {
  return s?.folio || `BEC-${String(s?.id || 0).padStart(5, '0')}`
}

function modalidadLabel(valor) {
  const item = modalidades.find(m => m.value === String(valor || '').toUpperCase())
  return item?.label || valor || '—'
}

function urlArchivo(item) {
  const ruta = item?.archivo_url || item?.url || item?.ruta || item?.archivo
  if (!ruta) return null
  if (String(ruta).startsWith('http')) return ruta
  if (String(ruta).startsWith('/')) return `http://127.0.0.1:8000${ruta}`
  return `http://127.0.0.1:8000/storage/${ruta}`
}

const documentos = computed(() => {
  const s = solicitudActiva.value
  if (!s) return []
  if (Array.isArray(s.documentos)) return s.documentos
  if (Array.isArray(s.documents)) return s.documents
  return []
})

const progresoDocumentos = computed(() => {
  if (!solicitudActiva.value) return 0
  const total = documentos.value.length
  if (!total) return 15

  const validos = documentos.value.filter(d => {
    const e = String(d.estado || d.estatus || '').toUpperCase()
    return !['RECHAZADO', 'OBSERVADO', 'CORRECCION'].includes(e)
  }).length

  return Math.min(100, 35 + validos * 15)
})

const estadoActual = computed(() =>
  solicitudActiva.value?.estado || solicitudActiva.value?.estatus || 'SIN_SOLICITUD'
)

const puedeCrear = computed(() => !!convocatoria.value && !solicitudActiva.value)

const convocatoriaAbierta = computed(() => {
  if (!convocatoria.value) return false
  const estado = String(convocatoria.value.estado || '').toUpperCase()
  if (estado && !['PUBLICADA', 'ACTIVA', 'ABIERTO', 'ABIERTA'].includes(estado)) return false

  const hoy = new Date()
  const inicio = convocatoria.value.fecha_inicio ? new Date(convocatoria.value.fecha_inicio) : null
  const cierre = convocatoria.value.fecha_cierre ? new Date(convocatoria.value.fecha_cierre) : null

  if (inicio && !Number.isNaN(inicio.getTime()) && hoy < inicio) return false
  if (cierre && !Number.isNaN(cierre.getTime())) {
    cierre.setHours(23, 59, 59, 999)
    if (hoy > cierre) return false
  }

  return true
})

const resumenCards = computed(() => [
  {
    titulo: 'Estado',
    valor: solicitudActiva.value ? nombreEstado(estadoActual.value) : 'Sin solicitud',
    detalle: solicitudActiva.value ? folio(solicitudActiva.value) : 'Puedes iniciar cuando haya convocatoria',
    clase: claseEstado(estadoActual.value),
  },
  {
    titulo: 'Documentos',
    valor: documentos.value.length,
    detalle: 'archivos cargados',
    clase: 'info',
  },
  {
    titulo: 'Modalidad',
    valor: solicitudActiva.value ? modalidadLabel(solicitudActiva.value.modalidad) : '—',
    detalle: 'modalidad registrada',
    clase: 'purple',
  },
  {
    titulo: 'Historial',
    valor: solicitudes.value.length,
    detalle: 'solicitudes registradas',
    clase: 'neutral',
  },
])

async function cargarDatos() {
  cargando.value = true
  errorGeneral.value = ''

  const resultados = await Promise.allSettled([
    api.get('/alumno/convocatoria-actual'),
    api.get('/alumno/mi-solicitud-activa'),
    api.get('/alumno/mis-solicitudes'),
    api.get('/carreras'),
  ])

  const [rConv, rActiva, rHistorial, rCarreras] = resultados

  if (rConv.status === 'fulfilled') {
    convocatoria.value = unwrapObject(rConv.value.data, ['convocatoria'])
  } else if (rConv.reason?.response?.status !== 404) {
    console.error('Convocatoria:', rConv.reason)
  }

  if (rActiva.status === 'fulfilled') {
    solicitudActiva.value = unwrapObject(rActiva.value.data, ['solicitud'])
  } else if (rActiva.reason?.response?.status === 404) {
    solicitudActiva.value = null
  } else {
    console.error('Solicitud activa:', rActiva.reason)
  }

  if (rHistorial.status === 'fulfilled') {
    solicitudes.value = unwrapArray(rHistorial.value.data)
  }

  if (rCarreras.status === 'fulfilled') {
    carreras.value = unwrapArray(rCarreras.value.data)
  }

  formSolicitud.value.carrera_id =
    solicitudActiva.value?.carrera_id ||
    props.usuario?.carrera_id ||
    ''

  formSolicitud.value.grupo_id =
    solicitudActiva.value?.grupo_id ||
    props.usuario?.grupo_id ||
    ''

  formSolicitud.value.grupo =
    solicitudActiva.value?.grupo ||
    props.usuario?.grupo ||
    ''

  const fallidosReales = resultados.filter(r =>
    r.status === 'rejected' && r.reason?.response?.status !== 404
  )

  if (fallidosReales.length === resultados.length) {
    errorGeneral.value = 'No fue posible conectar con el backend.'
  } else if (fallidosReales.length) {
    errorGeneral.value = 'Algunos datos no pudieron cargarse. Puedes actualizar el panel.'
  }

  cargando.value = false
}

async function crearSolicitud() {
  if (!convocatoria.value) {
    mostrarToast('No hay una convocatoria disponible.', 'error')
    return
  }

  if (!formSolicitud.value.modalidad) {
    mostrarToast('Selecciona una modalidad.', 'error')
    return
  }

  guardando.value = true

  try {
    const payload = {
      convocatoria_id: convocatoria.value.id,
      modalidad: formSolicitud.value.modalidad,
      carrera_id: formSolicitud.value.carrera_id || props.usuario?.carrera_id || null,
      grupo_id: formSolicitud.value.grupo_id || props.usuario?.grupo_id || null,
      grupo: formSolicitud.value.grupo || props.usuario?.grupo || null,
    }

    Object.keys(payload).forEach(k => {
      if (payload[k] === null || payload[k] === '') delete payload[k]
    })

    const { data } = await api.post('/alumno/solicitudes', payload)
    solicitudActiva.value = unwrapObject(data, ['solicitud']) || solicitudActiva.value

    mostrarToast('Solicitud creada correctamente.')
    await cargarDatos()
    seccion.value = 'documentos'
  } catch (e) {
    const errores = e.response?.data?.errors
    const primerError = errores
      ? Object.values(errores).flat().filter(Boolean)[0]
      : null

    mostrarToast(
      primerError ||
      e.response?.data?.message ||
      'No se pudo crear la solicitud.',
      'error'
    )
  } finally {
    guardando.value = false
  }
}

function seleccionarArchivo(evento) {
  formDocumento.value.archivo = evento.target.files?.[0] || null
}

async function subirDocumento() {
  if (!solicitudActiva.value) {
    mostrarToast('Primero debes crear una solicitud.', 'error')
    return
  }

  if (!formDocumento.value.tipo || !formDocumento.value.archivo) {
    mostrarToast('Selecciona el tipo de documento y el archivo.', 'error')
    return
  }

  subiendo.value = true

  try {
    const fd = new FormData()
    fd.append('archivo', formDocumento.value.archivo)
    fd.append('tipo', formDocumento.value.tipo)
    fd.append('tipo_documento', formDocumento.value.tipo)
    fd.append('nombre', formDocumento.value.tipo)

    await api.post(
      `/alumno/solicitudes/${solicitudActiva.value.id}/documentos`,
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    formDocumento.value = { tipo: '', archivo: null }
    mostrarToast('Documento cargado correctamente.')
    await cargarDatos()
  } catch (e) {
    const errores = e.response?.data?.errors
    const primerError = errores
      ? Object.values(errores).flat().filter(Boolean)[0]
      : null

    mostrarToast(
      primerError ||
      e.response?.data?.message ||
      'No se pudo cargar el documento.',
      'error'
    )
  } finally {
    subiendo.value = false
  }
}

function cerrarSesion() {
  emit('cerrar-sesion')
}

onMounted(cargarDatos)
</script>

<template>
  <div class="alumno-dashboard">
    <transition name="toast">
      <div v-if="toast" class="toast" :class="toast.tipo">
        {{ toast.mensaje }}
      </div>
    </transition>

    <header class="topbar">
      <div class="topbar-inner">
        <div class="brand">
          <div class="brand-logo">
            <span class="up">UP</span><span class="t">T</span><span class="ex">ex</span>
          </div>
          <div class="brand-copy">
            <strong>Sistema de Becas</strong>
            <span>Portal del estudiante · UPTex</span>
          </div>
        </div>

        <nav class="nav">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            :class="{ active: seccion === tab.id }"
            @click="seccion = tab.id"
          >
            {{ tab.label }}
          </button>
        </nav>

        <div class="profile">
          <div class="profile-copy">
            <strong>{{ usuario?.name || 'Alumno' }}</strong>
            <span>{{ usuario?.matricula || 'Estudiante' }}</span>
          </div>
          <div class="avatar">{{ iniciales(usuario?.name) }}</div>
          <button class="logout" type="button" @click="cerrarSesion">
            Salir
          </button>
        </div>
      </div>
    </header>

    <main class="main">
      <div v-if="errorGeneral" class="warning-banner">
        <div>
          <strong>Atención</strong>
          <span>{{ errorGeneral }}</span>
        </div>
        <button @click="cargarDatos">Reintentar</button>
      </div>

      <div v-if="cargando" class="loading-card">
        <div class="spinner"></div>
        <strong>Cargando tu panel</strong>
        <span>Consultando convocatoria, solicitud y documentos...</span>
      </div>

      <template v-else>
        <!-- RESUMEN -->
        <section v-if="seccion === 'resumen'" class="space">
          <div class="hero">
            <div class="hero-copy">
              <span class="eyebrow">PORTAL DEL ESTUDIANTE</span>
              <h1>Hola, {{ usuario?.name?.split(' ')[0] || 'estudiante' }}</h1>
              <p>
                Consulta tu convocatoria, completa tu solicitud y da seguimiento
                a tu proceso de beca desde un solo lugar.
              </p>

              <div class="hero-actions">
                <button
                  v-if="puedeCrear && convocatoriaAbierta"
                  class="primary-button"
                  @click="seccion = 'solicitud'"
                >
                  Iniciar solicitud
                </button>
                <button
                  v-else-if="solicitudActiva"
                  class="primary-button"
                  @click="seccion = 'solicitud'"
                >
                  Ver mi solicitud
                </button>
                <button class="soft-button" @click="cargarDatos">
                  Actualizar
                </button>
              </div>
            </div>

            <div class="hero-status">
              <span class="hero-status-label">PROCESO ACTUAL</span>
              <strong v-if="solicitudActiva">
                {{ nombreEstado(estadoActual) }}
              </strong>
              <strong v-else>Sin solicitud activa</strong>

              <div class="progress-track">
                <div
                  class="progress-fill"
                  :style="{ width: `${progresoDocumentos}%` }"
                ></div>
              </div>

              <small>
                {{ solicitudActiva
                  ? `${progresoDocumentos}% de avance estimado`
                  : 'Revisa la convocatoria vigente para comenzar' }}
              </small>
            </div>
          </div>

          <div class="kpis">
            <article
              v-for="card in resumenCards"
              :key="card.titulo"
              class="kpi"
              :class="card.clase"
            >
              <span>{{ card.titulo }}</span>
              <strong>{{ card.valor }}</strong>
              <small>{{ card.detalle }}</small>
            </article>
          </div>

          <div class="dashboard-grid">
            <article class="panel convocatoria-card">
              <div class="panel-heading">
                <div>
                  <span class="eyebrow">CONVOCATORIA</span>
                  <h2>Convocatoria vigente</h2>
                </div>
                <span
                  class="badge"
                  :class="convocatoriaAbierta ? 'success' : 'neutral'"
                >
                  {{ convocatoriaAbierta ? 'Disponible' : 'No disponible' }}
                </span>
              </div>

              <div v-if="convocatoria" class="convocatoria-content">
                <h3>{{ convocatoria.nombre || convocatoria.titulo || 'Convocatoria de becas' }}</h3>
                <p>
                  {{ convocatoria.descripcion || 'Consulta las fechas y requisitos antes de registrar tu solicitud.' }}
                </p>

                <div class="details-grid">
                  <div>
                    <span>Inicio</span>
                    <strong>{{ fecha(convocatoria.fecha_inicio) }}</strong>
                  </div>
                  <div>
                    <span>Cierre</span>
                    <strong>{{ fecha(convocatoria.fecha_cierre) }}</strong>
                  </div>
                  <div>
                    <span>Periodo</span>
                    <strong>{{ convocatoria.periodo?.nombre || '—' }}</strong>
                  </div>
                  <div>
                    <span>Promedio mínimo</span>
                    <strong>{{ convocatoria.promedio_minimo ?? 'Según modalidad' }}</strong>
                  </div>
                </div>
              </div>

              <div v-else class="empty-state compact">
                <div class="empty-icon">◎</div>
                <strong>No hay convocatoria vigente</strong>
                <span>Cuando se publique una convocatoria aparecerá aquí.</span>
              </div>
            </article>

            <article class="panel">
              <div class="panel-heading">
                <div>
                  <span class="eyebrow">DATOS ACADÉMICOS</span>
                  <h2>Mi información</h2>
                </div>
              </div>

              <div class="student-info">
                <div class="student-avatar">{{ iniciales(usuario?.name) }}</div>
                <div class="student-name">
                  <strong>{{ usuario?.name || 'Alumno' }}</strong>
                  <span>{{ usuario?.email || '—' }}</span>
                </div>

                <div class="info-row">
                  <span>Matrícula</span>
                  <strong>{{ usuario?.matricula || '—' }}</strong>
                </div>
                <div class="info-row">
                  <span>Carrera</span>
                  <strong>{{ usuario?.carrera?.nombre || 'Asignada en tu perfil' }}</strong>
                </div>
                <div class="info-row">
                  <span>Grupo</span>
                  <strong>{{ usuario?.grupo?.nombre || usuario?.grupo || '—' }}</strong>
                </div>
              </div>
            </article>
          </div>

          <article v-if="solicitudActiva" class="panel current-request">
            <div class="panel-heading">
              <div>
                <span class="eyebrow">SEGUIMIENTO</span>
                <h2>Tu solicitud actual</h2>
              </div>
              <span class="badge" :class="claseEstado(estadoActual)">
                {{ nombreEstado(estadoActual) }}
              </span>
            </div>

            <div class="request-summary">
              <div>
                <span>Folio</span>
                <strong>{{ folio(solicitudActiva) }}</strong>
              </div>
              <div>
                <span>Modalidad</span>
                <strong>{{ modalidadLabel(solicitudActiva.modalidad) }}</strong>
              </div>
              <div>
                <span>Fecha</span>
                <strong>{{ fecha(solicitudActiva.created_at) }}</strong>
              </div>
              <div>
                <span>Documentos</span>
                <strong>{{ documentos.length }}</strong>
              </div>
            </div>
          </article>
        </section>

        <!-- SOLICITUD -->
        <section v-if="seccion === 'solicitud'" class="space">
          <div class="section-heading">
            <div>
              <span class="eyebrow">TRÁMITE</span>
              <h1>Mi solicitud</h1>
              <p>Registra o consulta tu solicitud de apoyo.</p>
            </div>
          </div>

          <article v-if="solicitudActiva" class="panel request-detail">
            <div class="panel-heading">
              <div>
                <span class="eyebrow">{{ folio(solicitudActiva) }}</span>
                <h2>Solicitud registrada</h2>
              </div>
              <span class="badge" :class="claseEstado(estadoActual)">
                {{ nombreEstado(estadoActual) }}
              </span>
            </div>

            <div class="request-summary large">
              <div>
                <span>Modalidad</span>
                <strong>{{ modalidadLabel(solicitudActiva.modalidad) }}</strong>
              </div>
              <div>
                <span>Carrera</span>
                <strong>
                  {{ solicitudActiva.carrera?.nombre || usuario?.carrera?.nombre || '—' }}
                </strong>
              </div>
              <div>
                <span>Grupo</span>
                <strong>
                  {{ solicitudActiva.grupo?.nombre || solicitudActiva.grupo || usuario?.grupo?.nombre || usuario?.grupo || '—' }}
                </strong>
              </div>
              <div>
                <span>Registrada</span>
                <strong>{{ fecha(solicitudActiva.created_at) }}</strong>
              </div>
            </div>

            <div
              v-if="solicitudActiva.observaciones || solicitudActiva.comentarios"
              class="observation"
            >
              <strong>Observaciones</strong>
              <p>{{ solicitudActiva.observaciones || solicitudActiva.comentarios }}</p>
            </div>

            <div class="action-strip">
              <div>
                <strong>¿Te falta documentación?</strong>
                <span>Puedes cargar archivos desde la sección Documentos.</span>
              </div>
              <button class="primary-button" @click="seccion = 'documentos'">
                Ir a documentos
              </button>
            </div>
          </article>

          <article v-else class="panel form-panel">
            <div class="panel-heading">
              <div>
                <span class="eyebrow">NUEVA SOLICITUD</span>
                <h2>Selecciona tu modalidad</h2>
              </div>
            </div>

            <div v-if="!convocatoria" class="empty-state">
              <div class="empty-icon">⌛</div>
              <strong>No hay convocatoria disponible</strong>
              <span>No puedes crear una solicitud hasta que exista una convocatoria vigente.</span>
            </div>

            <div v-else-if="!convocatoriaAbierta" class="empty-state">
              <div class="empty-icon">⊘</div>
              <strong>La convocatoria no está abierta</strong>
              <span>Revisa las fechas de apertura y cierre.</span>
            </div>

            <form v-else class="application-form" @submit.prevent="crearSolicitud">
              <div class="conv-mini">
                <span>Convocatoria</span>
                <strong>{{ convocatoria.nombre || convocatoria.titulo }}</strong>
                <small>
                  {{ fecha(convocatoria.fecha_inicio) }} — {{ fecha(convocatoria.fecha_cierre) }}
                </small>
              </div>

              <label class="field full">
                <span>Modalidad *</span>
                <select v-model="formSolicitud.modalidad" required>
                  <option value="">Selecciona una modalidad</option>
                  <option
                    v-for="m in modalidades"
                    :key="m.value"
                    :value="m.value"
                  >
                    {{ m.label }}
                  </option>
                </select>
              </label>

              <label class="field">
                <span>Carrera</span>
                <select v-model="formSolicitud.carrera_id">
                  <option value="">Usar carrera de mi perfil</option>
                  <option v-for="c in carreras" :key="c.id" :value="c.id">
                    {{ c.nombre }}
                  </option>
                </select>
              </label>

              <label class="field">
                <span>Grupo</span>
                <input
                  v-model="formSolicitud.grupo"
                  type="text"
                  placeholder="Ej. 8A"
                />
              </label>

              <div class="form-note full">
                Al enviar la solicitud confirmas que la información registrada es correcta.
              </div>

              <div class="form-actions full">
                <button
                  type="submit"
                  class="primary-button"
                  :disabled="guardando"
                >
                  {{ guardando ? 'Registrando...' : 'Registrar solicitud' }}
                </button>
              </div>
            </form>
          </article>
        </section>

        <!-- DOCUMENTOS -->
        <section v-if="seccion === 'documentos'" class="space">
          <div class="section-heading">
            <div>
              <span class="eyebrow">EXPEDIENTE DIGITAL</span>
              <h1>Documentos</h1>
              <p>Adjunta los comprobantes correspondientes a tu modalidad.</p>
            </div>
          </div>

          <div v-if="!solicitudActiva" class="panel empty-state">
            <div class="empty-icon">▣</div>
            <strong>Primero crea una solicitud</strong>
            <span>Después podrás integrar tu expediente digital.</span>
            <button class="primary-button" @click="seccion = 'solicitud'">
              Ir a mi solicitud
            </button>
          </div>

          <template v-else>
            <div class="documents-grid">
              <article class="panel upload-panel">
                <div class="panel-heading">
                  <div>
                    <span class="eyebrow">NUEVO ARCHIVO</span>
                    <h2>Cargar documento</h2>
                  </div>
                </div>

                <form class="upload-form" @submit.prevent="subirDocumento">
                  <label class="field">
                    <span>Tipo de documento *</span>
                    <select v-model="formDocumento.tipo" required>
                      <option value="">Selecciona</option>
                      <option value="HISTORIAL_ACADEMICO">Historial académico</option>
                      <option value="CERTIFICADO_MEDICO">Certificado médico</option>
                      <option value="COMPROBANTE_INGRESOS">Comprobante de ingresos</option>
                      <option value="CONSTANCIA_INGRESOS">Constancia de ingresos</option>
                      <option value="COMPROBANTE_DOMICILIO">Comprobante de domicilio</option>
                      <option value="IDENTIFICACION">Identificación</option>
                      <option value="OTRO">Otro documento</option>
                    </select>
                  </label>

                  <label class="file-drop">
                    <input
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      @change="seleccionarArchivo"
                    />
                    <span class="file-icon">↑</span>
                    <strong>
                      {{ formDocumento.archivo?.name || 'Seleccionar archivo' }}
                    </strong>
                    <small>PDF, JPG o PNG</small>
                  </label>

                  <button
                    type="submit"
                    class="primary-button"
                    :disabled="subiendo"
                  >
                    {{ subiendo ? 'Subiendo...' : 'Subir documento' }}
                  </button>
                </form>
              </article>

              <article class="panel">
                <div class="panel-heading">
                  <div>
                    <span class="eyebrow">AVANCE</span>
                    <h2>Expediente</h2>
                  </div>
                  <strong class="progress-number">{{ progresoDocumentos }}%</strong>
                </div>

                <div class="progress-card">
                  <div class="progress-track big">
                    <div
                      class="progress-fill"
                      :style="{ width: `${progresoDocumentos}%` }"
                    ></div>
                  </div>
                  <p>
                    Mantén tus documentos completos y legibles para evitar observaciones.
                  </p>
                </div>
              </article>
            </div>

            <article class="panel">
              <div class="panel-heading">
                <div>
                  <span class="eyebrow">ARCHIVOS</span>
                  <h2>Documentos cargados</h2>
                </div>
                <span class="count-badge">{{ documentos.length }}</span>
              </div>

              <div v-if="documentos.length" class="document-list">
                <div
                  v-for="doc in documentos"
                  :key="doc.id"
                  class="document-row"
                >
                  <div class="document-icon">PDF</div>
                  <div class="document-main">
                    <strong>
                      {{ doc.nombre || doc.tipo_documento || doc.tipo || 'Documento' }}
                    </strong>
                    <span>{{ fecha(doc.created_at) }}</span>
                  </div>
                  <span
                    class="badge"
                    :class="claseEstado(doc.estado || doc.estatus)"
                  >
                    {{ nombreEstado(doc.estado || doc.estatus || 'CARGADO') }}
                  </span>
                  <a
                    v-if="urlArchivo(doc)"
                    :href="urlArchivo(doc)"
                    target="_blank"
                    rel="noopener"
                    class="text-button"
                  >
                    Ver
                  </a>
                </div>
              </div>

              <div v-else class="empty-state compact">
                <div class="empty-icon">□</div>
                <strong>Aún no has cargado documentos</strong>
                <span>Usa el formulario de arriba para comenzar.</span>
              </div>
            </article>
          </template>
        </section>

        <!-- HISTORIAL -->
        <section v-if="seccion === 'historial'" class="space">
          <div class="section-heading">
            <div>
              <span class="eyebrow">SEGUIMIENTO</span>
              <h1>Historial de solicitudes</h1>
              <p>Consulta tus trámites anteriores y su resultado.</p>
            </div>
          </div>

          <article class="panel">
            <div v-if="solicitudes.length" class="history-list">
              <div
                v-for="s in solicitudes"
                :key="s.id"
                class="history-row"
              >
                <div class="history-folio">
                  <span>Folio</span>
                  <strong>{{ folio(s) }}</strong>
                </div>

                <div>
                  <span>Convocatoria</span>
                  <strong>{{ s.convocatoria?.nombre || 'Convocatoria' }}</strong>
                </div>

                <div>
                  <span>Modalidad</span>
                  <strong>{{ modalidadLabel(s.modalidad) }}</strong>
                </div>

                <div>
                  <span>Fecha</span>
                  <strong>{{ fecha(s.created_at) }}</strong>
                </div>

                <span class="badge" :class="claseEstado(s.estado || s.estatus)">
                  {{ nombreEstado(s.estado || s.estatus) }}
                </span>
              </div>
            </div>

            <div v-else class="empty-state">
              <div class="empty-icon">↺</div>
              <strong>Sin historial todavía</strong>
              <span>Tus solicitudes aparecerán aquí.</span>
            </div>
          </article>
        </section>
      </template>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.alumno-dashboard {
  min-height: 100vh;
  background: #f4f6f5;
  color: #29312d;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  border-bottom: 1px solid #e2e6e3;
  background: rgba(255,255,255,.96);
  backdrop-filter: blur(16px);
}

.topbar-inner {
  width: min(1440px, calc(100% - 40px));
  min-height: 74px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 22px;
}

.brand,
.profile,
.nav {
  display: flex;
  align-items: center;
}

.brand {
  gap: 12px;
}

.brand-logo {
  display: flex;
  align-items: baseline;
  font-size: 22px;
  font-weight: 950;
  letter-spacing: -.07em;
}

.brand-logo .up { color: #111827; }
.brand-logo .t { color: #7a1c33; margin-left: 1px; }
.brand-logo .ex { color: #147a4a; }

.brand-copy {
  display: flex;
  flex-direction: column;
}

.brand-copy strong {
  font-size: 12px;
}

.brand-copy span,
.profile-copy span {
  margin-top: 2px;
  color: #9aa19d;
  font-size: 8px;
}

.nav {
  padding: 4px;
  gap: 3px;
  border: 1px solid #e6eae7;
  border-radius: 12px;
  background: #f7f9f8;
}

.nav button {
  border: 0;
  border-radius: 9px;
  background: transparent;
  padding: 9px 12px;
  color: #767e79;
  font: inherit;
  font-size: 9px;
  font-weight: 800;
  cursor: pointer;
}

.nav button:hover {
  color: #147a4a;
}

.nav button.active {
  background: #fff;
  color: #147a4a;
  box-shadow: 0 3px 10px rgba(26,42,33,.08);
}

.profile {
  justify-content: flex-end;
  gap: 10px;
}

.profile-copy {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.profile-copy strong {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 10px;
}

.avatar,
.student-avatar {
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #e9f4ee;
  color: #147a4a;
  font-weight: 900;
}

.avatar {
  width: 34px;
  height: 34px;
  font-size: 10px;
}

.logout {
  border: 1px solid #eadde1;
  border-radius: 9px;
  background: #fff;
  color: #8e2843;
  padding: 8px 10px;
  font: inherit;
  font-size: 8px;
  font-weight: 800;
  cursor: pointer;
}

.main {
  width: min(1240px, calc(100% - 40px));
  margin: 0 auto;
  padding: 30px 0 60px;
}

.space {
  display: grid;
  gap: 18px;
}

.hero {
  min-height: 225px;
  display: grid;
  grid-template-columns: 1.45fr .75fr;
  align-items: stretch;
  overflow: hidden;
  border-radius: 22px;
  background:
    radial-gradient(circle at 90% 10%, rgba(255,255,255,.11), transparent 28%),
    linear-gradient(135deg, #0f6f45 0%, #0a5d39 55%, #084c31 100%);
  color: white;
  box-shadow: 0 18px 40px rgba(12,83,51,.13);
}

.hero-copy {
  padding: 34px 38px;
}

.eyebrow {
  color: #8f9893;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.hero .eyebrow {
  color: #a9d7bd;
}

.hero h1 {
  margin: 7px 0 8px;
  font-size: clamp(28px, 4vw, 42px);
  line-height: 1;
  letter-spacing: -.045em;
}

.hero p {
  max-width: 620px;
  margin: 0;
  color: rgba(255,255,255,.78);
  font-size: 11px;
  line-height: 1.65;
}

.hero-actions {
  display: flex;
  gap: 9px;
  margin-top: 24px;
}

.hero .soft-button {
  border-color: rgba(255,255,255,.18);
  background: rgba(255,255,255,.08);
  color: white;
}

.hero-status {
  min-height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 32px;
  background: rgba(0,0,0,.09);
  border-left: 1px solid rgba(255,255,255,.10);
}

.hero-status-label {
  color: #a9d7bd;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: .12em;
}

.hero-status > strong {
  margin-top: 9px;
  font-size: 20px;
}

.hero-status small {
  margin-top: 9px;
  color: rgba(255,255,255,.68);
  font-size: 8px;
}

.progress-track {
  width: 100%;
  height: 7px;
  margin-top: 18px;
  overflow: hidden;
  border-radius: 99px;
  background: rgba(255,255,255,.17);
}

.progress-track.big {
  height: 10px;
  margin-top: 0;
  background: #e8ece9;
}

.progress-fill {
  height: 100%;
  border-radius: inherit;
  background: #dfb34b;
  transition: width .35s ease;
}

.kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.kpi {
  min-height: 112px;
  padding: 18px;
  border: 1px solid #e2e6e3;
  border-top: 3px solid #7d8580;
  border-radius: 16px;
  background: white;
  box-shadow: 0 8px 22px rgba(28,40,33,.035);
}

.kpi.success { border-top-color: #147a4a; }
.kpi.info { border-top-color: #3b82b6; }
.kpi.warning { border-top-color: #d99a25; }
.kpi.purple { border-top-color: #7656a5; }
.kpi.danger { border-top-color: #a63a51; }

.kpi span {
  color: #8f9692;
  font-size: 8px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: .08em;
}

.kpi strong {
  display: block;
  margin-top: 7px;
  color: #29312d;
  font-size: 22px;
  line-height: 1.15;
}

.kpi small {
  display: block;
  margin-top: 8px;
  color: #a0a6a3;
  font-size: 8px;
}

.dashboard-grid,
.documents-grid {
  display: grid;
  grid-template-columns: 1.45fr .75fr;
  gap: 16px;
}

.panel {
  overflow: hidden;
  border: 1px solid #e2e6e3;
  border-radius: 18px;
  background: white;
  box-shadow: 0 10px 28px rgba(27,39,32,.04);
}

.panel-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 20px 12px;
}

.panel-heading h2 {
  margin: 5px 0 0;
  font-size: 16px;
}

.convocatoria-content {
  padding: 5px 20px 22px;
}

.convocatoria-content h3 {
  margin: 0 0 7px;
  font-size: 15px;
}

.convocatoria-content > p {
  margin: 0;
  color: #808883;
  font-size: 9px;
  line-height: 1.6;
}

.details-grid,
.request-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 9px;
  margin-top: 20px;
}

.details-grid > div,
.request-summary > div {
  padding: 13px;
  border: 1px solid #e8ebe9;
  border-radius: 12px;
  background: #fafbfa;
}

.details-grid span,
.request-summary span,
.history-row > div > span,
.info-row span {
  display: block;
  color: #9aa09d;
  font-size: 7px;
  font-weight: 850;
  text-transform: uppercase;
  letter-spacing: .07em;
}

.details-grid strong,
.request-summary strong,
.history-row > div > strong {
  display: block;
  margin-top: 5px;
  font-size: 9px;
}

.student-info {
  padding: 4px 20px 22px;
}

.student-avatar {
  width: 50px;
  height: 50px;
  font-size: 14px;
}

.student-name {
  margin: 10px 0 16px;
}

.student-name strong {
  display: block;
  font-size: 13px;
}

.student-name span {
  color: #959c98;
  font-size: 8px;
}

.info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 10px 0;
  border-top: 1px solid #edf0ee;
}

.info-row strong {
  font-size: 9px;
  text-align: right;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: end;
  padding: 2px 2px 4px;
}

.section-heading h1 {
  margin: 5px 0 3px;
  font-size: 28px;
  letter-spacing: -.04em;
}

.section-heading p {
  margin: 0;
  color: #8d9590;
  font-size: 9px;
}

.request-detail,
.form-panel {
  min-height: 360px;
}

.request-summary.large {
  padding: 0 20px;
}

.observation {
  margin: 18px 20px 0;
  padding: 15px;
  border: 1px solid #f0dfad;
  border-radius: 12px;
  background: #fffaf0;
}

.observation strong {
  color: #8b6a19;
  font-size: 9px;
}

.observation p {
  margin: 6px 0 0;
  color: #746b56;
  font-size: 9px;
  line-height: 1.55;
}

.action-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin: 20px;
  padding: 16px 18px;
  border-radius: 14px;
  background: #f1f7f4;
}

.action-strip strong {
  display: block;
  font-size: 10px;
}

.action-strip span {
  display: block;
  margin-top: 3px;
  color: #7f8983;
  font-size: 8px;
}

.application-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  padding: 6px 20px 24px;
}

.full {
  grid-column: 1 / -1;
}

.conv-mini {
  grid-column: 1 / -1;
  padding: 15px;
  border-radius: 13px;
  background: #f3f8f5;
}

.conv-mini span,
.conv-mini small {
  display: block;
  color: #89918c;
  font-size: 8px;
}

.conv-mini strong {
  display: block;
  margin: 4px 0;
  font-size: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field > span {
  color: #68716c;
  font-size: 8px;
  font-weight: 850;
}

.field input,
.field select {
  width: 100%;
  border: 1px solid #dfe4e1;
  border-radius: 10px;
  background: #fff;
  color: #414944;
  padding: 11px 12px;
  outline: none;
  font: inherit;
  font-size: 9px;
}

.field input:focus,
.field select:focus {
  border-color: #70a88a;
  box-shadow: 0 0 0 3px #edf6f1;
}

.form-note {
  padding: 12px 14px;
  border-radius: 10px;
  background: #fafbfa;
  color: #858d88;
  font-size: 8px;
  line-height: 1.5;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

.upload-panel {
  min-height: 320px;
}

.upload-form {
  display: grid;
  gap: 14px;
  padding: 7px 20px 22px;
}

.file-drop {
  min-height: 130px;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 3px;
  border: 1px dashed #b9c6be;
  border-radius: 14px;
  background: #fafcfb;
  cursor: pointer;
}

.file-drop input {
  display: none;
}

.file-icon {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  margin-bottom: 4px;
  border-radius: 50%;
  background: #e9f4ee;
  color: #147a4a;
  font-size: 18px;
  font-weight: 900;
}

.file-drop strong {
  font-size: 9px;
}

.file-drop small {
  color: #9aa19d;
  font-size: 7px;
}

.progress-number {
  color: #147a4a;
  font-size: 20px;
}

.progress-card {
  padding: 10px 20px 22px;
}

.progress-card p {
  margin: 13px 0 0;
  color: #87908b;
  font-size: 9px;
  line-height: 1.55;
}

.document-list {
  padding: 0 20px 12px;
}

.document-row {
  display: grid;
  grid-template-columns: auto 1fr auto auto;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-top: 1px solid #edf0ee;
}

.document-icon {
  display: grid;
  place-items: center;
  width: 34px;
  height: 38px;
  border-radius: 8px;
  background: #f8ecef;
  color: #8e2843;
  font-size: 7px;
  font-weight: 950;
}

.document-main strong,
.document-main span {
  display: block;
}

.document-main strong {
  font-size: 9px;
}

.document-main span {
  margin-top: 3px;
  color: #9da39f;
  font-size: 7px;
}

.history-list {
  padding: 0 20px 12px;
}

.history-row {
  display: grid;
  grid-template-columns: .8fr 1.5fr 1.1fr .8fr auto;
  align-items: center;
  gap: 18px;
  padding: 16px 0;
  border-top: 1px solid #edf0ee;
}

.history-row:first-child {
  border-top: 0;
}

.history-folio strong {
  color: #147a4a;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 6px 9px;
  border-radius: 99px;
  background: #f0f2f1;
  color: #6e7772;
  font-size: 7px;
  font-weight: 900;
  white-space: nowrap;
}

.badge.success {
  background: #e8f5ed;
  color: #147a4a;
}

.badge.warning {
  background: #fff4da;
  color: #956910;
}

.badge.info {
  background: #e9f3fa;
  color: #2e6f98;
}

.badge.purple {
  background: #f1ebf8;
  color: #71519a;
}

.badge.danger {
  background: #f9e9ed;
  color: #9a3149;
}

.count-badge {
  display: grid;
  place-items: center;
  min-width: 29px;
  height: 29px;
  padding: 0 8px;
  border-radius: 99px;
  background: #edf6f1;
  color: #147a4a;
  font-size: 9px;
  font-weight: 900;
}

.primary-button,
.soft-button,
.text-button,
.warning-banner button {
  border: 0;
  border-radius: 10px;
  padding: 10px 14px;
  font: inherit;
  font-size: 8px;
  font-weight: 850;
  cursor: pointer;
}

.primary-button {
  background: #147a4a;
  color: white;
  box-shadow: 0 6px 14px rgba(20,122,74,.17);
}

.primary-button:hover {
  background: #106d41;
}

.primary-button:disabled {
  cursor: not-allowed;
  opacity: .55;
}

.soft-button {
  border: 1px solid #dfe4e1;
  background: white;
  color: #59625d;
}

.text-button {
  padding: 7px 9px;
  background: #f4f8f6;
  color: #147a4a;
  text-decoration: none;
}

.empty-state {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  text-align: center;
}

.empty-state.compact {
  min-height: 190px;
}

.empty-state .primary-button {
  margin-top: 16px;
}

.empty-icon {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  margin-bottom: 11px;
  border-radius: 50%;
  background: #f0f5f2;
  color: #147a4a;
  font-size: 23px;
}

.empty-state strong {
  font-size: 12px;
}

.empty-state span {
  max-width: 390px;
  margin-top: 5px;
  color: #939a96;
  font-size: 8px;
  line-height: 1.5;
}

.warning-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 16px;
  padding: 13px 15px;
  border: 1px solid #f0d6a0;
  border-radius: 12px;
  background: #fff9ed;
}

.warning-banner strong,
.warning-banner span {
  display: block;
}

.warning-banner strong {
  color: #8f6615;
  font-size: 9px;
}

.warning-banner span {
  margin-top: 2px;
  color: #8b8069;
  font-size: 8px;
}

.warning-banner button {
  background: #f5e8ca;
  color: #7f5d17;
}

.loading-card {
  min-height: 360px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e6e3;
  border-radius: 18px;
  background: white;
}

.spinner {
  width: 34px;
  height: 34px;
  margin-bottom: 14px;
  border: 3px solid #e5ebe7;
  border-top-color: #147a4a;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}

.loading-card strong {
  font-size: 11px;
}

.loading-card span {
  margin-top: 4px;
  color: #9aa19d;
  font-size: 8px;
}

.toast {
  position: fixed;
  top: 90px;
  right: 24px;
  z-index: 100;
  max-width: 360px;
  padding: 12px 15px;
  border-radius: 11px;
  background: #174f35;
  color: white;
  box-shadow: 0 14px 36px rgba(0,0,0,.16);
  font-size: 9px;
  font-weight: 750;
}

.toast.error {
  background: #8e2843;
}

.toast-enter-active,
.toast-leave-active {
  transition: .2s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1050px) {
  .topbar-inner {
    grid-template-columns: 1fr auto;
  }

  .nav {
    order: 3;
    grid-column: 1 / -1;
    justify-content: center;
    margin-bottom: 10px;
  }

  .hero,
  .dashboard-grid,
  .documents-grid {
    grid-template-columns: 1fr;
  }

  .hero-status {
    border-left: 0;
    border-top: 1px solid rgba(255,255,255,.1);
  }

  .kpis {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 720px) {
  .topbar-inner,
  .main {
    width: min(100% - 22px, 1240px);
  }

  .brand-copy {
    display: none;
  }

  .profile-copy {
    display: none;
  }

  .nav {
    overflow-x: auto;
    justify-content: flex-start;
  }

  .nav button {
    white-space: nowrap;
  }

  .hero-copy,
  .hero-status {
    padding: 25px 22px;
  }

  .kpis {
    grid-template-columns: 1fr 1fr;
  }

  .details-grid,
  .request-summary,
  .application-form {
    grid-template-columns: 1fr;
  }

  .application-form .full,
  .conv-mini {
    grid-column: auto;
  }

  .history-row {
    grid-template-columns: 1fr 1fr;
  }

  .history-row .badge {
    grid-column: 1 / -1;
  }

  .document-row {
    grid-template-columns: auto 1fr;
  }

  .document-row .badge,
  .document-row .text-button {
    grid-column: 2;
  }
}

@media (max-width: 460px) {
  .kpis {
    grid-template-columns: 1fr;
  }

  .profile .logout {
    padding: 7px 8px;
  }

  .hero-actions,
  .action-strip {
    align-items: stretch;
    flex-direction: column;
  }

  .history-row {
    grid-template-columns: 1fr;
  }

  .history-row .badge {
    grid-column: auto;
  }
}
</style>
