/**
 * Autenticacao ficticia (somente no navegador, via sessionStorage).
 * Nao ha backend: o guard de acesso de cada pagina (script inline no <head>)
 * ja redireciona para login.html quando o flag abaixo nao esta presente.
 * Este arquivo so cuida do botao de sair, presente na barra lateral.
 */

const CHAVE_AUTH = 'tpp-auth';

async function sair() {
  try {
    await fetch('api/logout.php', { method: 'POST', keepalive: true });
  } catch (_) {
    // O flag local ainda e removido caso a rede esteja indisponivel.
  }
  sessionStorage.removeItem(CHAVE_AUTH);
  window.location.href = 'login.html';
}

document.addEventListener('DOMContentLoaded', () => {
  const botaoSair = document.getElementById('botao-sair');
  if (botaoSair) {
    botaoSair.addEventListener('click', sair);
  }
});
