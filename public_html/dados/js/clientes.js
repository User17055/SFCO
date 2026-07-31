function tratarAcesso(resposta, resultado) {
  if (resposta.status === 401) {
    sessionStorage.removeItem('tpp-auth');
    window.location.replace('login.html');
    return true;
  }
  if (resposta.status === 403 && resultado.trocarSenha) {
    sessionStorage.removeItem('tpp-auth');
    window.location.replace('trocar-senha.html');
    return true;
  }
  return false;
}

function formatarCpfTabela(valor) {
  const digitos = String(valor || '').replace(/\D/g, '');
  if (digitos.length !== 11) return valor || '-';
  return digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatarTelefoneTabela(valor) {
  const digitos = String(valor || '').replace(/\D/g, '');
  if (digitos.length === 11) return digitos.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  if (digitos.length === 10) return digitos.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  return valor || '-';
}

function celulaTexto(texto, classe = 'text-slate-600') {
  const celula = document.createElement('td');
  celula.className = classe;
  celula.textContent = texto;
  return celula;
}

function preencherTabelaClientes(clientes) {
  const corpo = document.getElementById('clients-body');
  corpo.innerHTML = '';

  if (clientes.length === 0) {
    const linha = document.createElement('tr');
    const celula = celulaTexto('Nenhum cliente cadastrado.', 'text-center text-slate-500 py-8');
    celula.colSpan = 6;
    linha.appendChild(celula);
    corpo.appendChild(linha);
    return;
  }

  clientes.forEach((cliente) => {
    const linha = document.createElement('tr');
    const nomeCelula = document.createElement('td');
    const grupo = document.createElement('div');
    grupo.className = 'flex items-center gap-3';
    const avatar = document.createElement('div');
    avatar.className = 'avatar-iniciais';
    avatar.style.cssText = `width:32px;height:32px;font-size:12.16px;background:${corAvatar(cliente.nome)};`;
    avatar.textContent = getInitials(cliente.nome);
    const nome = document.createElement('span');
    nome.className = 'font-medium';
    nome.textContent = cliente.nome;
    grupo.append(avatar, nome);
    nomeCelula.appendChild(grupo);

    linha.appendChild(nomeCelula);
    linha.appendChild(celulaTexto(formatarTelefoneTabela(cliente.telefone)));
    linha.appendChild(celulaTexto(formatarCpfTabela(cliente.cpf)));
    linha.appendChild(celulaTexto(String(cliente.totalPets)));
    linha.appendChild(celulaTexto(cliente.planos));

    const statusCelula = document.createElement('td');
    const status = document.createElement('span');
    status.className = `selo ${STATUS_BADGE_MAP[cliente.status] || 'selo-cinza'}`;
    const ponto = document.createElement('span');
    ponto.className = 'selo-ponto';
    status.append(ponto, document.createTextNode(cliente.status));
    statusCelula.appendChild(status);
    linha.appendChild(statusCelula);
    corpo.appendChild(linha);
  });
}

function renderNewClientsChart(series) {
  const grafico = new Chart(document.getElementById('newClientsChart'), {
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
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
      },
    },
  });
  estabilizarGrafico(grafico);
}

function renderPlanStatusChart(status) {
  const grafico = new Chart(document.getElementById('planStatusChart'), {
    type: 'doughnut',
    data: {
      labels: status.labels,
      datasets: [{ data: status.valores, backgroundColor: status.cores, borderWidth: 0 }],
    },
    options: {
      animation: false,
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16 } },
      },
    },
  });
  estabilizarGrafico(grafico);
}

async function carregarClientes() {
  mostrarCarregamento('Carregando clientes...', 'Buscando clientes e preparando os graficos.');
  try {
    const resposta = await fetch('api/clientes.php', { headers: { Accept: 'application/json' }, cache: 'no-store' });
    const resultado = await resposta.json();
    if (tratarAcesso(resposta, resultado)) return;
    if (!resposta.ok) throw new Error(resultado.mensagem);
    const clientes = Array.isArray(resultado.clientes) ? resultado.clientes : [];
    const series = resultado.series || {
      labels: [],
      novosClientes: [],
      statusPlanos: { labels: [], valores: [], cores: [] },
    };
    preencherTabelaClientes(clientes);
    renderNewClientsChart(series);
    renderPlanStatusChart(series.statusPlanos);
  } catch (erro) {
    const corpo = document.getElementById('clients-body');
    corpo.innerHTML = `<tr><td colspan="6" class="text-center text-red-700 py-8"></td></tr>`;
    corpo.querySelector('td').textContent = erro.message || 'Não foi possível carregar os clientes.';
  } finally {
    ocultarCarregamento();
  }
}

document.addEventListener('DOMContentLoaded', carregarClientes);
