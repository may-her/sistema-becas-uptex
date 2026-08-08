<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

import api from '../api/axios'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Tooltip,
  Legend
)

/* =========================================================
   PROPS
========================================================= */

const props = defineProps({
  usuario: {
    type: Object,
    default: null
  }
})

const emit = defineEmits([
  'cerrar-sesion'
])

/* =========================================================
   NAVEGACIÓN
========================================================= */

const seccion = ref('resumen')

const tabs = [
  { id: 'resumen', label: 'Resumen' },
  { id: 'solicitudes', label: 'Solicitudes' },
  { id: 'convocatorias', label: 'Convocatorias' },
  { id: 'periodos', label: 'Periodos' },
  { id: 'carreras', label: 'Carreras' },
  { id: 'grupos', label: 'Grupos' },
  { id: 'alumnos', label: 'Alumnos' },
  { id: 'personal', label: 'Personal' },
  { id: 'alertas', label: 'Alertas' }
]

/* =========================================================
   ESTADO
========================================================= */

const cargando = ref(true)
const errorGeneral = ref('')
const toast = ref(null)
const modal = ref(null)

const solicitudes = ref([])
const convocatorias = ref([])
const periodos = ref([])
const carreras = ref([])
const grupos = ref([])
const alumnos = ref([])
const staff = ref([])
const statsApi = ref({})

/* =========================================================
   FILTROS
========================================================= */

const busqueda = ref('')
const filtroPeriodo = ref('todos')
const filtroCarrera = ref('todos')
const filtroGrupo = ref('todos')
const filtroEstado = ref('todos')

const estados = [
  'PENDIENTE',
  'EN_REVISION',
  'DOCUMENTACION_INCOMPLETA',
  'ACEPTADA',
  'RECHAZADA'
]

/* =========================================================
   HELPERS
========================================================= */

function unwrap(data) {
  if (Array.isArray(data)) return data

  const claves = [
    'data',
    'solicitudes',
    'usuarios',
    'convocatorias',
    'periodos',
    'carreras',
    'grupos',
    'alumnos',
    'staff'
  ]

  for (const clave of claves) {
    if (Array.isArray(data?.[clave])) {
      return data[clave]
    }
  }

  return []
}

function mostrarToast(mensaje, tipo = 'ok') {
  toast.value = {
    mensaje,
    tipo
  }

  setTimeout(() => {
    toast.value = null
  }, 3200)
}

function estado(valor) {
  return String(valor || '')
    .trim()
    .toUpperCase()
}

function nombreEstado(valor) {
  const mapa = {
    PENDIENTE: 'Pendiente',
    EN_REVISION: 'En revisión',
    DOCUMENTACION_INCOMPLETA: 'Docs. incompletos',
    ACEPTADA: 'Aceptada',
    RECHAZADA: 'Rechazada',

    BORRADOR: 'Borrador',
    PUBLICADA: 'Publicada',
    CERRADA: 'Cerrada',

    ACTIVO: 'Activo',
    INACTIVO: 'Inactivo',

    ACTIVA: 'Activa',
    INACTIVA: 'Inactiva'
  }

  return mapa[estado(valor)] || valor || 'Sin estado'
}

function claseEstado(valor) {
  const v = estado(valor)

  if (
    ['ACEPTADA', 'PUBLICADA', 'ACTIVO', 'ACTIVA']
      .includes(v)
  ) return 'success'

  if (
    ['RECHAZADA', 'CERRADA', 'INACTIVO', 'INACTIVA']
      .includes(v)
  ) return 'danger'

  if (v === 'EN_REVISION') return 'info'

  if (v === 'DOCUMENTACION_INCOMPLETA') {
    return 'purple'
  }

  return 'warning'
}

function iniciales(nombre) {
  return String(nombre || 'SA')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(v => v[0]?.toUpperCase())
    .join('')
}

function fecha(valor) {
  if (!valor) return '—'

  const d = new Date(valor)

  if (Number.isNaN(d.getTime())) {
    return valor
  }

  return d.toLocaleDateString('es-MX')
}

function carreraPorId(id) {
  return carreras.value.find(
    c => String(c.id) === String(id)
  )
}

function grupoPorId(id) {
  return grupos.value.find(
    g => String(g.id) === String(id)
  )
}

function alumnoDe(s) {
  return s?.user || s?.alumno || {}
}

function carreraSolicitud(s) {
  const alumno = alumnoDe(s)

  return (
    alumno?.carrera?.nombre ||
    carreraPorId(
      alumno?.carrera_id ||
      s?.carrera_id
    )?.nombre ||
    'Sin carrera'
  )
}

function periodoSolicitud(s) {
  return (
    s?.convocatoria?.periodo?.nombre ||
    s?.periodo?.nombre ||
    'Sin periodo'
  )
}

function folio(s) {
  return s?.folio ||
    `BEC-${String(s?.id || 0).padStart(5, '0')}`
}

/* =========================================================
   CARGA DE DATOS
========================================================= */

async function cargarTodo() {
  cargando.value = true
  errorGeneral.value = ''

  const respuestas = await Promise.allSettled([
    api.get('/master/stats'),
    api.get('/master/solicitudes'),
    api.get('/master/convocatorias'),
    api.get('/master/periodos'),
    api.get('/master/carreras'),
    api.get('/master/grupos'),
    api.get('/master/alumnos'),
    api.get('/master/staff')
  ])

  const [
    rStats,
    rSolicitudes,
    rConvocatorias,
    rPeriodos,
    rCarreras,
    rGrupos,
    rAlumnos,
    rStaff
  ] = respuestas

  if (rStats.status === 'fulfilled') {
    statsApi.value =
      rStats.value.data?.stats ||
      rStats.value.data ||
      {}
  }

  if (rSolicitudes.status === 'fulfilled') {
    solicitudes.value =
      unwrap(rSolicitudes.value.data)
  }

  if (rConvocatorias.status === 'fulfilled') {
    convocatorias.value =
      unwrap(rConvocatorias.value.data)
  }

  if (rPeriodos.status === 'fulfilled') {
    periodos.value =
      unwrap(rPeriodos.value.data)
  }

  if (rCarreras.status === 'fulfilled') {
    carreras.value =
      unwrap(rCarreras.value.data)
  }

  if (rGrupos.status === 'fulfilled') {
    grupos.value =
      unwrap(rGrupos.value.data)
  }

  if (rAlumnos.status === 'fulfilled') {
    alumnos.value =
      unwrap(rAlumnos.value.data)
  }

  if (rStaff.status === 'fulfilled') {
    staff.value =
      unwrap(rStaff.value.data)
  }

  const fallidos =
    respuestas.filter(
      r => r.status === 'rejected'
    )

  if (fallidos.length) {
    errorGeneral.value =
      `${fallidos.length} módulo(s) no respondieron. El resto sigue disponible.`
  }

  cargando.value = false
}

/* =========================================================
   FILTROS
========================================================= */

const solicitudesFiltradas = computed(() => {
  const q =
    busqueda.value
      .trim()
      .toLowerCase()

  return solicitudes.value.filter(s => {
    const alumno = alumnoDe(s)

    const idPeriodo =
      s?.convocatoria?.periodo_id ||
      s?.convocatoria?.periodo?.id

    const idCarrera =
      alumno?.carrera_id ||
      s?.carrera_id

    const estadoSolicitud =
      estado(s.estado || s.estatus)

    const universo = [
      alumno.name,
      alumno.matricula,
      alumno.email,
      folio(s),
      carreraSolicitud(s)
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return (
      (
        filtroPeriodo.value === 'todos' ||
        String(idPeriodo) ===
          String(filtroPeriodo.value)
      )
      &&
      (
        filtroCarrera.value === 'todos' ||
        String(idCarrera) ===
          String(filtroCarrera.value)
      )
      &&
      (
        filtroEstado.value === 'todos' ||
        estadoSolicitud === filtroEstado.value
      )
      &&
      (
        !q ||
        universo.includes(q)
      )
    )
  })
})

const alumnosFiltrados = computed(() => {
  const q =
    busqueda.value
      .trim()
      .toLowerCase()

  return alumnos.value.filter(a => {
    const okCarrera =
      filtroCarrera.value === 'todos' ||
      String(a.carrera_id) ===
        String(filtroCarrera.value)

    const okGrupo =
      filtroGrupo.value === 'todos' ||
      String(a.grupo_id) ===
        String(filtroGrupo.value)

    const universo = [
      a.name,
      a.email,
      a.matricula,
      a.grupo,
      a.carrera?.nombre,
      a.grupo_relacion?.nombre
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return (
      okCarrera &&
      okGrupo &&
      (
        !q ||
        universo.includes(q)
      )
    )
  })
})

/* =========================================================
   RESUMEN
========================================================= */

const resumen = computed(() => {
  const lista =
    solicitudesFiltradas.value

  const contar = e =>
    lista.filter(
      s =>
        estado(
          s.estado ||
          s.estatus
        ) === e
    ).length

  return {
    solicitudes: lista.length,
    pendientes: contar('PENDIENTE'),
    revision: contar('EN_REVISION'),
    incompletas:
      contar('DOCUMENTACION_INCOMPLETA'),
    aceptadas: contar('ACEPTADA'),
    rechazadas: contar('RECHAZADA'),

    alumnos:
      alumnos.value.length ||
      statsApi.value.alumnos ||
      0,

    personal:
      staff.value.length,

    carreras:
      carreras.value.length,

    grupos:
      grupos.value.length,

    convocatorias:
      convocatorias.value.length
  }
})

const periodoActivo = computed(() =>
  periodos.value.find(
    p => estado(p.estado) === 'ACTIVO'
  ) || null
)

const convocatoriaVigente = computed(() =>
  convocatorias.value.find(
    c => estado(c.estado) === 'PUBLICADA'
  ) || null
)

/* =========================================================
   GRÁFICAS
========================================================= */

const chartCarreras = computed(() => {
  const datos = {}

  solicitudesFiltradas.value.forEach(s => {
    const nombre =
      carreraSolicitud(s)

    datos[nombre] =
      (datos[nombre] || 0) + 1
  })

  return {
    labels:
      Object.keys(datos),

    datasets: [{
      label: 'Solicitudes',
      data: Object.values(datos),
      backgroundColor: '#147a4a',
      borderRadius: 8,
      maxBarThickness: 42
    }]
  }
})

const chartEstados = computed(() => ({
  labels: [
    'Pendientes',
    'En revisión',
    'Docs. incompletos',
    'Aceptadas',
    'Rechazadas'
  ],

  datasets: [{
    data: [
      resumen.value.pendientes,
      resumen.value.revision,
      resumen.value.incompletas,
      resumen.value.aceptadas,
      resumen.value.rechazadas
    ],

    backgroundColor: [
      '#d99a25',
      '#3b82b6',
      '#7754a4',
      '#147a4a',
      '#8e2843'
    ],

    borderWidth: 0
  }]
}))

const opcionesBarras = {
  responsive: true,
  maintainAspectRatio: false,

  plugins: {
    legend: {
      display: false
    }
  },

  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0
      }
    }
  }
}

const opcionesDona = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',

  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

/* =========================================================
   SOLICITUDES
========================================================= */

const solicitudSeleccionada = ref(null)

function abrirSolicitud(s) {
  solicitudSeleccionada.value = s
  modal.value = 'solicitud'
}

async function actualizarSolicitud(
  nuevoEstado
) {
  try {
    await api.patch(
      `/master/solicitudes/${solicitudSeleccionada.value.id}/estatus`,
      {
        estado: nuevoEstado
      }
    )

    modal.value = null

    await cargarTodo()

    mostrarToast(
      'Solicitud actualizada correctamente.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo actualizar.',
      'error'
    )
  }
}

/* =========================================================
   CONVOCATORIAS
========================================================= */

const convocatoriaForm = ref({})

function nuevaConvocatoria() {
  convocatoriaForm.value = {
    id: null,
    periodo_id:
      periodoActivo.value?.id || '',
    nombre: '',
    descripcion: '',
    requisitos: '',
    promedio_minimo: 8,
    fecha_inicio: '',
    fecha_cierre: '',
    estado: 'BORRADOR',
    archivo: null
  }

  modal.value = 'convocatoria'
}

function editarConvocatoria(c) {
  convocatoriaForm.value = {
    id: c.id,
    periodo_id:
      c.periodo_id ||
      c.periodo?.id ||
      '',
    nombre:
      c.nombre ||
      c.titulo ||
      '',
    descripcion:
      c.descripcion || '',
    requisitos:
      c.requisitos || '',
    promedio_minimo:
      c.promedio_minimo ?? 8,
    fecha_inicio:
      String(c.fecha_inicio || '')
        .slice(0, 10),
    fecha_cierre:
      String(c.fecha_cierre || '')
        .slice(0, 10),
    estado:
      c.estado || 'BORRADOR',
    archivo: null
  }

  modal.value = 'convocatoria'
}

function seleccionarPdf(evento) {
  convocatoriaForm.value.archivo =
    evento.target.files?.[0] || null
}

async function guardarConvocatoria() {
  const f =
    convocatoriaForm.value

  const payload = {
    periodo_id: f.periodo_id,
    nombre: f.nombre,
    descripcion: f.descripcion,
    requisitos: f.requisitos,
    promedio_minimo:
      Number(f.promedio_minimo),
    fecha_inicio: f.fecha_inicio,
    fecha_cierre: f.fecha_cierre,
    estado: f.estado
  }

  try {
    let id = f.id

    if (id) {
      await api.patch(
        `/master/convocatorias/${id}`,
        payload
      )
    } else {
      const { data } =
        await api.post(
          '/master/convocatorias',
          payload
        )

      id =
        data?.data?.id ||
        data?.convocatoria?.id
    }

    if (
      f.archivo &&
      id
    ) {
      const form =
        new FormData()

      form.append(
        'archivo',
        f.archivo
      )

      await api.post(
        `/master/convocatorias/${id}/archivo`,
        form
      )
    }

    modal.value = null
    await cargarTodo()

    mostrarToast(
      'Convocatoria guardada.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo guardar la convocatoria.',
      'error'
    )
  }
}

async function accionConvocatoria(
  c,
  accion
) {
  try {
    if (accion === 'eliminar') {
      if (
        !confirm(
          `¿Eliminar "${c.nombre}"?`
        )
      ) return

      await api.delete(
        `/master/convocatorias/${c.id}`
      )
    } else {
      await api.patch(
        `/master/convocatorias/${c.id}/${accion}`
      )
    }

    await cargarTodo()

    mostrarToast(
      'Convocatoria actualizada.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No fue posible realizar la operación.',
      'error'
    )
  }
}

function urlArchivo(c) {
  const ruta =
    c?.archivo_url ||
    c?.pdf_url ||
    c?.archivo

  if (!ruta) return null

  if (
    String(ruta)
      .startsWith('http')
  ) return ruta

  return `http://127.0.0.1:8000/storage/${ruta}`
}

/* =========================================================
   PERIODOS
========================================================= */

const periodoForm = ref({})

function nuevoPeriodo() {
  periodoForm.value = {
    id: null,
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'ACTIVO'
  }

  modal.value = 'periodo'
}

function editarPeriodo(p) {
  periodoForm.value = {
    id: p.id,
    nombre: p.nombre,

    fecha_inicio:
      String(p.fecha_inicio || '')
        .slice(0, 10),

    fecha_fin:
      String(p.fecha_fin || '')
        .slice(0, 10),

    estado:
      p.estado || 'ACTIVO'
  }

  modal.value = 'periodo'
}

async function guardarPeriodo() {
  const f =
    periodoForm.value

  try {
    if (f.id) {
      await api.patch(
        `/master/periodos/${f.id}`,
        f
      )
    } else {
      await api.post(
        '/master/periodos',
        f
      )
    }

    modal.value = null
    await cargarTodo()

    mostrarToast(
      'Periodo guardado.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo guardar el periodo.',
      'error'
    )
  }
}

/* =========================================================
   CARRERAS
========================================================= */

const carreraForm = ref({})

function nuevaCarrera() {
  carreraForm.value = {
    id: null,
    nombre: '',
    clave: '',
    descripcion: '',
    estado: 'ACTIVA'
  }

  modal.value = 'carrera'
}

function editarCarrera(c) {
  carreraForm.value = {
    id: c.id,
    nombre: c.nombre || '',
    clave: c.clave || '',
    descripcion: c.descripcion || '',
    estado: c.estado || 'ACTIVA'
  }

  modal.value = 'carrera'
}

async function guardarCarrera() {
  const f = carreraForm.value

  try {
    if (f.id) {
      await api.patch(
        `/master/carreras/${f.id}`,
        {
          nombre: f.nombre,
          clave: f.clave || null,
          descripcion:
            f.descripcion || null,
          estado: f.estado
        }
      )
    } else {
      await api.post(
        '/master/carreras',
        {
          nombre: f.nombre,
          clave: f.clave || null,
          descripcion:
            f.descripcion || null,
          estado: f.estado
        }
      )
    }

    modal.value = null
    await cargarTodo()

    mostrarToast(
      'Carrera guardada correctamente.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo guardar la carrera.',
      'error'
    )
  }
}

async function eliminarCarrera(c) {
  if (
    !confirm(
      `¿Eliminar la carrera "${c.nombre}"?`
    )
  ) return

  try {
    await api.delete(
      `/master/carreras/${c.id}`
    )

    await cargarTodo()

    mostrarToast(
      'Carrera eliminada.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No puede eliminarse. Prueba marcarla como inactiva.',
      'error'
    )
  }
}

/* =========================================================
   GRUPOS
========================================================= */

const grupoForm = ref({})

function nuevoGrupo() {
  grupoForm.value = {
    id: null,
    nombre: '',
    carrera_id: '',
    periodo_id:
      periodoActivo.value?.id || '',
    tutor_id: '',
    cuatrimestre: '',
    turno: 'MATUTINO',
    estado: 'ACTIVO'
  }

  modal.value = 'grupo'
}

function editarGrupo(g) {
  grupoForm.value = {
    id: g.id,
    nombre: g.nombre || '',
    carrera_id:
      g.carrera_id || '',
    periodo_id:
      g.periodo_id || '',
    tutor_id:
      g.tutor_id || '',
    cuatrimestre:
      g.cuatrimestre || '',
    turno:
      g.turno || 'MATUTINO',
    estado:
      g.estado || 'ACTIVO'
  }

  modal.value = 'grupo'
}

async function guardarGrupo() {
  const f =
    grupoForm.value

  const payload = {
    nombre: f.nombre,
    carrera_id: f.carrera_id,
    periodo_id:
      f.periodo_id || null,
    tutor_id:
      f.tutor_id || null,
    cuatrimestre:
      f.cuatrimestre
        ? Number(f.cuatrimestre)
        : null,
    turno: f.turno,
    estado: f.estado
  }

  try {
    if (f.id) {
      await api.patch(
        `/master/grupos/${f.id}`,
        payload
      )
    } else {
      await api.post(
        '/master/grupos',
        payload
      )
    }

    modal.value = null
    await cargarTodo()

    mostrarToast(
      'Grupo guardado correctamente.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo guardar el grupo.',
      'error'
    )
  }
}

async function eliminarGrupo(g) {
  if (
    !confirm(
      `¿Eliminar el grupo "${g.nombre}"?`
    )
  ) return

  try {
    await api.delete(
      `/master/grupos/${g.id}`
    )

    await cargarTodo()

    mostrarToast(
      'Grupo eliminado.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'El grupo tiene alumnos asignados.',
      'error'
    )
  }
}

/* =========================================================
   ALUMNOS
========================================================= */

const alumnoForm = ref({})

function editarAlumno(a) {
  alumnoForm.value = {
    id: a.id,
    name: a.name || '',
    matricula: a.matricula || '',
    carrera_id:
      a.carrera_id || '',
    grupo_id:
      a.grupo_id || '',
    grupo:
      a.grupo || ''
  }

  modal.value = 'alumno'
}

async function guardarAlumno() {
  const f =
    alumnoForm.value

  try {
    await api.patch(
      `/master/alumnos/${f.id}`,
      {
        name: f.name,
        matricula: f.matricula,
        carrera_id:
          f.carrera_id || null,
        grupo_id:
          f.grupo_id || null,

        grupo:
          grupoPorId(
            f.grupo_id
          )?.nombre ||
          f.grupo ||
          null
      }
    )

    /*
    | Si tu AlumnoGestionController aún no acepta grupo_id,
    | la asignación formal también puede hacerse por /grupos.
    */

    if (f.grupo_id) {
      try {
        await api.post(
          `/master/grupos/${f.grupo_id}/alumnos`,
          {
            alumno_id:
              f.id
          }
        )
      } catch (_) {
        // compatibilidad
      }
    }

    modal.value = null

    await cargarTodo()

    mostrarToast(
      'Alumno actualizado.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo actualizar el alumno.',
      'error'
    )
  }
}

/* =========================================================
   PERSONAL
========================================================= */

const staffForm = ref({})

function nuevoPersonal() {
  staffForm.value = {
    name: '',
    email: '',
    password: '',
    role: 'profesor',
    carrera_id: ''
  }

  modal.value = 'personal'
}

async function crearPersonal() {
  const f =
    staffForm.value

  try {
    await api.post(
      '/master/staff',
      {
        name: f.name,
        email: f.email,
        password: f.password,
        role: f.role,

        carreras:
          f.carrera_id
            ? [f.carrera_id]
            : []
      }
    )

    modal.value = null

    await cargarTodo()

    mostrarToast(
      'Usuario institucional creado.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo crear el usuario.',
      'error'
    )
  }
}

async function eliminarPersonal(u) {
  if (
    !confirm(
      `¿Eliminar a ${u.name}?`
    )
  ) return

  try {
    await api.delete(
      `/master/staff/${u.id}`
    )

    await cargarTodo()

    mostrarToast(
      'Usuario eliminado.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo eliminar.',
      'error'
    )
  }
}

/* =========================================================
   CONTRASEÑA
========================================================= */

const resetForm = ref({})

function abrirReset(u) {
  resetForm.value = {
    user: u,
    password: '',
    password_confirmation: ''
  }

  modal.value = 'reset'
}

async function restablecerPassword() {
  const f = resetForm.value

  if (
    f.password !==
    f.password_confirmation
  ) {
    mostrarToast(
      'Las contraseñas no coinciden.',
      'error'
    )

    return
  }

  try {
    await api.post(
      '/superadmin/reset-password',
      {
        user_id: f.user.id,
        password: f.password,
        password_confirmation:
          f.password_confirmation
      }
    )

    modal.value = null

    mostrarToast(
      'Contraseña restablecida.'
    )
  } catch (e) {
    mostrarToast(
      e.response?.data?.message ||
      'No se pudo cambiar la contraseña.',
      'error'
    )
  }
}

/* =========================================================
   ALERTAS
========================================================= */

const alertas = computed(() => {
  const lista = []

  if (resumen.value.pendientes) {
    lista.push({
      tipo: 'warning',
      titulo: 'Solicitudes pendientes',
      detalle:
        `${resumen.value.pendientes} solicitudes necesitan atención.`,
      destino: 'solicitudes'
    })
  }

  if (resumen.value.incompletas) {
    lista.push({
      tipo: 'purple',
      titulo: 'Documentación incompleta',
      detalle:
        `${resumen.value.incompletas} expedientes requieren corrección.`,
      destino: 'solicitudes'
    })
  }

  const sinCarrera =
    alumnos.value.filter(
      a => !a.carrera_id
    ).length

  if (sinCarrera) {
    lista.push({
      tipo: 'info',
      titulo: 'Alumnos sin carrera',
      detalle:
        `${sinCarrera} alumnos no tienen carrera asignada.`,
      destino: 'alumnos'
    })
  }

  const sinGrupo =
    alumnos.value.filter(
      a =>
        !a.grupo_id &&
        !a.grupo
    ).length

  if (sinGrupo) {
    lista.push({
      tipo: 'warning',
      titulo: 'Alumnos sin grupo',
      detalle:
        `${sinGrupo} alumnos necesitan grupo.`,
      destino: 'alumnos'
    })
  }

  if (!periodoActivo.value) {
    lista.push({
      tipo: 'danger',
      titulo: 'Sin periodo activo',
      detalle:
        'El sistema no tiene un periodo académico activo.',
      destino: 'periodos'
    })
  }

  if (!convocatoriaVigente.value) {
    lista.push({
      tipo: 'info',
      titulo: 'Sin convocatoria vigente',
      detalle:
        'No existe una convocatoria publicada actualmente.',
      destino: 'convocatorias'
    })
  }

  return lista
})

function cerrarSesion() {
  emit('cerrar-sesion')
}

onMounted(
  cargarTodo
)
</script>

<template>
<div class="dashboard">

  <!-- TOAST -->

  <transition name="fade">
    <div
      v-if="toast"
      class="toast"
      :class="toast.tipo"
    >
      {{ toast.mensaje }}
    </div>
  </transition>

  <!-- HEADER -->

  <header class="topbar">
    <div class="topbar-inner">

      <div class="brand">
        <div class="logo-text">
          <span>UP</span>T<span>ex</span>
        </div>

        <div>
          <strong>
            Sistema de Becas
          </strong>

          <small>
            Super Administración · UPTex
          </small>
        </div>
      </div>

      <nav>
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="{
            active:
              seccion === tab.id
          }"
          @click="
            seccion = tab.id
          "
        >
          {{ tab.label }}

          <b
            v-if="
              tab.id === 'alertas' &&
              alertas.length
            "
            class="counter"
          >
            {{ alertas.length }}
          </b>
        </button>
      </nav>

      <div class="profile">
        <div>
          <strong>
            {{
              props.usuario?.name ||
              'Super Administrador'
            }}
          </strong>

          <small>
            SuperAdmin
          </small>
        </div>

        <span class="avatar">
          {{
            iniciales(
              props.usuario?.name
            )
          }}
        </span>

        <button
          class="logout"
          @click="cerrarSesion"
        >
          Salir
        </button>
      </div>

    </div>
  </header>

  <!-- CONTENIDO -->

  <main>

    <div
      v-if="errorGeneral"
      class="warning-banner"
    >
      {{ errorGeneral }}
    </div>

    <div
      v-if="cargando"
      class="loading"
    >
      <div class="spinner"></div>
      Cargando administración...
    </div>

    <template v-else>

      <!-- =================================================
           RESUMEN
      ================================================== -->

      <section v-if="seccion === 'resumen'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              CENTRO DE CONTROL
            </span>

            <h1>
              Resumen institucional
            </h1>

            <p>
              Control completo del programa
              de becas.
            </p>
          </div>

          <div class="context">
            <div>
              <span>Periodo activo</span>
              <strong>
                {{
                  periodoActivo?.nombre ||
                  'Sin periodo'
                }}
              </strong>
            </div>

            <div>
              <span>Convocatoria</span>
              <strong>
                {{
                  convocatoriaVigente?.nombre ||
                  'Sin publicación'
                }}
              </strong>
            </div>
          </div>
        </div>

        <div class="filters">
          <select v-model="filtroPeriodo">
            <option value="todos">
              Todos los periodos
            </option>

            <option
              v-for="p in periodos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nombre }}
            </option>
          </select>

          <select v-model="filtroCarrera">
            <option value="todos">
              Todas las carreras
            </option>

            <option
              v-for="c in carreras"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nombre }}
            </option>
          </select>

          <select v-model="filtroEstado">
            <option value="todos">
              Todos los estados
            </option>

            <option
              v-for="e in estados"
              :key="e"
              :value="e"
            >
              {{ nombreEstado(e) }}
            </option>
          </select>

          <button
            class="secondary"
            @click="cargarTodo"
          >
            Actualizar
          </button>
        </div>

        <div class="kpis">
          <article>
            <span>Solicitudes</span>
            <strong>
              {{ resumen.solicitudes }}
            </strong>
            <small>Total filtrado</small>
          </article>

          <article class="amber">
            <span>Pendientes</span>
            <strong>
              {{ resumen.pendientes }}
            </strong>
            <small>Requieren atención</small>
          </article>

          <article class="blue">
            <span>En revisión</span>
            <strong>
              {{ resumen.revision }}
            </strong>
            <small>En proceso</small>
          </article>

          <article class="green">
            <span>Aceptadas</span>
            <strong>
              {{ resumen.aceptadas }}
            </strong>
            <small>Aprobadas</small>
          </article>

          <article class="burgundy">
            <span>Rechazadas</span>
            <strong>
              {{ resumen.rechazadas }}
            </strong>
            <small>No aprobadas</small>
          </article>
        </div>

        <div class="charts">
          <article class="panel">
            <div class="panel-title">
              <span class="eyebrow">
                DISTRIBUCIÓN
              </span>

              <h2>
                Solicitudes por carrera
              </h2>
            </div>

            <div class="chart">
              <Bar
                v-if="chartCarreras.labels.length"
                :data="chartCarreras"
                :options="opcionesBarras"
              />

              <div
                v-else
                class="empty"
              >
                Sin datos.
              </div>
            </div>
          </article>

          <article class="panel">
            <div class="panel-title">
              <span class="eyebrow">
                ESTATUS
              </span>

              <h2>
                Estado de solicitudes
              </h2>
            </div>

            <div class="chart">
              <Doughnut
                v-if="resumen.solicitudes"
                :data="chartEstados"
                :options="opcionesDona"
              />

              <div
                v-else
                class="empty"
              >
                Sin solicitudes.
              </div>
            </div>
          </article>
        </div>

        <div class="mini-stats">
          <article>
            <span>Alumnos</span>
            <strong>
              {{ resumen.alumnos }}
            </strong>
          </article>

          <article>
            <span>Carreras</span>
            <strong>
              {{ resumen.carreras }}
            </strong>
          </article>

          <article>
            <span>Grupos</span>
            <strong>
              {{ resumen.grupos }}
            </strong>
          </article>

          <article>
            <span>Personal</span>
            <strong>
              {{ resumen.personal }}
            </strong>
          </article>

          <article>
            <span>Alertas</span>
            <strong>
              {{ alertas.length }}
            </strong>
          </article>
        </div>

      </section>

      <!-- =================================================
           SOLICITUDES
      ================================================== -->

      <section v-if="seccion === 'solicitudes'">

        <SectionTitle
          v-if="false"
        />

        <div class="heading">
          <div>
            <span class="eyebrow">
              EXPEDIENTES
            </span>

            <h1>
              Solicitudes
            </h1>

            <p>
              Supervisa todas las carreras.
            </p>
          </div>
        </div>

        <div class="filters four">
          <input
            v-model="busqueda"
            placeholder="Buscar alumno, matrícula o folio..."
          />

          <select v-model="filtroPeriodo">
            <option value="todos">
              Todos los periodos
            </option>

            <option
              v-for="p in periodos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nombre }}
            </option>
          </select>

          <select v-model="filtroCarrera">
            <option value="todos">
              Todas las carreras
            </option>

            <option
              v-for="c in carreras"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nombre }}
            </option>
          </select>

          <select v-model="filtroEstado">
            <option value="todos">
              Todos los estados
            </option>

            <option
              v-for="e in estados"
              :key="e"
              :value="e"
            >
              {{ nombreEstado(e) }}
            </option>
          </select>
        </div>

        <div class="panel table-wrap">
          <table>
            <thead>
            <tr>
              <th>Folio</th>
              <th>Alumno</th>
              <th>Matrícula</th>
              <th>Carrera</th>
              <th>Periodo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="s in solicitudesFiltradas"
              :key="s.id"
            >
              <td>{{ folio(s) }}</td>

              <td>
                <strong>
                  {{
                    alumnoDe(s).name ||
                    'Alumno'
                  }}
                </strong>
              </td>

              <td>
                {{
                  alumnoDe(s).matricula ||
                  '—'
                }}
              </td>

              <td>
                {{ carreraSolicitud(s) }}
              </td>

              <td>
                {{ periodoSolicitud(s) }}
              </td>

              <td>
                <span
                  class="badge"
                  :class="
                    claseEstado(
                      s.estado ||
                      s.estatus
                    )
                  "
                >
                  {{
                    nombreEstado(
                      s.estado ||
                      s.estatus
                    )
                  }}
                </span>
              </td>

              <td>
                <button
                  class="table-button"
                  @click="abrirSolicitud(s)"
                >
                  Abrir expediente
                </button>
              </td>
            </tr>
            </tbody>
          </table>
        </div>

      </section>

      <!-- =================================================
           CONVOCATORIAS
      ================================================== -->

      <section v-if="seccion === 'convocatorias'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              PUBLICACIÓN
            </span>

            <h1>
              Convocatorias
            </h1>

            <p>
              Crea, edita, publica y
              administra documentos.
            </p>
          </div>

          <button
            class="primary"
            @click="nuevaConvocatoria"
          >
            + Nueva convocatoria
          </button>
        </div>

        <div class="records">
          <article
            v-for="c in convocatorias"
            :key="c.id"
            class="record"
          >
            <div>
              <span
                class="badge"
                :class="claseEstado(c.estado)"
              >
                {{ nombreEstado(c.estado) }}
              </span>

              <h3>
                {{ c.nombre }}
              </h3>

              <p>
                {{
                  c.periodo?.nombre ||
                  'Sin periodo'
                }}
                ·
                {{ fecha(c.fecha_inicio) }}
                —
                {{ fecha(c.fecha_cierre) }}
              </p>
            </div>

            <div class="actions">
              <a
                v-if="urlArchivo(c)"
                :href="urlArchivo(c)"
                target="_blank"
                class="action-link"
              >
                Ver PDF
              </a>

              <button
                @click="editarConvocatoria(c)"
              >
                Editar
              </button>

              <button
                v-if="
                  estado(c.estado) !==
                  'PUBLICADA'
                "
                class="green-text"
                @click="
                  accionConvocatoria(
                    c,
                    'publicar'
                  )
                "
              >
                Publicar
              </button>

              <button
                v-else
                @click="
                  accionConvocatoria(
                    c,
                    'cerrar'
                  )
                "
              >
                Cerrar
              </button>

              <button
                class="danger-text"
                @click="
                  accionConvocatoria(
                    c,
                    'eliminar'
                  )
                "
              >
                Eliminar
              </button>
            </div>
          </article>
        </div>

      </section>

      <!-- =================================================
           PERIODOS
      ================================================== -->

      <section v-if="seccion === 'periodos'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              CICLOS ESCOLARES
            </span>

            <h1>
              Periodos
            </h1>

            <p>
              Administra los ciclos
              académicos.
            </p>
          </div>

          <button
            class="primary"
            @click="nuevoPeriodo"
          >
            + Nuevo periodo
          </button>
        </div>

        <div class="records">
          <article
            v-for="p in periodos"
            :key="p.id"
            class="record"
          >
            <div>
              <span
                class="badge"
                :class="claseEstado(p.estado)"
              >
                {{ nombreEstado(p.estado) }}
              </span>

              <h3>{{ p.nombre }}</h3>

              <p>
                {{ fecha(p.fecha_inicio) }}
                —
                {{ fecha(p.fecha_fin) }}
              </p>
            </div>

            <div class="actions">
              <button
                @click="editarPeriodo(p)"
              >
                Editar
              </button>
            </div>
          </article>
        </div>

      </section>

      <!-- =================================================
           CARRERAS
      ================================================== -->

      <section v-if="seccion === 'carreras'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              ESTRUCTURA ACADÉMICA
            </span>

            <h1>
              Carreras
            </h1>

            <p>
              Administra los programas
              académicos de la universidad.
            </p>
          </div>

          <button
            class="primary"
            @click="nuevaCarrera"
          >
            + Nueva carrera
          </button>
        </div>

        <div class="card-grid">
          <article
            v-for="c in carreras"
            :key="c.id"
            class="academic-card"
          >
            <div class="academic-top">
              <span
                class="badge"
                :class="claseEstado(c.estado)"
              >
                {{
                  nombreEstado(
                    c.estado ||
                    'ACTIVA'
                  )
                }}
              </span>

              <span class="code">
                {{ c.clave || 'SIN CLAVE' }}
              </span>
            </div>

            <h3>
              {{ c.nombre }}
            </h3>

            <p>
              {{
                c.descripcion ||
                'Programa académico UPTex.'
              }}
            </p>

            <div class="academic-stats">
              <div>
                <span>Alumnos</span>
                <strong>
                  {{
                    c.alumnos_count ??
                    alumnos.filter(
                      a =>
                        String(a.carrera_id) ===
                        String(c.id)
                    ).length
                  }}
                </strong>
              </div>

              <div>
                <span>Grupos</span>
                <strong>
                  {{
                    c.grupos_count ??
                    grupos.filter(
                      g =>
                        String(g.carrera_id) ===
                        String(c.id)
                    ).length
                  }}
                </strong>
              </div>
            </div>

            <div class="actions full-actions">
              <button
                @click="editarCarrera(c)"
              >
                Editar
              </button>

              <button
                @click="
                  filtroCarrera = c.id;
                  seccion = 'grupos'
                "
              >
                Ver grupos
              </button>

              <button
                class="danger-text"
                @click="eliminarCarrera(c)"
              >
                Eliminar
              </button>
            </div>
          </article>
        </div>

      </section>

      <!-- =================================================
           GRUPOS
      ================================================== -->

      <section v-if="seccion === 'grupos'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              ORGANIZACIÓN ACADÉMICA
            </span>

            <h1>
              Grupos
            </h1>

            <p>
              Organiza alumnos por carrera,
              periodo y tutor.
            </p>
          </div>

          <button
            class="primary"
            @click="nuevoGrupo"
          >
            + Nuevo grupo
          </button>
        </div>

        <div class="filters">
          <select v-model="filtroCarrera">
            <option value="todos">
              Todas las carreras
            </option>

            <option
              v-for="c in carreras"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nombre }}
            </option>
          </select>

          <select v-model="filtroPeriodo">
            <option value="todos">
              Todos los periodos
            </option>

            <option
              v-for="p in periodos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nombre }}
            </option>
          </select>
        </div>

        <div class="card-grid">
          <article
            v-for="g in grupos.filter(g =>
              (
                filtroCarrera === 'todos' ||
                String(g.carrera_id) ===
                String(filtroCarrera)
              )
              &&
              (
                filtroPeriodo === 'todos' ||
                String(g.periodo_id) ===
                String(filtroPeriodo)
              )
            )"
            :key="g.id"
            class="academic-card"
          >
            <div class="academic-top">
              <span
                class="badge"
                :class="claseEstado(g.estado)"
              >
                {{ nombreEstado(g.estado) }}
              </span>

              <span class="code">
                {{
                  g.turno ||
                  'MATUTINO'
                }}
              </span>
            </div>

            <h3>
              {{ g.nombre }}
            </h3>

            <p>
              {{
                g.carrera?.nombre ||
                carreraPorId(
                  g.carrera_id
                )?.nombre ||
                'Sin carrera'
              }}
            </p>

            <div class="group-info">
              <span>
                Cuatrimestre:
                <b>
                  {{
                    g.cuatrimestre ||
                    '—'
                  }}
                </b>
              </span>

              <span>
                Tutor:
                <b>
                  {{
                    g.tutor?.name ||
                    'Sin asignar'
                  }}
                </b>
              </span>

              <span>
                Alumnos:
                <b>
                  {{
                    g.alumnos_count ??
                    alumnos.filter(
                      a =>
                        String(a.grupo_id) ===
                        String(g.id)
                    ).length
                  }}
                </b>
              </span>
            </div>

            <div class="actions full-actions">
              <button
                @click="editarGrupo(g)"
              >
                Editar grupo
              </button>

              <button
                @click="
                  filtroGrupo = g.id;
                  seccion = 'alumnos'
                "
              >
                Ver alumnos
              </button>

              <button
                class="danger-text"
                @click="eliminarGrupo(g)"
              >
                Eliminar
              </button>
            </div>
          </article>
        </div>

      </section>

      <!-- =================================================
           ALUMNOS
      ================================================== -->

      <section v-if="seccion === 'alumnos'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              PADRÓN
            </span>

            <h1>
              Alumnos
            </h1>

            <p>
              Carrera, grupo y acceso
              al sistema.
            </p>
          </div>
        </div>

        <div class="filters">
          <input
            v-model="busqueda"
            placeholder="Buscar alumno..."
          />

          <select v-model="filtroCarrera">
            <option value="todos">
              Todas las carreras
            </option>

            <option
              v-for="c in carreras"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nombre }}
            </option>
          </select>

          <select v-model="filtroGrupo">
            <option value="todos">
              Todos los grupos
            </option>

            <option
              v-for="g in grupos"
              :key="g.id"
              :value="g.id"
            >
              {{ g.nombre }}
            </option>
          </select>
        </div>

        <div class="panel table-wrap">
          <table>
            <thead>
            <tr>
              <th>Alumno</th>
              <th>Matrícula</th>
              <th>Carrera</th>
              <th>Grupo</th>
              <th>Correo</th>
              <th>Acciones</th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="a in alumnosFiltrados"
              :key="a.id"
            >
              <td>
                <strong>
                  {{ a.name }}
                </strong>
              </td>

              <td>
                {{ a.matricula || '—' }}
              </td>

              <td>
                {{
                  a.carrera?.nombre ||
                  carreraPorId(
                    a.carrera_id
                  )?.nombre ||
                  'Sin asignar'
                }}
              </td>

              <td>
                {{
                  a.grupo_relacion?.nombre ||
                  grupoPorId(
                    a.grupo_id
                  )?.nombre ||
                  a.grupo ||
                  'Sin grupo'
                }}
              </td>

              <td>
                {{ a.email }}
              </td>

              <td class="row-actions">
                <button
                  @click="editarAlumno(a)"
                >
                  Editar
                </button>

                <button
                  @click="abrirReset(a)"
                >
                  Contraseña
                </button>
              </td>
            </tr>
            </tbody>
          </table>
        </div>

      </section>

      <!-- =================================================
           PERSONAL
      ================================================== -->

      <section v-if="seccion === 'personal'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              USUARIOS INSTITUCIONALES
            </span>

            <h1>
              Personal
            </h1>

            <p>
              Jefes, administradores
              y profesores/tutores.
            </p>
          </div>

          <button
            class="primary"
            @click="nuevoPersonal"
          >
            + Nuevo usuario
          </button>
        </div>

        <div class="card-grid">
          <article
            v-for="u in staff"
            :key="u.id"
            class="person-card"
          >
            <div class="person-head">
              <span class="person-avatar">
                {{ iniciales(u.name) }}
              </span>

              <div>
                <strong>
                  {{ u.name }}
                </strong>

                <small>
                  {{ u.email }}
                </small>
              </div>
            </div>

            <div class="role">
              {{
                u.role === 'admin'
                  ? 'Jefe / Administrador'
                  : u.role === 'profesor'
                  ? 'Profesor / Tutor'
                  : u.role
              }}
            </div>

            <div class="actions full-actions">
              <button
                @click="abrirReset(u)"
              >
                Cambiar contraseña
              </button>

              <button
                class="danger-text"
                @click="eliminarPersonal(u)"
              >
                Eliminar
              </button>
            </div>
          </article>
        </div>

      </section>

      <!-- =================================================
           ALERTAS
      ================================================== -->

      <section v-if="seccion === 'alertas'">

        <div class="heading">
          <div>
            <span class="eyebrow">
              SUPERVISIÓN
            </span>

            <h1>
              Alertas del sistema
            </h1>

            <p>
              Situaciones que requieren
              atención administrativa.
            </p>
          </div>

          <button
            class="secondary"
            @click="cargarTodo"
          >
            Actualizar
          </button>
        </div>

        <div
          v-if="alertas.length"
          class="alerts"
        >
          <article
            v-for="(a, i) in alertas"
            :key="i"
            :class="a.tipo"
          >
            <div>
              <strong>
                {{ a.titulo }}
              </strong>

              <p>
                {{ a.detalle }}
              </p>
            </div>

            <button
              @click="
                seccion = a.destino
              "
            >
              Revisar →
            </button>
          </article>
        </div>

        <div
          v-else
          class="all-good"
        >
          <span>✓</span>

          <h2>
            Todo está en orden
          </h2>

          <p>
            No existen alertas
            administrativas.
          </p>
        </div>

      </section>

    </template>
  </main>

  <!-- =====================================================
       MODAL SOLICITUD
  ====================================================== -->

  <div
    v-if="modal === 'solicitud'"
    class="overlay"
    @click.self="modal = null"
  >
    <div class="modal">
      <button
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <span class="eyebrow">
        EXPEDIENTE
      </span>

      <h2>
        {{
          alumnoDe(
            solicitudSeleccionada
          ).name
        }}
      </h2>

      <p>
        {{ folio(solicitudSeleccionada) }}
        ·
        {{ carreraSolicitud(solicitudSeleccionada) }}
      </p>

      <label>
        Estado

        <select
          :value="
            estado(
              solicitudSeleccionada.estado ||
              solicitudSeleccionada.estatus
            )
          "
          @change="
            actualizarSolicitud(
              $event.target.value
            )
          "
        >
          <option value="PENDIENTE">
            Pendiente
          </option>

          <option value="EN_REVISION">
            En revisión
          </option>

          <option value="DOCUMENTACION_INCOMPLETA">
            Documentación incompleta
          </option>

          <option value="ACEPTADA">
            Aceptada
          </option>

          <option value="RECHAZADA">
            Rechazada
          </option>
        </select>
      </label>
    </div>
  </div>

  <!-- CONVOCATORIA -->

  <div
    v-if="modal === 'convocatoria'"
    class="overlay"
  >
    <form
      class="modal large"
      @submit.prevent="guardarConvocatoria"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        {{
          convocatoriaForm.id
            ? 'Editar convocatoria'
            : 'Nueva convocatoria'
        }}
      </h2>

      <div class="form-grid">
        <label>
          Nombre
          <input
            v-model="convocatoriaForm.nombre"
            required
          />
        </label>

        <label>
          Periodo
          <select
            v-model="convocatoriaForm.periodo_id"
            required
          >
            <option value="">
              Selecciona
            </option>

            <option
              v-for="p in periodos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nombre }}
            </option>
          </select>
        </label>

        <label class="full">
          Descripción
          <textarea
            v-model="convocatoriaForm.descripcion"
            required
          ></textarea>
        </label>

        <label class="full">
          Requisitos
          <textarea
            v-model="convocatoriaForm.requisitos"
            required
          ></textarea>
        </label>

        <label>
          Promedio mínimo
          <input
            v-model="convocatoriaForm.promedio_minimo"
            type="number"
            min="0"
            max="10"
            step=".1"
          />
        </label>

        <label>
          Estado
          <select
            v-model="convocatoriaForm.estado"
          >
            <option value="BORRADOR">
              Borrador
            </option>

            <option value="PUBLICADA">
              Publicada
            </option>

            <option value="CERRADA">
              Cerrada
            </option>
          </select>
        </label>

        <label>
          Inicio
          <input
            v-model="convocatoriaForm.fecha_inicio"
            type="date"
            required
          />
        </label>

        <label>
          Cierre
          <input
            v-model="convocatoriaForm.fecha_cierre"
            type="date"
            required
          />
        </label>

        <label class="full">
          PDF oficial
          <input
            type="file"
            accept="application/pdf"
            @change="seleccionarPdf"
          />
        </label>
      </div>

      <button class="primary submit">
        Guardar convocatoria
      </button>
    </form>
  </div>

  <!-- PERIODO -->

  <div
    v-if="modal === 'periodo'"
    class="overlay"
  >
    <form
      class="modal"
      @submit.prevent="guardarPeriodo"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        {{
          periodoForm.id
            ? 'Editar periodo'
            : 'Nuevo periodo'
        }}
      </h2>

      <label>
        Nombre
        <input
          v-model="periodoForm.nombre"
          required
        />
      </label>

      <label>
        Inicio
        <input
          v-model="periodoForm.fecha_inicio"
          type="date"
          required
        />
      </label>

      <label>
        Fin
        <input
          v-model="periodoForm.fecha_fin"
          type="date"
          required
        />
      </label>

      <label>
        Estado
        <select
          v-model="periodoForm.estado"
        >
          <option value="ACTIVO">
            Activo
          </option>

          <option value="CERRADO">
            Cerrado
          </option>
        </select>
      </label>

      <button class="primary submit">
        Guardar
      </button>
    </form>
  </div>

  <!-- CARRERA -->

  <div
    v-if="modal === 'carrera'"
    class="overlay"
  >
    <form
      class="modal"
      @submit.prevent="guardarCarrera"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        {{
          carreraForm.id
            ? 'Editar carrera'
            : 'Nueva carrera'
        }}
      </h2>

      <label>
        Nombre
        <input
          v-model="carreraForm.nombre"
          required
        />
      </label>

      <label>
        Clave
        <input
          v-model="carreraForm.clave"
          placeholder="ITI"
        />
      </label>

      <label>
        Descripción
        <textarea
          v-model="carreraForm.descripcion"
        ></textarea>
      </label>

      <label>
        Estado
        <select
          v-model="carreraForm.estado"
        >
          <option value="ACTIVA">
            Activa
          </option>

          <option value="INACTIVA">
            Inactiva
          </option>
        </select>
      </label>

      <button class="primary submit">
        Guardar carrera
      </button>
    </form>
  </div>

  <!-- GRUPO -->

  <div
    v-if="modal === 'grupo'"
    class="overlay"
  >
    <form
      class="modal large"
      @submit.prevent="guardarGrupo"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        {{
          grupoForm.id
            ? 'Editar grupo'
            : 'Nuevo grupo'
        }}
      </h2>

      <div class="form-grid">
        <label>
          Nombre
          <input
            v-model="grupoForm.nombre"
            placeholder="8ITI1"
            required
          />
        </label>

        <label>
          Carrera
          <select
            v-model="grupoForm.carrera_id"
            required
          >
            <option value="">
              Selecciona
            </option>

            <option
              v-for="c in carreras"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nombre }}
            </option>
          </select>
        </label>

        <label>
          Periodo
          <select
            v-model="grupoForm.periodo_id"
          >
            <option value="">
              Sin periodo
            </option>

            <option
              v-for="p in periodos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nombre }}
            </option>
          </select>
        </label>

        <label>
          Tutor
          <select
            v-model="grupoForm.tutor_id"
          >
            <option value="">
              Sin tutor
            </option>

            <option
              v-for="u in staff.filter(
                u =>
                  u.role === 'profesor'
              )"
              :key="u.id"
              :value="u.id"
            >
              {{ u.name }}
            </option>
          </select>
        </label>

        <label>
          Cuatrimestre
          <input
            v-model="grupoForm.cuatrimestre"
            type="number"
            min="1"
            max="12"
          />
        </label>

        <label>
          Turno
          <select
            v-model="grupoForm.turno"
          >
            <option value="MATUTINO">
              Matutino
            </option>

            <option value="VESPERTINO">
              Vespertino
            </option>

            <option value="MIXTO">
              Mixto
            </option>
          </select>
        </label>

        <label>
          Estado
          <select
            v-model="grupoForm.estado"
          >
            <option value="ACTIVO">
              Activo
            </option>

            <option value="INACTIVO">
              Inactivo
            </option>
          </select>
        </label>
      </div>

      <button class="primary submit">
        Guardar grupo
      </button>
    </form>
  </div>

  <!-- ALUMNO -->

  <div
    v-if="modal === 'alumno'"
    class="overlay"
  >
    <form
      class="modal"
      @submit.prevent="guardarAlumno"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        Editar alumno
      </h2>

      <label>
        Nombre
        <input
          v-model="alumnoForm.name"
          required
        />
      </label>

      <label>
        Matrícula
        <input
          v-model="alumnoForm.matricula"
        />
      </label>

      <label>
        Carrera
        <select
          v-model="alumnoForm.carrera_id"
        >
          <option value="">
            Sin carrera
          </option>

          <option
            v-for="c in carreras"
            :key="c.id"
            :value="c.id"
          >
            {{ c.nombre }}
          </option>
        </select>
      </label>

      <label>
        Grupo
        <select
          v-model="alumnoForm.grupo_id"
        >
          <option value="">
            Sin grupo
          </option>

          <option
            v-for="g in grupos.filter(
              g =>
                !alumnoForm.carrera_id ||
                String(g.carrera_id) ===
                String(alumnoForm.carrera_id)
            )"
            :key="g.id"
            :value="g.id"
          >
            {{ g.nombre }}
          </option>
        </select>
      </label>

      <button class="primary submit">
        Guardar alumno
      </button>
    </form>
  </div>

  <!-- PERSONAL -->

  <div
    v-if="modal === 'personal'"
    class="overlay"
  >
    <form
      class="modal"
      @submit.prevent="crearPersonal"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        Nuevo usuario institucional
      </h2>

      <label>
        Nombre
        <input
          v-model="staffForm.name"
          required
        />
      </label>

      <label>
        Correo
        <input
          v-model="staffForm.email"
          type="email"
          required
        />
      </label>

      <label>
        Contraseña temporal
        <input
          v-model="staffForm.password"
          type="password"
          minlength="8"
          required
        />
      </label>

      <label>
        Rol
        <select
          v-model="staffForm.role"
        >
          <option value="admin">
            Jefe / Administrador
          </option>

          <option value="profesor">
            Profesor / Tutor
          </option>
        </select>
      </label>

      <label>
        Carrera
        <select
          v-model="staffForm.carrera_id"
        >
          <option value="">
            Sin asignar
          </option>

          <option
            v-for="c in carreras"
            :key="c.id"
            :value="c.id"
          >
            {{ c.nombre }}
          </option>
        </select>
      </label>

      <button class="primary submit">
        Crear usuario
      </button>
    </form>
  </div>

  <!-- RESET -->

  <div
    v-if="modal === 'reset'"
    class="overlay"
  >
    <form
      class="modal"
      @submit.prevent="restablecerPassword"
    >
      <button
        type="button"
        class="close"
        @click="modal = null"
      >
        ×
      </button>

      <h2>
        Restablecer contraseña
      </h2>

      <p>
        {{ resetForm.user?.name }}
      </p>

      <label>
        Nueva contraseña
        <input
          v-model="resetForm.password"
          type="password"
          minlength="8"
          required
        />
      </label>

      <label>
        Confirmar
        <input
          v-model="resetForm.password_confirmation"
          type="password"
          minlength="8"
          required
        />
      </label>

      <button class="primary submit">
        Cambiar contraseña
      </button>
    </form>
  </div>

</div>
</template>

<style scoped>
*{box-sizing:border-box}
.dashboard{min-height:100vh;background:#f4f7f5;color:#27312b;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:15px}
.topbar{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.98);border-bottom:1px solid #dfe6e1;box-shadow:0 3px 12px rgba(20,50,35,.04)}
.topbar-inner{width:min(1420px,calc(100% - 32px));min-height:78px;margin:auto;display:flex;align-items:center;gap:22px}
.brand{min-width:225px;display:flex;align-items:center;gap:13px}.logo-text{font-size:23px;font-weight:900;color:#b32643}.logo-text span:first-child{color:#087846}.logo-text span:last-child{color:#707a74}
.brand>div:last-child{display:flex;flex-direction:column;border-left:1px solid #dfe5e1;padding-left:12px}.brand strong{font-size:14px}.brand small,.profile small{font-size:11px;color:#8c9690}
nav{flex:1;display:flex;justify-content:center;gap:3px;overflow-x:auto}nav button{position:relative;border:0;background:transparent;border-radius:9px;padding:10px;color:#66716a;font-size:13px;font-weight:750;white-space:nowrap;cursor:pointer}nav button:hover,nav button.active{background:#e8f3ed;color:#087846}.counter{position:absolute;right:1px;top:0;min-width:17px;height:17px;display:grid;place-items:center;border-radius:20px;background:#8e2843;color:#fff;font-size:10px}
.profile{display:flex;align-items:center;gap:8px}.profile>div{display:flex;flex-direction:column;text-align:right}.profile strong{font-size:12px}.avatar{width:39px;height:39px;display:grid;place-items:center;border-radius:50%;background:#087846;color:#fff;font-size:12px;font-weight:900}.logout{border:1px solid #dce3df;background:#fff;color:#8e2843;border-radius:8px;padding:8px 11px;font-size:12px;font-weight:750;cursor:pointer}
main{width:min(1280px,calc(100% - 32px));margin:auto;padding:38px 0 70px}.heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px}.eyebrow{display:block;color:#8a948e;font-size:11px;font-weight:900;letter-spacing:.14em}.heading h1{margin:5px 0;font-size:34px;letter-spacing:-.035em}.heading p{margin:0;color:#748078;font-size:14px}
.context{display:flex;gap:10px}.context>div{min-width:160px;padding:11px 14px;background:#fff;border:1px solid #e0e6e2;border-radius:11px}.context span{display:block;font-size:10px;color:#8d9791;text-transform:uppercase}.context strong{display:block;margin-top:3px;font-size:12px}
.primary,.secondary{border-radius:9px;padding:10px 14px;font-size:13px;font-weight:800;cursor:pointer}.primary{border:0;background:#087846;color:#fff}.primary:hover{background:#05683c}.secondary{border:1px solid #dce3df;background:#fff;color:#087846}
.filters{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:11px;margin-bottom:15px;background:#fff;border:1px solid #e0e6e2;border-radius:13px}.filters.four{grid-template-columns:repeat(4,1fr)}
input,select,textarea{width:100%;padding:11px 12px;border:1px solid #d9e1dc;border-radius:8px;background:#fff;color:#344039;font:inherit;font-size:13px;outline:none}input:focus,select:focus,textarea:focus{border-color:#6da486;box-shadow:0 0 0 3px #edf6f1}textarea{min-height:85px;resize:vertical}
.kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:15px}.kpis article{padding:17px;border:1px solid #e0e6e2;border-top:3px solid #65716a;border-radius:13px;background:#fff}.kpis .amber{border-top-color:#d99a25}.kpis .blue{border-top-color:#3b82b6}.kpis .green{border-top-color:#147a4a}.kpis .burgundy{border-top-color:#8e2843}.kpis span,.mini-stats span{display:block;color:#838e88;font-size:11px;font-weight:800;text-transform:uppercase}.kpis strong{display:block;margin:5px 0;font-size:28px}.kpis small{font-size:11px;color:#919a95}
.charts{display:grid;grid-template-columns:1.25fr .85fr;gap:14px;margin-bottom:15px}.panel{background:#fff;border:1px solid #e0e6e2;border-radius:14px;overflow:hidden}.panel-title{padding:17px 18px 0}.panel-title h2{margin:4px 0;font-size:17px}.chart{height:285px;padding:14px}
.mini-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}.mini-stats article{padding:14px 16px;background:#fff;border:1px solid #e0e6e2;border-radius:11px}.mini-stats strong{display:block;margin-top:4px;font-size:21px}
.table-wrap{overflow:auto}table{width:100%;min-width:850px;border-collapse:collapse}th,td{padding:13px;border-bottom:1px solid #edf1ee;text-align:left;font-size:13px}th{background:#fafbfa;color:#7d8881;font-size:11px;text-transform:uppercase}td strong{font-size:13px}.table-button,.row-actions button{border:0;background:#e8f3ed;color:#087846;border-radius:7px;padding:8px 10px;font-size:12px;font-weight:800;cursor:pointer}.row-actions{display:flex;gap:5px}
.badge{display:inline-flex;padding:5px 8px;border-radius:99px;font-size:11px;font-weight:800}.badge.success{background:#e6f3eb;color:#087846}.badge.danger{background:#fae9ee;color:#8e2843}.badge.info{background:#e8f2f8;color:#39749a}.badge.purple{background:#f1eaf8;color:#704994}.badge.warning{background:#fff2d3;color:#8d611c}
.records{display:grid;gap:10px}.record{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:17px;background:#fff;border:1px solid #e0e6e2;border-radius:13px}.record h3,.academic-card h3{margin:8px 0 4px;font-size:17px}.record p,.academic-card p{margin:0;color:#77827b;font-size:13px}.actions{display:flex;gap:6px;flex-wrap:wrap}.actions button,.action-link{border:1px solid #d9e1dc;background:#fff;border-radius:7px;padding:8px 10px;color:#536058;font-size:12px;font-weight:750;text-decoration:none;cursor:pointer}.green-text{color:#087846!important}.danger-text{color:#8e2843!important}
.card-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}.academic-card,.person-card{padding:17px;background:#fff;border:1px solid #e0e6e2;border-radius:13px}.academic-top{display:flex;justify-content:space-between;align-items:center}.code{font-size:11px;color:#8c9690;font-weight:800}.academic-stats{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:15px 0}.academic-stats>div{padding:10px;background:#f5f7f6;border-radius:8px}.academic-stats span{display:block;color:#87918b;font-size:11px}.academic-stats strong{font-size:19px}.group-info{display:grid;gap:6px;margin:13px 0;padding:11px;background:#f6f8f7;border-radius:8px}.group-info span{font-size:12px;color:#65716a}.full-actions{margin-top:14px}
.person-head{display:flex;align-items:center;gap:10px}.person-avatar{width:42px;height:42px;display:grid;place-items:center;border-radius:10px;background:#e8f3ed;color:#087846;font-weight:900}.person-head>div{display:flex;flex-direction:column}.person-head strong{font-size:14px}.person-head small{font-size:11px;color:#86918a}.role{margin:13px 0;padding-top:11px;border-top:1px solid #edf1ee;font-size:12px;color:#65716a}
.alerts{display:grid;gap:10px}.alerts article{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;background:#fff;border:1px solid #e0e6e2;border-left:4px solid #d99a25;border-radius:11px}.alerts article.danger{border-left-color:#8e2843}.alerts article.info{border-left-color:#3b82b6}.alerts article.purple{border-left-color:#7754a4}.alerts strong{font-size:14px}.alerts p{margin:4px 0 0;color:#77827b;font-size:13px}.alerts button{border:0;background:#edf5f1;color:#087846;border-radius:7px;padding:8px 10px;font-size:12px;font-weight:800;cursor:pointer}.all-good{text-align:center;padding:55px;background:#fff;border:1px solid #e0e6e2;border-radius:14px}.all-good>span{width:55px;height:55px;margin:auto;display:grid;place-items:center;border-radius:50%;background:#e6f3eb;color:#087846;font-size:25px}.all-good h2{margin:12px 0 4px}.all-good p{color:#7b867f}
.overlay{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:18px;background:rgba(20,30,24,.5);backdrop-filter:blur(4px)}.modal{position:relative;width:min(500px,100%);max-height:90vh;overflow:auto;padding:25px;background:#fff;border-radius:16px;box-shadow:0 30px 80px rgba(20,30,24,.25)}.modal.large{width:min(700px,100%)}.modal h2{margin:0 0 12px;font-size:22px}.modal p{font-size:13px;color:#758078}.modal label{display:grid;gap:6px;margin-top:10px;font-size:13px;font-weight:750}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.form-grid .full{grid-column:1/-1}.submit{width:100%;margin-top:14px}.close{position:absolute;right:12px;top:12px;width:32px;height:32px;border:0;border-radius:7px;background:#f2f5f3;font-size:18px;cursor:pointer}
.toast{position:fixed;right:20px;top:90px;z-index:150;padding:12px 16px;border-radius:9px;background:#087846;color:#fff;font-size:13px;font-weight:800}.toast.error{background:#8e2843}.warning-banner{margin-bottom:14px;padding:12px;background:#fff5d9;color:#7c591b;border:1px solid #ead7a0;border-radius:9px;font-size:13px}.loading{min-height:420px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#6f7973}.spinner{width:36px;height:36px;border:3px solid #e2e8e4;border-top-color:#087846;border-radius:50%;animation:spin .8s linear infinite}.empty{display:grid;place-items:center;height:100%;color:#8b958f;font-size:13px}@keyframes spin{to{transform:rotate(360deg)}}.fade-enter-active,.fade-leave-active{transition:.2s}.fade-enter-from,.fade-leave-to{opacity:0}
@media(max-width:1050px){.topbar-inner{flex-wrap:wrap;padding:9px 0}nav{order:3;flex-basis:100%;justify-content:flex-start}.profile>div{display:none}.kpis{grid-template-columns:repeat(2,1fr)}.charts{grid-template-columns:1fr}.card-grid{grid-template-columns:repeat(2,1fr)}.mini-stats{grid-template-columns:repeat(3,1fr)}}
@media(max-width:650px){main,.topbar-inner{width:calc(100% - 20px)}.heading,.record,.alerts article{align-items:flex-start;flex-direction:column}.context{flex-direction:column;width:100%}.context>div{width:100%}.filters,.filters.four,.kpis,.mini-stats,.card-grid,.form-grid{grid-template-columns:1fr}.form-grid .full{grid-column:auto}.heading h1{font-size:28px}}
</style>