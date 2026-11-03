/**
 * SISTEMA DE REGISTRO CLÍNICO DE PACIENTES - CORE JAVASCRIPT
 * Alianza Fundación ALES & IPS Restaurando Vidas
 */

// --- 1. Detección Dinámica de Ruta Base del Proyecto ---
function getAppBase() {
  const path = window.location.pathname;
  const subdirs = ['/pacientes/', '/profesionales/', '/ips/', '/estadisticas/', '/perfil/', '/api/'];
  for (const sub of subdirs) {
    const idx = path.indexOf(sub);
    if (idx !== -1) {
      return path.substring(0, idx);
    }
  }
  const lastSlash = path.lastIndexOf('/');
  if (lastSlash > 0) {
    return path.substring(0, lastSlash);
  }
  return '';
}

function appUrl(relPath) {
  const base = getAppBase();
  const cleanRel = relPath.startsWith('/') ? relPath : '/' + relPath;
  return base + cleanRel;
}

// --- 2. Sanitización Anti-XSS para renderizado seguro en HTML ---
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// --- 3. Sistema de Notificaciones Toast ---
function showToast(message, type = 'success') {
  let container = document.getElementById('global-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'global-toast-container';
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `custom-toast toast-${type}`;

  const iconMap = {
    success: 'bx-check-circle',
    error: 'bx-error-circle',
    warning: 'bx-error',
    info: 'bx-info-circle'
  };

  toast.innerHTML = `
    <i class="bx ${iconMap[type] || 'bx-bell'} fs-4"></i>
    <div class="flex-grow-1 font-weight-medium">${escapeHtml(message)}</div>
    <button type="button" class="btn-close btn-close-white ms-2" aria-label="Cerrar"></button>
  `;

  toast.querySelector('.btn-close').onclick = () => {
    toast.remove();
  };

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-10px)';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// --- 4. Peticiones API Fetch Seguras ---
async function apiFetch(url, options = {}) {
  const config = {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(options.headers || {})
    },
    ...options
  };

  let targetUrl = url;
  if (url.startsWith('/api/')) {
    const sub = url.substring('/api/'.length);
    targetUrl = appUrl('/api/index.php/' + sub);
  } else if (url.startsWith('/api')) {
    targetUrl = appUrl('/api/index.php');
  }

  try {
    const res = await fetch(targetUrl, config);
    const contentType = res.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const data = isJson ? await res.json() : null;

    if (!res.ok) {
      const errorMsg = data?.error || `Error del servidor (${res.status})`;
      throw new Error(errorMsg);
    }
    return data;
  } catch (err) {
    throw err;
  }
}

// --- 5. Manejo de Tema (Claro / Oscuro) ---
function initTheme() {
  const savedTheme = localStorage.getItem('rp_theme') || 'dark';
  document.documentElement.setAttribute('data-bs-theme', savedTheme);
  updateThemeIcon(savedTheme);
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-bs-theme') || 'dark';
  const newTheme = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-bs-theme', newTheme);
  localStorage.setItem('rp_theme', newTheme);
  updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
  const icon = document.getElementById('theme-toggle-icon');
  if (icon) {
    icon.className = theme === 'dark' ? 'bx bx-sun fs-5' : 'bx bx-moon fs-5';
  }
}

// --- 6. Renderizado Dinámico de Navegación por Rol ---
function renderAppLayout(user, activePage = '') {
  const sidebarNav = document.getElementById('sidebar-navigation');
  if (sidebarNav) {
    let html = '';

    if (user.rol === 'ADMIN') {
      html += `
        <div class="nav-section-title">Administración Clínica</div>
        <a href="${appUrl('/dashboard.html')}" class="sidebar-link ${activePage === 'dashboard' ? 'active' : ''}">
          <div class="icon-box icon-primary"><i class="bx bxs-dashboard"></i></div>
          <span>Panel principal</span>
        </a>
        <a href="${appUrl('/ips/index.html')}" class="sidebar-link ${activePage === 'ips' ? 'active' : ''}">
          <div class="icon-box icon-warning"><i class="bx bxs-business"></i></div>
          <span>Gestión de IPS</span>
        </a>
        <a href="${appUrl('/profesionales/index.html')}" class="sidebar-link ${activePage === 'profesionales' ? 'active' : ''}">
          <div class="icon-box icon-purple"><i class="bx bxs-user-badge"></i></div>
          <span>Profesionales</span>
        </a>
        <a href="${appUrl('/pacientes/consultar.html')}" class="sidebar-link ${activePage === 'consultar' ? 'active' : ''}">
          <div class="icon-box icon-info"><i class="bx bxs-folder-open"></i></div>
          <span>Consultar pacientes</span>
        </a>
        <a href="${appUrl('/pacientes/exportar.html')}" class="sidebar-link ${activePage === 'exportar' ? 'active' : ''}">
          <div class="icon-box icon-success"><i class="bx bxs-file-export"></i></div>
          <span>Exportar registros</span>
        </a>
      `;
    } else if (user.rol === 'PROFESIONAL') {
      html += `
        <div class="nav-section-title">Atención Médica</div>
        <a href="${appUrl('/estadisticas/index.html')}" class="sidebar-link ${activePage === 'estadisticas' ? 'active' : ''}">
          <div class="icon-box icon-info"><i class="bx bxs-bar-chart-alt-2"></i></div>
          <span>Mis estadísticas</span>
        </a>
        <a href="${appUrl('/pacientes/registrar.html')}" class="sidebar-link ${activePage === 'registrar' ? 'active' : ''}">
          <div class="icon-box icon-rose"><i class="bx bxs-user-plus"></i></div>
          <span>Registrar paciente</span>
        </a>
      `;
    }

    html += `
      <div class="nav-section-title">Cuenta y Sistema</div>
      <a href="${appUrl('/perfil/index.html')}" class="sidebar-link ${activePage === 'perfil' ? 'active' : ''}">
        <div class="icon-box icon-primary"><i class="bx bx-user-circle"></i></div>
        <span>Mi Perfil</span>
      </a>
      <a href="${appUrl('/index.html')}" class="sidebar-link">
        <div class="icon-box icon-info"><i class="bx bx-globe"></i></div>
        <span>Portal Institucional</span>
      </a>
    `;

    sidebarNav.innerHTML = html;
  }

  // Configurar Perfil de Usuario en Navbar
  const userProfileContainer = document.getElementById('user-profile-widget');
  if (userProfileContainer) {
    const initials = user.nombre ? user.nombre.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() : 'RP';
    const avatarHtml = user.foto
      ? `<img src="${user.foto}" alt="${escapeHtml(user.nombre)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
      : escapeHtml(initials);

    userProfileContainer.innerHTML = `
      <div class="dropdown">
        <button class="btn p-0 border-0 d-flex align-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="avatar-circle position-relative">
            ${avatarHtml}
            <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle" style="width: 10px; height: 10px;"></span>
          </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" style="min-width: 220px; border-radius: 14px;">
          <li class="px-3 py-2 border-bottom">
            <div class="fw-bold text-truncate">${escapeHtml(user.nombre)}</div>
            <div class="text-muted small">${user.rol === 'ADMIN' ? 'Administrador General' : escapeHtml(user.especialidadTexto || 'Personal Clínico')}</div>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 py-2 rounded-2 mt-1" href="${appUrl('/perfil/index.html')}">
              <i class="bx bx-user fs-5 text-primary"></i>
              <span>Mi Perfil</span>
            </a>
          </li>
          <li>
            <button class="dropdown-item text-danger d-flex align-items-center gap-2 py-2 rounded-2" onclick="logout()">
              <i class="bx bx-log-out fs-5"></i>
              <span>Cerrar sesión</span>
            </button>
          </li>
        </ul>
      </div>
    `;
  }
}

// --- 7. Verificación y Protección de Ruta por Rol ---
async function requireUserAuth(requiredRole = null) {
  try {
    const user = await apiFetch('/api/me');
    if (!user) {
      window.location.href = appUrl('/login.html?redirect=' + encodeURIComponent(window.location.pathname));
      return null;
    }

    if (requiredRole && user.rol !== requiredRole) {
      window.location.href = user.rol === 'ADMIN' ? appUrl('/dashboard.html') : appUrl('/pacientes/registrar.html');
      return null;
    }

    return user;
  } catch (e) {
    window.location.href = appUrl('/login.html?redirect=' + encodeURIComponent(window.location.pathname));
    return null;
  }
}

// --- 8. Cerrar Sesión ---
async function logout() {
  try {
    await apiFetch('/api/logout', { method: 'POST' });
  } catch (e) {
    // Ignorar si ya expiró
  }
  showToast('Has cerrado sesión correctamente.', 'info');
  setTimeout(() => {
    window.location.href = appUrl('/login.html');
  }, 400);
}

// Inicializar tema y control móvil de sidebar
document.addEventListener('DOMContentLoaded', () => {
  initTheme();

  const toggleBtn = document.getElementById('sidebar-toggle-btn');
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');

  if (toggleBtn && sidebar) {
    toggleBtn.onclick = () => {
      sidebar.classList.toggle('show');
      if (backdrop) backdrop.classList.toggle('show');
    };
  }

  if (backdrop && sidebar) {
    backdrop.onclick = () => {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    };
  }
});
