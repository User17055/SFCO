/** Dashboard conectado aos dados reais, preservando o layout personalizado. */

function definirTexto(id, valor) {
  const elemento = document.getElementById(id);
  if (elemento) elemento.textContent = valor;
}

function rotuloCompetencia(competencia) {
  const [ano, mes] = competencia.split('-');
  return `${mes}/${ano}`;
}

function preencherResumo(resultado) {
  definirTexto('kpi-total-clientes', resultado.kpis.totalClientes);
  definirTexto('kpi-total-pets', resultado.kpis.totalPets);
  definirTexto('kpi-planos-ativos', resultado.kpis.planosAtivos);
  definirTexto('kpi-planos-novos', resultado.kpis.planosNovosMes);
  definirTexto('kpi-cancelados-mes', resultado.kpis.canceladosMes);
  definirTexto('finance-projecao', formatCurrency(Number(resultado.projecao.mensal)));
  definirTexto('finance-cancelados', resultado.kpis.planosCancelados);
}

function renderRevenueChart(historico) {
  const grafico = new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
      labels: historico.map((item) => rotuloCompetencia(item.competencia)),
      datasets: [{
        label: 'Projecao',
        data: historico.map((item) => item.projecao),
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
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (item) => formatCurrency(Number(item.raw)) } },
      },
      scales: {
        y: { beginAtZero: true, ticks: { callback: (valor) => formatCurrency(Number(valor)) } },
        x: { grid: { display: false } },
      },
    },
  });
  estabilizarGrafico(grafico);
}

function renderQuantityChart(historico) {
  const mesesRecentes = historico.slice(-6);
  const grafico = new Chart(document.getElementById('newClientsChart'), {
    type: 'bar',
    data: {
      labels: mesesRecentes.map((item) => rotuloCompetencia(item.competencia)),
      datasets: [
        {
          label: 'Quantidade geral de planos',
          data: mesesRecentes.map((item) => item.quantidade),
          backgroundColor: '#f1c744',
          borderRadius: 6,
          maxBarThickness: 34,
        },
        {
          label: 'Cancelados',
          data: mesesRecentes.map((item) => item.cancelados),
          backgroundColor: '#dc2626',
          borderRadius: 6,
          maxBarThickness: 34,
        },
      ],
    },
    options: {
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          labels: { usePointStyle: true, pointStyle: 'circle' },
        },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } },
        x: { grid: { display: false } },
      },
    },
  });
  estabilizarGrafico(grafico);
}

function renderStatusChart(kpis) {
  const grafico = new Chart(document.getElementById('planStatusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Ativos', 'Cancelados'],
      datasets: [{
        data: [kpis.planosAtivos, kpis.planosCancelados],
        backgroundColor: ['#16a34a', '#94a3b8'],
        borderWidth: 0,
        hoverOffset: 4,
      }],
    },
    options: {
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { usePointStyle: true, pointStyle: 'circle', padding: 16 },
        },
      },
    },
  });
  estabilizarGrafico(grafico);
}

async function carregarDashboard() {
  const mensagemErro = document.getElementById('dashboard-erro');
  mostrarCarregamento('Carregando dashboard...');

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
      throw new Error(resultado.mensagem || 'Nao foi possivel carregar os dados.');
    }

    preencherResumo(resultado);
    renderRevenueChart(resultado.historico);
    renderQuantityChart(resultado.historico);
    renderStatusChart(resultado.kpis);

    definirTexto(
      'dashboard-atualizado',
      `Atualizado em ${new Date(resultado.atualizadoEm).toLocaleString('pt-BR')}`
    );
  } catch (erro) {
    const textoErro = mensagemErro?.querySelector('span');
    if (textoErro) textoErro.textContent = erro.message;
    if (mensagemErro) mensagemErro.hidden = false;
  } finally {
    ocultarCarregamento();
  }
}

document.addEventListener('DOMContentLoaded', carregarDashboard);

document.addEventListener('DOMContentLoaded', () => {
  const navegar = (filtro) => { window.location.href = `planos.html?filtro=${filtro}`; };
  const configurarCard = (id, filtro) => {
    const card = document.getElementById(id);
    if (!card) return;
    card.addEventListener('click', () => navegar(filtro));
    card.addEventListener('keydown', (evento) => {
      if (evento.key === 'Enter' || evento.key === ' ') navegar(filtro);
    });
  };
  configurarCard('card-planos-novos', 'novos');
  configurarCard('card-planos-cancelados', 'cancelados');
});
