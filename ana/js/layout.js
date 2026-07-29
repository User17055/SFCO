/**
 * Sidebar e navbar compartilhadas, injetadas em toda pagina.
 * Equivalente estatico dos componentes <Sidebar /> e <Navbar /> do layout React.
 */

const NAV_ITEMS = [
  { href: 'index.html', label: 'Dashboard', icon: 'layout-dashboard' },
  { href: 'clientes.html', label: 'Clientes', icon: 'users' },
  { href: 'pets.html', label: 'Pets', icon: 'paw-print' },
  { href: 'planos.html', label: 'Planos', icon: 'clipboard-list' },
];

function currentPageFile() {
  const path = window.location.pathname.split('/').pop();
  return path === '' ? 'index.html' : path;
}

function renderSidebar() {
  const current = currentPageFile();
  const links = NAV_ITEMS.map(item => `
    <a href="${item.href}" class="link-menu ${item.href === current ? 'ativo' : ''}">
      <i data-lucide="${item.icon}"></i>
      <span>${item.label}</span>
    </a>
  `).join('');

  return `
    <div class="px-4 pt-6 pb-3">
      <span class="font-extrabold text-2xl tracking-tight">
        <span style="color:var(--color-accent);">Tudo</span><span class="text-white">Pra</span><span style="color:var(--color-accent);">Pet</span>
      </span>
    </div>

    <div class="px-3 pb-3">
      <div class="relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:var(--color-primary);"></i>
        <input type="text" placeholder="Buscar clientes, pets, planos..."
          class="w-full pl-9 pr-3 py-2 text-sm rounded-lg outline-none transition-shadow focus:ring-2"
          style="background:#ffffff; color:var(--color-text-primary); border:1px solid rgba(255,255,255,0.16); --tw-ring-color:rgba(241,199,68,0.6);">
      </div>
    </div>

    <div class="painel-menu">
      <nav class="flex-1 px-4 pt-4 pb-2 overflow-y-auto">
        <p class="text-[11px] font-semibold uppercase tracking-wider mb-1 text-slate-400">Menu</p>
        <div>
          ${links}
        </div>
      </nav>
      <div class="p-3 border-t" style="border-color:var(--color-border);">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg cursor-pointer transition-colors hover:bg-slate-100">
          ${renderAvatar('São Francisco', 34)}
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-900 truncate">São Francisco</p>
            <p class="text-xs truncate text-slate-500">sao@francisco.com</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderTopbar() {
  return `
    <div class="h-16 flex items-center justify-between gap-4 px-4 lg:px-8">
      <div class="flex items-center gap-3 min-w-0">
        <button id="sidebar-toggle" class="botao-icone lg:hidden">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <button class="botao-icone relative">
          <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
          <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full" style="background:#dc2626;"></span>
        </button>
        <button class="botao-icone">
          <i data-lucide="settings" class="w-[18px] h-[18px]"></i>
        </button>
        <div class="w-px h-6 bg-slate-200 mx-1 hidden sm:block"></div>
        <button class="hidden sm:flex items-center gap-2 pl-1 pr-3 py-1 rounded-lg hover:bg-slate-100 transition-colors">
          ${renderAvatar('Andre Junior', 30)}
          <span class="text-sm font-medium text-slate-700">Andre</span>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
        </button>
      </div>
    </div>
  `;
}

/** Gera o cabecalho de titulo da pagina, exibido na area branca de conteudo */
function renderPageHeader(title, subtitle) {
  return `
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-slate-900">${title}</h1>
      ${subtitle ? `<p class="text-sm text-slate-500 mt-1">${subtitle}</p>` : ''}
    </div>
  `;
}

/** Monta o shell da pagina (sidebar + topbar) e devolve o container onde o conteudo da pagina deve ser inserido */
function mountLayout({ title, subtitle }) {
  document.getElementById('sidebar-root').innerHTML = renderSidebar();
  document.getElementById('topbar-root').innerHTML = renderTopbar();

  const pageHeaderRoot = document.getElementById('page-header-root');
  if (pageHeaderRoot) pageHeaderRoot.innerHTML = title ? renderPageHeader(title, subtitle) : '';

  const toggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.barra-lateral');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('aberta'));
    document.getElementById('main-area').addEventListener('click', (e) => {
      if (sidebar.classList.contains('aberta') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('aberta');
      }
    });
  }

  if (window.lucide) lucide.createIcons();
}
