/** Dashboard alimentado exclusivamente por dados do MySQL. */

function preencherIdentidade(usuario) {
  document.querySelectorAll('[data-usuario-nome]').forEach((elemento) => {
    elemento.textContent = usuario.nome;
  });
  document.querySelectorAll('[data-usuario-email]').forEach((elemento) => {
    elemento.textContent = usuario.email;
  });
  document.querySelectorAll('[data-usuario-iniciais]').forEach((elemento) => {
    elemento.textContent = usuario.iniciais;
  });
}

function preencherKpiCards(stats) {
  document.getElementById('kpi-total-clientes').textContent = stats.totalClientes;
  document.getElementById('kpi-total-pets').textContent = stats.totalPets;
  document.getElementById('kpi-planos-ativos').textContent = stats.planosAtivos;
  document.getElementById('kpi-planos-vencendo').textContent = stats.planosVencendo;
  document.getElementById('kpi-planos-vencidos').textContent = stats.planosVencidos;
}

function preencherFinanceCards(financeiro) {
  document.getElementById('finance-receita-ativa').textContent =
    formatCurrency(Number(financeiro.receitaAtiva));
  document.getElementById('finance-a-receber').textContent =
    formatCurrency(Number(financeiro.aReceber));
  document.getElementById('finance-prejuizo').textContent =
    formatCurrency(Number(financeiro.prejuizo));
  document.getElementById('finance-perdas').textContent =
    formatCurrency(Number(financeiro.perdas));
}

function renderRevenueChart(series) {
  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
      labels: series.labels,
      datasets: [{
        label: 'Receita',
        data: series.receitaMensal,
        borderColor: '#16a34a',
        backgroundColor: 'rgba(22, 163, 74, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointBackgroundColor: '#16a34a',
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (item) => formatCurrency(Number(item.raw)) } },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#94a3b8',
            font: { size: 11 },
            callback: (valor) => formatCurrency(Number(valor)),
          },
        },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
      },
    },
  });
}

function renderNewClientsChart(series) {
  new Chart(document.getElementById('newClientsChart'), {
    type: 'bar',
    data: {
      labels: series.labels,
      datasets: [{
        label: 'Novos clientes',
        data: series.novosClientes,
        backgroundColor: '#f1c744',
        borderRadius: 6,
        maxBarThickness: 34,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } },
        },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
      },
    },
  });
}

function renderPlanStatusChart(status) {
  new Chart(document.getElementById('planStatusChart'), {
    type: 'doughnut',
    data: {
      labels: status.labels,
      datasets: [{
        data: status.valores,
        backgroundColor: status.cores,
        borderWidth: 0,
        hoverOffset: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            pointStyle: 'circle',
            padding: 16,
            font: { size: 12 },
            color: '#475569',
          },
        },
      },
    },
  });
}

async function carregarDashboard() {
  const mensagemErro = document.getElementById('dashboard-erro');

  try {
    const resposta = await fetch('api/dashboard.php', {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
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
    if (!resposta.ok) {
      throw new Error(resultado.mensagem || 'Não foi possível carregar os dados.');
    }

    preencherIdentidade(resultado.usuario);
    preencherKpiCards(resultado.kpis);
    preencherFinanceCards(resultado.financeiro);
    renderRevenueChart(resultado.series);
    renderNewClientsChart(resultado.series);
    renderPlanStatusChart(resultado.series.statusPlanos);

    const atualizado = new Date(resultado.atualizadoEm);
    document.getElementById('dashboard-atualizado').textContent =
      `Atualizado em ${atualizado.toLocaleString('pt-BR')}`;
  } catch (erro) {
    mensagemErro.querySelector('span').textContent = erro.message;
    mensagemErro.hidden = false;
  }
}

document.addEventListener('DOMContentLoaded', carregarDashboard);
