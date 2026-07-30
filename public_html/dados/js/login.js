/**
 * Logica da tela de login.
 * Autenticacao ficticia: qualquer e-mail valido e senha preenchida sao aceitos.
 * Ao autenticar com sucesso, grava um flag em sessionStorage e libera o acesso
 * ao restante do site (o guard em cada pagina checa esse mesmo flag).
 */

function definirErroCampoLogin(campo, mensagem) {
  const wrapper = campo.closest('.campo-form');
  const erro = wrapper.querySelector('.campo-erro');
  if (mensagem) {
    wrapper.classList.add('campo-invalido');
    erro.textContent = mensagem;
  } else {
    wrapper.classList.remove('campo-invalido');
    erro.textContent = '';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();

  const form = document.getElementById('form-login');
  const campoEmail = document.getElementById('campo-login-email');
  const campoSenha = document.getElementById('campo-login-senha');
  const botaoMostrarSenha = document.getElementById('botao-mostrar-senha');

  botaoMostrarSenha.addEventListener('click', () => {
    const mostrando = campoSenha.type === 'text';
    campoSenha.type = mostrando ? 'password' : 'text';
    botaoMostrarSenha.innerHTML = mostrando
      ? '<i data-lucide="eye" class="w-4 h-4"></i>'
      : '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    if (window.lucide) lucide.createIcons();
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();

    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(campoEmail.value.trim());
    definirErroCampoLogin(campoEmail, emailValido ? '' : 'Informe um e-mail valido.');

    const senhaValida = campoSenha.value.length > 0;
    definirErroCampoLogin(campoSenha, senhaValida ? '' : 'Informe sua senha.');

    if (!emailValido || !senhaValida) return;

    sessionStorage.setItem('tpp-auth', '1');
    window.location.href = 'index.html';
  });
});
