<script setup>
import { ref } from 'vue';
import { 
  LayoutDashboard, FileText, Users, 
  FolderOpen, Search, Plus, Edit2, Trash2 
} from 'lucide-vue-next';
import logoUptex from '../assets/logo-uptex.png';

// Recibimos el rol del usuario desde App.vue
defineProps({
  role: {
    type: String,
    default: 'alumno'
  }
});

const convocatorias = ref([
  { id: 19, titulo: "Beca Excelencia Académica UPTEX", tipo: "Convocatorias", periodo: "1 abr 2026 - May 2026", estado: "Active", presupuesto: 25000 },
  { id: 21, titulo: "Beca Aprovechamiento Escolar", tipo: "Convocatorias", periodo: "6 abr 2026 - May 2026", estado: "Active", presupuesto: 2600.91 },
  { id: 22, titulo: "Beca Apoyo Socioeconómico", tipo: "Convocatorias", periodo: "6 abr 2026 - May 2026", estado: "Closed", presupuesto: 5600.95 }
]);

const meses = [
  { name: 'Jan', valor: 65 }, { name: 'Feb', valor: 40 }, { name: 'Mar', valor: 55 },
  { name: 'Apr', valor: 70 }, { name: 'May', valor: 90 }, { name: 'Jun', valor: 120 },
  { name: 'Jul', valor: 110 }, { name: 'Aug', valor: 30 }, { name: 'Sep', valor: 85 },
  { name: 'Oct', valor: 95 }, { name: 'Nov', valor: 105 }, { name: 'Dec', valor: 50 }
];
</script>

<template>
  <div class="flex h-screen bg-slate-100 font-sans text-slate-800 w-full overflow-hidden antialiased">
    
    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col justify-between shadow-xl h-full flex-shrink-0">
      <div>
        <div class="p-4 flex flex-col items-center gap-2 border-b border-slate-800 bg-white m-3 rounded-xl shadow-inner">
          <img :src="logoUptex" alt="UPTEX" class="h-12 w-auto object-contain" />
          <span class="text-[10px] font-bold text-slate-500 tracking-widest uppercase">Control de Becas</span>
        </div>
        
        <nav class="p-4 space-y-2">
          <button class="flex items-center gap-3 w-full p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors text-sm font-medium cursor-pointer">
            <LayoutDashboard :size="18" /> Dashboard
          </button>
          
          <button v-if="role !== 'alumno'" class="flex items-center gap-3 w-full p-3 rounded-lg bg-emerald-700 text-white transition-colors text-sm font-semibold shadow-md cursor-pointer">
            <FileText :size="18" /> Convocatorias
          </button>
          
          <button v-if="role === 'super_admin' || role === 'admin'" class="flex items-center gap-3 w-full p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors text-sm font-medium cursor-pointer">
            <Users :size="18" /> Usuarios
          </button>
          
          <button class="flex items-center gap-3 w-full p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors text-sm font-medium cursor-pointer">
            <FolderOpen :size="18" /> Solicitudes
          </button>
        </nav>
      </div>
      <div class="p-4 border-t border-slate-800 text-[10px] text-center text-slate-500 font-mono">UPTEX • V1.0.0 • 2026</div>
    </aside>

    <!-- CONTENIDO -->
    <main class="flex-1 flex flex-col h-full overflow-y-auto">
      <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center shadow-sm">
        <h2 class="text-xl font-bold text-slate-800">
          {{ role === 'alumno' ? 'Portal del Estudiante' : 'Panel de Administración' }} 
          <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded ml-2 uppercase font-mono border border-slate-200">
            {{ role }}
          </span>
        </h2>
        <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center font-bold text-white shadow-sm uppercase">
          {{ role[0] }}
        </div>
      </header>

      <div class="p-8 space-y-6">
        <!-- BARRA DE HERRAMIENTAS -->
        <div v-if="role !== 'alumno'" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex justify-between items-center">
          <div class="relative w-72">
            <Search class="absolute left-3 top-2.5 text-slate-400" :size="18" />
            <input type="text" placeholder="Buscar convocatoria..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 focus:outline-none" />
          </div>
          <button v-if="role === 'super_admin' || role === 'admin'" class="flex items-center gap-2 bg-emerald-700 text-white font-semibold text-sm px-4 py-2 rounded-lg cursor-pointer hover:bg-emerald-800">
            <Plus :size="16" /> Nueva Convocatoria
          </button>
        </div>

        <!-- TABLA DE CONTROL CORREGIDA -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase font-bold tracking-wider">
                <th class="p-4 text-center">ID</th>
                <th class="p-4">Título de Beca de Descuento</th>
                <th class="p-4">Tipo</th>
                <th class="p-4">Periodo</th>
                <th class="p-4 text-center">Estado</th>
                <th v-if="role !== 'alumno'" class="p-4 text-right">Presupuesto</th>
                <th v-if="role === 'super_admin' || role === 'admin'" class="p-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="beca in convocatorias" :key="beca.id" class="hover:bg-slate-50/80 transition-colors">
                <td class="p-4 text-center font-medium text-slate-400">{{ beca.id }}</td>
                <td class="p-4 font-semibold text-slate-700">{{ beca.titulo }}</td>
                <td class="p-4 text-slate-500">{{ beca.tipo }}</td>
                <td class="p-4 text-slate-500 text-xs">{{ beca.periodo }}</td>
                <td class="p-4 text-center">
                  <span :class="`px-2.5 py-1 rounded-full text-xs font-bold border ${beca.estado === 'Active' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'}`">
                    {{ beca.estado }}
                  </span>
                </td>
                <td v-if="role !== 'alumno'" class="p-4 text-right font-bold text-slate-700">${{ beca.presupuesto }}</td>
                <td v-if="role === 'super_admin' || role === 'admin'" class="p-4 text-center">
                  <div class="flex justify-center gap-2">
                    <button class="text-slate-400 hover:text-emerald-600 p-1 cursor-pointer"><Edit2 :size="16" /></button>
                    <button class="text-slate-400 hover:text-red-600 p-1 cursor-pointer"><Trash2 :size="16" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- GRÁFICA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h3 class="font-bold text-slate-700 mb-6 text-sm uppercase tracking-wide">Solicitudes de Descuento Procesadas</h3>
            <div class="flex items-end justify-between h-48 pt-4 px-2 border-b border-l border-slate-200">
              <div v-for="m in meses" :key="m.name" class="flex flex-col items-center flex-1 group relative mx-1">
                <div :style="{ height: `${(m.valor / 130) * 100}%` }" class="w-full bg-emerald-600 rounded-t hover:bg-amber-500 transition-all duration-300 min-h-[4px]" />
                <span class="text-[10px] text-slate-400 font-medium mt-2">{{ m.name }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-between">
            <div>
              <span class="px-2 py-1 bg-green-100 text-green-700 font-bold text-[10px] rounded uppercase">Estatus</span>
              <h4 class="font-bold text-slate-800 mt-2 text-base">Convocatoria Primavera 2026</h4>
              <p class="text-xs text-slate-500 mt-1">Aplica directo para exención parcial del pago de reinscripción.</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>