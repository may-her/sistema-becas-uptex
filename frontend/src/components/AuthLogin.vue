<script setup>
import { ref, computed } from 'vue';
import { 
  Lock, Mail, ShieldCheck, UserPlus, ArrowLeft, 
  KeyRound, FileText, UploadCloud, Eye, EyeOff 
} from 'lucide-vue-next';

const emit = defineEmits(['onLoginExitoso']);

// --- CONTROL DE VISTAS ---
const vista = ref('login'); // 'login' | 'registro' | 'otp'
const cargando = ref(false);
const verPass = ref(false);

// --- MODELOS ---
const loginForm = ref({ correo: '', pass: '' });
const registroForm = ref({ correo: '', pass: '', confirma: '' });
const otpInput = ref('');
const correoTemporal = ref(''); // Para recordar a quién enviamos el código

// --- GUÍA DE POSTULACIÓN (DINÁMICA) ---
const guiaDisponible = ref(true); // Esto vendrá de tu BD después

// --- NAVEGACIÓN ---
const cambiarVista = (nueva) => {
  vista.value = nueva;
  otpInput.value = '';
};

// --- LOGICA DE REGISTRO (PASO 1) ---
const solicitarRegistro = async () => {
  if (registroForm.value.pass !== registroForm.value.confirma) {
    alert("❌ Las contraseñas no coinciden.");
    return;
  }
  cargando.value = true;
  try {
    // Simulación de envío a XAMPP
    const res = await fetch('http://localhost/api/registro_paso1.php', {
      method: 'POST',
      body: JSON.stringify({ correo: registroForm.value.correo })
    });
    const data = await res.json();
    if (data.success) {
      correoTemporal.value = registroForm.value.correo;
      cambiarVista('otp');
      alert("📧 Código enviado a tu correo institucional.");
    }
  } catch (e) {
    alert("💥 Error de conexión con XAMPP.");
  } finally { cargando.value = false; }
};

// --- VALIDAR OTP (PASO 2) ---
const validarCodigo = async () => {
  cargando.value = true;
  try {
    const res = await fetch('http://localhost/api/verificar_otp.php', {
      method: 'POST',
      body: JSON.stringify({ 
        correo: correoTemporal.value, 
        codigo: otpInput.value,
        pass: registroForm.value.pass 
      })
    });
    const data = await res.json();
    if (data.success) {
      alert("✅ Cuenta creada. Ya puedes iniciar sesión.");
      cambiarVista('login');
    }
  } catch (e) { alert("💥 Error al validar."); }
  finally { cargando.value = false; }
};
</script>

<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col items-center justify-center p-6 font-sans">
    
    <div class="w-full max-w-md text-center mb-8">
      <div class="text-4xl font-black mb-2 tracking-tighter select-none">
        <span class="text-[#007a54]">UP</span><span class="text-[#8a1c1c]">Tex</span>
      </div>
      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Universidad Politécnica de Texcoco</p>
      <div class="w-12 h-1 bg-[#007a54] mx-auto mt-2 rounded-full"></div>
    </div>

    <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden p-8">
      
      <div v-if="vista === 'login'" class="space-y-6">
        <div class="text-left">
          <h3 class="text-lg font-black text-slate-800">SISTEMA DE BECAS</h3>
          <p class="text-xs text-slate-400">Accede con tu cuenta institucional.</p>
        </div>

        <form @submit.prevent="/* Función Login */" class="space-y-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Correo Electrónico</label>
            <div class="relative">
              <Mail class="absolute left-3 top-2.5 text-slate-300" :size="16" />
              <input v-model="loginForm.correo" type="email" class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-[#007a54] outline-none transition-all" placeholder="usuario@alumno.uptex.edu.mx">
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Contraseña</label>
            <div class="relative">
              <Lock class="absolute left-3 top-2.5 text-slate-300" :size="16" />
              <input v-model="loginForm.pass" :type="verPass ? 'text' : 'password'" class="w-full pl-10 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-[#007a54] outline-none transition-all">
              <button type="button" @click="verPass = !verPass" class="absolute right-3 top-2.5 text-slate-300 hover:text-slate-500">
                <EyeOff v-if="verPass" :size="16" /> <Eye v-else :size="16" />
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 pt-2">
            <button type="submit" class="bg-[#007a54] text-white py-3 rounded-xl text-[11px] font-bold uppercase tracking-wider hover:bg-[#006344] shadow-md transition-all active:scale-95">
              Acceso al Portal
            </button>
            <button type="button" @click="guiaDisponible ? window.open('guia.pdf') : alert('Próximamente')" class="border-2 border-slate-100 text-slate-500 py-3 rounded-xl text-[11px] font-bold uppercase flex items-center justify-center gap-2 hover:bg-slate-50 transition-all">
              <FileText :size="14" /> Guía
            </button>
          </div>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center">
          <button @click="cambiarVista('registro')" class="text-[11px] font-bold text-[#007a54] hover:underline">¿No tienes cuenta? Regístrate aquí</button>
        </div>
      </div>

      <div v-else-if="vista === 'registro'" class="space-y-6">
        <button @click="cambiarVista('login')" class="flex items-center gap-2 text-slate-400 hover:text-slate-600 transition-colors">
          <ArrowLeft :size="16" /> <span class="text-xs font-bold uppercase">Regresar</span>
        </button>
        
        <div class="text-left">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Crear Cuenta Nueva</h3>
          <p class="text-xs text-slate-400">Se enviará un código de seguridad a tu correo.</p>
        </div>

        <form @submit.prevent="solicitarRegistro" class="space-y-4">
          <input v-model="registroForm.correo" type="email" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="Correo Institucional">
          <input v-model="registroForm.pass" type="password" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="Crear Contraseña">
          <input v-model="registroForm.confirma" type="password" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="Confirmar Contraseña">
          
          <button type="submit" :disabled="cargando" class="w-full bg-[#007a54] text-white py-3 rounded-xl text-[11px] font-bold uppercase tracking-wider shadow-lg flex items-center justify-center gap-2">
            <UserPlus :size="16" /> {{ cargando ? 'Enviando...' : 'Obtener Código' }}
          </button>
        </form>
      </div>

      <div v-else-if="vista === 'otp'" class="space-y-6 text-center">
        <div class="w-16 h-16 bg-blue-50 text-[#007a54] rounded-full flex items-center justify-center mx-auto shadow-inner">
          <KeyRound :size="32" />
        </div>
        <div>
          <h3 class="text-sm font-bold uppercase">Verifica tu correo</h3>
          <p class="text-[10px] text-slate-400 mt-1">Ingresa el código de 6 dígitos enviado a:<br><strong>{{ correoTemporal }}</strong></p>
        </div>

        <input v-model="otpInput" type="text" maxlength="6" class="w-full text-center text-2xl font-mono tracking-[0.5em] py-3 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-[#007a54] outline-none">
        
        <button @click="validarCodigo" :disabled="cargando" class="w-full bg-slate-900 text-white py-3 rounded-xl text-[11px] font-bold uppercase tracking-widest shadow-xl">
          {{ cargando ? 'Validando...' : 'Confirmar y Activar' }}
        </button>
      </div>

    </div>
  </div>
</template>