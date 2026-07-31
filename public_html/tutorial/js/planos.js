/**
 * Logica da pagina Planos.
 * Toda a estrutura visual (tabela, cartao do grafico) ja existe pronta
 * no planos.html. Aqui a gente so:
 *  - preenche os elementos existentes com os dados de js/data.js;
 *  - desenha o grafico (Chart.js) dentro do <canvas> ja presente.
 * Nenhuma funcao deste arquivo cria HTML novo.
 */

/** Desenha o grafico de colunas com o total de planos contratados nos ultimos 6 meses */
function renderPlansPeriodChart() {
  const ctx = document.getElementById('plansPeriodChart');
  const s = MOCK_PLANS_GROWTH_SERIES;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        {
          data: s.values,
          backgroundColor: '#2f5fa8',
          borderRadius: 4,
          maxBarThickness: 44,
          categoryPercentage: 0.6,
          barPercentage: 0.9,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          displayColors: false,
          backgroundColor: '#f1f5f9',
          titleColor: '#0f172a',
          bodyColor: '#0f172a',
          padding: 10,
          titleFont: { size: 12 },
          bodyFont: { size: 13, weight: '600' },
          callbacks: {
            label: (item) => `${item.parsed.y} planos contratados`,
          },
        },
      },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
      },
    },
  });
}

/**
 * Preenche as linhas (ja existentes no HTML) da tabela de planos recentes.
 * So define texto/estilo dos elementos de cada linha - nao cria linhas novas.
 */
function preencherTabelaPlanosRecentes() {
  const linhas = document.querySelectorAll('#recent-plans-body tr');
  const planos = MOCK_PLANS.slice(0, linhas.length);

  linhas.forEach((linha, i) => {
    const plano = planos[i];

    const avatar = linha.querySelector('.linha-avatar');
    avatar.textContent = getInitials(plano.clientName);
    avatar.style.background = corAvatar(plano.clientName);

    linha.querySelector('.linha-cliente').textContent = plano.clientName;
    linha.querySelector('.linha-pet').textContent = plano.petName;
    linha.querySelector('.linha-plano').textContent = plano.planName;
    linha.querySelector('.linha-valor').textContent = formatCurrency(plano.value);
    linha.querySelector('.linha-vencimento').textContent = formatDate(plano.dueDate);

    const selo = linha.querySelector('.linha-status');
    selo.classList.add(STATUS_BADGE_MAP[plano.status] || 'selo-cinza');
    linha.querySelector('.linha-status-texto').textContent = plano.status;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  renderPlansPeriodChart();
  preencherTabelaPlanosRecentes();
});
