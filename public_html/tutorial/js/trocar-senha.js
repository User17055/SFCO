function mostrarErro(campo, mensagem) {
  const wrapper = campo.closest('.campo-form');
  wrapper.classList.toggle('campo-invalido', Boolean(mensagem));
  wrapper.querySelector('.campo-erro').textContent = mensagem;
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) lucide.createIcons();

  const form = document.getElementById('form-trocar-senha');
  const novaSenha = document.getElementById('campo-nova-senha');
  const confirmacao = document.getElementById('campo-confirmar-senha');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    mostrarErro(novaSenha, novaSenha.value.length >= 8
      ? ''
      : 'Use pelo menos 8 caracteres.');
    mostrarErro(confirmacao, novaSenha.value === confirmacao.value
      ? ''
      : 'As senhas não conferem.');

    if (novaSenha.value.length < 8 || novaSenha.value !== confirmacao.value) return;

    const botao = form.querySelector('[type="submit"]');
    const original = botao.innerHTML;
    botao.disabled = true;
    botao.textContent = 'Salvando...';

    try {
      const resposta = await fetch('api/alterar-senha.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          novaSenha: novaSenha.value,
          confirmacao: confirmacao.value,
        }),
      });
      const resultado = await resposta.json();
      if (!resposta.ok) throw new Error(resultado.mensagem);

      sessionStorage.setItem('tpp-auth', '1');
      window.location.href = 'index.html';
    } catch (erro) {
      mostrarErro(confirmacao, erro.message || 'Não foi possível alterar a senha.');
      if ((erro.message || '').includes('Sessao expirada')) {
        setTimeout(() => { window.location.href = 'login.html'; }, 1500);
      }
    } finally {
      botao.disabled = false;
      botao.innerHTML = original;
      if (window.lucide) lucide.createIcons();
    }
  });
});
