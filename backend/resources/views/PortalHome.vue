<template>
  <div class="min-h-screen bg-[#F8FAF9] font-sans text-[#1E293B]">
    <!-- Navbar Institucional -->
    <header class="bg-[#003e1a] text-white border-b-4 border-[#C59B27] shadow-md">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <div class="bg-white px-3 py-1 rounded shadow-sm">
            <span class="font-black text-[#005826] text-xl tracking-wider">UPTEx</span>
          </div>
          <div>
            <h1 class="text-base font-bold uppercase tracking-wide leading-tight">Universidad Politécnica de Texcoco</h1>
            <p class="text-xs text-[#E6C665]">Portal Institucional de Becas</p>
          </div>
        </div>
        <div>
          <router-link to="/login" class="inline-flex items-center px-4 py-2 bg-[#C59B27] hover:bg-[#E6C665] text-[#003e1a] font-bold text-xs uppercase tracking-wider rounded transition shadow">
            Acceso al Sistema
          </router-link>
        </div>
      </div>
    </header>

    <!-- Contenido Principal -->
    <main class="max-w-7xl mx-auto px-6 py-8">
      <!-- Banner Convocatoria en PDF -->
      <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 mb-10 flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="space-y-3 max-w-2xl">
          <span class="px-3 py-1 bg-[#005826]/10 text-[#005826] text-xs font-semibold uppercase tracking-wider rounded-full">
            Convocatoria Vigente
          </span>
          <h2 class="text-2xl font-bold text-[#003e1a]">
            {{ convocatoria ? convocatoria.titulo : 'Cargando Convocatoria...' }}
          </h2>
          <p class="text-slate-600 text-sm leading-relaxed">
            Consulte los requisitos oficiales, fechas límites y la documentación que debe anexar para la recepción de su expediente.
          </p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <!-- Botón que activa el Visor PDF de la Convocatoria -->
          <button 
            @click="abrirModalPdf" 
            :disabled="!convocatoria"
            class="px-5 py-2.5 bg-[#005826] hover:bg-[#007A35] text-white font-medium rounded-lg text-sm transition shadow flex items-center justify-center space-x-2 disabled:opacity-50"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span>Ver Convocatoria</span>
          </button>

          <!-- Botón de Descarga Directa -->
          <a 
            v-if="convocatoria" 
            :href="convocatoria.pdf_url" 
            download
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg text-sm border border-slate-300 transition flex items-center justify-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Descargar PDF</span>
          </a>
        </div>
      </section>

      <!-- Carreras UPTEx con Logos Vectoriales -->
      <section class="mb-12">
        <h3 class="text-base font-bold text-[#003e1a] uppercase tracking-wider mb-6 pb-2 border-b border-slate-200">
          Programas Académicos Atendidos
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="carrera in carreras" :key="carrera.id" class="bg-white p-5 rounded-lg border border-slate-200 hover:border-[#005826]/40 transition shadow-sm flex items-start space-x-4">
            <CarreraLogo :carrera="carrera.clave" size="lg" />
            <div>
              <h4 class="font-bold text-sm text-slate-800">{{ carrera.nombre }}</h4>
              <p class="text-xs text-slate-500 mt-1">{{ carrera.departamento }}</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Modal Modal/Iframe Visor de PDF -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden border border-slate-300">
        <div class="px-6 py-4 bg-[#003e1a] text-white flex justify-between items-center">
          <h3 class="font-bold text-sm tracking-wide">{{ convocatoria?.titulo }}</h3>
          <button @click="mostrarModal = false" class="text-white/80 hover:text-white text-xl font-bold">&times;</button>
        </div>
        <div class="flex-1 bg-slate-100 p-2">
          <iframe :src="convocatoria?.pdf_url" class="w-full h-full rounded border-0"></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CarreraLogo from '../components/CarreraLogo.vue'

const convocatoria = ref(null)
const mostrarModal = ref(false)

const carreras = ref([
  { id: 1, clave: 'sistemas', nombre: 'Ing. en Sistemas Computacionales', departamento: 'División de Tecnologías' },
  { id: 2, clave: 'robotica', nombre: 'Ing. en Robótica Computacional', departamento: 'División de Ingeniería' },
  { id: 3, clave: 'industrial', nombre: 'Ing. Industrial y Logística', departamento: 'División Industrial' },
  { id: 4, clave: 'gestion', nombre: 'Lic. en Gestión Empresarial', departamento: 'División Económico Admin.' }
])

const abrirModalPdf = () => {
  if (convocatoria.value?.pdf_url) {
    mostrarModal.value = true
  }
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/convocatoria/activa')
    convocatoria.value = res.data.convocatoria
  } catch (error) {
    console.error('Error al consultar la convocatoria:', error)
  }
})
</script>