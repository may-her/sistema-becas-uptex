<script setup>
import { ref } from 'vue';
import { 
  Briefcase, LogOut, Settings, Calendar, 
  Percent, FileText, CheckCircle, BarChart3, ShieldCheck 
} from 'lucide-vue-next';

defineProps({ onSalir: Function });

const estadisticas = ref({
  totalSolicitudes: 420,
  aprobadas: 185,
  montoAhorrado: '$245,000 MXN'
});
</script>

<template>
  <div class="min-h-screen bg-slate-50/60 text-slate-800 font-sans">
    <nav class="bg-blue-900 text-white px-6 py-4 flex justify-between items-center shadow-sm">
      <div class="flex items-center gap-3">
        <div class="bg-blue-950 p-2 rounded-lg text-blue-300">
          <ShieldCheck :size="24" />
        </div>
        <div>
          <h1 class="text-base font-bold tracking-wide leading-none">UPTEX</h1>
          <span class="text-xs text-blue-200 font-medium">Administración Institucional de Becas</span>
        </div>
      </div>
      <button @click="onSalir" class="flex items-center gap-2 bg-blue-950 hover:bg-black/30 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-blue-700/50 cursor-pointer shadow-sm">
        <LogOut :size="14" /> Cerrar Sesión
      </button>
    </nav>

    <main class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
      
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="bg-blue-50 p-3 rounded-xl text-blue-600"><FileText :size="22" /></div>
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Global Recibidas</p>
            <h3 class="text-xl font-black text-slate-800 mt-0.5">{{ estadisticas.totalSolicitudes }}</h3>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="bg-emerald-50 p-3 rounded-xl text-emerald-600"><CheckCircle :size="22" /></div>
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Plazas Asignadas</p>
            <h3 class="text-xl font-black text-slate-800 mt-0.5">{{ estadisticas.aprobadas }} alumnos</h3>
          </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="bg-indigo-50 p-3 rounded-xl text-indigo-600"><BarChart3 :size="22" /></div>
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Subsidio Emitido</p>
            <h3 class="text-xl font-black text-slate-800 mt-0.5">{{ estadisticas.montoAhorrado }}</h3>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm lg:col-span-2 space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center gap-2 text-slate-800">
            <Calendar class="text-blue-600" :size="18" />
            <h3 class="font-black text-base">Vigencia y Calendario del Periodo</h3>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1.5">Apertura del Registro</label>
              <input type="date" value="2026-06-01" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-600" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-1.5">Cierre del Registro</label>
              <input type="date" value="2026-07-15" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-600" />
            </div>
          </div>
          <div class="pt-2">
            <button class="bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-sm transition-all cursor-pointer">
              Actualizar Fechas de Servidor
            </button>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center gap-2 text-slate-800">
            <Percent class="text-emerald-600" :size="18" />
            <h3 class="font-black text-base">Topes de Descuento</h3>
          </div>
          <div class="space-y-2 text-xs">
            <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl font-medium">
              <span class="text-slate-500">Beca de Excelencia</span>
              <span class="font-bold text-slate-800">50% Descuento</span>
            </div>
            <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl font-medium">
              <span class="text-slate-500">Beca Socioeconómica</span>
              <span class="font-bold text-slate-800">30% Descuento</span>
            </div>
            <div class="flex justify-between p-2.5 bg-slate-50 rounded-xl font-medium">
              <span class="text-slate-500">Beca Deportiva / Cultural</span>
              <span class="font-bold text-slate-800">25% Descuento</span>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</template>