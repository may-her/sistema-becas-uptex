<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from './api/axios';

import logoUptex from './assets/logo-uptex.png';
import campus1 from './assets/universidad1.jpg';
import campus2 from './assets/universidad2.jpg';
import campus3 from './assets/universidad3.jpg';

import SuperAdminDashboard from './components/SuperAdminDashboard.vue';
import JefeDashboard from './components/JefeDashboard.vue';
import TutorDashboard from './components/TutorDashboard.vue';
import AlumnoDashboard from './components/AlumnoDashboard.vue';


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

  intervaloFondo = setInterval(() => {

    fondoActivo.value =
      (
        fondoActivo.value + 1
      ) % fondos.value.length;

  }, 4000);
};


/* =========================================================
   VISTA PRINCIPAL
========================================================= */

const vistaActiva = ref('inicio');

const usuarioActivo = ref(null);


const cambiarALogin = () => {

  vistaActiva.value = 'login';
};


const cambiarAInicio = () => {

  vistaActiva.value = 'inicio';
};


const cambiarARegistro = () => {

  vistaActiva.value = 'registro';
};


/* =========================================================
   REDIRECCIÓN SEGÚN ROL
========================================================= */

const redirigirSegunRol = (role) => {

  const rol =
    String(role || '')
      .trim()
      .toLowerCase();


  const rutas = {

    /*
    |--------------------------------------------------------------------------
    | Rol real actual de tu BD
    |--------------------------------------------------------------------------
    */

    superadmin:
      'panel-master',

    /*
    |--------------------------------------------------------------------------
    | Lo dejamos por compatibilidad si existiera algún usuario antiguo.
    |--------------------------------------------------------------------------
    */

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

    usuarioActivo.value = null;

    vistaActiva.value = 'inicio';

    return;
  }


  vistaActiva.value =
    rutas[rol];
};


/* =========================================================
   RESTAURAR SESIÓN
========================================================= */

/*
|--------------------------------------------------------------------------
| IMPORTANTE
|--------------------------------------------------------------------------
|
| Antes:
|
| Si había auth_token + auth_user en localStorage,
| App.vue confiaba directamente en esos datos y abría el dashboard.
|
| Ahora:
|
| 1. Busca el token.
| 2. Si no existe, va a inicio.
| 3. Si existe, Laravel valida el token con GET /api/user.
| 4. Sólo si Laravel confirma el usuario, abre el dashboard.
|
*/

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


    /*
    |--------------------------------------------------------------------------
    | Dependiendo de cómo responda tu endpoint:
    |
    | { user: {...} }
    |
    | { data: {...} }
    |
    | o directamente {...}
    |--------------------------------------------------------------------------
    */

    const usuario =
      data?.user
      ||
      data?.data
      ||
      data;


    if (
      !usuario
      ||
      !usuario.id
      ||
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


    /*
    |--------------------------------------------------------------------------
    | Si el token ya venció o es inválido,
    | eliminamos cualquier sesión vieja.
    |--------------------------------------------------------------------------
    */

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


const verConvocatoriaPublica = async () => {

  vistaActiva.value =
    'convocatoria-publica';


  cargandoConvocatoriasPublicas.value =
    true;


  try {

    const { data } =
      await api.get(
        '/convocatorias-publicas'
      );


    /*
    |--------------------------------------------------------------------------
    | Tu código original esperaba data.convocatorias.
    | Dejamos compatibilidad con otras respuestas.
    |--------------------------------------------------------------------------
    */

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
      'Error cargando convocatorias públicas:',
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
    | Comprobamos que Laravel realmente devolvió token + user.
    |--------------------------------------------------------------------------
    */

    if (
      !data?.token
      ||
      !data?.user
      ||
      !data?.user?.role
    ) {

      throw new Error(
        'El servidor no devolvió la sesión correctamente.'
      );
    }


    /*
    |--------------------------------------------------------------------------
    | Guardamos token primero.
    |--------------------------------------------------------------------------
    */

    localStorage.setItem(
      'auth_token',
      data.token
    );


    /*
    |--------------------------------------------------------------------------
    | Guardamos usuario sólo después de validar respuesta.
    |--------------------------------------------------------------------------
    */

    localStorage.setItem(
      'auth_user',
      JSON.stringify(
        data.user
      )
    );


    usuarioActivo.value =
      data.user;


    /*
    |--------------------------------------------------------------------------
    | Redirige según el rol REAL.
    |--------------------------------------------------------------------------
    */

    redirigirSegunRol(
      data.user.role
    );

  } catch (error) {

    console.error(
      'Error login:',
      error
    );


    /*
    |--------------------------------------------------------------------------
    | Si falló el login, no dejamos basura de una sesión anterior.
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Si existe token, Laravel lo revoca.
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Aunque falle el backend, limpiamos la sesión local.
    |--------------------------------------------------------------------------
    */

    console.warn(
      'Error cerrando sesión en backend:',
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


    /*
    |--------------------------------------------------------------------------
    | No iniciamos sesión automáticamente.
    | Primero verifica correo.
    |--------------------------------------------------------------------------
    */

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
      2000
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
   RECUPERAR CONTRASEÑA
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

        correoRecuperacion.value =
          '';

        codigoRecuperacion.value =
          '';

        passwordNueva.value =
          '';

        passwordNuevaConfirmar.value =
          '';

        vistaActiva.value =
          'login';

      },
      2000
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
   FORMATEAR FECHA
========================================================= */

const formatearFecha =
(fecha) => {

  if (!fecha) {
    return '';
  }


  const fechaObjeto =
    new Date(fecha);


  if (
    Number.isNaN(
      fechaObjeto.getTime()
    )
  ) {

    return fecha;
  }


  return fechaObjeto
    .toLocaleDateString(
      'es-MX',
      {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      }
    );
};


/* =========================================================
   CICLO DE VIDA
========================================================= */

onMounted(() => {

  /*
  |--------------------------------------------------------------------------
  | Iniciamos únicamente la animación de fondo.
  |--------------------------------------------------------------------------
  */

  cambiarFondoAutomatico();


  /*
  |--------------------------------------------------------------------------
  | Después comprobamos la sesión real con Laravel.
  |
  | Esto elimina el problema de entrar automáticamente al SuperAdmin
  | por un auth_user antiguo guardado en el navegador.
  |--------------------------------------------------------------------------
  */

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
  <div class="min-h-screen font-sans">

    <div
      v-if="['inicio', 'login', 'registro', 'verificacion', 'recuperar', 'convocatoria-publica'].includes(vistaActiva)"
      class="min-h-screen flex flex-col items-center justify-between p-4 relative overflow-hidden select-none"
    >

      <div class="absolute inset-0 z-0 pointer-events-none">

        <div
          v-for="(img, index) in fondos"
          :key="index"
          :style="{
            backgroundImage:
              `url(${img})`
          }"
          :class="[
            'absolute inset-0 bg-cover bg-center transition-opacity duration-1000 ease-in-out',

            fondoActivo === index
              ? 'opacity-40 scale-100'
              : 'opacity-0 scale-105'
          ]"
        ></div>

        <div class="absolute inset-0 bg-[#1C1F26]/30"></div>

      </div>


      <div></div>


      <!-- ===================================================
           INICIO
      ==================================================== -->

      <div
        v-if="vistaActiva === 'inicio'"
        class="w-full max-w-md relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-6 text-center flex flex-col items-center">

          <div class="flex flex-col items-center w-full">

            <div class="max-w-[260px] w-full flex justify-center items-center overflow-hidden">

              <img
                :src="logoUptex"
                alt="UPTex"
                class="w-full h-auto object-contain block"
              />

            </div>


            <div class="text-[11px] font-bold text-[#00723F] uppercase tracking-[0.25em] mt-4 text-center">
              Universidad Politécnica de Texcoco
            </div>


            <div class="w-16 h-[2px] bg-[#7A1C33] mt-2"></div>

          </div>


          <div class="space-y-2">

            <h2 class="text-xl font-black text-[#1C1F26] tracking-tight uppercase">

              Sistema de Control

              <span class="text-[#00723F]">
                de Becas
              </span>

            </h2>


            <p class="text-xs text-slate-500 max-w-xs mx-auto font-medium leading-relaxed">

              Portal digital para el registro,
              validación y seguimiento de becas
              de descuento en colegiaturas.

            </p>

          </div>


          <div class="w-full pt-2 space-y-2.5">

            <!-- ESTE BOTÓN QUEDA EXACTAMENTE IGUAL -->

            <button
              @click="verConvocatoriaPublica"
              type="button"
              class="bg-white border-2 border-[#00723F] text-[#00723F] hover:bg-[#00723F] hover:text-white font-bold text-xs uppercase tracking-wider px-10 py-3.5 rounded-xl transition-all w-full"
            >
              Ver Convocatoria Vigente
            </button>


            <!-- ESTE BOTÓN QUEDA EXACTAMENTE IGUAL -->

            <button
              @click="cambiarALogin"
              type="button"
              class="bg-[#00723F] hover:bg-[#005C32] text-white font-bold text-xs uppercase tracking-wider px-10 py-3.5 rounded-xl shadow-md transition-all w-full"
            >
              Acceso al Portal
            </button>

          </div>

        </div>

      </div>


      <!-- ===================================================
           CONVOCATORIA PÚBLICA
      ==================================================== -->

      <div
        v-if="vistaActiva === 'convocatoria-publica'"
        class="w-full max-w-lg relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-5">

          <div class="flex justify-between items-start">

            <h3 class="text-base font-black text-[#1C1F26] uppercase tracking-tight">
              Convocatoria Vigente
            </h3>


            <button
              @click="cambiarAInicio"
              class="text-xs font-bold text-slate-500 hover:text-[#00723F] hover:underline"
            >
              Regresar
            </button>

          </div>


          <div
            v-if="cargandoConvocatoriasPublicas"
            class="text-center py-10 text-xs text-slate-400 font-semibold"
          >
            Cargando...
          </div>


          <div
            v-else-if="
              convocatoriasPublicas.length ===
              0
            "
            class="text-center py-10 space-y-2"
          >

            <p class="text-sm font-bold text-slate-700">
              No hay convocatoria activa en este momento.
            </p>


            <p class="text-xs text-slate-400">
              Las convocatorias se publican una vez por cuatrimestre.
              Vuelve a revisar más adelante.
            </p>

          </div>


          <div
            v-else
            class="space-y-4 max-h-96 overflow-y-auto"
          >

            <div
              v-for="c in convocatoriasPublicas"
              :key="c.id"
              class="border border-slate-200 rounded-2xl p-4 space-y-2"
            >

              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#00723F]/10 text-[#00723F]">

                {{
                  c.carrera?.nombre ||
                  'Todas las carreras'
                }}

              </span>


              <h4 class="text-sm font-black text-[#1C1F26]">

                {{
                  c.titulo ||
                  c.nombre
                }}

              </h4>


              <p class="text-xs text-slate-500">

                {{ c.descripcion }}

              </p>


              <p class="text-[11px] text-slate-400 font-semibold">

                Cierra el

                {{
                  formatearFecha(
                    c.fecha_cierre
                  )
                }}

              </p>


              <a
                v-if="
                  c.pdf_url ||
                  c.archivo_url
                "
                :href="
                  c.pdf_url ||
                  c.archivo_url
                "
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block text-[11px] font-bold text-[#00723F] hover:underline"
              >
                Ver documento oficial (PDF) →
              </a>

            </div>

          </div>


          <button
            @click="cambiarALogin"
            class="w-full bg-[#00723F] hover:bg-[#005C32] text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider"
          >
            Iniciar sesión para solicitar
          </button>

        </div>

      </div>


      <!-- ===================================================
           LOGIN
      ==================================================== -->

      <div
        v-if="vistaActiva === 'login'"
        class="w-full max-w-md relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-6">

          <div class="flex flex-col items-center pb-2 border-b border-slate-100">

            <div class="max-w-[160px] w-full overflow-hidden">

              <img
                :src="logoUptex"
                alt="UPTex"
                class="w-full h-auto object-contain block"
              />

            </div>

          </div>


          <div class="flex justify-between items-start pt-1">

            <div class="space-y-0.5">

              <h3 class="text-base font-black text-[#1C1F26] uppercase tracking-tight">
                Iniciar Sesión
              </h3>


              <p class="text-[11px] text-slate-500 font-semibold">
                Ingresa tus credenciales institucionales.
              </p>

            </div>


            <button
              @click="cambiarAInicio"
              class="text-xs font-bold text-slate-500 hover:text-[#00723F] hover:underline"
            >
              Regresar
            </button>

          </div>


          <form
            @submit.prevent="manejarLogin"
            class="space-y-4"
          >

            <div class="space-y-1">

              <label class="text-[10px] font-black text-slate-700 uppercase tracking-wider block">
                Usuario Institucional
              </label>


              <input
                v-model="correoUsuario"
                type="email"
                required
                placeholder="correo@alumno.uptex.edu.mx"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F] text-[#1C1F26] font-medium"
              />

            </div>


            <div class="space-y-1">

              <label class="text-[10px] font-black text-slate-700 uppercase tracking-wider block">
                Contraseña
              </label>


              <input
                v-model="passwordUsuario"
                type="password"
                required
                placeholder="••••••••"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F] text-[#1C1F26] font-medium"
              />

            </div>


            <p class="text-right">

              <span
                @click="irARecuperar"
                class="text-[11px] text-slate-500 hover:text-[#00723F] hover:underline cursor-pointer font-semibold"
              >
                ¿Olvidaste tu contraseña?
              </span>

            </p>


            <p
              v-if="mensajeLogin"
              :class="
                errorLogin
                  ? 'text-[#7A1C33]'
                  : 'text-[#0F766E]'
              "
              class="text-[11px] font-semibold text-center"
            >
              {{ mensajeLogin }}
            </p>


            <button
              type="submit"
              :disabled="cargandoLogin"
              class="w-full bg-[#00723F] hover:bg-[#005C32] text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider shadow-md disabled:opacity-50"
            >

              {{
                cargandoLogin
                  ? 'Validando...'
                  : 'Validar Credenciales'
              }}

            </button>


            <div class="text-center pt-2">

              <p class="text-[11px] text-slate-500 font-medium">

                ¿No tienes una cuenta académica?

                <span
                  @click="cambiarARegistro"
                  class="text-[#7A1C33] font-bold hover:underline cursor-pointer"
                >
                  Regístrate aquí
                </span>

              </p>

            </div>

          </form>

        </div>

      </div>


      <!-- ===================================================
           REGISTRO
      ==================================================== -->

      <div
        v-if="vistaActiva === 'registro'"
        class="w-full max-w-md relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-6">

          <div class="flex flex-col items-center pb-2 border-b border-slate-100">

            <div class="max-w-[160px] w-full overflow-hidden">

              <img
                :src="logoUptex"
                alt="UPTex"
                class="w-full h-auto object-contain block"
              />

            </div>

          </div>


          <div class="flex justify-between items-start pt-1">

            <div class="space-y-0.5">

              <h3 class="text-base font-black text-[#1C1F26] uppercase tracking-tight">
                Crear Cuenta
              </h3>

              <p class="text-[11px] text-slate-500 font-semibold">
                Regístrate con tu correo institucional.
              </p>

            </div>


            <button
              @click="cambiarALogin"
              class="text-xs font-bold text-slate-500 hover:text-[#00723F] hover:underline"
            >
              Regresar
            </button>

          </div>


          <form
            @submit.prevent="manejarRegistro"
            class="space-y-4"
          >

            <input
              v-model="nombreRegistro"
              type="text"
              required
              placeholder="Nombre completo"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F]"
            />


            <input
              v-model="correoRegistro"
              type="email"
              required
              placeholder="correo@alumno.uptex.edu.mx"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F]"
            />


            <input
              v-model="passwordRegistro"
              type="password"
              required
              placeholder="Contraseña"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F]"
            />


            <input
              v-model="passwordConfirmacion"
              type="password"
              required
              placeholder="Confirmar contraseña"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-[#00723F]"
            />


            <p
              v-if="mensajeRegistro"
              :class="
                errorRegistro
                  ? 'text-[#7A1C33]'
                  : 'text-[#0F766E]'
              "
              class="text-[11px] font-semibold text-center"
            >
              {{ mensajeRegistro }}
            </p>


            <button
              type="submit"
              :disabled="cargandoRegistro"
              class="w-full bg-[#00723F] hover:bg-[#005C32] text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider shadow-md disabled:opacity-50"
            >

              {{
                cargandoRegistro
                  ? 'Registrando...'
                  : 'Crear Cuenta'
              }}

            </button>

          </form>

        </div>

      </div>


      <!-- ===================================================
           VERIFICACIÓN
      ==================================================== -->

      <div
        v-if="vistaActiva === 'verificacion'"
        class="w-full max-w-md relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-4 text-center">

          <div class="max-w-[160px] w-full mx-auto overflow-hidden">

            <img
              :src="logoUptex"
              alt="UPTex"
              class="w-full h-auto object-contain block"
            />

          </div>


          <h3 class="text-base font-black text-[#1C1F26] uppercase tracking-tight">
            Verifica tu correo
          </h3>


          <p class="text-xs text-slate-500">

            Código enviado a

            <strong>
              {{ correoParaVerificar }}
            </strong>

          </p>


          <input
            v-model="codigoVerificacion"
            type="text"
            maxlength="6"
            placeholder="ABC123"
            class="w-full text-center tracking-[0.3em] font-mono uppercase bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#00723F]"
          />


          <p
            v-if="mensajeVerificacion"
            :class="
              errorVerificacion
                ? 'text-[#7A1C33]'
                : 'text-[#0F766E]'
            "
            class="text-xs font-semibold"
          >
            {{ mensajeVerificacion }}
          </p>


          <button
            @click="verificarCodigo"
            :disabled="cargandoVerificacion"
            class="w-full bg-[#00723F] hover:bg-[#005C32] text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50"
          >

            {{
              cargandoVerificacion
                ? 'Verificando...'
                : 'Verificar código'
            }}

          </button>


          <button
            @click="reenviarCodigo"
            class="text-[11px] text-slate-500 hover:text-[#00723F] hover:underline font-semibold"
          >
            ¿No recibiste el código? Reenviar
          </button>

        </div>

      </div>


      <!-- ===================================================
           RECUPERAR CONTRASEÑA
      ==================================================== -->

      <div
        v-if="vistaActiva === 'recuperar'"
        class="w-full max-w-md relative z-20 flex flex-col items-center"
      >

        <div class="w-full bg-white/97 backdrop-blur-md rounded-3xl p-8 border border-slate-200/60 shadow-2xl space-y-4">

          <div class="flex justify-between items-start">

            <h3 class="text-base font-black text-[#1C1F26] uppercase tracking-tight">
              Recuperar Contraseña
            </h3>


            <button
              @click="cambiarALogin"
              class="text-xs font-bold text-slate-500 hover:underline"
            >
              Regresar
            </button>

          </div>


          <div
            v-if="
              pasoRecuperacion ===
              1
            "
            class="space-y-4"
          >

            <p class="text-xs text-slate-500">
              Ingresa tu correo institucional y te enviaremos un código.
            </p>


            <input
              v-model="correoRecuperacion"
              type="email"
              required
              placeholder="Correo institucional"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs"
            />


            <p
              v-if="mensajeRecuperacion"
              :class="
                errorRecuperacion
                  ? 'text-[#7A1C33]'
                  : 'text-[#0F766E]'
              "
              class="text-[11px] font-semibold text-center"
            >
              {{ mensajeRecuperacion }}
            </p>


            <button
              @click="enviarCodigoRecuperacion"
              :disabled="cargandoRecuperacion"
              class="w-full bg-[#00723F] text-white font-bold py-3 rounded-xl text-xs uppercase disabled:opacity-50"
            >

              {{
                cargandoRecuperacion
                  ? 'Enviando...'
                  : 'Enviar código'
              }}

            </button>

          </div>


          <div
            v-if="
              pasoRecuperacion ===
              2
            "
            class="space-y-4"
          >

            <input
              v-model="codigoRecuperacion"
              type="text"
              maxlength="6"
              placeholder="Código (ABC123)"
              class="w-full text-center tracking-[0.3em] font-mono uppercase bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm"
            />


            <input
              v-model="passwordNueva"
              type="password"
              required
              placeholder="Nueva contraseña"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs"
            />


            <input
              v-model="passwordNuevaConfirmar"
              type="password"
              required
              placeholder="Confirmar nueva contraseña"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs"
            />


            <p
              v-if="mensajeRecuperacion"
              :class="
                errorRecuperacion
                  ? 'text-[#7A1C33]'
                  : 'text-[#0F766E]'
              "
              class="text-[11px] font-semibold text-center"
            >
              {{ mensajeRecuperacion }}
            </p>


            <button
              @click="restablecerContrasena"
              :disabled="cargandoRecuperacion"
              class="w-full bg-[#00723F] text-white font-bold py-3 rounded-xl text-xs uppercase disabled:opacity-50"
            >

              {{
                cargandoRecuperacion
                  ? 'Guardando...'
                  : 'Restablecer contraseña'
              }}

            </button>

          </div>

        </div>

      </div>


      <!-- FOOTER -->

      <footer class="w-full text-center relative z-20 pt-6">

        <p class="text-[10px] font-bold text-white/95 tracking-wide drop-shadow-[0_1px_3px_rgba(0,0,0,0.75)]">

          &copy; 2026 Universidad Politécnica de Texcoco.
          Todos los derechos reservados.

        </p>

      </footer>

    </div>


    <!-- =====================================================
         DASHBOARDS
    ====================================================== -->

    <SuperAdminDashboard
      v-if="
        vistaActiva ===
        'panel-master'
      "
      :usuario="usuarioActivo"
      @cerrar-sesion="cerrarSesion"
    />


    <JefeDashboard
      v-if="
        vistaActiva ===
        'panel-admin'
      "
      :usuario="usuarioActivo"
      @cerrar-sesion="cerrarSesion"
    />


    <TutorDashboard
      v-if="
        vistaActiva ===
        'panel-profesor'
      "
      :usuario="usuarioActivo"
      @cerrar-sesion="cerrarSesion"
    />


    <AlumnoDashboard
      v-if="
        vistaActiva ===
        'panel-alumno'
      "
      :usuario="usuarioActivo"
      @cerrar-sesion="cerrarSesion"
    />

  </div>
</template>