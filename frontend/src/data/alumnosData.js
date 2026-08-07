export const CARRERAS = [
  { id: 'ir', nombre: 'Ingeniería en Robótica', icono: '🤖', color: 'from-cyan-500 to-blue-600' },
  { id: 'isic', nombre: 'Ingeniería en Sistemas Computacionales', icono: '💻', color: 'from-indigo-500 to-purple-600' },
  { id: 'iet', nombre: 'Ingeniería en Electrónica y Telecomunicaciones', icono: '📡', color: 'from-emerald-500 to-teal-600' },
  { id: 'ilt', nombre: 'Ingeniería en Logística y Transporte', icono: '📍', color: 'from-orange-500 to-red-600' },
  { id: 'lage', nombre: 'Licenciatura en Administración y Gestión Empresarial', icono: '📊', color: 'from-sky-500 to-indigo-700' },
  { id: 'cia', nombre: 'Licenciatura en Comercio Internacional y Aduanas', icono: '🌎', color: 'from-amber-500 to-orange-600' }
];

export const ALUMNOS_MOCK = [
  { id: 1, nombre: 'Juan Pérez Gómez', matricula: '202611090', carrera: 'isic', grupo: 'ISC-601', promedio: 9.4, estado: 'pendiente', documentos: ['Constancia_Estudios.pdf', 'Comprobante_Ingresos.pdf'] },
  { id: 2, nombre: 'María Flores Solís', matricula: '202611091', carrera: 'isic', grupo: 'ISC-601', promedio: 8.9, estado: 'aceptado', comentario: 'Documentación validada por tutor.', documentos: ['Constancia_Estudios.pdf'] },
  { id: 3, nombre: 'Carlos López Ruíz', matricula: '202611092', carrera: 'isic', grupo: 'ISC-602', promedio: 7.6, estado: 'pendiente', documentos: ['Constancia_Estudios.pdf'] },
  { id: 4, nombre: 'Ana Martínez Lara', matricula: '202611093', carrera: 'ir', grupo: 'IRO-401', promedio: 9.1, estado: 'pendiente', documentos: ['Carta_Motivos.pdf'] },
  { id: 5, nombre: 'Pedro Juárez Díaz', matricula: '202611094', carrera: 'ilt', grupo: 'ILT-201', promedio: 8.2, estado: 'pendiente', documentos: ['Ingresos.pdf'] },
];