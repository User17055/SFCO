/**
 * Comportamentos do layout (sidebar e topbar).
 * A estrutura HTML da sidebar/topbar ja existe pronta no index.html -
 * este arquivo so adiciona interatividade a ela.
 */

function garantirTelaCarregamento() {
  let tela = document.getElementById('tela-carregamento');
  if (tela || !document.body) return tela;

  tela = document.createElement('div');
  tela.id = 'tela-carregamento';
  tela.className = 'tela-carregamento tela-carregamento-oculta';
  tela.setAttribute('role', 'status');
  tela.setAttribute('aria-live', 'polite');
  tela.innerHTML = `
    <div class="tela-carregamento-conteudo">
      <div class="tela-carregamento-spinner" aria-hidden="true"></div>
      <span id="tela-carregamento-titulo" class="sr-only">Carregando dados...</span>
      <span id="tela-carregamento-detalhe" class="sr-only">Aguarde um instante.</span>
    </div>`;
  document.body.appendChild(tela);
  return tela;
}

window.mostrarCarregamento = function (titulo = 'Carregando dados...', detalhe = 'Aguarde um instante.') {
  const tela = garantirTelaCarregamento();
  if (!tela) return;
  document.getElementById('tela-carregamento-titulo').textContent = titulo;
  document.getElementById('tela-carregamento-detalhe').textContent = detalhe;
  tela.classList.remove('tela-carregamento-oculta');
  document.body.setAttribute('aria-busy', 'true');
};

window.ocultarCarregamento = function () {
  const tela = document.getElementById('tela-carregamento');
  if (tela) tela.classList.add('tela-carregamento-oculta');
  document.body.removeAttribute('aria-busy');
};

window.estabilizarGrafico = function (grafico) {
  if (!grafico) return;
  requestAnimationFrame(() => requestAnimationFrame(() => {
    grafico.resize();
    grafico.update('none');
  }));
};

document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.barra-lateral');
  const mainArea = document.getElementById('main-area');

  // Abre/fecha a sidebar no mobile ao clicar no botao de menu
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('aberta');
    });
  }

  // Fecha a sidebar (mobile) ao clicar fora dela
  if (sidebar && mainArea) {
    mainArea.addEventListener('click', (event) => {
      const sidebarEstaAberta = sidebar.classList.contains('aberta');
      const cliqueForaDaSidebar = !sidebar.contains(event.target);
      if (sidebarEstaAberta && cliqueForaDaSidebar) {
        sidebar.classList.remove('aberta');
      }
    });
  }

  // Desenha os icones (lucide) a partir dos atributos data-lucide do HTML
  if (window.lucide) lucide.createIcons();
});

async function carregarUsuarioLayout() {
  if (!document.querySelector('[data-usuario-nome]')) return;

  try {
    const resposta = await fetch('api/usuario.php', {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    const resultado = await resposta.json();
    if (!resposta.ok) return;

    document.querySelectorAll('[data-usuario-nome]').forEach((elemento) => {
      elemento.textContent = resultado.usuario.nome;
    });
    document.querySelectorAll('[data-usuario-email]').forEach((elemento) => {
      elemento.textContent = resultado.usuario.email;
    });
    document.querySelectorAll('[data-usuario-iniciais]').forEach((elemento) => {
      elemento.textContent = resultado.usuario.iniciais;
    });
  } catch (_) {
    // Os endpoints de cada pagina tratam uma eventual sessao expirada.
  }
}

document.addEventListener('DOMContentLoaded', carregarUsuarioLayout);
