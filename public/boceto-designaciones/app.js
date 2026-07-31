// Sistema de Designación Docente UATF - Prototipo Interactivo (Ajuste Vicerrectorado: Botón Único 'Confirmar revisión' + Checkbox Aprobar Todas)

const USERS = {
    inf: {
        id: 1,
        name: 'Mgtr. María Quispe',
        email: 'maria.quispe@uatf.edu.bo',
        role: 'director_carrera',
        roleLabel: 'Director de Carrera',
        careerId: 1,
        carreraSigla: 'INF',
        carreraNombre: 'Ingeniería Informática',
        initial: 'M'
    },
    civ: {
        id: 2,
        name: 'Ing. Carlos Flores',
        email: 'carlos.flores@uatf.edu.bo',
        role: 'director_carrera',
        roleLabel: 'Director de Carrera',
        careerId: 2,
        carreraSigla: 'CIV',
        carreraNombre: 'Ingeniería Civil',
        initial: 'C'
    },
    med: {
        id: 3,
        name: 'Dra. Ana Rojas',
        email: 'ana.rojas@uatf.edu.bo',
        role: 'director_carrera',
        roleLabel: 'Director de Carrera',
        careerId: 3,
        carreraSigla: 'MED',
        carreraNombre: 'Medicina',
        initial: 'A'
    },
    vic: {
        id: 4,
        name: 'Dr. Ricardo Villca',
        email: 'ricardo.villca@uatf.edu.bo',
        role: 'vicerrectorado',
        roleLabel: 'Vicerrectorado Académico',
        careerId: null,
        carreraSigla: 'VRA',
        carreraNombre: 'Vicerrectorado Académico',
        initial: 'R'
    }
};

const CATALOGO_MATERIAS_BASE = {
    1: [ // INF
        { materiaId: 1, materiaNombre: 'Programación I', materiaSigla: 'INF-101', codigo: 'A', horas: 5 },
        { materiaId: 2, materiaNombre: 'Programación II', materiaSigla: 'INF-202', codigo: 'A', horas: 6 },
        { materiaId: 3, materiaNombre: 'Bases de Datos', materiaSigla: 'INF-305', codigo: 'B', horas: 5 },
        { materiaId: 4, materiaNombre: 'Ingeniería de Software', materiaSigla: 'INF-408', codigo: 'A', horas: 4 },
        { materiaId: 5, materiaNombre: 'Redes de Computadoras I', materiaSigla: 'INF-302', codigo: 'A', horas: 5 },
        { materiaId: 6, materiaNombre: 'Sistemas Operativos', materiaSigla: 'INF-401', codigo: 'A', horas: 6 }
    ],
    2: [ // CIV
        { materiaId: 21, materiaNombre: 'Hormigón Armado I', materiaSigla: 'CIV-301', codigo: 'A', horas: 6 },
        { materiaId: 22, materiaNombre: 'Mecánica de Suelos', materiaSigla: 'CIV-204', codigo: 'A', horas: 5 }
    ],
    3: [ // MED
        { materiaId: 31, materiaNombre: 'Anatomía Humana', materiaSigla: 'MED-101', codigo: 'A', horas: 8 },
        { materiaId: 32, materiaNombre: 'Fisiología Médica', materiaSigla: 'MED-201', codigo: 'B', horas: 6 }
    ]
};

let currentUserKey = 'inf';
let currentView = 'lista_director';
let activePropuestaId = 1;
let activeDocenteModalId = null;

let busquedaDocente = '';
let docenteSeleccionadoId = null;
let inboxFolder = 'inbox';
let busquedaInbox = '';

let propuestas = [
    {
        id: 1,
        carreraId: 1,
        descripcion: 'Propuesta de Designación Docente I/2026',
        gestion: '2026',
        periodo: 'Primer Período',
        estado: 'borrador',
        numeroVersion: 1,
        solicitante: 'Mgtr. María Quispe',
        solicitadoEn: null,
        observacionGeneral: null
    },
    {
        id: 2,
        carreraId: 2,
        descripcion: 'Propuesta de Designación Docente Civil I/2026',
        gestion: '2026',
        periodo: 'Primer Período',
        estado: 'pendiente',
        numeroVersion: 1,
        solicitante: 'Ing. Carlos Flores',
        solicitadoEn: '31/07/2026 09:30',
        observacionGeneral: null
    },
    {
        id: 3,
        carreraId: 3,
        descripcion: 'Propuesta de Designación Docente Medicina I/2026',
        gestion: '2026',
        periodo: 'Primer Período',
        estado: 'observada',
        numeroVersion: 1,
        solicitante: 'Dra. Ana Rojas',
        solicitadoEn: '30/07/2026 16:10',
        observacionGeneral: 'Revisar la asignación de Fisiología en el grupo B por superposición de carga docente.'
    },
    {
        id: 4,
        carreraId: 1,
        descripcion: 'Regularización de Cargas Docentes I/2025',
        gestion: '2025',
        periodo: 'Primer Período',
        estado: 'aprobada',
        numeroVersion: 2,
        solicitante: 'Mgtr. María Quispe',
        solicitadoEn: '15/01/2025 14:20',
        observacionGeneral: 'Aprobada oficialmente por Vicerrectorado Académico.'
    }
];

let todosLosDocentesUniversidad = [
    { id: 101, nombre: 'Lic. Pedro Mendoza', carreraOrigenId: 1, carreraSigla: 'INF', horasOtrasCarreras: 0 },
    { id: 102, nombre: 'Ing. Andrea Salas', carreraOrigenId: 1, carreraSigla: 'INF', horasOtrasCarreras: 0 },
    { id: 103, nombre: 'Lic. Elena Chambi', carreraOrigenId: 1, carreraSigla: 'INF', horasOtrasCarreras: 0 },
    { id: 104, nombre: 'Msc. Julio Vargas', carreraOrigenId: 2, carreraSigla: 'CIV', horasOtrasCarreras: 6 },
    { id: 201, nombre: 'Ing. Roberto Mamani', carreraOrigenId: 2, carreraSigla: 'CIV', horasOtrasCarreras: 0 },
    { id: 202, nombre: 'Ing. Lucía Fernández', carreraOrigenId: 2, carreraSigla: 'CIV', horasOtrasCarreras: 4 },
    { id: 301, nombre: 'Dr. Hugo Gutiérrez', carreraOrigenId: 3, carreraSigla: 'MED', horasOtrasCarreras: 0 },
    { id: 302, nombre: 'Dra. Carmen Zenteno', carreraOrigenId: 3, carreraSigla: 'MED', horasOtrasCarreras: 0 },
    { id: 401, nombre: 'Lic. Ramiro Solares', carreraOrigenId: 4, carreraSigla: 'IND', horasOtrasCarreras: 8 },
    { id: 402, nombre: 'Ing. Claudia Peñaranda', carreraOrigenId: 5, carreraSigla: 'SIS', horasOtrasCarreras: 0 }
];

let rosterGruposPorPropuesta = {
    1: [
        { id: 1001, materiaId: 1, materiaNombre: 'Programación I', materiaSigla: 'INF-101', codigo: 'A', horas: 5, docenteId: 101, estado: 'editable', observacion: null },
        { id: 1002, materiaId: 2, materiaNombre: 'Programación II', materiaSigla: 'INF-202', codigo: 'A', horas: 6, docenteId: 101, estado: 'editable', observacion: null },
        { id: 1003, materiaId: 3, materiaNombre: 'Bases de Datos', materiaSigla: 'INF-305', codigo: 'B', horas: 5, docenteId: 102, estado: 'editable', observacion: null },
        { id: 1004, materiaId: 4, materiaNombre: 'Ingeniería de Software', materiaSigla: 'INF-408', codigo: 'A', horas: 4, docenteId: 103, estado: 'editable', observacion: null },
        { id: 1005, materiaId: 5, materiaNombre: 'Redes de Computadoras I', materiaSigla: 'INF-302', codigo: 'A', horas: 5, docenteId: 103, estado: 'editable', observacion: null },
        { id: 1006, materiaId: 6, materiaNombre: 'Sistemas Operativos', materiaSigla: 'INF-401', codigo: 'A', horas: 6, docenteId: 104, estado: 'editable', observacion: null }
    ],
    2: [
        { id: 2001, materiaId: 21, materiaNombre: 'Hormigón Armado I', materiaSigla: 'CIV-301', codigo: 'A', horas: 6, docenteId: 201, estado: 'editable', observacion: null },
        { id: 2002, materiaId: 22, materiaNombre: 'Mecánica de Suelos', materiaSigla: 'CIV-204', codigo: 'A', horas: 5, docenteId: 202, estado: 'editable', observacion: null }
    ],
    3: [
        { id: 3001, materiaId: 31, materiaNombre: 'Anatomía Humana', materiaSigla: 'MED-101', codigo: 'A', horas: 8, docenteId: 301, estado: 'aprobada_previamente', observacion: null },
        { id: 3002, materiaId: 32, materiaNombre: 'Fisiología Médica', materiaSigla: 'MED-201', codigo: 'B', horas: 6, docenteId: 302, estado: 'observada', observacion: 'El docente presenta superposición horaria en la gestión actual.' }
    ],
    4: [
        { id: 4001, materiaId: 1, materiaNombre: 'Programación I', materiaSigla: 'INF-101', codigo: 'A', horas: 5, docenteId: 101, estado: 'aprobada_previamente', observacion: null },
        { id: 4002, materiaId: 2, materiaNombre: 'Programación II', materiaSigla: 'INF-202', codigo: 'A', horas: 6, docenteId: 101, estado: 'aprobada_previamente', observacion: null },
        { id: 4003, materiaId: 3, materiaNombre: 'Bases de Datos', materiaSigla: 'INF-305', codigo: 'B', horas: 5, docenteId: 102, estado: 'aprobada_previamente', observacion: null },
        { id: 4004, materiaId: 4, materiaNombre: 'Ingeniería de Software', materiaSigla: 'INF-408', codigo: 'A', horas: 4, docenteId: 103, estado: 'aprobada_previamente', observacion: null },
        { id: 4005, materiaId: 5, materiaNombre: 'Redes de Computadoras I', materiaSigla: 'INF-302', codigo: 'A', horas: 5, docenteId: 103, estado: 'aprobada_previamente', observacion: null },
        { id: 4006, materiaId: 6, materiaNombre: 'Sistemas Operativos', materiaSigla: 'INF-401', codigo: 'A', horas: 6, docenteId: 104, estado: 'aprobada_previamente', observacion: null }
    ]
};

let notifications = [
    { id: 1, text: 'Propuesta de Ingeniería Civil enviada a revisión.', time: 'Hace 10 min', unread: true },
    { id: 2, text: 'Vicerrectorado observó la propuesta de Medicina.', time: 'Hace 2 horas', unread: true },
    { id: 3, text: 'Gestión 2026 habilitada oficialmente.', time: 'Ayer', unread: false }
];

function getCurrentUser() {
    return USERS[currentUserKey];
}

function switchUser(key) {
    if (!USERS[key]) return;
    currentUserKey = key;
    const u = USERS[key];

    if (u.role === 'vicerrectorado') {
        currentView = 'bandeja_vicerrectorado';
    } else {
        currentView = 'lista_director';
    }

    renderLayout();
}

function switchView(viewName, propuestaId = null) {
    currentView = viewName;
    if (propuestaId) {
        activePropuestaId = propuestaId;
    }
    renderLayout();
}

function renderLayout() {
    const u = getCurrentUser();

    document.getElementById('headerUserName').textContent = u.name;
    document.getElementById('headerUserEmail').textContent = u.email;
    document.getElementById('headerAvatarCircle').textContent = u.initial;
    document.getElementById('dropdownUserName').textContent = u.name;
    document.getElementById('dropdownUserRole').textContent = `Rol: ${u.roleLabel}`;
    document.getElementById('userSelector').value = currentUserKey;

    const unreadCount = notifications.filter(n => n.unread).length;
    const badgeEl = document.getElementById('notifBadge');
    if (unreadCount > 0) {
        badgeEl.textContent = unreadCount;
        badgeEl.classList.remove('hidden');
    } else {
        badgeEl.classList.add('hidden');
    }

    renderNotificationsList();

    document.getElementById('sidebarSubtitle').textContent = u.role === 'vicerrectorado'
        ? 'Vicerrectorado Académico'
        : `Carrera de ${u.carreraNombre}`;

    const navContainer = document.getElementById('sidebarNavItems');
    if (u.role === 'vicerrectorado') {
        navContainer.innerHTML = `
            <a href="#" onclick="switchView('bandeja_vicerrectorado'); return false;"
               class="flex items-center justify-between px-4 py-2.5 transition-all duration-150 ${currentView.includes('vicerrectorado') || currentView.includes('revisar') ? 'bg-[#20252a] text-[#00acac] font-semibold border-l-4 border-[#00acac]' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]'}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 ${currentView.includes('vicerrectorado') || currentView.includes('revisar') ? 'text-[#00acac]' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <span>Bandeja de Revisiones</span>
                </div>
                <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm uppercase tracking-wider">
                    INBOX
                </span>
            </a>
        `;
    } else {
        navContainer.innerHTML = `
            <a href="#" onclick="switchView('lista_director'); return false;"
               class="flex items-center justify-between px-4 py-2.5 transition-all duration-150 ${currentView.includes('director') || currentView.includes('carrera') ? 'bg-[#20252a] text-[#00acac] font-semibold border-l-4 border-[#00acac]' : 'hover:bg-[#23282c] hover:text-white text-[#a8b6c1]'}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 ${currentView.includes('director') || currentView.includes('carrera') ? 'text-[#00acac]' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Designaciones</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        `;
    }

    renderMainView();
}

function renderNotificationsList() {
    const listEl = document.getElementById('notifList');
    if (!listEl) return;

    if (notifications.length === 0) {
        listEl.innerHTML = `<div class="p-4 text-center text-gray-400">Sin notificaciones</div>`;
        return;
    }

    listEl.innerHTML = notifications.map(n => `
        <div class="px-4 py-2.5 hover:bg-gray-50 transition-colors ${n.unread ? 'bg-teal-50/40' : ''}">
            <p class="text-xs font-semibold text-gray-800">${n.text}</p>
            <span class="text-[10px] text-gray-400 mt-0.5 block">${n.time}</span>
        </div>
    `).join('');
}

function markAllRead() {
    notifications.forEach(n => n.unread = false);
    renderLayout();
}

function renderMainView() {
    const mainEl = document.getElementById('mainContent');
    const u = getCurrentUser();

    if (currentView === 'lista_director') {
        renderListaDirectorView(mainEl, u);
    } else if (currentView === 'editor_carrera') {
        renderEditorCarreraView(mainEl, u);
    } else if (currentView === 'importar_propuesta') {
        renderImportarPropuestaView(mainEl, u);
    } else if (currentView === 'bandeja_vicerrectorado') {
        renderBandejaVicerrectoradoView(mainEl, u);
    } else if (currentView === 'revisar_version') {
        renderRevisarVersionView(mainEl, u);
    }
}

// --------------------------------------------------------------------------
// PANTALLA 1: LISTA DE PROPUESTAS CON BOTÓN IMPRIMIR
// --------------------------------------------------------------------------
function renderListaDirectorView(container, u) {
    const misPropuestas = propuestas.filter(p => p.carreraId === u.careerId);

    container.innerHTML = `
        <div class="space-y-4 text-xs text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3.5 rounded border border-gray-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="bg-[#2d353c] text-white text-[10px] font-bold px-2 py-0.5 rounded-xs uppercase tracking-wide">Carrera</span>
                        <h1 class="text-lg font-bold tracking-tight text-gray-900">
                            Designaciones Docentes &mdash; ${u.carreraNombre} (${u.carreraSigla})
                        </h1>
                    </div>
                </div>

                <button onclick="abrirModalNuevaPropuesta()"
                        class="px-3.5 py-2 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nueva Propuesta de Designación</span>
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-xs shadow-2xs overflow-hidden">
                <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
                    <span>Propuestas de Designación</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-white text-gray-900 font-bold border-b border-gray-200 text-xs">
                            <tr>
                                <th class="py-3 px-4 text-center w-12 border-r border-gray-200/80">#</th>
                                <th class="py-3 px-5 border-r border-gray-200/80">Descripción</th>
                                <th class="py-3 px-4 text-center w-24 border-r border-gray-200/80">Gestión</th>
                                <th class="py-3 px-4 text-center w-24 border-r border-gray-200/80">Periodo</th>
                                <th class="py-3 px-4 text-center w-48 border-r border-gray-200/80">Estado</th>
                                <th class="py-3 px-4 text-center w-72">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 font-medium">
                            ${misPropuestas.length === 0 ? `
                                <tr>
                                    <td colspan="6" class="py-8 px-4 text-center text-gray-500 italic">
                                        No existen propuestas de designación registradas para esta carrera.
                                    </td>
                                </tr>
                            ` : misPropuestas.map((p, idx) => {
                                const gruposProp = rosterGruposPorPropuesta[p.id] || [];
                                const asignadasCount = gruposProp.filter(g => g.docenteId !== null).length;
                                return `
                                    <tr ondblclick="verObservacionesPropuesta(${p.id})"
                                        class="transition-colors cursor-pointer select-none ${idx % 2 === 0 ? 'bg-[#f2f4f8]' : 'bg-white hover:bg-gray-100/70'}">
                                        <td class="py-3.5 px-4 text-center font-bold text-gray-500 border-r border-gray-200/60">${idx + 1}</td>
                                        <td class="py-3.5 px-5 border-r border-gray-200/60">
                                            <span class="font-bold text-gray-900 text-xs block">${p.descripcion}</span>
                                            <span class="text-[11px] text-gray-500 font-normal">Carrera de ${u.carreraNombre}</span>
                                            <span class="text-[11px] text-gray-500 font-normal block">Versión ${p.numeroVersion} &bull; ${asignadasCount} de ${gruposProp.length} materias asignadas</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-bold">${p.gestion}</td>
                                        <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-bold">${p.periodo}</td>
                                        <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                            ${getBadgeEstado(p.estado)}
                                        </td>
                                        <td class="py-3.5 px-4 text-center space-x-1">
                                            <button onclick="imprimirPropuesta(${p.id})" class="px-2.5 py-1 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-[11px] cursor-pointer inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>Imprimir</span>
                                            </button>

                                            ${p.estado === 'observada' ? `
                                                <button onclick="verObservacionesPropuesta(${p.id})" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-800 font-bold rounded text-[11px] border border-rose-300 cursor-pointer">
                                                    Observaciones
                                                </button>
                                            ` : p.estado === 'aprobada' && p.observacionGeneral ? `
                                                <button onclick="verObservacionesPropuesta(${p.id})" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded text-[11px] border border-emerald-300 cursor-pointer">
                                                    ✓ Decisión
                                                </button>
                                            ` : ''}

                                            <button onclick="switchView('editor_carrera', ${p.id})" class="px-2.5 py-1 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded text-[11px] cursor-pointer">
                                                ${p.estado === 'borrador' ? 'Abrir' : p.estado === 'observada' ? 'Corregir' : 'Ver detalle'}
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        ${renderModales(u)}
    `;
}

function imprimirPropuesta(id) {
    const p = propuestas.find(item => item.id === id);
    alert(`Generando reporte de impresión oficial para: "${p ? p.descripcion : 'Propuesta de Designación'}"...`);
}

function getBadgeEstado(estado) {
    if (estado === 'aprobada') {
        return `<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">✓ Aprobada / Bloqueada</span>`;
    } else if (estado === 'pendiente') {
        return `<span class="bg-blue-100 text-blue-800 border border-blue-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">⏱ Enviada a Vicerrectorado</span>`;
    } else if (estado === 'observada') {
        return `<span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">⚠ Con observaciones</span>`;
    } else {
        return `<span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">✎ Borrador / Propuesta</span>`;
    }
}

// --------------------------------------------------------------------------
// PANTALLA 2: DATATABLE CANÓNICO DE DESIGNACIÓN POR PROPUESTA ID
// --------------------------------------------------------------------------
function renderEditorCarreraView(container, u) {
    const prop = propuestas.find(p => p.id === activePropuestaId) || propuestas[0];
    const grupos = rosterGruposPorPropuesta[prop.id] || [];

    const docentesConPrioridad = todosLosDocentesUniversidad.map(d => {
        const asignados = grupos.filter(g => g.docenteId === d.id);
        const materiasEtiquetas = asignados.map(g => {
            if (g.estado === 'aprobada_previamente' || prop.estado === 'aprobada') {
                return { text: `${g.materiaSigla} (G${g.codigo})`, estado: 'aprobada' };
            } else if (g.estado === 'observada') {
                return { text: `${g.materiaSigla} (G${g.codigo})`, estado: 'observada' };
            } else {
                return { text: `${g.materiaSigla} (G${g.codigo})`, estado: 'editable' };
            }
        });
        const horasLocal = asignados.reduce((sum, g) => sum + g.horas, 0);
        const horasTotalGlobal = horasLocal + d.horasOtrasCarreras;

        let prioridad = 3;
        let prioridadEtiqueta = 'Docente Universidad';
        let prioridadBadgeColor = 'bg-gray-100 text-gray-700 border-gray-300';

        if (d.carreraOrigenId === u.careerId) {
            prioridad = 1;
            prioridadEtiqueta = 'Titular Carrera';
            prioridadBadgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-300';
        } else if (d.id === 104 || asignados.length > 0) {
            prioridad = 2;
            prioridadEtiqueta = 'Histórico Carrera';
            prioridadBadgeColor = 'bg-cyan-100 text-cyan-800 border-cyan-300';
        }

        let estadoCarga = 'optimo';
        let estadoEtiqueta = 'Óptimo';
        let estadoColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';

        if (horasTotalGlobal === 0) {
            estadoCarga = 'sin_asignacion';
            estadoEtiqueta = 'Sin asignación';
            estadoColor = 'bg-gray-100 text-gray-700 border-gray-200';
        } else if (horasTotalGlobal < 6) {
            estadoCarga = 'bajo_minimo';
            estadoEtiqueta = 'Bajo mínimo (< 6h)';
            estadoColor = 'bg-amber-100 text-amber-800 border-amber-200';
        } else if (horasTotalGlobal > 32) {
            estadoCarga = 'sobrecarga';
            estadoEtiqueta = 'Sobrecarga (> 32h)';
            estadoColor = 'bg-rose-100 text-rose-800 border-rose-200';
        }

        return {
            ...d,
            prioridad,
            prioridadEtiqueta,
            prioridadBadgeColor,
            materiasEtiquetas,
            horasLocal,
            horasTotalGlobal,
            asignados,
            estadoEtiqueta,
            estadoColor
        };
    });

    docentesConPrioridad.sort((a, b) => a.prioridad - b.prioridad);

    const docentesFiltrados = docentesConPrioridad.filter(d => {
        if (!busquedaDocente) return true;
        const q = busquedaDocente.toLowerCase();
        return d.nombre.toLowerCase().includes(q) || d.carreraSigla.toLowerCase().includes(q);
    });

    const esEditable = prop.estado === 'borrador' || prop.estado === 'observada';

    container.innerHTML = `
        <div class="space-y-4 text-xs text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3.5 rounded border border-gray-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="bg-[#00acac] text-white text-xs font-bold px-2 py-0.5 rounded">Carrera</span>
                        <h1 class="text-xl font-bold tracking-tight text-gray-900">${u.carreraNombre} (${u.carreraSigla})</h1>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">${prop.descripcion} &bull; Versión ${prop.numeroVersion}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="switchView('lista_director')"
                            class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold rounded text-xs transition-colors cursor-pointer">
                        ← Mis propuestas
                    </button>

                    <button onclick="imprimirPropuesta(${prop.id})"
                            class="px-3 py-1.5 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-xs transition-colors cursor-pointer flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Imprimir</span>
                    </button>

                    ${esEditable ? `
                        <button onclick="switchView('importar_propuesta')"
                                class="bg-gray-800 hover:bg-gray-900 text-white font-bold px-3 py-1.5 rounded text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                            <span>Importar Asignaciones</span>
                        </button>

                        <button onclick="enviarPropuestaVicerrectorado(${prop.id})"
                                class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-3.5 py-1.5 rounded text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span>${prop.estado === 'observada' ? 'Reenviar Versión Corregida' : 'Enviar Propuesta a Vicerrectorado'}</span>
                        </button>
                    ` : prop.estado === 'pendiente' ? `
                        <button onclick="retirarEnvioPropuesta(${prop.id})"
                                class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3.5 py-1.5 rounded text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Retirar Envío</span>
                        </button>
                    ` : `
                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold px-3 py-1.5 rounded text-xs inline-flex items-center gap-1">
                            ✓ Propuesta Aprobada e Inmutable
                        </span>
                    `}
                </div>
            </div>

            ${prop.estado === 'observada' && prop.observacionGeneral ? `
                <div class="bg-rose-50 border border-rose-200 text-rose-900 p-3.5 rounded text-xs flex items-start gap-2.5 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <strong class="font-bold text-rose-900 block">Observación del Vicerrectorado (Versión ${prop.numeroVersion}):</strong>
                        <p class="mt-0.5 text-rose-800 font-medium">${prop.observacionGeneral}</p>
                    </div>
                </div>
            ` : prop.estado === 'aprobada' && prop.observacionGeneral ? `
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 p-3.5 rounded text-xs flex items-start gap-2.5 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <strong class="font-bold text-emerald-900 block">✓ Decisión del Vicerrectorado:</strong>
                        <p class="mt-0.5 text-emerald-800 font-medium">${prop.observacionGeneral}</p>
                    </div>
                </div>
            ` : ''}

            <div class="rounded-lg border border-gray-200/80 bg-white shadow-md overflow-hidden">
                <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
                    <div class="flex items-center gap-2">
                        <span>Asignación de Carga Docente UATF</span>
                        <span class="bg-[#00acac] text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-xs tracking-wider uppercase">${u.carreraSigla}</span>
                    </div>
                </div>

                <div class="p-3.5 border-b border-gray-200 bg-white flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <select class="border border-gray-300 rounded-xs px-2 py-1 bg-white text-gray-700 font-bold shadow-2xs outline-none">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-gray-600 font-medium">registros por página</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-gray-600 font-medium">Buscar docente:</span>
                        <input type="text"
                               value="${busquedaDocente}"
                               oninput="filtrarDocente(this.value)"
                               placeholder="Nombre o carrera origen..."
                               class="border border-gray-300 rounded-xs px-2.5 py-1 text-xs w-56 shadow-2xs focus:border-[#348fe2] focus:ring-1 focus:ring-[#348fe2] outline-none">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-white text-gray-900 font-bold border-b border-gray-200 text-xs">
                            <tr>
                                <th class="py-3 px-4 text-center w-12 border-r border-gray-200/80">#</th>
                                <th class="py-3 px-4 border-r border-gray-200/80">Docente & Prioridad</th>
                                <th class="py-3 px-4 border-r border-gray-200/80">Materias / Grupos Asignados</th>
                                <th class="py-3 px-4 text-center w-36 border-r border-gray-200/80">Carga Horaria</th>
                                <th class="py-3 px-4 text-center w-44 border-r border-gray-200/80">Estado Carga</th>
                                <th class="py-3 px-4 text-center w-36">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 font-medium">
                            ${docentesFiltrados.length === 0 ? `
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400 italic">No se encontraron docentes coincidentes.</td>
                                </tr>
                            ` : docentesFiltrados.map((d, index) => {
                                const tieneObservada = d.asignados.some(g => g.estado === 'observada');
                                return `
                                    <tr onclick="seleccionarDocenteFila(${d.id})"
                                        class="transition-colors cursor-pointer select-none ${tieneObservada ? 'bg-rose-50/70 hover:bg-rose-100/70' : docenteSeleccionadoId === d.id ? 'bg-[#fff9d6]' : (index % 2 === 0 ? 'bg-[#f2f4f8]' : 'bg-white hover:bg-gray-100/70')}">

                                        <td class="py-3.5 px-4 text-center font-bold text-gray-500 border-r border-gray-200/60">${index + 1}</td>

                                        <td class="py-3.5 px-4 border-r border-gray-200/60">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-full bg-[#00acac] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">${d.nombre.charAt(0)}</div>
                                                <div>
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <p class="font-bold text-gray-900">${d.nombre}</p>
                                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-xs border ${d.prioridadBadgeColor}">
                                                            ${d.prioridadEtiqueta}
                                                        </span>
                                                    </div>
                                                    <p class="text-[10px] text-gray-400 font-medium uppercase">Origen: Carrera de ${d.carreraSigla}</p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-3.5 px-4 border-r border-gray-200/60">
                                            <div class="flex flex-wrap gap-1">
                                                ${d.materiasEtiquetas.length === 0 ? `
                                                    <span class="text-gray-400 italic text-[11px]">Sin materias asignadas</span>
                                                ` : d.materiasEtiquetas.map(mat => {
                                                    if (mat.estado === 'aprobada') {
                                                        return `<span class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold px-2 py-0.5 rounded-xs text-[10px] inline-flex items-center gap-1">✓ ${mat.text}</span>`;
                                                    } else if (mat.estado === 'observada') {
                                                        return `<span class="bg-rose-100 text-rose-900 border border-rose-300 font-bold px-2 py-0.5 rounded-xs text-[10px] inline-flex items-center gap-1">⚠ ${mat.text}</span>`;
                                                    } else {
                                                        return `<span class="bg-gray-100 text-gray-800 border border-gray-300 font-bold px-2 py-0.5 rounded-xs text-[10px]">${mat.text}</span>`;
                                                    }
                                                }).join('')}
                                            </div>
                                            ${d.asignados.some(g => g.observacion) ? `
                                                <span class="text-[10px] text-rose-700 font-bold block mt-1">
                                                    ⚠ ${d.asignados.find(g => g.observacion).observacion}
                                                </span>
                                            ` : ''}
                                        </td>

                                        <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                            <span class="font-black text-gray-900 text-xs tabular-nums">${d.horasTotalGlobal} hrs</span>
                                        </td>

                                        <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border ${d.estadoColor}">
                                                ${d.estadoEtiqueta}
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-center">
                                            ${esEditable ? `
                                                <button onclick="event.stopPropagation(); abrirModalAsignarDocente(${d.id})"
                                                        class="px-2.5 py-1 ${tieneObservada ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#00acac] hover:bg-[#008a8a]'} text-white font-bold rounded text-xs shadow-2xs cursor-pointer">
                                                    ${tieneObservada ? 'Corregir Materias' : 'Designar Materias'}
                                                </button>
                                            ` : `
                                                <span class="text-gray-400 font-bold">Bloqueado</span>
                                            `}
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        ${renderModales(u)}
    `;
}

function filtrarDocente(val) {
    busquedaDocente = val;
    renderMainView();
}

function seleccionarDocenteFila(id) {
    docenteSeleccionadoId = (docenteSeleccionadoId === id ? null : id);
    renderMainView();
}

// --------------------------------------------------------------------------
// PANTALLA IMPORTACIÓN
// --------------------------------------------------------------------------
let importOrigenPropuestaId = null;

function renderImportarPropuestaView(container, u) {
    const propActual = propuestas.find(p => p.id === activePropuestaId) || propuestas[0];
    const historicas = propuestas.filter(p => p.carreraId === u.careerId && p.id !== activePropuestaId);

    container.innerHTML = `
        <div class="max-w-4xl mx-auto space-y-4 text-xs text-gray-800">
            <div class="flex items-center justify-between bg-white p-3.5 rounded border border-gray-200 shadow-2xs">
                <div>
                    <h1 class="text-base font-bold text-gray-900">Importar Asignaciones Históricas</h1>
                    <p class="text-xs text-gray-500">Copiar antecedentes docentes hacia el borrador de: <strong>${propActual.descripcion}</strong></p>
                </div>
                <button onclick="switchView('editor_carrera', ${propActual.id})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded text-xs border border-gray-300 cursor-pointer">
                    ← Volver al editor
                </button>
            </div>

            <div id="stepForm" class="bg-white border border-gray-200 rounded p-5 space-y-4 shadow-2xs">
                <h3 class="font-bold text-gray-900 text-sm border-b border-gray-100 pb-2">1. Seleccionar Propuesta Histórica de Origen</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Seleccionar Propuesta Previa:</label>
                        <select id="selectPropuestaOrigen" class="w-full bg-white border border-gray-300 rounded p-2 font-bold text-xs">
                            ${historicas.length === 0 ? `
                                <option value="">No hay propuestas previas registradas</option>
                            ` : historicas.map(h => `
                                <option value="${h.id}">${h.descripcion} (${h.gestion} - ${h.periodo})</option>
                            `).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Carrera:</label>
                        <input type="text" disabled value="${u.carreraNombre}" class="w-full bg-gray-100 border border-gray-300 rounded p-2 font-bold text-xs text-gray-600">
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-100 text-right">
                    <button onclick="previsualizarImportacion()" class="px-4 py-2 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded text-xs cursor-pointer">
                        Previsualizar Diferencias →
                    </button>
                </div>
            </div>

            <div id="stepPreview" class="hidden bg-white border border-gray-200 rounded p-5 space-y-4 shadow-2xs">
                <h3 class="font-bold text-gray-900 text-sm border-b border-gray-100 pb-2">2. Previsualización de Cambios a Aplicar al Borrador</h3>
                <div id="tablaDiferenciasImportacion" class="overflow-x-auto">
                </div>

                <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                    <button onclick="document.getElementById('stepPreview').classList.add('hidden')" class="text-gray-500 hover:underline">← Modificar Origen</button>
                    <button onclick="aplicarImportacion()" class="px-4 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded text-xs cursor-pointer">
                        Confirmar y Aplicar al Borrador
                    </button>
                </div>
            </div>
        </div>
    `;
}

function previsualizarImportacion() {
    const sel = document.getElementById('selectPropuestaOrigen');
    if (!sel || !sel.value) {
        alert('Por favor selecciona una propuesta de origen válida.');
        return;
    }
    importOrigenPropuestaId = parseInt(sel.value);
    const origenGrupos = rosterGruposPorPropuesta[importOrigenPropuestaId] || [];

    const tableEl = document.getElementById('tablaDiferenciasImportacion');
    tableEl.innerHTML = `
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-gray-100 font-bold border-b border-gray-200">
                <tr>
                    <th class="py-2.5 px-3">Materia & Grupo</th>
                    <th class="py-2.5 px-3">Docente en Origen Histórico</th>
                    <th class="py-2.5 px-3">Impacto en el Borrador Actual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${origenGrupos.map(g => {
                    const doc = todosLosDocentesUniversidad.find(d => d.id === g.docenteId);
                    return `
                        <tr>
                            <td class="py-2.5 px-3 font-bold">${g.materiaSigla} ${g.materiaNombre} (G-${g.codigo})</td>
                            <td class="py-2.5 px-3 font-bold text-teal-700">${doc ? doc.nombre : 'Sin asignar'}</td>
                            <td class="py-2.5 px-3"><span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded">Se asignará al borrador</span></td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;

    document.getElementById('stepPreview').classList.remove('hidden');
}

function aplicarImportacion() {
    if (!importOrigenPropuestaId) return;
    const origenGrupos = rosterGruposPorPropuesta[importOrigenPropuestaId] || [];
    const destinoGrupos = rosterGruposPorPropuesta[activePropuestaId] || [];

    origenGrupos.forEach(og => {
        const dg = destinoGrupos.find(g => g.materiaSigla === og.materiaSigla && g.codigo === og.codigo);
        if (dg) {
            dg.docenteId = og.docenteId;
            dg.estado = 'editable';
            dg.observacion = null;
        }
    });

    alert('Importación completada exitosamente. Las asignaciones históricas fueron aplicadas a tu borrador.');
    switchView('editor_carrera', activePropuestaId);
}

// --------------------------------------------------------------------------
// PANTALLA BANDEJA DE VICERRECTORADO
// --------------------------------------------------------------------------
function renderBandejaVicerrectoradoView(container, u) {
    let peticiones = propuestas.filter(p => p.estado !== 'borrador');

    if (inboxFolder === 'pendientes') {
        peticiones = peticiones.filter(p => p.estado === 'pendiente');
    } else if (inboxFolder === 'revisadas') {
        peticiones = peticiones.filter(p => p.estado === 'aprobada' || p.estado === 'observada');
    }

    if (busquedaInbox) {
        const q = busquedaInbox.toLowerCase();
        peticiones = peticiones.filter(p =>
            p.descripcion.toLowerCase().includes(q) ||
            p.solicitante.toLowerCase().includes(q)
        );
    }

    const countInbox = propuestas.filter(p => p.estado !== 'borrador').length;
    const countPendientes = propuestas.filter(p => p.estado === 'pendiente').length;
    const countRevisadas = propuestas.filter(p => p.estado === 'aprobada' || p.estado === 'observada').length;

    container.innerHTML = `
        <div class="flex flex-col lg:flex-row border border-gray-200/80 rounded-lg shadow-sm bg-white overflow-hidden min-h-[calc(100vh-6.5rem)] text-xs text-gray-800">
            <div class="w-full lg:w-64 bg-[#f0f3f8] border-r border-gray-200 p-4 shrink-0 flex flex-col justify-between">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5 px-2">Carpetas</h3>
                        <nav class="space-y-1">
                            <button onclick="setInboxFolder('inbox')"
                                    class="w-full flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold cursor-pointer ${inboxFolder === 'inbox' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60'}">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <span>Inbox</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-[#2d353c] text-white">${countInbox}</span>
                            </button>

                            <button onclick="setInboxFolder('pendientes')"
                                    class="w-full flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold cursor-pointer ${inboxFolder === 'pendientes' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60'}">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Pendientes</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">${countPendientes}</span>
                            </button>

                            <button onclick="setInboxFolder('revisadas')"
                                    class="w-full flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold cursor-pointer ${inboxFolder === 'revisadas' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60'}">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Revisadas</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">${countRevisadas}</span>
                            </button>
                        </nav>
                    </div>

                    <div class="pt-4 border-t border-gray-200/80">
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5 px-2">Carreras</h3>
                        <div class="space-y-2 text-xs text-gray-700 font-medium px-2">
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#348fe2]"></span><span>Ingeniería Informática</span></div>
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#00acac]"></span><span>Ingeniería Civil</span></div>
                            <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#727cb6]"></span><span>Medicina</span></div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200/80 text-[11px] text-gray-400 font-medium text-center">
                    UATF &bull; Vicerrectorado
                </div>
            </div>

            <div class="flex-1 flex flex-col min-w-0 bg-white">
                <div class="bg-[#f0f3f8] border-b border-gray-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2">
                        <select onchange="setInboxFolder(this.value)" class="border border-gray-300 rounded px-3 py-1 bg-white text-gray-700 font-bold focus:outline-none text-xs shadow-2xs cursor-pointer">
                            <option value="inbox" ${inboxFolder === 'inbox' ? 'selected' : ''}>Bandeja (Todas)</option>
                            <option value="pendientes" ${inboxFolder === 'pendientes' ? 'selected' : ''}>Ver Pendientes</option>
                            <option value="revisadas" ${inboxFolder === 'revisadas' ? 'selected' : ''}>Ver Revisadas</option>
                        </select>

                        <button onclick="renderLayout()" title="Actualizar Bandeja" class="px-2.5 py-1 border border-gray-300 rounded bg-white hover:bg-gray-50 text-gray-700 font-bold shadow-2xs cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="text" value="${busquedaInbox}" oninput="filtrarInbox(this.value)" placeholder="Buscar por director o carrera..."
                               class="px-3 py-1 border border-gray-300 rounded text-xs w-48 sm:w-60 bg-white focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-200/70">
                    ${peticiones.length === 0 ? `
                        <div class="py-16 text-center text-gray-400 space-y-2">
                            <svg class="w-12 h-12 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="font-bold text-gray-700 text-sm">No hay solicitudes en esta carpeta</p>
                        </div>
                    ` : peticiones.map(p => {
                        const carrera = USERS[p.carreraId === 1 ? 'inf' : p.carreraId === 2 ? 'civ' : 'med'];
                        const bgAvatar = p.carreraId === 1 ? 'bg-[#348fe2]' : p.carreraId === 2 ? 'bg-[#00acac]' : 'bg-[#727cb6]';

                        return `
                            <div onclick="switchView('revisar_version', ${p.id})"
                                 class="flex items-center justify-between px-4 py-3 hover:bg-[#f0f3f8] cursor-pointer transition-colors group">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="h-8 w-8 rounded-full ${bgAvatar} text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                        ${carrera.carreraSigla.charAt(0)}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-gray-900 text-xs truncate group-hover:text-[#348fe2] transition-colors">
                                                ${carrera.carreraNombre} (${carrera.carreraSigla})
                                            </h4>
                                            <span class="bg-gray-100 border border-gray-300 text-gray-700 text-[10px] font-bold px-1.5 py-0.2 rounded">
                                                Versión ${p.numeroVersion}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 truncate mt-0.5">
                                            <span class="font-medium text-gray-700">De: ${p.solicitante}</span> &bull; ${p.descripcion}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 shrink-0 ml-4">
                                    <span class="text-[11px] font-medium text-gray-400 tabular-nums">${p.solicitadoEn || 'Reciente'}</span>
                                    ${getBadgeEstado(p.estado)}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

function setInboxFolder(folder) {
    inboxFolder = folder;
    renderLayout();
}

function filtrarInbox(val) {
    busquedaInbox = val;
    renderMainView();
}

// --------------------------------------------------------------------------
// PANTALLA REVISAR VERSIÓN EN VICERRECTORADO (BOTÓN ÚNICO 'Confirmar revisión')
// --------------------------------------------------------------------------
function renderRevisarVersionView(container, u) {
    const prop = propuestas.find(p => p.id === activePropuestaId) || propuestas[0];
    const carrera = USERS[prop.carreraId === 1 ? 'inf' : prop.carreraId === 2 ? 'civ' : 'med'];
    const grupos = rosterGruposPorPropuesta[prop.id] || [];
    const puedeDecidir = prop.estado === 'pendiente';

    container.innerHTML = `
        <div class="max-w-7xl mx-auto space-y-5 text-xs text-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <a href="#" onclick="switchView('bandeja_vicerrectorado'); return false;" class="text-sm font-semibold text-[#007c7c] hover:underline">← Volver a la bandeja</a>
                    <h1 class="text-xl font-bold text-gray-900 mt-2">${carrera.carreraNombre} &bull; Revisión de Versión ${prop.numeroVersion}</h1>
                    <p class="text-sm text-gray-600 mt-0.5">Enviada por <strong>${prop.solicitante}</strong> el ${prop.solicitadoEn || 'hoy'}.</p>
                </div>
                <button onclick="imprimirPropuesta(${prop.id})" class="px-3.5 py-2 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-xs cursor-pointer flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Imprimir Reporte</span>
                </button>
            </div>

            ${prop.observacionGeneral ? `
                <div class="${prop.estado === 'aprobada' ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-amber-300 bg-amber-50 text-amber-900'} border p-4 rounded text-sm font-medium">
                    <strong>${prop.estado === 'aprobada' ? '✓ Decisión del Vicerrectorado:' : 'Observación General Previa:'}</strong> ${prop.observacionGeneral}
                </div>
            ` : ''}

            ${puedeDecidir ? `
                <form id="formRevision" onsubmit="event.preventDefault();" class="space-y-5">
                    <section class="bg-white border border-gray-200 shadow-sm p-4 rounded-lg">
                        <label for="obs_general_input" class="block text-sm font-bold text-gray-900">Observación General para la propuesta</label>
                        <textarea id="obs_general_input" rows="2" class="w-full mt-2 border border-gray-300 rounded p-2 text-xs focus:ring-1 focus:ring-[#00acac] outline-none" placeholder="Visible para el Director cuando la revisión sea devuelta con observaciones.">${prop.observacionGeneral || ''}</textarea>
                    </section>

                    <section class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                        <div class="bg-[#2d353c] text-white px-4 py-2.5 font-bold text-xs flex justify-between items-center">
                            <span>Snapshot de Designaciones Enviadas &bull; Versión ${prop.numeroVersion}</span>

                            <!-- CHECKBOX GLOBAL PARA APROBAR / RECHAZAR TODAS LAS FILAS -->
                            <label class="flex items-center gap-2 bg-[#20252a] px-3 py-1 rounded text-white font-bold text-xs cursor-pointer hover:bg-black/30 transition-colors">
                                <input type="checkbox" id="checkAprobarTodos" onchange="toggleAprobarTodasLasFilas(this.checked)" checked class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                                <span>Aprobar todas las filas</span>
                            </label>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-700 font-bold uppercase border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Docente Asignado</th>
                                        <th class="px-4 py-3">Materia</th>
                                        <th class="px-4 py-3 text-center">Grupo</th>
                                        <th class="px-4 py-3 text-center w-48">Decisión</th>
                                        <th class="px-4 py-3">Observación por Fila</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    ${grupos.map((g, idx) => {
                                        const doc = todosLosDocentesUniversidad.find(d => d.id === g.docenteId);
                                        const esAprobadaPreviamente = g.estado === 'aprobada_previamente';

                                        return `
                                            <tr class="${esAprobadaPreviamente ? 'bg-emerald-50/60' : 'hover:bg-gray-50'}">
                                                <td class="px-4 py-3 font-bold text-gray-900">${doc ? doc.nombre : 'Sin asignar'}</td>
                                                <td class="px-4 py-3"><span class="font-bold text-gray-900">${g.materiaSigla}</span> ${g.materiaNombre}</td>
                                                <td class="px-4 py-3 text-center font-bold text-teal-700">Grupo ${g.codigo}</td>
                                                ${esAprobadaPreviamente ? `
                                                    <td colspan="2" class="px-4 py-3">
                                                        <span class="bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold px-2.5 py-1 rounded inline-flex items-center gap-1">
                                                            ✓ Aprobada previamente (Inalterable)
                                                        </span>
                                                    </td>
                                                ` : `
                                                    <td class="px-4 py-3 min-w-40 text-center">
                                                        <select id="dec_${g.id}" class="select-decision w-full border border-gray-300 rounded px-2 py-1 font-bold text-xs bg-white">
                                                            <option value="aprobada" ${g.estado === 'editable' || g.estado === 'aprobada' ? 'selected' : ''}>Aprobar</option>
                                                            <option value="observada" ${g.estado === 'observada' ? 'selected' : ''}>Observar</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-3 min-w-72">
                                                        <input id="obs_${g.id}" type="text" value="${g.observacion || ''}" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Motivo explicativo si se observa">
                                                    </td>
                                                `}
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- UN SOLO BOTÓN DE ACCIÓN: CONFIRMAR REVISIÓN -->
                    <div class="flex justify-end pt-2">
                        <button onclick="confirmarRevisionUnica(${prop.id})" class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-6 py-2.5 text-xs rounded shadow-md transition-colors cursor-pointer flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Confirmar Revisión</span>
                        </button>
                    </div>
                </form>
            ` : `
                <section class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-[#2d353c] text-white px-4 py-2.5 font-bold text-xs flex justify-between items-center">
                        <span>Snapshot de Versión ${prop.numeroVersion} &bull; Estado: ${prop.estado.toUpperCase()}</span>
                        ${prop.estado === 'aprobada' ? '<span class="bg-emerald-500 text-white px-2 py-0.5 rounded text-[10px] font-bold">✓ Aprobada Oficialmente</span>' : ''}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50 text-gray-700 font-bold uppercase border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3">Docente</th>
                                    <th class="px-4 py-3">Materia</th>
                                    <th class="px-4 py-3">Grupo</th>
                                    <th class="px-4 py-3 text-center">Estado</th>
                                    <th class="px-4 py-3">Observación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                ${grupos.map(g => {
                                    const doc = todosLosDocentesUniversidad.find(d => d.id === g.docenteId);
                                    const esAprobado = g.estado === 'aprobada_previamente' || prop.estado === 'aprobada';
                                    return `
                                        <tr class="${esAprobado ? 'bg-emerald-50/50' : ''}">
                                            <td class="px-4 py-3 font-bold">${doc ? doc.nombre : 'Sin asignar'}</td>
                                            <td class="px-4 py-3"><span class="font-bold">${g.materiaSigla}</span> ${g.materiaNombre}</td>
                                            <td class="px-4 py-3 text-center font-bold text-teal-700">Grupo ${g.codigo}</td>
                                            <td class="px-4 py-3 text-center font-bold">
                                                ${esAprobado ? `
                                                    <span class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold px-2 py-0.5 rounded inline-flex items-center gap-1">
                                                        ✓ Aprobada
                                                    </span>
                                                ` : `
                                                    <span class="bg-rose-100 text-rose-900 border border-rose-300 font-bold px-2 py-0.5 rounded inline-flex items-center gap-1">
                                                        ⚠ Observada
                                                    </span>
                                                `}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">${g.observacion || '-'}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </section>
            `}
        </div>
    `;
}

// TOGGLE SELECT ALL ROWS AS APPROVED OR OBSERVED (SÓLO CAMBIA ESTADO EN PANTALLA, NO ENVÍA)
function toggleAprobarTodasLasFilas(checked) {
    const selects = document.querySelectorAll('.select-decision');
    selects.forEach(s => {
        s.value = checked ? 'aprobada' : 'observada';
    });
}

// UNIFICACIÓN EN UN SOLO BOTÓN 'Confirmar revisión'
function confirmarRevisionUnica(propId) {
    const prop = propuestas.find(p => p.id === propId);
    const grupos = rosterGruposPorPropuesta[prop.id] || [];
    const obsGeneral = document.getElementById('obs_general_input').value;

    let hayObservada = false;

    grupos.forEach(g => {
        if (g.estado !== 'aprobada_previamente') {
            const decSelect = document.getElementById(`dec_${g.id}`);
            const obsInput = document.getElementById(`obs_${g.id}`);
            if (decSelect && obsInput) {
                g.estado = decSelect.value;
                g.observacion = obsInput.value || null;
                if (g.estado === 'observada') {
                    hayObservada = true;
                } else if (g.estado === 'aprobada') {
                    g.estado = 'aprobada_previamente';
                }
            }
        }
    });

    if (hayObservada) {
        prop.estado = 'observada';
        prop.observacionGeneral = obsGeneral || 'Existen materias observadas en la propuesta que requieren corrección.';
        notifications.unshift({
            id: Date.now(),
            text: `Vicerrectorado devolvió la propuesta de ${USERS[prop.carreraId === 1 ? 'inf' : prop.carreraId === 2 ? 'civ' : 'med'].carreraSigla} con observaciones.`,
            time: 'Justo ahora',
            unread: true
        });
        alert('Revisión confirmada: La propuesta ha sido devuelta al Director con observaciones.');
    } else {
        prop.estado = 'aprobada';
        prop.observacionGeneral = obsGeneral || 'Aprobada oficialmente por Vicerrectorado Académico.';
        notifications.unshift({
            id: Date.now(),
            text: `Propuesta de ${USERS[prop.carreraId === 1 ? 'inf' : prop.carreraId === 2 ? 'civ' : 'med'].carreraSigla} APROBADA oficialmente.`,
            time: 'Justo ahora',
            unread: true
        });
        alert('Revisión confirmada: La propuesta y sus materias han sido APROBADAS oficialmente de forma inmutable.');
    }

    switchView('bandeja_vicerrectorado');
}

function crearBorradorVacio() {
    cerrarModal('modalNuevaPropuesta');
    const u = getCurrentUser();
    const newId = Math.max(...propuestas.map(p => p.id), 0) + 1;

    propuestas.push({
        id: newId,
        carreraId: u.careerId,
        descripcion: `Propuesta de Designación Docente I/2026 (Borrador ${newId})`,
        gestion: '2026',
        periodo: 'Primer Período',
        estado: 'borrador',
        numeroVersion: 1,
        solicitante: u.name,
        solicitadoEn: null,
        observacionGeneral: null
    });

    const materiasBase = CATALOGO_MATERIAS_BASE[u.careerId] || [];
    rosterGruposPorPropuesta[newId] = materiasBase.map((m, idx) => ({
        id: newId * 1000 + idx + 1,
        materiaId: m.materiaId,
        materiaNombre: m.materiaNombre,
        materiaSigla: m.materiaSigla,
        codigo: m.codigo,
        horas: m.horas,
        docenteId: null,
        estado: 'editable',
        observacion: null
    }));

    activePropuestaId = newId;
    alert(`Se ha creado una nueva propuesta vacía (Borrador ${newId}). Puedes designar materias a los docentes desde la tabla.`);
    switchView('editor_carrera', newId);
}

function renderModales(u) {
    return `
        <div id="modalNuevaPropuesta" class="hidden fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl border border-gray-200 max-w-md w-full overflow-hidden text-xs">
                <div class="bg-[#2d353c] text-white px-4 py-3 font-bold flex justify-between items-center">
                    <span>Nueva Propuesta de Designación Docente</span>
                    <button onclick="cerrarModal('modalNuevaPropuesta')" class="text-gray-400 hover:text-white text-base cursor-pointer">×</button>
                </div>
                <div class="p-5 space-y-4">
                    <p class="text-gray-600">Selecciona cómo deseas inicializar el borrador para la carrera <strong>${u.carreraNombre}</strong>:</p>
                    <div class="space-y-2">
                        <button onclick="crearBorradorVacio()" class="w-full text-left p-3 border border-gray-200 hover:border-[#00acac] hover:bg-teal-50/50 rounded-lg transition-colors cursor-pointer group">
                            <span class="font-bold text-gray-900 block group-hover:text-[#00acac]">Crear propuesta vacía</span>
                            <span class="text-[11px] text-gray-500 block">Comenzar desde cero con 0 materias asignadas en el borrador.</span>
                        </button>
                        <button onclick="cerrarModal('modalNuevaPropuesta'); switchView('importar_propuesta')" class="w-full text-left p-3 border border-gray-200 hover:border-[#348fe2] hover:bg-blue-50/50 rounded-lg transition-colors cursor-pointer group">
                            <span class="font-bold text-gray-900 block group-hover:text-[#348fe2]">Importar de gestión anterior</span>
                            <span class="text-[11px] text-gray-500 block">Previsualizar y copiar asignaciones históricas de una gestión previa.</span>
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200 text-right">
                    <button onclick="cerrarModal('modalNuevaPropuesta')" class="px-3 py-1.5 bg-gray-200 text-gray-700 font-bold rounded hover:bg-gray-300 cursor-pointer">Cancelar</button>
                </div>
            </div>
        </div>

        <div id="modalObservaciones" class="hidden fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl border border-gray-200 max-w-lg w-full overflow-hidden text-xs">
                <div class="bg-[#2d353c] text-white px-4 py-3 font-bold flex justify-between items-center">
                    <span id="modalObsHeaderTitle">Decisión de Vicerrectorado</span>
                    <button onclick="cerrarModal('modalObservaciones')" class="text-gray-400 hover:text-white text-base cursor-pointer">×</button>
                </div>
                <div class="p-5 space-y-3">
                    <div id="modalObsContent" class="p-3 rounded font-medium text-xs">
                        Sin información registrada.
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200 text-right">
                    <button onclick="cerrarModal('modalObservaciones')" class="px-3 py-1.5 bg-gray-800 text-white font-bold rounded cursor-pointer">Cerrar</button>
                </div>
            </div>
        </div>

        <div id="modalAsignarDocente" class="hidden fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl border border-gray-200 max-w-md w-full overflow-hidden text-xs">
                <div class="bg-[#2d353c] text-white px-4 py-3 font-bold flex justify-between items-center">
                    <span id="modalDocenteNombre">Designar Materias a Docente</span>
                    <button onclick="cerrarModal('modalAsignarDocente')" class="text-gray-400 hover:text-white text-base cursor-pointer">×</button>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-gray-600">Selecciona las materias y grupos de la carrera que dictará este docente:</p>
                    <div id="modalDocenteGruposList" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200 flex justify-between items-center">
                    <span id="modalDocenteHorasTotal" class="font-bold text-gray-700 tabular-nums">Total Carga Horaria: 0 hrs</span>
                    <button onclick="guardarAsignacionDocente()" class="px-4 py-1.5 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded cursor-pointer">Guardar Designación</button>
                </div>
            </div>
        </div>
    `;
}

function abrirModalNuevaPropuesta() {
    document.getElementById('modalNuevaPropuesta').classList.remove('hidden');
}

function cerrarModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function verObservacionesPropuesta(id) {
    const p = propuestas.find(item => item.id === id);
    const contentEl = document.getElementById('modalObsContent');
    const headerTitle = document.getElementById('modalObsHeaderTitle');

    if (p) {
        if (p.estado === 'aprobada') {
            headerTitle.textContent = 'Decisión de Aprobación Oficial';
            contentEl.className = 'p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 font-medium rounded';
            contentEl.innerHTML = `<strong>✓ Decisión Oficial:</strong> ${p.observacionGeneral || 'Propuesta aprobada oficialmente por Vicerrectorado Académico.'}`;
        } else {
            headerTitle.textContent = 'Observaciones del Vicerrectorado';
            contentEl.className = 'p-3 bg-rose-50 border border-rose-200 text-rose-900 font-medium rounded';
            contentEl.innerHTML = `<strong>⚠ Observación:</strong> ${p.observacionGeneral || 'Existen materias observadas que requieren corrección.'}`;
        }
    }
    document.getElementById('modalObservaciones').classList.remove('hidden');
}

function enviarPropuestaVicerrectorado(id) {
    const p = propuestas.find(item => item.id === id);
    if (p) {
        if (p.estado === 'observada') {
            p.numeroVersion += 1;
            const grupos = rosterGruposPorPropuesta[p.id] || [];
            grupos.forEach(g => {
                if (g.estado === 'editable') {
                    g.estado = 'aprobada_previamente';
                }
            });
        }
        p.estado = 'pendiente';
        p.solicitadoEn = '31/07/2026 ' + new Date().toLocaleTimeString().slice(0,5);
        notifications.unshift({
            id: Date.now(),
            text: `Propuesta Versión ${p.numeroVersion} de ${USERS[p.carreraId === 1 ? 'inf' : p.carreraId === 2 ? 'civ' : 'med'].carreraSigla} enviada a revisión.`,
            time: 'Justo ahora',
            unread: true
        });
        alert(`La propuesta Versión ${p.numeroVersion} ha sido enviada exitosamente a Vicerrectorado.`);
        switchView('lista_director');
    }
}

function retirarEnvioPropuesta(id) {
    const p = propuestas.find(item => item.id === id);
    if (p) {
        p.estado = 'borrador';
        p.solicitadoEn = null;
        alert('Se ha retirado la versión pendiente de revisión. La propuesta vuelve a estado editable.');
        renderLayout();
    }
}

function abrirModalAsignarDocente(docenteId) {
    activeDocenteModalId = docenteId;
    const u = getCurrentUser();
    const doc = todosLosDocentesUniversidad.find(d => d.id === docenteId);
    const grupos = rosterGruposPorPropuesta[activePropuestaId] || [];

    document.getElementById('modalDocenteNombre').textContent = `Designar Materias: ${doc.nombre}`;
    const listContainer = document.getElementById('modalDocenteGruposList');

    listContainer.innerHTML = grupos.map(g => {
        const isChecked = g.docenteId === docenteId;
        const isDisabled = g.estado === 'aprobada_previamente';

        return `
            <label class="flex items-center justify-between p-2 rounded border border-gray-200 hover:bg-gray-50 cursor-pointer ${isDisabled ? 'opacity-60 bg-gray-100' : ''}">
                <div class="flex items-center gap-2">
                    <input type="checkbox" value="${g.id}" ${isChecked ? 'checked' : ''} ${isDisabled ? 'disabled' : ''} onchange="recalcularModalHoras(${doc.horasOtrasCarreras})" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                    <div>
                        <span class="font-bold text-gray-900">${g.materiaSigla}</span> &mdash; ${g.materiaNombre}
                        <span class="text-teal-700 font-bold">(Grupo ${g.codigo})</span>
                        ${isDisabled ? '<span class="text-[10px] text-emerald-800 font-bold ml-1">✓ Aprobada previamente</span>' : ''}
                    </div>
                </div>
                <span class="font-bold text-gray-700 tabular-nums">${g.horas} hrs</span>
            </label>
        `;
    }).join('');

    recalcularModalHoras(doc.horasOtrasCarreras);
    document.getElementById('modalAsignarDocente').classList.remove('hidden');
}

function recalcularModalHoras(horasOtras = 0) {
    const checks = document.querySelectorAll('#modalDocenteGruposList input[type="checkbox"]:checked');
    let totalLocal = 0;
    const grupos = rosterGruposPorPropuesta[activePropuestaId] || [];

    checks.forEach(c => {
        const g = grupos.find(item => item.id === parseInt(c.value));
        if (g) totalLocal += g.horas;
    });

    const totalGlobal = totalLocal + horasOtras;
    document.getElementById('modalDocenteHorasTotal').textContent = `Total Carga Horaria: ${totalGlobal} hrs (${totalLocal}h local + ${horasOtras}h otras)`;
}

function guardarAsignacionDocente() {
    const checks = document.querySelectorAll('#modalDocenteGruposList input[type="checkbox"]:checked');
    const selectedIds = Array.from(checks).map(c => parseInt(c.value));
    const grupos = rosterGruposPorPropuesta[activePropuestaId] || [];

    grupos.forEach(g => {
        if (g.estado !== 'aprobada_previamente') {
            if (selectedIds.includes(g.id)) {
                g.docenteId = activeDocenteModalId;
                if (g.estado === 'observada') {
                    g.estado = 'editable';
                    g.observacion = null;
                }
            } else if (g.docenteId === activeDocenteModalId) {
                g.docenteId = null;
            }
        }
    });

    cerrarModal('modalAsignarDocente');
    renderLayout();
}

document.addEventListener('DOMContentLoaded', () => {
    renderLayout();
});
