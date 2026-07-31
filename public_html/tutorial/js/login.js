/**
 * Logica da tela de login.
 * Autenticacao pelo PHP e pela tabela usuarios do MySQL.
 * Contas marcadas para troca de senha sao direcionadas para a tela obrigatoria
 * antes de receber o flag que libera as paginas do painel.
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

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(campoEmail.value.trim());
    definirErroCampoLogin(campoEmail, emailValido ? '' : 'Informe um e-mail valido.');

    const senhaValida = campoSenha.value.length > 0;
    definirErroCampoLogin(campoSenha, senhaValida ? '' : 'Informe sua senha.');

    if (!emailValido || !senhaValida) return;

    const botaoEntrar = form.querySelector('[type="submit"]');
    const conteudoOriginal = botaoEntrar.innerHTML;
    botaoEntrar.disabled = true;
    botaoEntrar.textContent = 'Entrando...';

    try {
      const resposta = await fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          email: campoEmail.value.trim(),
          senha: campoSenha.value,
        }),
      });
      const resultado = await resposta.json();

      if (!resposta.ok) {
        throw new Error(resultado.mensagem || 'Nao foi possivel entrar.');
      }

      if (resultado.trocarSenha) {
        sessionStorage.removeItem('tpp-auth');
        window.location.href = 'trocar-senha.html';
        return;
      }

      sessionStorage.setItem('tpp-auth', '1');
      window.location.href = 'index.html';
    } catch (erro) {
      definirErroCampoLogin(campoSenha, erro.message);
    } finally {
      botaoEntrar.disabled = false;
      botaoEntrar.innerHTML = conteudoOriginal;
      if (window.lucide) lucide.createIcons();
    }
  });
});
