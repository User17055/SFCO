let dadosPlanos = { assinaturas: [], cancelamentosImportados: [], crescimento: null, relatoriosPlanos: [] };
let filtroAtual = new URLSearchParams(window.location.search).get('filtro') || 'todos';
let graficoRelatorioPlano = null;

if (!['todos', 'novos', 'cancelados'].includes(filtroAtual)) filtroAtual = 'todos';

function criarElemento(tag, classe, texto) {
  const elemento = document.createElement(tag);
  if (classe) elemento.className = classe;
  if (texto !== undefined) elemento.textContent = texto;
  return elemento;
}

function assinaturasParaExibir() {
  if (filtroAtual === 'novos') {
    return dadosPlanos.assinaturas.filter((item) => item.novo && item.status === 'Ativo');
  }

  if (filtroAtual === 'cancelados') {
    const canceladosAtuais = dadosPlanos.assinaturas
      .filter((item) => item.status === 'Cancelado')
      .map((item) => ({ ...item, tipoRegistro: 'assinatura' }));

    const importados = dadosPlanos.cancelamentosImportados.map((item) => ({
      cliente: item.cliente,
      pet: 'Pet nao informado na planilha',
      plano: item.plano,
      valorMensal: item.valor,
      status: 'Cancelado',
      motivoCancelamento: item.motivo,
      competencia: item.competencia,
      tipoRegistro: 'cancelamento-importado',
    }));

    return [...canceladosAtuais, ...importados];
  }

  return dadosPlanos.assinaturas;
}

function correspondeBusca(item, termo) {
  if (!termo) return true;
  return [item.cliente, item.pet, item.plano, item.status, item.motivoCancelamento]
    .join(' ')
    .toLocaleLowerCase('pt-BR')
    .includes(termo);
}

function badgeStatus(item) {
  if (item.status === 'Cancelado') return ['bg-red-100 text-red-700', 'Cancelado'];
  if (item.novo) return ['bg-amber-100 text-amber-800', 'Novo'];
  return ['bg-green-100 text-green-700', 'Ativo'];
}

function criarDetalhePlano(item) {
  const caixa = criarElemento('div', 'rounded-lg border border-slate-200 bg-white p-3');
  const topo = criarElemento('div', 'flex flex-wrap items-center justify-between gap-2');
  topo.appendChild(criarElemento('strong', 'text-sm text-slate-800', item.plano || 'Plano nao informado'));
  const [classe, texto] = badgeStatus(item);
  topo.appendChild(criarElemento('span', `px-2 py-1 rounded-full text-[11px] font-semibold ${classe}`, texto));
  caixa.appendChild(topo);

  const detalhes = criarElemento('div', 'grid sm:grid-cols-2 xl:grid-cols-4 gap-2 mt-3 text-xs text-slate-500');
  detalhes.appendChild(criarElemento('span', '', `Valor mensal: ${formatCurrency(Number(item.valorMensal || 0))}`));
  detalhes.appendChild(criarElemento('span', '', `Inicio: ${item.dataInicio ? formatDate(item.dataInicio) : '-'}`));
  detalhes.appendChild(criarElemento('span', '', `Reajuste: ${item.dataReajuste ? formatDate(item.dataReajuste) : '-'}`));
  detalhes.appendChild(criarElemento('span', '', `Competencia: ${item.competencia ? formatDate(item.competencia) : '-'}`));
  caixa.appendChild(detalhes);

  if (item.adicional) caixa.appendChild(criarElemento('p', 'text-xs text-slate-500 mt-2', `Adicional: ${item.adicional}`));
  if (item.motivoCancelamento) caixa.appendChild(criarElemento('p', 'text-xs text-red-600 mt-2', `Motivo: ${item.motivoCancelamento}`));
  if (item.observacoes) caixa.appendChild(criarElemento('p', 'text-xs text-slate-500 mt-2', item.observacoes));
  return caixa;
}

function criarPet(nomePet, planos) {
  const detalhesPet = criarElemento('details', 'rounded-lg border border-slate-200 bg-slate-50');
  const resumoPet = criarElemento('summary', 'cursor-pointer list-none flex items-center justify-between gap-3 px-4 py-3');
  const ladoEsquerdo = criarElemento('span', 'flex items-center gap-2');
  ladoEsquerdo.appendChild(criarElemento('span', 'text-base', '🐾'));
  ladoEsquerdo.appendChild(criarElemento('strong', 'text-sm text-slate-700', nomePet));
  resumoPet.appendChild(ladoEsquerdo);
  resumoPet.appendChild(criarElemento('span', 'text-xs text-slate-500', `${planos.length} plano(s)`));
  detalhesPet.appendChild(resumoPet);

  const lista = criarElemento('div', 'grid gap-2 px-4 pb-4');
  planos.forEach((plano) => lista.appendChild(criarDetalhePlano(plano)));
  detalhesPet.appendChild(lista);
  return detalhesPet;
}

function criarTutor(nomeTutor, itens) {
  const tutor = criarElemento('details', 'rounded-xl border border-slate-200 bg-white overflow-hidden');
  const resumo = criarElemento('summary', 'cursor-pointer list-none flex items-center justify-between gap-4 px-4 py-4 hover:bg-slate-50');
  const identidade = criarElemento('span', 'flex items-center gap-3 min-w-0');
  const avatar = criarElemento('span', 'avatar-iniciais shrink-0', getInitials(nomeTutor));
  avatar.style.cssText = `width:34px;height:34px;font-size:12px;background:${corAvatar(nomeTutor)};`;
  identidade.appendChild(avatar);
  identidade.appendChild(criarElemento('strong', 'text-sm text-slate-800 truncate', nomeTutor));
  resumo.appendChild(identidade);

  const nomesPets = new Set(itens.map((item) => item.pet || 'Pet nao informado'));
  resumo.appendChild(criarElemento('span', 'text-xs text-slate-500 shrink-0', `${nomesPets.size} pet(s) · ${itens.length} plano(s)`));
  tutor.appendChild(resumo);

  const pets = criarElemento('div', 'grid gap-2 px-4 pb-4');
  tutor.appendChild(pets);

  let detalhesMontados = false;
  tutor.addEventListener('toggle', () => {
    if (!tutor.open || detalhesMontados) return;
    detalhesMontados = true;
    const porPet = new Map();
    itens.forEach((item) => {
      const pet = item.pet || 'Pet nao informado';
      if (!porPet.has(pet)) porPet.set(pet, []);
      porPet.get(pet).push(item);
    });
    [...porPet.entries()]
      .sort(([a], [b]) => a.localeCompare(b, 'pt-BR'))
      .forEach(([pet, planos]) => pets.appendChild(criarPet(pet, planos)));
  });
  return tutor;
}

function renderDiretorio() {
  const diretorio = document.getElementById('diretorio-planos');
  const termo = document.getElementById('busca-planos').value.trim().toLocaleLowerCase('pt-BR');
  const itens = assinaturasParaExibir().filter((item) => correspondeBusca(item, termo));
  diretorio.innerHTML = '';
  document.getElementById('total-planos-listados').textContent = `${itens.length} plano(s)`;

  document.querySelectorAll('[data-filtro]').forEach((botao) => {
    const ativo = botao.dataset.filtro === filtroAtual;
    botao.classList.toggle('ring-2', ativo);
    botao.classList.toggle('ring-blue-400', ativo);
  });

  if (itens.length === 0) {
    diretorio.appendChild(criarElemento('p', 'text-center text-sm text-slate-500 py-8', 'Nenhum plano encontrado neste filtro.'));
    return;
  }

  const porTutor = new Map();
  itens.forEach((item) => {
    const tutor = item.cliente || 'Tutor nao informado';
    if (!porTutor.has(tutor)) porTutor.set(tutor, []);
    porTutor.get(tutor).push(item);
  });
  [...porTutor.entries()]
    .sort(([a], [b]) => a.localeCompare(b, 'pt-BR'))
    .forEach(([tutor, planos]) => diretorio.appendChild(criarTutor(tutor, planos)));

  if (window.lucide) lucide.createIcons();
}

function renderPlansPeriodChart(serie) {
  const grafico = new Chart(document.getElementById('plansPeriodChart'), {
    type: 'bar',
    data: {
      labels: serie.labels,
      datasets: [
        {
          label: 'Planos novos',
          data: serie.novos,
          backgroundColor: '#f1c744',
          borderRadius: 4,
          maxBarThickness: 44,
        },
        {
          label: 'Cancelados',
          data: serie.cancelados,
          backgroundColor: '#dc2626',
          borderRadius: 4,
          maxBarThickness: 44,
        },
      ],
    },
    options: {
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { usePointStyle: true, pointStyle: 'circle' } },
        tooltip: { callbacks: { label: (item) => `${item.dataset.label}: ${item.parsed.y}` } },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
      },
    },
  });
  estabilizarGrafico(grafico);
}

function abrirRelatorioPlano(relatorio, botaoSelecionado) {
  document.querySelectorAll('[data-relatorio-plano]').forEach((botao) => {
    botao.classList.toggle('ring-2', botao === botaoSelecionado);
    botao.classList.toggle('ring-blue-400', botao === botaoSelecionado);
  });

  const detalhe = document.getElementById('detalhe-relatorio-plano');
  detalhe.hidden = false;
  document.getElementById('nome-relatorio-plano').textContent = relatorio.nome;
  document.getElementById('quantidade-relatorio-plano').textContent = `${relatorio.quantidadeAtual} plano(s) atual(is)`;
  document.getElementById('projecao-relatorio-plano').textContent = `Projecao: ${formatCurrency(Number(relatorio.projecaoAtual))}`;
  document.getElementById('cancelados-relatorio-plano').textContent = `${relatorio.totalCancelados} cancelamento(s)`;

  if (graficoRelatorioPlano) graficoRelatorioPlano.destroy();
  graficoRelatorioPlano = new Chart(document.getElementById('planReportChart'), {
    data: {
      labels: relatorio.labels,
      datasets: [
        {
          type: 'line',
          label: 'Quantidade do plano',
          data: relatorio.quantidades,
          borderColor: '#2f5fa8',
          backgroundColor: 'rgba(47, 95, 168, 0.12)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          borderWidth: 2,
        },
        {
          type: 'bar',
          label: 'Cancelamentos',
          data: relatorio.cancelados,
          backgroundColor: '#dc2626',
          borderRadius: 4,
          maxBarThickness: 30,
        },
      ],
    },
    options: {
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { usePointStyle: true, pointStyle: 'circle' } },
        tooltip: { callbacks: { label: (item) => `${item.dataset.label}: ${item.parsed.y}` } },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } },
      },
    },
  });
  estabilizarGrafico(graficoRelatorioPlano);

  detalhe.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderRelatoriosPlanos() {
  const lista = document.getElementById('lista-relatorios-planos');
  const termo = document.getElementById('busca-relatorio-plano').value.trim().toLocaleLowerCase('pt-BR');
  const relatorios = dadosPlanos.relatoriosPlanos.filter((relatorio) =>
    relatorio.nome.toLocaleLowerCase('pt-BR').includes(termo)
  );
  lista.innerHTML = '';

  if (relatorios.length === 0) {
    lista.appendChild(criarElemento('p', 'text-sm text-slate-500 py-4', 'Nenhum plano encontrado.'));
    return;
  }

  relatorios.forEach((relatorio) => {
    const botao = criarElemento('button', 'text-left rounded-xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition-colors');
    botao.type = 'button';
    botao.dataset.relatorioPlano = relatorio.nome;
    botao.appendChild(criarElemento('strong', 'block text-sm text-slate-800', relatorio.nome));
    const resumo = criarElemento('span', 'flex flex-wrap gap-2 mt-2 text-[11px]');
    resumo.appendChild(criarElemento('span', 'px-2 py-1 rounded-full bg-blue-50 text-blue-700', `${relatorio.quantidadeAtual} atual(is)`));
    resumo.appendChild(criarElemento('span', 'px-2 py-1 rounded-full bg-red-50 text-red-700', `${relatorio.totalCancelados} cancelado(s)`));
    botao.appendChild(resumo);
    botao.addEventListener('click', () => abrirRelatorioPlano(relatorio, botao));
    lista.appendChild(botao);
  });
}

async function carregarPlanos() {
  const erro = document.getElementById('planos-erro');
  mostrarCarregamento('Carregando planos...', 'Preparando assinaturas, cancelamentos e relatorios mensais.');
  try {
    const resposta = await fetch('api/planos.php', { headers: { Accept: 'application/json' }, cache: 'no-store' });
    const resultado = await resposta.json();
    if (resposta.status === 401) {
      sessionStorage.removeItem('tpp-auth');
      window.location.replace('login.html');
      return;
    }
    if (resposta.status === 403 && resultado.trocarSenha) {
      sessionStorage.removeItem('tpp-auth');
      window.location.replace('trocar-senha.html');
      return;
    }
    if (!resposta.ok) throw new Error(resultado.mensagem);
    dadosPlanos = {
      ...resultado,
      assinaturas: Array.isArray(resultado.assinaturas) ? resultado.assinaturas : [],
      cancelamentosImportados: Array.isArray(resultado.cancelamentosImportados) ? resultado.cancelamentosImportados : [],
      relatoriosPlanos: Array.isArray(resultado.relatoriosPlanos) ? resultado.relatoriosPlanos : [],
    };
    /* As listas nao dependem do Chart.js: os dados sempre aparecem. */
    renderRelatoriosPlanos();
    renderDiretorio();
    if (resultado.crescimento && typeof Chart !== 'undefined') {
      try {
        renderPlansPeriodChart(resultado.crescimento);
      } catch (erroGrafico) {
        console.error('Nao foi possivel desenhar o grafico de planos.', erroGrafico);
      }
    }
  } catch (falha) {
    const texto = erro?.querySelector('span');
    if (texto) texto.textContent = falha.message || 'Nao foi possivel carregar os planos.';
    if (erro) erro.hidden = false;
  } finally {
    ocultarCarregamento();
  }
}

document.querySelectorAll('[data-filtro]').forEach((botao) => {
  botao.addEventListener('click', () => {
    filtroAtual = botao.dataset.filtro;
    const url = new URL(window.location.href);
    url.searchParams.set('filtro', filtroAtual);
    window.history.replaceState({}, '', url);
    renderDiretorio();
  });
});
document.getElementById('busca-planos').addEventListener('input', renderDiretorio);
document.getElementById('busca-relatorio-plano').addEventListener('input', renderRelatoriosPlanos);
document.addEventListener('DOMContentLoaded', carregarPlanos);
