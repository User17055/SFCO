/**
 * Controle de navegacao no cliente. A autenticacao real e a autorizacao dos
 * endpoints sao feitas pela sessao PHP; este flag evita mostrar as paginas
 * durante o redirecionamento e o logout tambem encerra a sessao no servidor.
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
