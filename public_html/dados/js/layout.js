/**
 * Comportamentos do layout (sidebar e topbar).
 * A estrutura HTML da sidebar/topbar ja existe pronta no index.html -
 * este arquivo so adiciona interatividade a ela.
 */

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
