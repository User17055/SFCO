function rotuloMes(valor) {
  const [ano, mes] = valor.split('-');
  return `${mes}/${ano}`;
}

function grafico(id, labels, dados, legenda, cor, moeda = false) {
  return new Chart(document.getElementById(id), {
    type: 'bar',
    data: { labels, datasets: [{ label: legenda, data: dados, backgroundColor: cor, borderRadius: 5 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: moeda ? { callbacks: { label: x => formatCurrency(Number(x.raw)) } } : {} },
      scales: { y: { beginAtZero: true, ticks: moeda ? { callback: x => formatCurrency(Number(x)) } : { precision: 0 } } },
    },
  });
}

async function carregarDashboard() {
  try {
    const resposta = await fetch('api/dashboard.php', { cache: 'no-store' });
    const dados = await resposta.json();
    if (resposta.status === 401) return location.replace('login.html');
    if (resposta.status === 403) return location.replace('trocar-senha.html');
    if (!resposta.ok) throw new Error(dados.mensagem);
    const campos = {
      'kpi-total-clientes': dados.kpis.totalClientes,
      'kpi-total-pets': dados.kpis.totalPets,
      'kpi-planos-ativos': dados.kpis.planosAtivos,
      'kpi-planos-cancelados': dados.kpis.planosCancelados,
      'kpi-tipos-planos': dados.kpis.tiposPlanos,
      'projecao-mensal': formatCurrency(dados.projecao.mensal),
      'ticket-medio': formatCurrency(dados.projecao.ticketMedio),
    };
    Object.entries(campos).forEach(([id, valor]) => { document.getElementById(id).textContent = valor; });
    document.getElementById('dashboard-atualizado').textContent = `Atualizado em ${new Date(dados.atualizadoEm).toLocaleString('pt-BR')}`;
    const labels = dados.historico.map(x => rotuloMes(x.competencia));
    grafico('historicoChart', labels, dados.historico.map(x => x.projecao), 'Projecao', '#2563eb', true);
    grafico('quantidadeChart', labels, dados.historico.map(x => x.quantidade), 'Planos', '#0f766e');
    grafico('cancelamentosChart', dados.cancelamentos.map(x => rotuloMes(x.competencia)), dados.cancelamentos.map(x => x.quantidade), 'Cancelamentos', '#dc2626');
    const corpo = document.getElementById('planos-resumo');
    corpo.innerHTML = dados.porPlano.map(x => `<tr><td>${escapar(x.nome)}</td><td>${x.quantidade}</td><td>${formatCurrency(x.projecao)}</td></tr>`).join('');
  } catch (erro) {
    const caixa = document.getElementById('dashboard-erro');
    caixa.hidden = false; caixa.textContent = erro.message || 'Erro ao carregar.';
  }
}

document.addEventListener('DOMContentLoaded', carregarDashboard);
