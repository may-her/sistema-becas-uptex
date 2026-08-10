<script setup>
import { ref } from 'vue';
import api from '../api/axios';

const props = defineProps({
  challengeToken: {
    type: String,
    required: true,
  },
});

const emit = defineEmits([
  'completado',
  'cancelar',
]);

const codigo = ref('');
const cargando = ref(false);
const error = ref('');

const verificar = async () => {
  error.value = '';

  if (!/^\d{6}$/.test(codigo.value)) {
    error.value =
      'Ingresa un código válido de 6 dígitos.';

    return;
  }

  cargando.value = true;

  try {
    const { data } =
      await api.post(
        '/two-factor/challenge',
        {
          challenge_token:
            props.challengeToken,

          code:
            codigo.value,
        }
      );

    if (
      !data?.token ||
      !data?.user
    ) {
      throw new Error(
        'Respuesta de autenticación incompleta.'
      );
    }

    emit(
      'completado',
      data
    );
  } catch (e) {
    console.error(
      'Error verificando 2FA:',
      e
    );

    error.value =
      e.response?.data?.message ||
      e.message ||
      'Código incorrecto o expirado.';
  } finally {
    cargando.value = false;
  }
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-slate-100 p-4"
  >
    <div
      class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200 p-8 space-y-6 text-center"
    >
      <h1
        class="text-xl font-black text-[#1C1F26] uppercase"
      >
        Verificación en dos pasos
      </h1>

      <p
        class="text-sm text-slate-500"
      >
        Abre tu aplicación autenticadora
        e introduce el código de seis dígitos.
      </p>

      <input
        v-model="codigo"
        type="text"
        maxlength="6"
        inputmode="numeric"
        autocomplete="one-time-code"
        placeholder="000000"
        @keyup.enter="verificar"
        class="w-full text-center text-2xl tracking-[0.4em] font-mono border border-slate-200 rounded-xl py-3"
      />

      <p
        v-if="error"
        class="text-xs text-red-700 font-bold"
      >
        {{ error }}
      </p>

      <button
        type="button"
        @click="verificar"
        :disabled="
          cargando ||
          codigo.length !== 6
        "
        class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold uppercase text-xs disabled:opacity-50"
      >
        {{
          cargando
            ? 'Verificando...'
            : 'Verificar código'
        }}
      </button>

      <button
        type="button"
        @click="emit('cancelar')"
        class="text-xs text-slate-500 hover:underline"
      >
        Cancelar
      </button>
    </div>
  </div>
</template>