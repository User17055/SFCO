let clientes = [];
const modal = document.getElementById('modal-cliente');
const formulario = document.getElementById('form-cliente');

function formatarContato(cliente) {
  return [cliente.telefone, cliente.email, cliente.cpf].filter(Boolean).map(escapar).join('<br>') || '-';
}

function renderClientes() {
  const termo = document.getElementById('busca-clientes').value.toLocaleLowerCase('pt-BR');
  const filtrados = clientes.filter(x => [x.nome, x.telefone, x.cpf, x.email, x.planos].join(' ').toLocaleLowerCase('pt-BR').includes(termo));
  document.getElementById('total-clientes').textContent = `${filtrados.length} cliente(s)`;
  document.getElementById('clients-body').innerHTML = filtrados.length ? filtrados.map(x => `
    <tr><td><strong>${escapar(x.nome)}</strong><div class="text-xs text-slate-500">${escapar(x.planos)}</div></td>
    <td>${formatarContato(x)}</td><td>${x.totalPets}</td><td>${x.planosAtivos}</td><td>${formatCurrency(x.projecao)}</td>
    <td class="whitespace-nowrap"><button class="acao" data-editar="${x.id}">Editar</button><button class="acao perigo" data-excluir="${x.id}">Excluir</button></td></tr>`).join('') : '<tr><td colspan="6">Nenhum cliente encontrado.</td></tr>';
}

function abrirCliente(cliente = {}) {
  formulario.reset();
  ['id', 'nome', 'email', 'telefone', 'cpf', 'observacoes'].forEach(campo => { formulario.elements[campo].value = cliente[campo] ?? ''; });
  document.getElementById('titulo-cliente').textContent = cliente.id ? 'Editar cliente' : 'Novo cliente';
  document.getElementById('erro-cliente').textContent = '';
  modal.showModal();
}

async function carregarClientes() {
  const resposta = await fetch('api/clientes.php', { cache: 'no-store' });
  const dados = await resposta.json();
  if (!resposta.ok) throw new Error(dados.mensagem);
  clientes = dados.clientes; renderClientes();
}

document.getElementById('novo-cliente').addEventListener('click', () => abrirCliente());
document.getElementById('busca-clientes').addEventListener('input', renderClientes);
document.querySelectorAll('[data-fechar]').forEach(x => x.addEventListener('click', () => modal.close()));
document.getElementById('clients-body').addEventListener('click', async evento => {
  const editar = evento.target.closest('[data-editar]');
  const excluir = evento.target.closest('[data-excluir]');
  if (editar) abrirCliente(clientes.find(x => x.id === Number(editar.dataset.editar)));
  if (excluir && confirm('Excluir este cliente, seus pets e planos?')) {
    await enviarJson('api/excluir-pessoa.php', { id: Number(excluir.dataset.excluir) });
    await carregarClientes();
  }
});
formulario.addEventListener('submit', async evento => {
  evento.preventDefault();
  const dados = Object.fromEntries(new FormData(formulario));
  dados.id = Number(dados.id || 0);
  try { await enviarJson('api/salvar-pessoa.php', dados); modal.close(); await carregarClientes(); }
  catch (erro) { document.getElementById('erro-cliente').textContent = erro.message; }
});
document.addEventListener('DOMContentLoaded', () => carregarClientes().catch(erro => { document.getElementById('clients-body').innerHTML = `<tr><td colspan="6">${escapar(erro.message)}</td></tr>`; }));
