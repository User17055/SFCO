let pets = [];
let tutores = [];
const modalPet = document.getElementById('modal-pet');
const formPet = document.getElementById('form-pet');

function dadosPet(pet) {
  return [pet.especie, pet.raca, pet.sexo, pet.idade !== null ? `${pet.idade} ano(s)` : '', pet.peso !== null ? `${pet.peso} kg` : ''].filter(Boolean).map(escapar).join(' · ') || '-';
}
function renderPets() {
  const termo = document.getElementById('busca-pets').value.toLocaleLowerCase('pt-BR');
  const lista = pets.filter(x => [x.nome, x.tutor, x.planos, x.especie, x.raca].join(' ').toLocaleLowerCase('pt-BR').includes(termo));
  document.getElementById('total-pets').textContent = `${lista.length} pet(s)`;
  document.getElementById('pets-body').innerHTML = lista.length ? lista.map(x => `<tr>
    <td><strong>${escapar(x.nome)}</strong></td><td>${escapar(x.tutor)}</td><td>${dadosPet(x)}</td>
    <td>${x.planosAtivos}<div class="text-xs text-slate-500">${escapar(x.planos)}</div></td><td>${formatCurrency(x.projecao)}</td>
    <td class="whitespace-nowrap"><button class="acao" data-editar="${x.id}">Editar</button><button class="acao perigo" data-excluir="${x.id}">Excluir</button></td></tr>`).join('') : '<tr><td colspan="6">Nenhum pet encontrado.</td></tr>';
}
function preencherTutores() {
  formPet.elements.clienteId.innerHTML = '<option value="">Selecione</option>' + tutores.map(x => `<option value="${x.id}">${escapar(x.nome)}</option>`).join('');
}
function abrirPet(pet = {}) {
  formPet.reset(); preencherTutores();
  ['id', 'clienteId', 'nome', 'especie', 'raca', 'sexo', 'idade', 'nascimento', 'peso', 'observacoes'].forEach(campo => { formPet.elements[campo].value = pet[campo] ?? ''; });
  document.getElementById('erro-pet').textContent = ''; modalPet.showModal();
}
async function carregarPets() {
  const [resPets, resClientes] = await Promise.all([fetch('api/pets.php', { cache: 'no-store' }), fetch('api/clientes.php', { cache: 'no-store' })]);
  const [dadosPets, dadosClientes] = await Promise.all([resPets.json(), resClientes.json()]);
  if (!resPets.ok) throw new Error(dadosPets.mensagem);
  pets = dadosPets.pets; tutores = dadosClientes.clientes || []; renderPets();
}
document.getElementById('novo-pet').addEventListener('click', () => abrirPet());
document.getElementById('busca-pets').addEventListener('input', renderPets);
document.querySelectorAll('[data-fechar]').forEach(x => x.addEventListener('click', () => modalPet.close()));
document.getElementById('pets-body').addEventListener('click', async evento => {
  const editar = evento.target.closest('[data-editar]'); const excluir = evento.target.closest('[data-excluir]');
  if (editar) abrirPet(pets.find(x => x.id === Number(editar.dataset.editar)));
  if (excluir && confirm('Excluir este pet e seus planos?')) { await enviarJson('api/excluir-pet.php', { id: Number(excluir.dataset.excluir) }); await carregarPets(); }
});
formPet.addEventListener('submit', async evento => {
  evento.preventDefault(); const dados = Object.fromEntries(new FormData(formPet));
  dados.id = Number(dados.id || 0); dados.clienteId = Number(dados.clienteId);
  try { await enviarJson('api/salvar-pet.php', dados); modalPet.close(); await carregarPets(); }
  catch (erro) { document.getElementById('erro-pet').textContent = erro.message; }
});
document.addEventListener('DOMContentLoaded', () => carregarPets().catch(erro => { document.getElementById('pets-body').innerHTML = `<tr><td colspan="6">${escapar(erro.message)}</td></tr>`; }));
