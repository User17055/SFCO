/**
 * Logica de renderizacao da pagina Dashboard.
 * Todos os numeros e series vem de js/data.js (dados ficticios).
 */

function renderKpiCards() {
  const stats = getDashboardStats();
  const cards = [
    { label: 'Total de clientes', value: stats.totalClients, icon: 'users', accent: '#2563eb' },
    { label: 'Total de pets', value: stats.totalPets, icon: 'paw-print', accent: '#7c3aed' },
    { label: 'Planos ativos', value: stats.activePlans, icon: 'circle-check', accent: '#16a34a' },
    { label: 'Planos vencendo', value: stats.plansDueSoon, icon: 'clock', accent: '#d97706' },
    { label: 'Planos vencidos', value: stats.overduePlans, icon: 'circle-alert', accent: '#dc2626' },
  ];

  document.getElementById('kpi-cards').innerHTML = cards
    .map((c, i) => renderStatCard({ ...c, delay: `atraso-${i + 1}` }))
    .join('');
}

function renderFinanceCards() {
  const f = getFinanceStats();
  const cards = [
    {
      label: 'Receita de planos ativos',
      value: f.activeRevenue,
      icon: 'wallet',
      accent: '#16a34a',
      trend: { direction: 'up', sentiment: 'good', value: '+8.2% vs mes anterior' },
    },
    {
      label: 'A receber (pendentes)',
      value: f.pendingRevenue,
      icon: 'clock',
      accent: '#d97706',
      trend: { direction: 'down', sentiment: 'good', value: '-2.1% vs mes anterior' },
    },
    {
      label: 'Prejuizo (planos vencidos)',
      value: f.overdueLoss,
      icon: 'trending-down',
      accent: '#dc2626',
      trend: { direction: 'up', sentiment: 'bad', value: '+5.4% vs mes anterior' },
    },
    {
      label: 'Perdas (cancelamentos)',
      value: f.canceledLoss,
      icon: 'x-circle',
      accent: '#64748b',
      trend: { direction: 'down', sentiment: 'good', value: '-1.3% vs mes anterior' },
    },
  ];

  document.getElementById('finance-cards').innerHTML = cards
    .map((c, i) => renderFinanceCard({ ...c, delay: `atraso-${i + 1}` }))
    .join('');
}

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

function renderRecentPlansTable() {
  const rows = MOCK_PLANS.slice(0, 6).map(p => `
    <tr>
      <td>
        <div class="flex items-center gap-3">
          ${renderAvatar(p.clientName, 32)}
          <span class="font-medium">${p.clientName}</span>
        </div>
      </td>
      <td class="text-slate-600">${p.petName}</td>
      <td class="text-slate-600">${p.planName}</td>
      <td class="text-slate-600">${formatCurrency(p.value)}</td>
      <td class="text-slate-600">${formatDate(p.dueDate)}</td>
      <td>${renderStatusBadge(p.status)}</td>
    </tr>
  `).join('');

  document.getElementById('recent-plans-body').innerHTML = rows;
}

document.addEventListener('DOMContentLoaded', () => {
  mountLayout({});
  renderKpiCards();
  renderFinanceCards();
  renderRevenueChart();
  renderNewClientsChart();
  renderPlanStatusChart();
  renderRecentPlansTable();
  if (window.lucide) lucide.createIcons();
});
