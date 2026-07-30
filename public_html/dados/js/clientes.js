/**
 * Logica da pagina Clientes.
 * Toda a estrutura visual (tabela, cartoes dos graficos) ja existe pronta
 * no clientes.html. Aqui a gente so:
 *  - preenche os elementos existentes com os dados de js/data.js;
 *  - desenha os graficos (Chart.js) dentro dos <canvas> ja presentes.
 * Nenhuma funcao deste arquivo cria HTML novo.
 */

/**
 * Preenche as linhas (ja existentes no HTML) da tabela de clientes.
 * So define texto/estilo dos elementos de cada linha - nao cria linhas novas.
 */
function preencherTabelaClientes() {
  const linhas = document.querySelectorAll('#clients-body tr');
  const clientes = MOCK_CLIENTS.slice(0, linhas.length);

  linhas.forEach((linha, i) => {
    const cliente = clientes[i];

    const avatar = linha.querySelector('.linha-avatar');
    avatar.textContent = getInitials(cliente.name);
    avatar.style.background = corAvatar(cliente.name);

    linha.querySelector('.linha-cliente').textContent = cliente.name;
    linha.querySelector('.linha-telefone').textContent = cliente.phone;
    linha.querySelector('.linha-cpf').textContent = cliente.cpf;
    linha.querySelector('.linha-pets').textContent = cliente.petsCount;
    linha.querySelector('.linha-plano').textContent = cliente.plan;

    const selo = linha.querySelector('.linha-status');
    selo.classList.add(STATUS_BADGE_MAP[cliente.status] || 'selo-cinza');
    linha.querySelector('.linha-status-texto').textContent = cliente.status;
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
  preencherTabelaClientes();
  renderNewClientsChart();
  renderPlanStatusChart();
});
