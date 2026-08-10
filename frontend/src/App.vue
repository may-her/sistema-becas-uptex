<script setup>
import {
  ref,
  onMounted,
  onUnmounted
} from 'vue';

import api from './api/axios';

import logoUptex from './assets/logo-uptex.png';
import campus1 from './assets/universidad1.jpg';
import campus2 from './assets/universidad2.jpg';
import campus3 from './assets/universidad3.jpg';

import SuperAdminDashboard
  from './components/SuperAdminDashboard.vue';

import JefeDashboard
  from './components/JefeDashboard.vue';

import TutorDashboard
  from './components/TutorDashboard.vue';

import AlumnoDashboard
  from './components/AlumnoDashboard.vue';

import TwoFactorSetup
  from './views/TwoFactorSetup.vue';

import TwoFactorChallenge
  from './views/TwoFactorChallenge.vue';


/* =========================================================
   FONDOS
========================================================= */

const fondos = ref([
  campus1,
  campus2,
  campus3
]);

const fondoActivo = ref(0);

let intervaloFondo = null;

const cambiarFondoAutomatico = () => {

  intervaloFondo =
    setInterval(() => {

      fondoActivo.value =
        (
          fondoActivo.value + 1
        ) % fondos.value.length;

    }, 4000);
};


/* =========================================================
   VISTA
========================================================= */

const vistaActiva =
  ref('inicio');

const usuarioActivo =
  ref(null);


/* =========================================================
   2FA
========================================================= */

const twoFactorChallengeToken =
  ref('');


const abrirTwoFactorSetup = () => {

  if (!usuarioActivo.value) {
    return;
  }

  vistaActiva.value =
    'two-factor-setup';
};


const cancelarTwoFactorChallenge = () => {

  twoFactorChallengeToken.value =
    '';

  vistaActiva.value =
    'login';
};


const completarTwoFactor = (data) => {

  if (
    !data?.token ||
    !data?.user
  ) {
    return;
  }

  localStorage.setItem(
    'auth_token',
    data.token
  );

  localStorage.setItem(
    'auth_user',
    JSON.stringify(
      data.user
    )
  );

  usuarioActivo.value =
    data.user;

  twoFactorChallengeToken.value =
    '';

  redirigirSegunRol(
    data.user.role
  );
};


/* =========================================================
   CAMBIO DE VISTAS
========================================================= */

const cambiarALogin = () => {

  vistaActiva.value =
    'login';
};


const cambiarAInicio = () => {

  vistaActiva.value =
    'inicio';
};


const cambiarARegistro = () => {

  vistaActiva.value =
    'registro';
};


/* =========================================================
   REDIRECCIÓN SEGÚN ROL
========================================================= */

const redirigirSegunRol = (role) => {

  const rol =
    String(
      role || ''
    )
      .trim()
      .toLowerCase();


  const rutas = {

    superadmin:
      'panel-master',

    master:
      'panel-master',

    admin:
      'panel-admin',

    profesor:
      'panel-profesor',

    alumno:
      'panel-alumno'
  };


  if (!rutas[rol]) {

    console.error(
      'Rol no reconocido:',
      rol
    );

    usuarioActivo.value =
      null;

    vistaActiva.value =
      'inicio';

    return;
  }


  vistaActiva.value =
    rutas[rol];
};


/* =========================================================
   RESTAURAR SESIÓN
========================================================= */

const restaurarSesion = async () => {

  const tokenGuardado =
    localStorage.getItem(
      'auth_token'
    );


  if (!tokenGuardado) {

    localStorage.removeItem(
      'auth_user'
    );

    usuarioActivo.value =
      null;

    vistaActiva.value =
      'inicio';

    return;
  }


  try {

    const { data } =
      await api.get(
        '/user'
      );


    const usuario =
      data?.user
      ||
      data?.data
      ||
      data;


    if (
      !usuario ||
      !usuario.id ||
      !usuario.role
    ) {

      throw new Error(
        'La API no devolvió un usuario válido.'
      );
    }


    usuarioActivo.value =
      usuario;


    localStorage.setItem(
      'auth_user',
      JSON.stringify(
        usuario
      )
    );


    redirigirSegunRol(
      usuario.role
    );

  } catch (error) {

    console.warn(
      'No se pudo restaurar la sesión:',
      error
    );


    localStorage.removeItem(
      'auth_token'
    );

    localStorage.removeItem(
      'auth_user'
    );


    usuarioActivo.value =
      null;


    vistaActiva.value =
      'inicio';
  }
};


/* =========================================================
   CONVOCATORIAS PÚBLICAS
========================================================= */

const convocatoriasPublicas =
  ref([]);

const cargandoConvocatoriasPublicas =
  ref(false);


const verConvocatoriaPublica =
async () => {

  vistaActiva.value =
    'convocatoria-publica';


  cargandoConvocatoriasPublicas.value =
    true;


  try {

    const { data } =
      await api.get(
        '/convocatorias-publicas'
      );


    convocatoriasPublicas.value =
      data?.convocatorias
      ||
      data?.data
      ||
      (
        Array.isArray(data)
          ? data
          : []
      );

  } catch (error) {

    console.error(
      'Error cargando convocatorias:',
      error
    );


    convocatoriasPublicas.value =
      [];

  } finally {

    cargandoConvocatoriasPublicas.value =
      false;
  }
};


/* =========================================================
   LOGIN
========================================================= */

const correoUsuario =
  ref('');

const passwordUsuario =
  ref('');

const cargandoLogin =
  ref(false);

const mensajeLogin =
  ref('');

const errorLogin =
  ref(false);


const manejarLogin = async () => {

  errorLogin.value =
    false;

  mensajeLogin.value =
    '';

  cargandoLogin.value =
    true;


  try {

    const { data } =
      await api.post(
        '/login',
        {
          email:
            correoUsuario.value
              .trim(),

          password:
            passwordUsuario.value
        }
      );


    /*
    |--------------------------------------------------------------------------
    | 2FA REQUERIDO
    |--------------------------------------------------------------------------
    */

    if (
      data?.two_factor_required ===
      true
    ) {

      if (
        !data?.challenge_token
      ) {

        throw new Error(
          'No se recibió el desafío 2FA.'
        );
      }


      twoFactorChallengeToken.value =
        data.challenge_token;


      vistaActiva.value =
        'two-factor-challenge';


      mensajeLogin.value =
        'Introduce el código de tu aplicación autenticadora.';


      return;
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN SIN 2FA
    |--------------------------------------------------------------------------
    */

    if (
      !data?.token ||
      !data?.user ||
      !data?.user?.role
    ) {

      throw new Error(
        'El servidor no devolvió la sesión correctamente.'
      );
    }


    localStorage.setItem(
      'auth_token',
      data.token
    );


    localStorage.setItem(
      'auth_user',
      JSON.stringify(
        data.user
      )
    );


    usuarioActivo.value =
      data.user;


    redirigirSegunRol(
      data.user.role
    );

  } catch (error) {

    console.error(
      'Error login:',
      error
    );


    localStorage.removeItem(
      'auth_token'
    );

    localStorage.removeItem(
      'auth_user'
    );


    usuarioActivo.value =
      null;


    errorLogin.value =
      true;


    if (
      error.response
    ) {

      mensajeLogin.value =
        error.response
          ?.data
          ?.message
        ||
        'Error al iniciar sesión.';

    } else if (
      error.request
    ) {

      mensajeLogin.value =
        'No se pudo conectar con el servidor.';

    } else {

      mensajeLogin.value =
        error.message
        ||
        'Error al iniciar sesión.';
    }

  } finally {

    cargandoLogin.value =
      false;
  }
};


/* =========================================================
   CERRAR SESIÓN
========================================================= */

const cerrarSesion = async () => {

  try {

    if (
      localStorage.getItem(
        'auth_token'
      )
    ) {

      await api.post(
        '/logout'
      );
    }

  } catch (error) {

    console.warn(
      'Error cerrando sesión:',
      error
    );
  }


  localStorage.removeItem(
    'auth_token'
  );

  localStorage.removeItem(
    'auth_user'
  );


  usuarioActivo.value =
    null;


  correoUsuario.value =
    '';

  passwordUsuario.value =
    '';

  mensajeLogin.value =
    '';

  errorLogin.value =
    false;

  twoFactorChallengeToken.value =
    '';


  vistaActiva.value =
    'inicio';
};


/* =========================================================
   REGISTRO
========================================================= */

const nombreRegistro =
  ref('');

const correoRegistro =
  ref('');

const passwordRegistro =
  ref('');

const passwordConfirmacion =
  ref('');

const cargandoRegistro =
  ref(false);

const mensajeRegistro =
  ref('');

const errorRegistro =
  ref(false);


const manejarRegistro = async () => {

  errorRegistro.value =
    false;

  mensajeRegistro.value =
    '';


  if (
    passwordRegistro.value !==
    passwordConfirmacion.value
  ) {

    errorRegistro.value =
      true;

    mensajeRegistro.value =
      'Las contraseñas no coinciden.';

    return;
  }


  cargandoRegistro.value =
    true;


  try {

    const { data } =
      await api.post(
        '/register',
        {

          name:
            nombreRegistro.value
              .trim(),

          email:
            correoRegistro.value
              .trim(),

          password:
            passwordRegistro.value,

          password_confirmation:
            passwordConfirmacion.value
        }
      );


    correoParaVerificar.value =
      correoRegistro.value
        .trim();


    mensajeRegistro.value =
      data?.message
      ||
      'Cuenta registrada correctamente.';


    vistaActiva.value =
      'verificacion';

  } catch (error) {

    errorRegistro.value =
      true;


    mensajeRegistro.value =
      error.response
        ?.data
        ?.message
      ||
      (
        error.request
          ?
          'No se pudo conectar con el servidor.'
          :
          'Error inesperado.'
      );

  } finally {

    cargandoRegistro.value =
      false;
  }
};


/* =========================================================
   VERIFICACIÓN DE CORREO
========================================================= */

const correoParaVerificar =
  ref('');

const codigoVerificacion =
  ref('');

const cargandoVerificacion =
  ref(false);

const mensajeVerificacion =
  ref('');

const errorVerificacion =
  ref(false);


const verificarCodigo = async () => {

  errorVerificacion.value =
    false;

  mensajeVerificacion.value =
    '';


  const codigoLimpio =
    codigoVerificacion.value
      .trim()
      .toUpperCase();


  if (!codigoLimpio) {

    errorVerificacion.value =
      true;

    mensajeVerificacion.value =
      'Ingresa el código.';

    return;
  }


  cargandoVerificacion.value =
    true;


  try {

    const { data } =
      await api.get(
        `/verify-email/${codigoLimpio}`
      );


    mensajeVerificacion.value =
      data?.message
      ||
      'Correo verificado correctamente.';


    setTimeout(
      () => {

        codigoVerificacion.value =
          '';

        vistaActiva.value =
          'login';

      },
      1500
    );

  } catch (error) {

    errorVerificacion.value =
      true;


    mensajeVerificacion.value =
      error.response
        ?.data
        ?.message
      ||
      'No se pudo verificar el código.';

  } finally {

    cargandoVerificacion.value =
      false;
  }
};


/* =========================================================
   REENVIAR CÓDIGO
========================================================= */

const reenviarCodigo = async () => {

  if (
    !correoParaVerificar.value
  ) {

    errorVerificacion.value =
      true;

    mensajeVerificacion.value =
      'No se encontró el correo a verificar.';

    return;
  }


  cargandoVerificacion.value =
    true;


  try {

    const { data } =
      await api.post(
        '/resend-token',
        {
          email:
            correoParaVerificar.value
        }
      );


    errorVerificacion.value =
      false;


    mensajeVerificacion.value =
      data?.message
      ||
      'Código reenviado correctamente.';

  } catch (error) {

    errorVerificacion.value =
      true;


    mensajeVerificacion.value =
      error.response
        ?.data
        ?.message
      ||
      'No se pudo reenviar el código.';

  } finally {

    cargandoVerificacion.value =
      false;
  }
};


/* =========================================================
   RECUPERACIÓN
========================================================= */

const correoRecuperacion =
  ref('');

const codigoRecuperacion =
  ref('');

const passwordNueva =
  ref('');

const passwordNuevaConfirmar =
  ref('');

const cargandoRecuperacion =
  ref(false);

const mensajeRecuperacion =
  ref('');

const errorRecuperacion =
  ref(false);

const pasoRecuperacion =
  ref(1);


const irARecuperar = () => {

  vistaActiva.value =
    'recuperar';

  pasoRecuperacion.value =
    1;

  mensajeRecuperacion.value =
    '';

  errorRecuperacion.value =
    false;

  correoRecuperacion.value =
    '';

  codigoRecuperacion.value =
    '';

  passwordNueva.value =
    '';

  passwordNuevaConfirmar.value =
    '';
};


const enviarCodigoRecuperacion =
async () => {

  errorRecuperacion.value =
    false;

  mensajeRecuperacion.value =
    '';


  if (
    !correoRecuperacion.value
      .trim()
  ) {

    errorRecuperacion.value =
      true;

    mensajeRecuperacion.value =
      'Ingresa tu correo institucional.';

    return;
  }


  cargandoRecuperacion.value =
    true;


  try {

    const { data } =
      await api.post(
        '/forgot-password',
        {
          email:
            correoRecuperacion.value
              .trim()
        }
      );


    mensajeRecuperacion.value =
      data?.message
      ||
      'Código enviado.';


    pasoRecuperacion.value =
      2;

  } catch (error) {

    errorRecuperacion.value =
      true;


    mensajeRecuperacion.value =
      error.response
        ?.data
        ?.message
      ||
      'Error al enviar el código.';

  } finally {

    cargandoRecuperacion.value =
      false;
  }
};


const restablecerContrasena =
async () => {

  errorRecuperacion.value =
    false;

  mensajeRecuperacion.value =
    '';


  if (
    passwordNueva.value !==
    passwordNuevaConfirmar.value
  ) {

    errorRecuperacion.value =
      true;

    mensajeRecuperacion.value =
      'Las contraseñas no coinciden.';

    return;
  }


  cargandoRecuperacion.value =
    true;


  try {

    const { data } =
      await api.post(
        '/reset-password',
        {

          email:
            correoRecuperacion.value
              .trim(),

          codigo:
            codigoRecuperacion.value
              .trim()
              .toUpperCase(),

          password:
            passwordNueva.value,

          password_confirmation:
            passwordNuevaConfirmar.value
        }
      );


    mensajeRecuperacion.value =
      data?.message
      ||
      'Contraseña actualizada.';


    setTimeout(
      () => {

        pasoRecuperacion.value =
          1;

        vistaActiva.value =
          'login';

      },
      1500
    );

  } catch (error) {

    errorRecuperacion.value =
      true;


    mensajeRecuperacion.value =
      error.response
        ?.data
        ?.message
      ||
      'No se pudo restablecer la contraseña.';

  } finally {

    cargandoRecuperacion.value =
      false;
  }
};


/* =========================================================
   FECHAS
========================================================= */

const formatearFecha = (fecha) => {

  if (!fecha) {
    return '';
  }

  const valor =
    new Date(fecha);

  if (
    Number.isNaN(
      valor.getTime()
    )
  ) {

    return fecha;
  }

  return valor.toLocaleDateString(
    'es-MX',
    {
      day:
        'numeric',

      month:
        'long',

      year:
        'numeric'
    }
  );
};


/* =========================================================
   CICLO DE VIDA
========================================================= */

onMounted(() => {

  cambiarFondoAutomatico();

  restaurarSesion();
});


onUnmounted(() => {

  if (
    intervaloFondo
  ) {

    clearInterval(
      intervaloFondo
    );
  }
});
</script>


<template>

  <div
    class="min-h-screen font-sans"
  >


    <!-- =====================================================
         PÁGINAS PÚBLICAS
    ====================================================== -->

    <div
      v-if="
        [
          'inicio',
          'login',
          'registro',
          'verificacion',
          'recuperar',
          'convocatoria-publica'
        ].includes(vistaActiva)
      "
      class="min-h-screen flex flex-col items-center justify-between p-4 relative overflow-hidden"
    >


      <!-- FONDO -->

      <div
        class="absolute inset-0 z-0 pointer-events-none"
      >

        <div
          v-for="
            (img, index)
            in fondos
          "
          :key="index"
          :style="{
            backgroundImage:
              `url(${img})`
          }"
          :class="[
            'absolute inset-0 bg-cover bg-center transition-opacity duration-1000',

            fondoActivo === index
              ? 'opacity-40'
              : 'opacity-0'
          ]"
        ></div>


        <div
          class="absolute inset-0 bg-[#1C1F26]/40"
        ></div>

      </div>


      <div></div>


      <!-- ===================================================
           INICIO
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'inicio'
        "
        class="w-full max-w-md relative z-20"
      >

        <div
          class="bg-white rounded-3xl p-8 shadow-2xl text-center space-y-6"
        >

          <img
            :src="logoUptex"
            alt="UPTex"
            class="w-64 mx-auto"
          />


          <h1
            class="text-xl font-black"
          >
            Sistema de Control
            <span
              class="text-[#00723F]"
            >
              de Becas
            </span>
          </h1>


          <p
            class="text-xs text-slate-500"
          >
            Universidad Politécnica
            de Texcoco
          </p>


          <button
            @click="
              verConvocatoriaPublica
            "
            class="w-full border-2 border-[#00723F] text-[#00723F] py-3 rounded-xl font-bold text-xs uppercase"
          >
            Ver Convocatoria Vigente
          </button>


          <button
            @click="
              cambiarALogin
            "
            class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold text-xs uppercase"
          >
            Acceso al Portal
          </button>

        </div>

      </div>


      <!-- ===================================================
           CONVOCATORIA
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'convocatoria-publica'
        "
        class="w-full max-w-lg relative z-20"
      >

        <div
          class="bg-white rounded-3xl p-8 shadow-2xl space-y-5"
        >

          <div
            class="flex justify-between"
          >

            <h2
              class="font-black uppercase"
            >
              Convocatoria Vigente
            </h2>


            <button
              @click="
                cambiarAInicio
              "
              class="text-xs text-[#00723F] font-bold"
            >
              Regresar
            </button>

          </div>


          <p
            v-if="
              cargandoConvocatoriasPublicas
            "
            class="text-center text-sm"
          >
            Cargando...
          </p>


          <p
            v-else-if="
              convocatoriasPublicas.length ===
              0
            "
            class="text-center text-sm"
          >
            No hay convocatoria activa.
          </p>


          <div
            v-else
            class="space-y-3"
          >

            <article
              v-for="
                convocatoria
                in convocatoriasPublicas
              "
              :key="
                convocatoria.id
              "
              class="border rounded-xl p-4"
            >

              <h3
                class="font-bold"
              >
                {{
                  convocatoria.titulo
                  ||
                  convocatoria.nombre
                }}
              </h3>


              <p
                class="text-xs text-slate-500"
              >
                {{
                  convocatoria.descripcion
                }}
              </p>


              <p
                class="text-xs mt-2"
              >
                Cierre:
                {{
                  formatearFecha(
                    convocatoria.fecha_cierre
                  )
                }}
              </p>

            </article>

          </div>


          <button
            @click="
              cambiarALogin
            "
            class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold text-xs uppercase"
          >
            Iniciar sesión
          </button>

        </div>

      </div>


      <!-- ===================================================
           LOGIN
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'login'
        "
        class="w-full max-w-md relative z-20"
      >

        <div
          class="bg-white rounded-3xl p-8 shadow-2xl space-y-5"
        >

          <img
            :src="logoUptex"
            alt="UPTex"
            class="w-40 mx-auto"
          />


          <div
            class="flex justify-between"
          >

            <div>

              <h2
                class="font-black uppercase"
              >
                Iniciar Sesión
              </h2>

              <p
                class="text-xs text-slate-500"
              >
                Credenciales institucionales
              </p>

            </div>


            <button
              @click="
                cambiarAInicio
              "
              class="text-xs text-[#00723F]"
            >
              Regresar
            </button>

          </div>


          <form
            @submit.prevent="
              manejarLogin
            "
            class="space-y-4"
          >

            <input
              v-model="
                correoUsuario
              "
              type="email"
              required
              placeholder="Correo institucional"
              class="w-full border rounded-xl px-4 py-3 text-sm"
            />


            <input
              v-model="
                passwordUsuario
              "
              type="password"
              required
              placeholder="Contraseña"
              class="w-full border rounded-xl px-4 py-3 text-sm"
            />


            <button
              type="button"
              @click="
                irARecuperar
              "
              class="text-xs text-slate-500 hover:underline"
            >
              ¿Olvidaste tu contraseña?
            </button>


            <p
              v-if="
                mensajeLogin
              "
              :class="
                errorLogin
                  ? 'text-red-700'
                  : 'text-green-700'
              "
              class="text-xs text-center font-bold"
            >
              {{ mensajeLogin }}
            </p>


            <button
              type="submit"
              :disabled="
                cargandoLogin
              "
              class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold text-xs uppercase disabled:opacity-50"
            >

              {{
                cargandoLogin
                  ? 'Validando...'
                  : 'Validar Credenciales'
              }}

            </button>


            <p
              class="text-center text-xs"
            >

              ¿No tienes cuenta?

              <button
                type="button"
                @click="
                  cambiarARegistro
                "
                class="text-[#7A1C33] font-bold"
              >
                Regístrate
              </button>

            </p>

          </form>

        </div>

      </div>


      <!-- ===================================================
           REGISTRO
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'registro'
        "
        class="w-full max-w-md relative z-20"
      >

        <form
          @submit.prevent="
            manejarRegistro
          "
          class="bg-white rounded-3xl p-8 shadow-2xl space-y-4"
        >

          <h2
            class="font-black uppercase"
          >
            Crear Cuenta
          </h2>


          <input
            v-model="
              nombreRegistro
            "
            required
            placeholder="Nombre completo"
            class="w-full border rounded-xl px-4 py-3"
          />


          <input
            v-model="
              correoRegistro
            "
            type="email"
            required
            placeholder="Correo institucional"
            class="w-full border rounded-xl px-4 py-3"
          />


          <input
            v-model="
              passwordRegistro
            "
            type="password"
            required
            placeholder="Contraseña"
            class="w-full border rounded-xl px-4 py-3"
          />


          <input
            v-model="
              passwordConfirmacion
            "
            type="password"
            required
            placeholder="Confirmar contraseña"
            class="w-full border rounded-xl px-4 py-3"
          />


          <p
            v-if="
              mensajeRegistro
            "
            :class="
              errorRegistro
                ? 'text-red-700'
                : 'text-green-700'
            "
            class="text-xs text-center"
          >
            {{ mensajeRegistro }}
          </p>


          <button
            class="w-full bg-[#00723F] text-white py-3 rounded-xl font-bold"
          >
            {{
              cargandoRegistro
                ? 'Registrando...'
                : 'Crear Cuenta'
            }}
          </button>


          <button
            type="button"
            @click="
              cambiarALogin
            "
            class="w-full text-xs text-slate-500"
          >
            Regresar
          </button>

        </form>

      </div>


      <!-- ===================================================
           VERIFICACIÓN
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'verificacion'
        "
        class="w-full max-w-md relative z-20"
      >

        <div
          class="bg-white rounded-3xl p-8 shadow-2xl space-y-4 text-center"
        >

          <h2
            class="font-black"
          >
            Verifica tu correo
          </h2>


          <p
            class="text-xs"
          >
            Código enviado a
            <strong>
              {{ correoParaVerificar }}
            </strong>
          </p>


          <input
            v-model="
              codigoVerificacion
            "
            maxlength="6"
            placeholder="ABC123"
            class="w-full text-center border rounded-xl py-3"
          />


          <p
            v-if="
              mensajeVerificacion
            "
            :class="
              errorVerificacion
                ? 'text-red-700'
                : 'text-green-700'
            "
          >
            {{ mensajeVerificacion }}
          </p>


          <button
            @click="
              verificarCodigo
            "
            class="w-full bg-[#00723F] text-white py-3 rounded-xl"
          >
            Verificar código
          </button>


          <button
            @click="
              reenviarCodigo
            "
            class="text-xs text-slate-500"
          >
            Reenviar código
          </button>

        </div>

      </div>


      <!-- ===================================================
           RECUPERAR CONTRASEÑA
      ==================================================== -->

      <div
        v-if="
          vistaActiva ===
          'recuperar'
        "
        class="w-full max-w-md relative z-20"
      >

        <div
          class="bg-white rounded-3xl p-8 shadow-2xl space-y-4"
        >

          <h2
            class="font-black"
          >
            Recuperar Contraseña
          </h2>


          <template
            v-if="
              pasoRecuperacion ===
              1
            "
          >

            <input
              v-model="
                correoRecuperacion
              "
              type="email"
              placeholder="Correo institucional"
              class="w-full border rounded-xl px-4 py-3"
            />


            <button
              @click="
                enviarCodigoRecuperacion
              "
              class="w-full bg-[#00723F] text-white py-3 rounded-xl"
            >
              Enviar código
            </button>

          </template>


          <template
            v-else
          >

            <input
              v-model="
                codigoRecuperacion
              "
              maxlength="6"
              placeholder="Código"
              class="w-full border rounded-xl px-4 py-3"
            />


            <input
              v-model="
                passwordNueva
              "
              type="password"
              placeholder="Nueva contraseña"
              class="w-full border rounded-xl px-4 py-3"
            />


            <input
              v-model="
                passwordNuevaConfirmar
              "
              type="password"
              placeholder="Confirmar contraseña"
              class="w-full border rounded-xl px-4 py-3"
            />


            <button
              @click="
                restablecerContrasena
              "
              class="w-full bg-[#00723F] text-white py-3 rounded-xl"
            >
              Restablecer contraseña
            </button>

          </template>


          <p
            v-if="
              mensajeRecuperacion
            "
            :class="
              errorRecuperacion
                ? 'text-red-700'
                : 'text-green-700'
            "
            class="text-xs"
          >
            {{ mensajeRecuperacion }}
          </p>


          <button
            @click="
              cambiarALogin
            "
            class="w-full text-xs text-slate-500"
          >
            Regresar
          </button>

        </div>

      </div>


      <footer
        class="relative z-20 text-white text-xs"
      >
        © 2026 Universidad Politécnica de Texcoco
      </footer>

    </div>


    <!-- =====================================================
         CHALLENGE 2FA
    ====================================================== -->

    <TwoFactorChallenge
      v-if="
        vistaActiva ===
        'two-factor-challenge'
      "
      :challenge-token="
        twoFactorChallengeToken
      "
      @completado="
        completarTwoFactor
      "
      @cancelar="
        cancelarTwoFactorChallenge
      "
    />


    <!-- =====================================================
         CONFIGURACIÓN 2FA
    ====================================================== -->

    <TwoFactorSetup
      v-if="
        vistaActiva ===
        'two-factor-setup'
      "
      @regresar="
        redirigirSegunRol(
          usuarioActivo?.role
        )
      "
    />


    <!-- =====================================================
         BOTÓN 2FA
    ====================================================== -->

    <button
      v-if="
        usuarioActivo &&
        [
          'panel-master',
          'panel-admin',
          'panel-profesor',
          'panel-alumno'
        ].includes(
          vistaActiva
        )
      "
      type="button"
      @click="
        abrirTwoFactorSetup
      "
      class="fixed right-5 bottom-5 z-50 bg-[#00723F] text-white px-5 py-3 rounded-xl shadow-xl text-xs font-black uppercase"
    >
      Seguridad 2FA
    </button>


    <!-- =====================================================
         DASHBOARDS
    ====================================================== -->

    <SuperAdminDashboard
      v-if="
        vistaActiva ===
        'panel-master'
      "
      :usuario="
        usuarioActivo
      "
      @cerrar-sesion="
        cerrarSesion
      "
    />


    <JefeDashboard
      v-if="
        vistaActiva ===
        'panel-admin'
      "
      :usuario="
        usuarioActivo
      "
      @cerrar-sesion="
        cerrarSesion
      "
    />


    <TutorDashboard
      v-if="
        vistaActiva ===
        'panel-profesor'
      "
      :usuario="
        usuarioActivo
      "
      @cerrar-sesion="
        cerrarSesion
      "
    />


    <AlumnoDashboard
      v-if="
        vistaActiva ===
        'panel-alumno'
      "
      :usuario="
        usuarioActivo
      "
      @cerrar-sesion="
        cerrarSesion
      "
    />

  </div>

</template>