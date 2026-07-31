function preencherAssinaturas(assinaturas) {
  const corpo = document.getElementById('recent-plans-body');
  corpo.innerHTML = '';

  if (assinaturas.length === 0) {
    const linha = document.createElement('tr');
    const celula = document.createElement('td');
    celula.colSpan = 6;
    celula.className = 'text-center text-slate-500 py-8';
    celula.textContent = 'Nenhuma assinatura cadastrada.';
    linha.appendChild(celula);
    corpo.appendChild(linha);
    return;
  }

  assinaturas.forEach((assinatura) => {
    const linha = document.createElement('tr');
    const clienteCelula = document.createElement('td');
    const grupo = document.createElement('div');
    grupo.className = 'flex items-center gap-3';
    const avatar = document.createElement('div');
    avatar.className = 'avatar-iniciais';
    avatar.style.cssText = `width:32px;height:32px;font-size:12.16px;background:${corAvatar(assinatura.cliente)};`;
    avatar.textContent = getInitials(assinatura.cliente);
    const nome = document.createElement('span');
    nome.className = 'font-medium';
    nome.textContent = assinatura.cliente;
    grupo.append(avatar, nome);
    clienteCelula.appendChild(grupo);
    linha.appendChild(clienteCelula);

    [
      assinatura.pet,
      assinatura.plano,
      formatCurrency(Number(assinatura.valor)),
      assinatura.dataVencimento ? formatDate(assinatura.dataVencimento) : '-',
    ].forEach((texto) => {
      const celula = document.createElement('td');
      celula.className = 'text-slate-600';
      celula.textContent = texto;
      linha.appendChild(celula);
    });

    const statusCelula = document.createElement('td');
    const status = document.createElement('span');
    status.className = `selo ${STATUS_BADGE_MAP[assinatura.status] || 'selo-cinza'}`;
    const ponto = document.createElement('span');
    ponto.className = 'selo-ponto';
    status.append(ponto, document.createTextNode(assinatura.status));
    statusCelula.appendChild(status);
    linha.appendChild(statusCelula);
    corpo.appendChild(linha);
  });
}

function renderPlansPeriodChart(serie) {
  new Chart(document.getElementById('plansPeriodChart'), {
    type: 'bar',
    data: {
      labels: serie.labels,
      datasets: [{
        data: serie.valores,
        backgroundColor: '#2f5fa8',
        borderRadius: 4,
        maxBarThickness: 44,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (item) => `${item.parsed.y} planos contratados` } },
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
      },
    },
  });
}

async function carregarPlanos() {
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
    preencherAssinaturas(resultado.assinaturas);
    renderPlansPeriodChart(resultado.serie);
  } catch (erro) {
    const corpo = document.getElementById('recent-plans-body');
    corpo.innerHTML = '<tr><td colspan="6" class="text-center text-red-700 py-8"></td></tr>';
    corpo.querySelector('td').textContent = erro.message || 'Não foi possível carregar os planos.';
  }
}

document.addEventListener('DOMContentLoaded', carregarPlanos);
