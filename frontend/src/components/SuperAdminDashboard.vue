<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '../api/axios';

const props = defineProps({ usuario: Object });
const emit = defineEmits(['cerrar-sesion']);

// ==================== NAVEGACIÓN PRINCIPAL ====================
const seccionActiva = ref('resumen');

const secciones = [
  { id: 'resumen', label: 'Resumen General' },
  { id: 'carreras', label: 'Carreras y Solicitudes' },
  { id: 'solicitudes', label: 'Todas las Solicitudes' },
  { id: 'personal', label: 'Personal' },
  { id: 'convocatorias', label: 'Convocatorias' },
  { id: 'alumnos', label: 'Alumnos' },
];

// ==================== ICONOS SVG (sin emojis) ====================
const iconosCarrera = {
  admin_empresarial: 'M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6',
  comercio: 'M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.9 13.9 0 010 18M12 3a13.9 13.9 0 000 18',
  electronica: 'M13 2L3 14h7l-1 8 10-12h-7l1-8z',
  robotica: 'M9 3h6v3H9V3zM6 9h12v9a3 3 0 01-3 3H9a3 3 0 01-3-3V9zM9 13h.01M15 13h.01M9 17h6',
  sistemas: 'M4 5h16v10H4V5zM2 19h20M9 15v4M15 15v4',
  logistica: 'M3 7h11v9H3V7zM14 10h4l3 3v3h-7v-6zM6 19a2 2 0 100-4 2 2 0 000 4zM17 19a2 2 0 100-4 2 2 0 000 4z',
};

// ==================== TOASTS ====================
const toasts = ref([]);
let toastId = 0;
const mostrarToast = (mensaje, tipo = 'exito') => {
  const id = toastId++;
  toasts.value.push({ id, mensaje, tipo });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 4000);
};

// ==================== DATOS BASE ====================
const carreras = ref([]);
const cargarCarreras = async () => {
  try {
    const { data } = await api.get('/carreras');
    carreras.value = data;
  } catch (e) { console.error(e); }
};

const solicitudesGlobal = ref([]);
const cargandoResumen = ref(false);
const cargarResumen = async () => {
  cargandoResumen.value = true;
  try {
    const { data } = await api.get('/master/solicitudes');
    solicitudesGlobal.value = data;
  } catch (e) { console.error(e); }
  finally { cargandoResumen.value = false; }
};

const resumen = computed(() => {
  const total = solicitudesGlobal.value.length;
  const pendiente = solicitudesGlobal.value.filter(s => s.estatus === 'pendiente').length;
  const aceptado = solicitudesGlobal.value.filter(s => s.estatus === 'aceptado').length;
  const rechazado = solicitudesGlobal.value.filter(s => s.estatus === 'rechazado').length;
  const porCarrera = carreras.value.map(c => ({
    ...c,
    total: solicitudesGlobal.value.filter(s => s.carrera_id === c.id).length,
  }));
  return { total, pendiente, aceptado, rechazado, porCarrera };
});

// ==================== SOLICITUDES POR CARRERA ====================
const carreraSeleccionada = ref(null);
const solicitudesDeCarrera = ref([]);
const cargandoCarrera = ref(false);
const filtroEstatus = ref('todos');
const filtroTipoBeca = ref('todos');
const busqueda = ref('');

const abrirCarrera = async (carrera) => {
  carreraSeleccionada.value = carrera;
  filtroEstatus.value = 'todos';
  filtroTipoBeca.value = 'todos';
  busqueda.value = '';
  await cargarSolicitudesCarrera();
};

const cargarSolicitudesCarrera = async () => {
  if (!carreraSeleccionada.value) return;
  cargandoCarrera.value = true;
  try {
    const params = { carrera_id: carreraSeleccionada.value.id };
    if (filtroEstatus.value !== 'todos') params.estatus = filtroEstatus.value;
    if (filtroTipoBeca.value !== 'todos') params.tipo_beca = filtroTipoBeca.value;
    if (busqueda.value.trim()) params.buscar = busqueda.value.trim();
    const { data } = await api.get('/master/solicitudes', { params });
    solicitudesDeCarrera.value = data;
  } catch (e) { console.error(e); }
  finally { cargandoCarrera.value = false; }
};

const cerrarCarrera = () => { carreraSeleccionada.value = null; };

const conteoCarrera = computed(() => ({
  total: solicitudesDeCarrera.value.length,
  pendiente: solicitudesDeCarrera.value.filter(s => s.estatus === 'pendiente').length,
  aceptado: solicitudesDeCarrera.value.filter(s => s.estatus === 'aceptado').length,
  rechazado: solicitudesDeCarrera.value.filter(s => s.estatus === 'rechazado').length,
}));

const cambiarEstatus = async (solicitud, nuevoEstatus) => {
  try {
    await api.patch(`/master/solicitudes/${solicitud.id}/estatus`, { estatus: nuevoEstatus });
    await cargarSolicitudesCarrera();
    mostrarToast(nuevoEstatus === 'aceptado' ? 'Solicitud aceptada.' : 'Solicitud rechazada.');
  } catch (e) { mostrarToast(e.response?.data?.message || 'Error al actualizar.', 'error'); }
};

// ==================== TODAS LAS SOLICITUDES ====================
const terminoBusquedaGeneral = ref('');
const filtroEstatusGeneral = ref('todos');
const resultadosGenerales = ref([]);
const buscandoGeneral = ref(false);

const buscarGeneral = async () => {
  buscandoGeneral.value = true;
  try {
    const params = {};
    if (terminoBusquedaGeneral.value.trim()) params.buscar = terminoBusquedaGeneral.value.trim();
    if (filtroEstatusGeneral.value !== 'todos') params.estatus = filtroEstatusGeneral.value;
    const { data } = await api.get('/master/solicitudes', { params });
    resultadosGenerales.value = data;
  } catch (e) { console.error(e); }
  finally { buscandoGeneral.value = false; }
};

// ==================== PERSONAL ====================
const staff = ref([]);
const cargarStaff = async () => {
  try {
    const { data } = await api.get('/master/staff');
    staff.value = data;
  } catch (e) { console.error(e); }
};

const mostrarFormularioStaff = ref(false);
const nuevoStaff = reactive({ name: '', email: '', password: '', role: 'profesor', carreras: [] });
const cargandoStaff = ref(false);
const mensajeStaff = ref('');
const errorStaff = ref(false);

const crearStaff = async () => {
  errorStaff.value = false;
  mensajeStaff.value = '';
  cargandoStaff.value = true;
  try {
    await api.post('/master/staff', nuevoStaff);
    mensajeStaff.value = 'Usuario creado correctamente.';
    await cargarStaff();
    Object.assign(nuevoStaff, { name: '', email: '', password: '', role: 'profesor', carreras: [] });
    setTimeout(() => { mostrarFormularioStaff.value = false; mensajeStaff.value = ''; }, 1200);
    mostrarToast('Usuario de personal creado.');
  } catch (e) {
    errorStaff.value = true;
    mensajeStaff.value = e.response?.data?.message || 'Error al crear usuario.';
  } finally { cargandoStaff.value = false; }
};

const eliminarStaff = async (u) => {
  if (!confirm(`¿Eliminar a ${u.name}? Esta acción no se puede deshacer.`)) return;
  try {
    await api.delete(`/master/staff/${u.id}`);
    await cargarStaff();
    mostrarToast('Usuario eliminado.');
  } catch (e) { mostrarToast(e.response?.data?.message || 'No se pudo eliminar.', 'error'); }
};

// ==================== CONVOCATORIAS (solo lectura) ====================
const convocatorias = ref([]);
const cargandoConvocatorias = ref(false);
const cargarConvocatorias = async () => {
  cargandoConvocatorias.value = true;
  try {
    const { data } = await api.get('/master/convocatorias');
    convocatorias.value = data;
  } catch (e) { console.error(e); }
  finally { cargandoConvocatorias.value = false; }
};

// ==================== ALUMNOS ====================
const alumnos = ref([]);
const cargandoAlumnos = ref(false);
const busquedaAlumnos = ref('');
const filtroCarreraAlumnos = ref('todas');

const cargarAlumnos = async () => {
  cargandoAlumnos.value = true;
  try {
    const params = {};
    if (busquedaAlumnos.value.trim()) params.buscar = busquedaAlumnos.value.trim();
    if (filtroCarreraAlumnos.value !== 'todas') params.carrera_id = filtroCarreraAlumnos.value;
    const { data } = await api.get('/master/alumnos', { params });
    alumnos.value = data;
  } catch (e) { console.error(e); }
  finally { cargandoAlumnos.value = false; }
};

const forzandoReset = ref(null);
const forzarResetPassword = async (alumno) => {
  if (!confirm(`¿Enviar código de recuperación de contraseña a ${alumno.name}?`)) return;
  forzandoReset.value = alumno.id;
  try {
    const { data } = await api.post(`/master/usuarios/${alumno.id}/forzar-reset`);
    mostrarToast(data.message);
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'No se pudo enviar el código.', 'error');
  } finally { forzandoReset.value = null; }
};

// ==================== ESTILOS COMPARTIDOS ====================
const estiloBadge = (estatus) => ({
  pendiente: 'bg-[#B45309]/10 text-[#B45309] border-[#B45309]/20',
  aceptado: 'bg-[#0F766E]/10 text-[#0F766E] border-[#0F766E]/20',
  rechazado: 'bg-[#7A1C33]/10 text-[#7A1C33] border-[#7A1C33]/20',
}[estatus] || 'bg-gray-50 text-gray-600 border-gray-200');

const estiloTipoBeca = (tipo) => ({
  promedio: 'bg-blue-50 text-blue-700',
  discapacidad: 'bg-purple-50 text-purple-700',
  socioeconomico: 'bg-teal-50 text-teal-700',
}[tipo] || 'bg-gray-50 text-gray-600');

// ==================== NAVEGACIÓN ====================
const irASeccion = async (id) => {
  seccionActiva.value = id;
  carreraSeleccionada.value = null;
  if (id === 'resumen') await Promise.all([cargarCarreras(), cargarResumen()]);
  if (id === 'carreras') await cargarCarreras();
  if (id === 'solicitudes') { resultadosGenerales.value = []; }
  if (id === 'personal') await cargarStaff();
  if (id === 'convocatorias') await cargarConvocatorias();
  if (id === 'alumnos') { await cargarCarreras(); await cargarAlumnos(); }
};

onMounted(async () => {
  await cargarCarreras();
  await cargarResumen();
});

const cerrarSesion = () => emit('cerrar-sesion');
</script>

<template>
  <div class="min-h-screen bg-[#F7F8FA] text-[#1F2937] font-sans antialiased flex">

    <!-- TOASTS -->
    <div class="fixed top-4 right-4 z-[100] space-y-2 w-80">
      <transition-group name="toast">
        <div v-for="t in toasts" :key="t.id"
          :class="t.tipo === 'error' ? 'bg-[#7A1C33] border-[#5C1526]' : 'bg-[#0F766E] border-[#0b5850]'"
          class="text-white text-xs font-bold px-4 py-3 rounded-xl shadow-lg border">
          {{ t.mensaje }}
        </div>
      </transition-group>
    </div>

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#1C1F26] text-white flex flex-col fixed inset-y-0 left-0 z-30">
      <div class="p-6 border-b border-white/10">
        <div class="flex items-center space-x-2.5">
          <div class="w-2.5 h-8 bg-[#7A1C33] rounded-sm"></div>
          <div>
            <p class="font-black text-lg tracking-tight leading-none">UPTeX</p>
            <p class="text-[10px] text-white/50 uppercase tracking-widest mt-1">Control de Becas</p>
          </div>
        </div>
      </div>

      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <button
          v-for="s in secciones" :key="s.id"
          @click="irASeccion(s.id)"
          :class="seccionActiva === s.id ? 'bg-[#00723F] text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'"
          class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-colors"
        >
          {{ s.label }}
        </button>
      </nav>

      <div class="p-4 border-t border-white/10 space-y-3">
        <div class="px-2">
          <p class="text-xs font-bold text-white truncate">{{ usuario?.name }}</p>
          <p class="text-[10px] text-white/40 uppercase tracking-wider">Master</p>
        </div>
        <button @click="cerrarSesion" class="w-full px-4 py-2.5 bg-[#7A1C33]/20 hover:bg-[#7A1C33]/30 text-[#f3a0af] rounded-xl text-xs font-bold transition-colors">
          Cerrar Sesión
        </button>
      </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 ml-64 min-h-screen">

      <!-- ============ RESUMEN GENERAL ============ -->
      <section v-if="seccionActiva === 'resumen'" class="p-8 space-y-8">
        <div>
          <h1 class="text-2xl font-black text-[#1C1F26]">Resumen General</h1>
          <p class="text-xs text-slate-500 font-medium mt-1">Vista consolidada de todas las carreras y solicitudes del sistema.</p>
        </div>

        <div v-if="cargandoResumen" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-5 border border-slate-200 h-24 animate-pulse"></div>
        </div>

        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl p-5 border border-slate-200">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Total Solicitudes</p>
            <p class="text-3xl font-black text-[#1C1F26] mt-1">{{ resumen.total }}</p>
          </div>
          <div class="bg-white rounded-2xl p-5 border border-slate-200">
            <p class="text-[10px] font-black text-[#B45309] uppercase tracking-wider">Pendientes</p>
            <p class="text-3xl font-black text-[#B45309] mt-1">{{ resumen.pendiente }}</p>
          </div>
          <div class="bg-white rounded-2xl p-5 border border-slate-200">
            <p class="text-[10px] font-black text-[#0F766E] uppercase tracking-wider">Aceptadas</p>
            <p class="text-3xl font-black text-[#0F766E] mt-1">{{ resumen.aceptado }}</p>
          </div>
          <div class="bg-white rounded-2xl p-5 border border-slate-200">
            <p class="text-[10px] font-black text-[#7A1C33] uppercase tracking-wider">Rechazadas</p>
            <p class="text-3xl font-black text-[#7A1C33] mt-1">{{ resumen.rechazado }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200">
          <h2 class="text-sm font-black text-[#1C1F26] mb-4">Solicitudes por Carrera</h2>
          <div v-if="resumen.porCarrera.length === 0" class="text-center py-6 text-xs text-slate-400 font-semibold">Sin datos aún.</div>
          <div v-else class="space-y-3">
            <div v-for="c in resumen.porCarrera" :key="c.id" class="flex items-center gap-4">
              <div class="w-9 h-9 rounded-lg bg-[#00723F]/10 flex items-center justify-center shrink-0">
                <svg viewBox="0 0 24 24" fill="none" stroke="#00723F" stroke-width="1.8" class="w-5 h-5">
                  <path :d="iconosCarrera[c.clave]" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-xs font-bold text-[#1C1F26]">{{ c.nombre }}</p>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1">
                  <div class="bg-[#00723F] h-1.5 rounded-full" :style="{ width: resumen.total ? (c.total / resumen.total * 100) + '%' : '0%' }"></div>
                </div>
              </div>
              <span class="text-xs font-black text-[#1C1F26] w-8 text-right">{{ c.total }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ============ CARRERAS Y SOLICITUDES ============ -->
      <section v-if="seccionActiva === 'carreras'" class="p-8 space-y-6">
        <div v-if="!carreraSeleccionada">
          <h1 class="text-2xl font-black text-[#1C1F26] mb-1">Carreras y Solicitudes</h1>
          <p class="text-xs text-slate-500 font-medium mb-6">Selecciona una carrera para revisar y gestionar sus solicitudes de beca.</p>

          <div v-if="carreras.length === 0" class="text-center py-16 text-xs text-slate-400 font-semibold bg-white rounded-2xl border border-slate-200">
            No hay carreras registradas.
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button
              v-for="c in carreras" :key="c.id"
              @click="abrirCarrera(c)"
              class="bg-white border border-slate-200 rounded-2xl p-5 text-left hover:border-[#00723F]/40 hover:shadow-md transition-all flex flex-col gap-4"
            >
              <div class="w-11 h-11 rounded-xl bg-[#00723F]/10 flex items-center justify-center">
                <svg viewBox="0 0 24 24" fill="none" stroke="#00723F" stroke-width="1.8" class="w-6 h-6">
                  <path :d="iconosCarrera[c.clave]" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-black text-[#1C1F26]">{{ c.nombre }}</p>
                <p class="text-[11px] text-slate-400 font-semibold mt-1">Ver solicitudes →</p>
              </div>
            </button>
          </div>
        </div>

        <div v-else>
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-[#00723F]/10 flex items-center justify-center">
                <svg viewBox="0 0 24 24" fill="none" stroke="#00723F" stroke-width="1.8" class="w-5 h-5">
                  <path :d="iconosCarrera[carreraSeleccionada.clave]" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <div>
                <h1 class="text-xl font-black text-[#1C1F26]">{{ carreraSeleccionada.nombre }}</h1>
                <p class="text-[11px] text-slate-400 font-semibold">{{ conteoCarrera.total }} solicitudes encontradas</p>
              </div>
            </div>
            <button @click="cerrarCarrera" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold">
              ← Volver a carreras
            </button>
          </div>

          <div class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl p-3 border border-slate-200 text-center">
              <p class="text-[9px] font-black text-slate-400 uppercase">Total</p>
              <p class="text-lg font-black text-[#1C1F26]">{{ conteoCarrera.total }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-slate-200 text-center">
              <p class="text-[9px] font-black text-[#B45309] uppercase">Pendientes</p>
              <p class="text-lg font-black text-[#B45309]">{{ conteoCarrera.pendiente }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-slate-200 text-center">
              <p class="text-[9px] font-black text-[#0F766E] uppercase">Aceptadas</p>
              <p class="text-lg font-black text-[#0F766E]">{{ conteoCarrera.aceptado }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-slate-200 text-center">
              <p class="text-[9px] font-black text-[#7A1C33] uppercase">Rechazadas</p>
              <p class="text-lg font-black text-[#7A1C33]">{{ conteoCarrera.rechazado }}</p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
              <input v-model="busqueda" @keyup.enter="cargarSolicitudesCarrera" type="text" placeholder="Buscar por nombre o matrícula..."
                class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-xs outline-none focus:border-[#00723F]" />
              <select v-model="filtroEstatus" @change="cargarSolicitudesCarrera" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold">
                <option value="todos">Todos los estatus</option>
                <option value="pendiente">Pendiente</option>
                <option value="aceptado">Aceptado</option>
                <option value="rechazado">Rechazado</option>
              </select>
              <select v-model="filtroTipoBeca" @change="cargarSolicitudesCarrera" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold">
                <option value="todos">Todos los tipos</option>
                <option value="promedio">Promedio</option>
                <option value="discapacidad">Discapacidad</option>
                <option value="socioeconomico">Socioeconómico</option>
              </select>
              <button @click="cargarSolicitudesCarrera" class="px-5 py-2.5 bg-[#1C1F26] hover:bg-black text-white rounded-xl text-xs font-bold">Buscar</button>
            </div>

            <div v-if="cargandoCarrera" class="text-center py-10 text-xs text-slate-400 font-semibold">Cargando...</div>
            <div v-else-if="solicitudesDeCarrera.length === 0" class="text-center py-10 text-xs text-slate-400 font-semibold">Sin solicitudes con estos filtros.</div>

            <div v-else class="divide-y divide-slate-100">
              <div v-for="s in solicitudesDeCarrera" :key="s.id" class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <p class="text-xs font-black text-[#1C1F26]">{{ s.alumno.name }}</p>
                  <p class="text-[10px] text-slate-400 font-semibold">{{ s.alumno.matricula }} · Grupo {{ s.grupo }} · {{ s.convocatoria?.periodo }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="estiloTipoBeca(s.tipo_beca)" class="text-[10px] font-bold px-2.5 py-1 rounded-full capitalize">{{ s.tipo_beca }}</span>
                  <span :class="estiloBadge(s.estatus)" class="text-[10px] font-bold px-2.5 py-1 rounded-full border uppercase">{{ s.estatus }}</span>
                  <button @click="cambiarEstatus(s, 'aceptado')" class="px-2.5 py-1 bg-[#0F766E]/10 text-[#0F766E] rounded-lg text-[10px] font-bold hover:bg-[#0F766E]/20">Aceptar</button>
                  <button @click="cambiarEstatus(s, 'rechazado')" class="px-2.5 py-1 bg-[#7A1C33]/10 text-[#7A1C33] rounded-lg text-[10px] font-bold hover:bg-[#7A1C33]/20">Rechazar</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ============ TODAS LAS SOLICITUDES ============ -->
      <section v-if="seccionActiva === 'solicitudes'" class="p-8 space-y-6">
        <div>
          <h1 class="text-2xl font-black text-[#1C1F26]">Todas las Solicitudes</h1>
          <p class="text-xs text-slate-500 font-medium mt-1">Búsqueda global por nombre o matrícula, en cualquier carrera.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row gap-3">
            <input v-model="terminoBusquedaGeneral" @keyup.enter="buscarGeneral" type="text" placeholder="Buscar por nombre o matrícula..."
              class="flex-1 border border-slate-200 rounded-xl px-4 py-3 text-xs outline-none focus:border-[#00723F]" />
            <select v-model="filtroEstatusGeneral" class="border border-slate-200 rounded-xl px-3 py-3 text-xs font-bold">
              <option value="todos">Todos los estatus</option>
              <option value="pendiente">Pendiente</option>
              <option value="aceptado">Aceptado</option>
              <option value="rechazado">Rechazado</option>
            </select>
            <button @click="buscarGeneral" class="px-6 py-3 bg-[#1C1F26] hover:bg-black text-white rounded-xl text-xs font-bold">Buscar</button>
          </div>

          <div v-if="buscandoGeneral" class="text-center py-10 text-xs text-slate-400 font-semibold">Buscando...</div>
          <div v-else-if="resultadosGenerales.length === 0" class="text-center py-10 text-xs text-slate-400 font-semibold">
            Ingresa un término o selecciona un estatus y presiona Buscar.
          </div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="s in resultadosGenerales" :key="s.id" class="py-3 flex items-center justify-between">
              <div>
                <p class="text-xs font-black text-[#1C1F26]">{{ s.alumno.name }}</p>
                <p class="text-[10px] text-slate-400 font-semibold">{{ s.alumno.matricula }} · {{ s.carrera.nombre }} · Grupo {{ s.grupo }}</p>
              </div>
              <span :class="estiloBadge(s.estatus)" class="text-[10px] font-bold px-2.5 py-1 rounded-full border uppercase">{{ s.estatus }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ============ PERSONAL ============ -->
      <section v-if="seccionActiva === 'personal'" class="p-8 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-[#1C1F26]">Personal del Sistema</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Jefes de Carrera y Profesores/Tutores registrados.</p>
          </div>
          <button @click="mostrarFormularioStaff = !mostrarFormularioStaff" class="px-5 py-2.5 bg-[#00723F] hover:bg-[#005C32] text-white rounded-xl text-xs font-bold">
            {{ mostrarFormularioStaff ? 'Cancelar' : '+ Agregar Personal' }}
          </button>
        </div>

        <div v-if="mostrarFormularioStaff" class="bg-white rounded-2xl p-6 border border-slate-200 space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input v-model="nuevoStaff.name" type="text" placeholder="Nombre completo" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs" />
            <input v-model="nuevoStaff.email" type="email" placeholder="Correo institucional" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs" />
            <input v-model="nuevoStaff.password" type="password" placeholder="Contraseña temporal" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs" />
            <select v-model="nuevoStaff.role" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold">
              <option value="profesor">Profesor/Tutor</option>
              <option value="admin">Jefe de Carrera</option>
              <option value="master">Master</option>
            </select>
          </div>
          <select v-model="nuevoStaff.carreras" multiple class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs h-24">
            <option v-for="c in carreras" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
          <p v-if="mensajeStaff" :class="errorStaff ? 'text-[#7A1C33]' : 'text-[#0F766E]'" class="text-[11px] font-semibold">{{ mensajeStaff }}</p>
          <button @click="crearStaff" :disabled="cargandoStaff" class="px-6 py-2.5 bg-[#1C1F26] hover:bg-black text-white rounded-xl text-xs font-bold disabled:opacity-50">
            {{ cargandoStaff ? 'Creando...' : 'Crear Usuario' }}
          </button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div v-if="staff.length === 0" class="text-center py-10 text-xs text-slate-400 font-semibold">Sin personal registrado.</div>
          <div v-for="u in staff" :key="u.id" class="flex items-center justify-between p-4 border-b border-slate-100 last:border-0">
            <div>
              <p class="text-xs font-black text-[#1C1F26]">{{ u.name }}</p>
              <p class="text-[10px] text-slate-400 font-semibold capitalize">
                {{ u.role === 'admin' ? 'Jefe de Carrera' : u.role === 'profesor' ? 'Profesor/Tutor' : 'Master' }}
                · {{ (u.carreras_asignadas || []).map(c => c.nombre).join(', ') || 'Sin carrera asignada' }}
              </p>
            </div>
            <button @click="eliminarStaff(u)" class="text-[10px] font-bold text-[#7A1C33] hover:underline">Eliminar</button>
          </div>
        </div>
      </section>

      <!-- ============ CONVOCATORIAS (solo lectura) ============ -->
      <section v-if="seccionActiva === 'convocatorias'" class="p-8 space-y-6">
        <div>
          <h1 class="text-2xl font-black text-[#1C1F26]">Convocatorias</h1>
          <p class="text-xs text-slate-500 font-medium mt-1">Vista de solo lectura. Las convocatorias se crean desde el panel de Jefe de Carrera.</p>
        </div>

        <div v-if="cargandoConvocatorias" class="text-center py-10 text-xs text-slate-400 font-semibold">Cargando...</div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div v-if="convocatorias.length === 0" class="text-center py-10 text-xs text-slate-400 font-semibold">No hay convocatorias registradas.</div>
          <div v-for="c in convocatorias" :key="c.id" class="p-4 border-b border-slate-100 last:border-0 flex items-center justify-between">
            <div>
              <p class="text-xs font-black text-[#1C1F26]">{{ c.titulo }}</p>
              <p class="text-[10px] text-slate-400 font-semibold">{{ c.periodo }} · {{ c.carrera?.nombre || 'Todas las carreras' }}</p>
            </div>
            <span :class="c.activa ? 'bg-[#0F766E]/10 text-[#0F766E]' : 'bg-slate-100 text-slate-500'" class="text-[10px] font-bold px-3 py-1 rounded-full">
              {{ c.activa ? 'Activa' : 'Inactiva' }}
            </span>
          </div>
        </div>
      </section>

      <!-- ============ ALUMNOS ============ -->
      <section v-if="seccionActiva === 'alumnos'" class="p-8 space-y-6">
        <div>
          <h1 class="text-2xl font-black text-[#1C1F26]">Alumnos</h1>
          <p class="text-xs text-slate-500 font-medium mt-1">Vista de solo lectura. Puedes forzar el envío de un código de recuperación de contraseña.</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 flex flex-col sm:flex-row gap-3">
          <input v-model="busquedaAlumnos" @keyup.enter="cargarAlumnos" type="text" placeholder="Buscar por nombre o matrícula..."
            class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-xs outline-none focus:border-[#00723F]" />
          <select v-model="filtroCarreraAlumnos" @change="cargarAlumnos" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold">
            <option value="todas">Todas las carreras</option>
            <option v-for="c in carreras" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
          <button @click="cargarAlumnos" class="px-5 py-2.5 bg-[#1C1F26] hover:bg-black text-white rounded-xl text-xs font-bold">Buscar</button>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div v-if="cargandoAlumnos" class="text-center py-10 text-xs text-slate-400 font-semibold">Cargando...</div>
          <div v-else-if="alumnos.length === 0" class="text-center py-10 text-xs text-slate-400 font-semibold">Sin alumnos encontrados.</div>
          <div v-for="a in alumnos" :key="a.id" class="flex items-center justify-between p-4 border-b border-slate-100 last:border-0">
            <div>
              <p class="text-xs font-black text-[#1C1F26]">{{ a.name }}</p>
              <p class="text-[10px] text-slate-400 font-semibold">{{ a.matricula || 'Sin matrícula' }} · {{ a.carrera?.nombre || 'Sin carrera' }} · Grupo {{ a.grupo || '—' }}</p>
            </div>
            <button @click="forzarResetPassword(a)" :disabled="forzandoReset === a.id"
              class="text-[10px] font-bold text-[#00723F] hover:underline disabled:opacity-50">
              {{ forzandoReset === a.id ? 'Enviando...' : 'Forzar recuperación de contraseña' }}
            </button>
          </div>
        </div>
      </section>

    </main>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>