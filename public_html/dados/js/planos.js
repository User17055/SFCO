let dadosPlanos = { catalogo: [], assinaturas: [], cancelamentosImportados: [] };
let listaPets = [];
const modalTipo = document.getElementById('modal-tipo');
const modalAssinatura = document.getElementById('modal-assinatura');
const formTipo = document.getElementById('form-tipo');
const formAssinatura = document.getElementById('form-assinatura');

function renderCatalogo() {
  document.getElementById('catalogo-body').innerHTML = dadosPlanos.catalogo.map(x => `<tr>
    <td><strong>${escapar(x.nome)}</strong></td><td>${formatCurrency(x.valorPadrao)}</td><td>${x.quantidadeAtiva}</td><td>${x.quantidadeCancelada}</td><td>${formatCurrency(x.projecao)}</td>
    <td><span class="selo ${x.ativo ? 'selo-verde' : 'selo-cinza'}">${x.ativo ? 'Disponivel' : 'Inativo'}</span></td><td><button class="acao" data-editar-tipo="${x.id}">Editar / renomear</button></td></tr>`).join('');
}
function renderAssinaturas() {
  const termo = document.getElementById('busca-assinaturas').value.toLocaleLowerCase('pt-BR');
  const status = document.getElementById('filtro-status').value;
  const lista = dadosPlanos.assinaturas.filter(x => (!status || x.status === status) && [x.cliente, x.pet, x.plano].join(' ').toLocaleLowerCase('pt-BR').includes(termo));
  document.getElementById('total-assinaturas').textContent = `${lista.length} registro(s)`;
  document.getElementById('assinaturas-body').innerHTML = lista.map(x => `<tr>
    <td><strong>${escapar(x.cliente)}</strong><div class="text-xs text-slate-500">${escapar(x.pet)}</div></td><td>${escapar(x.plano)}${x.adicional ? `<div class="text-xs text-slate-500">${escapar(x.adicional)}</div>` : ''}</td>
    <td>${formatCurrency(x.valorMensal)}</td><td>${x.dataInicio ? formatDate(x.dataInicio) : '-'}<div class="text-xs text-slate-500">Reajuste: ${x.dataReajuste ? formatDate(x.dataReajuste) : '-'}</div></td>
    <td><span class="selo ${x.status === 'Ativo' ? 'selo-verde' : 'selo-vermelho'}">${x.status}</span>${x.motivoCancelamento ? `<div class="text-xs text-slate-500">${escapar(x.motivoCancelamento)}</div>` : ''}</td>
    <td class="whitespace-nowrap"><button class="acao" data-editar-assinatura="${x.id}">Editar / mudar plano</button><button class="acao ${x.status === 'Ativo' ? 'perigo' : ''}" data-status="${x.id}" data-novo-status="${x.status === 'Ativo' ? 'Cancelado' : 'Ativo'}">${x.status === 'Ativo' ? 'Cancelar' : 'Reativar'}</button></td></tr>`).join('');
}
function renderCancelamentos() {
  document.getElementById('cancelamentos-body').innerHTML = dadosPlanos.cancelamentosImportados.map(x => `<tr><td>${formatDate(x.competencia)}</td><td>${escapar(x.cliente)}</td><td>${escapar(x.plano)}</td><td>${escapar(x.motivo)}</td><td>${formatCurrency(x.valor)}</td></tr>`).join('');
}
function opcoesAssinatura() {
  formAssinatura.elements.petId.innerHTML = '<option value="">Selecione</option>' + listaPets.map(x => `<option value="${x.id}">${escapar(x.tutor)} — ${escapar(x.nome)}</option>`).join('');
  formAssinatura.elements.planoId.innerHTML = '<option value="">Selecione</option>' + dadosPlanos.catalogo.filter(x => x.ativo).map(x => `<option value="${x.id}" data-valor="${x.valorPadrao}">${escapar(x.nome)}</option>`).join('');
}
function abrirTipo(plano = {}) {
  formTipo.reset(); formTipo.elements.id.value = plano.id || ''; formTipo.elements.nome.value = plano.nome || '';
  formTipo.elements.valorPadrao.value = plano.valorPadrao ?? 0; formTipo.elements.ativo.checked = plano.id ? plano.ativo : true;
  document.getElementById('erro-tipo').textContent = ''; modalTipo.showModal();
}
function abrirAssinatura(item = {}) {
  formAssinatura.reset(); opcoesAssinatura();
  ['id', 'petId', 'planoId', 'valorMensal', 'dataInicio', 'dataReajuste', 'adicional', 'observacoes'].forEach(campo => { formAssinatura.elements[campo].value = item[campo] ?? ''; });
  document.getElementById('erro-assinatura').textContent = ''; modalAssinatura.showModal();
}
async function carregarPlanos() {
  const [resPlanos, resPets] = await Promise.all([fetch('api/planos.php', { cache: 'no-store' }), fetch('api/pets.php', { cache: 'no-store' })]);
  const [planos, pets] = await Promise.all([resPlanos.json(), resPets.json()]);
  if (!resPlanos.ok) throw new Error(planos.mensagem);
  dadosPlanos = planos; listaPets = pets.pets || []; renderCatalogo(); renderAssinaturas(); renderCancelamentos();
}
document.querySelectorAll('[data-aba]').forEach(botao => botao.addEventListener('click', () => {
  document.querySelectorAll('[data-aba]').forEach(x => x.classList.toggle('ativa', x === botao));
  ['catalogo', 'assinaturas', 'cancelamentos'].forEach(nome => document.getElementById(`secao-${nome}`).classList.toggle('hidden', nome !== botao.dataset.aba));
}));
document.getElementById('novo-tipo').addEventListener('click', () => abrirTipo());
document.getElementById('nova-assinatura').addEventListener('click', () => abrirAssinatura());
document.getElementById('busca-assinaturas').addEventListener('input', renderAssinaturas);
document.getElementById('filtro-status').addEventListener('change', renderAssinaturas);
document.querySelectorAll('[data-fechar]').forEach(x => x.addEventListener('click', () => x.closest('dialog').close()));
document.getElementById('catalogo-body').addEventListener('click', evento => {
  const botao = evento.target.closest('[data-editar-tipo]'); if (botao) abrirTipo(dadosPlanos.catalogo.find(x => x.id === Number(botao.dataset.editarTipo)));
});
document.getElementById('assinaturas-body').addEventListener('click', async evento => {
  const editar = evento.target.closest('[data-editar-assinatura]'); const status = evento.target.closest('[data-status]');
  if (editar) abrirAssinatura(dadosPlanos.assinaturas.find(x => x.id === Number(editar.dataset.editarAssinatura)));
  if (status) {
    const novoStatus = status.dataset.novoStatus; let motivo = '';
    if (novoStatus === 'Cancelado') { motivo = prompt('Motivo do cancelamento:') || ''; if (!motivo) return; }
    await enviarJson('api/cancelar-plano.php', { id: Number(status.dataset.status), status: novoStatus, motivo }); await carregarPlanos();
  }
});
formAssinatura.elements.planoId.addEventListener('change', () => {
  if (!formAssinatura.elements.id.value) formAssinatura.elements.valorMensal.value = formAssinatura.elements.planoId.selectedOptions[0]?.dataset.valor || 0;
});
formTipo.addEventListener('submit', async evento => {
  evento.preventDefault(); const dados = Object.fromEntries(new FormData(formTipo)); dados.id = Number(dados.id || 0); dados.ativo = formTipo.elements.ativo.checked;
  try { await enviarJson('api/salvar-plano.php', dados); modalTipo.close(); await carregarPlanos(); } catch (erro) { document.getElementById('erro-tipo').textContent = erro.message; }
});
formAssinatura.addEventListener('submit', async evento => {
  evento.preventDefault(); const dados = Object.fromEntries(new FormData(formAssinatura)); dados.id = Number(dados.id || 0); dados.petId = Number(dados.petId); dados.planoId = Number(dados.planoId);
  try { await enviarJson('api/salvar-assinatura.php', dados); modalAssinatura.close(); await carregarPlanos(); } catch (erro) { document.getElementById('erro-assinatura').textContent = erro.message; }
});
document.addEventListener('DOMContentLoaded', () => carregarPlanos().catch(erro => { document.getElementById('catalogo-body').innerHTML = `<tr><td colspan="7">${escapar(erro.message)}</td></tr>`; }));
