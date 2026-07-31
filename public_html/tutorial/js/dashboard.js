/**
 * Logica da pagina Dashboard.
 * Toda a estrutura visual (cards, cartoes dos graficos) ja existe
 * pronta no index.html. Aqui a gente so:
 *  - preenche os elementos existentes com os dados de js/data.js;
 *  - desenha os graficos (Chart.js) dentro dos <canvas> ja presentes.
 * Nenhuma funcao deste arquivo cria HTML novo.
 */

/** Preenche os 5 cards de KPI do topo do dashboard */
function preencherKpiCards() {
  const stats = getDashboardStats();

  document.getElementById('kpi-total-clientes').textContent = stats.totalClients;
  document.getElementById('kpi-total-pets').textContent = stats.totalPets;
  document.getElementById('kpi-planos-ativos').textContent = stats.activePlans;
  document.getElementById('kpi-planos-vencendo').textContent = stats.plansDueSoon;
  document.getElementById('kpi-planos-vencidos').textContent = stats.overduePlans;
}

/** Preenche os 4 cards do resumo financeiro */
function preencherFinanceCards() {
  const f = getFinanceStats();

  document.getElementById('finance-receita-ativa').textContent = formatCurrency(f.activeRevenue);
  document.getElementById('finance-a-receber').textContent = formatCurrency(f.pendingRevenue);
  document.getElementById('finance-prejuizo').textContent = formatCurrency(f.overdueLoss);
  document.getElementById('finance-perdas').textContent = formatCurrency(f.canceledLoss);
}

/** Desenha o grafico de linha da receita mensal */
function renderRevenueChart() {
  const ctx = document.getElementById('revenueChart');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: MOCK_REVENUE_SERIES.labels,
      datasets: [{
        label: 'Receita',
        data: MOCK_REVENUE_SERIES.values,
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
        tooltip: {
          callbacks: {
            label: (item) => formatCurrency(item.raw),
          },
        },
      },
      scales: {
        y: {
          beginAtZero: false,
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#94a3b8',
            font: { size: 11 },
            callback: (v) => formatCurrency(v),
          },
        },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
      },
    },
  });
}

/** Desenha o grafico de colunas de novos clientes por mes */
function renderNewClientsChart() {
  const ctx = document.getElementById('newClientsChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: MOCK_NEW_CLIENTS_SERIES.labels,
      datasets: [{
        label: 'Novos clientes',
        data: MOCK_NEW_CLIENTS_SERIES.values,
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
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
      },
    },
  });
}

/** Desenha o grafico de rosca de planos por status */
function renderPlanStatusChart() {
  const ctx = document.getElementById('planStatusChart');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: MOCK_PLAN_STATUS_SERIES.labels,
      datasets: [{
        data: MOCK_PLAN_STATUS_SERIES.values,
        backgroundColor: MOCK_PLAN_STATUS_SERIES.colors,
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
          labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 }, color: '#475569' },
        },
      },
    },
  });
}

document.addEventListener('DOMContentLoaded', () => {
  preencherKpiCards();
  preencherFinanceCards();
  renderRevenueChart();
  renderNewClientsChart();
  renderPlanStatusChart();
});
