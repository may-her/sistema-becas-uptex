<template>
  <div :class="[sizeClass, 'flex items-center justify-center rounded-lg bg-uptex-green/10 text-uptex-green border border-uptex-green/20']">
    <!-- Robótica / Mecatrónica -->
    <svg v-if="carreraKey === 'robotica' || carreraKey === 'mecatronica'" class="w-2/3 h-2/3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
    </svg>

    <!-- Sistemas / Software / Computación -->
    <svg v-else-if="carreraKey === 'sistemas' || carreraKey === 'software'" class="w-2/3 h-2/3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
    </svg>

    <!-- Industrial / Logística -->
    <svg v-else-if="carreraKey === 'industrial' || carreraKey === 'logistica'" class="w-2/3 h-2/3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h9m-9 0V9a2 2 0 012-2h2a2 2 0 012 2v12"/>
    </svg>

    <!-- Administración / Gestión -->
    <svg v-else-if="carreraKey === 'gestion' || carreraKey === 'administracion'" class="w-2/3 h-2/3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>

    <!-- Genérico por defecto -->
    <svg v-else class="w-2/3 h-2/3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
    </svg>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  carrera: { type: String, required: true },
  size: { type: String, default: 'md' }
})

const carreraKey = computed(() => props.carrera.toLowerCase())

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-8 h-8'
    case 'lg': return 'w-12 h-12'
    default: return 'w-10 h-10'
  }
})
</script>