<script setup>
import { onMounted, ref } from 'vue';
import api from '../api/axios';

const emit = defineEmits(['regresar']);

const cargando = ref(false);
const error = ref('');
const mensaje = ref('');

const habilitado = ref(false);
const qrSvg = ref('');
const codigo = ref('');
const recoveryCodes = ref([]);

const limpiarMensajes = () => {
  error.value = '';
  mensaje.value = '';
};

const cargarEstado = async () => {
  limpiarMensajes();

  try {
    const { data } = await api.get('/two-factor/status');

    habilitado.value = Boolean(data?.enabled);

    if (habilitado.value) {
      await cargarCodigosRecuperacion();
    }
  } catch (e) {
    console.error('Error cargando estado 2FA:', e);

    error.value =
      e.response?.data?.message ||
      'No fue posible consultar el estado de 2FA.';
  }
};

const activar = async () => {
  limpiarMensajes();

  cargando.value = true;

  try {
    const { data } = await api.post('/two-factor/enable');

    qrSvg.value = data?.svg || '';

    codigo.value = '';

    mensaje.value =
      'Escanea el código QR y escribe el código de 6 dígitos.';
  } catch (e) {
    console.error('Error activando 2FA:', e);

    error.value =
      e.response?.data?.message ||
      'No fue posible preparar el 2FA.';
  } finally {
    cargando.value = false;
  }
};

const confirmar = async () => {
  limpiarMensajes();

  if (!/^\d{6}$/.test(codigo.value)) {
    error.value =
      'Debes ingresar un código de 6 dígitos.';

    return;
  }

  cargando.value = true;

  try {
    await api.post('/two-factor/confirm', {
      code: codigo.value,
    });

    habilitado.value = true;

    qrSvg.value = '';

    codigo.value = '';

    mensaje.value =
      '2FA activado correctamente.';

    await cargarCodigosRecuperacion();
  } catch (e) {
    console.error('Error confirmando 2FA:', e);

    error.value =
      e.response?.data?.message ||
      'El código es incorrecto o expiró.';
  } finally {
    cargando.value = false;
  }
};

const cargarCodigosRecuperacion = async () => {
  try {
    const { data } =
      await api.get(
        '/two-factor/recovery-codes'
      );

    recoveryCodes.value =
      data?.codes || [];
  } catch (e) {
    console.error(
      'Error cargando códigos de recuperación:',
      e
    );
  }
};

const desactivar = async () => {
  limpiarMensajes();

  cargando.value = true;

  try {
    await api.delete('/two-factor');

    habilitado.value = false;

    qrSvg.value = '';

    codigo.value = '';

    recoveryCodes.value = [];

    mensaje.value =
      '2FA desactivado correctamente.';
  } catch (e) {
    console.error(
      'Error desactivando 2FA:',
      e
    );

    error.value =
      e.response?.data?.message ||
      'No se pudo desactivar 2FA.';
  } finally {
    cargando.value = false;
  }
};

onMounted(cargarEstado);
</script>

<template>
  <div
    class="min-h-screen bg-slate-100 flex items-center justify-center p-4"
  >
    <div
      class="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-slate-200 p-8 space-y-6"
    >
      <div
        class="flex justify-between items-start"
      >
        <div>
          <h1
            class="text-xl font-black text-[#1C1F26] uppercase"
          >
            Seguridad 2FA
          </h1>

          <p
            class="text-xs text-slate-500 mt-1"
          >
            Protege tu cuenta con un código temporal.
          </p>
        </div>

        <button
          type="button"
          @click="emit('regresar')"
          class="text-xs font-bold text-slate-500 hover:text-[#00723F]"
        >
          Regresar
        </button>
      </div>

      <p
        v-if="mensaje"
        class="text-sm text-green-700 bg-green-50 p-3 rounded-xl"
      >
        {{ mensaje }}
      </p>

      <p
        v-if="error"
        class="text-sm text-red-700 bg-red-50 p-3 rounded-xl"
      >
        {{ error }}
      </p>

      <div
        v-if="!habilitado && !qrSvg"
        class="text-center space-y-4"
      >
        <p
          class="text-sm text-slate-600"
        >
          Utiliza Google Authenticator,
          Microsoft Authenticator
          u otra aplicación compatible con TOTP.
        </p>

        <button
          type="button"
          @click="activar"
          :disabled="cargando"
          class="bg-[#00723F] text-white px-6 py-3 rounded-xl font-bold text-xs uppercase disabled:opacity-50"
        >
          {{
            cargando
              ? 'Preparando...'
              : 'Activar autenticación 2FA'
          }}
        </button>
      </div>

      <div
        v-if="qrSvg && !habilitado"
        class="space-y-5 text-center"
      >
        <h2
          class="font-black text-slate-800"
        >
          Escanea este QR
        </h2>

        <div
          class="flex justify-center bg-white p-4 rounded-2xl"
          v-html="qrSvg"
        ></div>

        <p
          class="text-xs text-slate-500"
        >
          Después escribe el código de seis dígitos
          generado por tu aplicación.
        </p>

        <input
          v-model="codigo"
          type="text"
          maxlength="6"
          inputmode="numeric"
          autocomplete="one-time-code"
          placeholder="000000"
          class="w-full text-center text-2xl tracking-[0.4em] font-mono border border-slate-200 rounded-xl py-3"
        />

        <button
          type="button"
          @click="confirmar"
          :disabled="
            cargando ||
            codigo.length !== 6
          "
          class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold uppercase text-xs disabled:opacity-50"
        >
          {{
            cargando
              ? 'Confirmando...'
              : 'Confirmar código'
          }}
        </button>
      </div>

      <div
        v-if="habilitado"
        class="space-y-5"
      >
        <div
          class="bg-green-50 border border-green-200 rounded-xl p-4"
        >
          <p
            class="font-bold text-green-700"
          >
            ✓ 2FA está activado
          </p>
        </div>

        <div
          v-if="recoveryCodes.length"
        >
          <h3
            class="font-black text-sm text-slate-800 mb-2"
          >
            Códigos de recuperación
          </h3>

          <p
            class="text-xs text-slate-500 mb-3"
          >
            Guárdalos en un lugar seguro.
          </p>

          <div
            class="grid grid-cols-1 sm:grid-cols-2 gap-2"
          >
            <code
              v-for="item in recoveryCodes"
              :key="item"
              class="bg-slate-100 rounded-lg px-3 py-2 text-xs"
            >
              {{ item }}
            </code>
          </div>
        </div>

        <button
          type="button"
          @click="desactivar"
          :disabled="cargando"
          class="w-full bg-red-700 text-white py-3 rounded-xl font-bold uppercase text-xs disabled:opacity-50"
        >
          {{
            cargando
              ? 'Desactivando...'
              : 'Desactivar 2FA'
          }}
        </button>
      </div>
    </div>
  </div>
</template>