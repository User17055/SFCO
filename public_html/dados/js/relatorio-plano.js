function textoMetrica(id, valor) {
  const elemento = document.getElementById(id);
  if (elemento) elemento.textContent = valor;
}

function opcoesGrafico(formatarValor = false) {
  return {
    animation: false,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: formatarValor ? { callbacks: { label: (item) => formatCurrency(Number(item.raw || 0)) } } : {},
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: formatarValor ? { callback: (valor) => formatCurrency(Number(valor)) } : { precision: 0 },
        grid: { color: '#f1f5f9' },
      },
      x: { grid: { display: false } },
    },
  };
}

function criarGrafico(id, configuracao) {
  if (typeof Chart === 'undefined') return null;
  const grafico = new Chart(document.getElementById(id), configuracao);
  estabilizarGrafico(grafico);
  return grafico;
}

function preencherCancelamentos(relatorio) {
  const corpo = document.getElementById('historico-cancelamentos-plano');
  corpo.innerHTML = '';
  const registros = Array.isArray(relatorio.motivosCancelamentos)
    ? relatorio.motivosCancelamentos
    : [];

  if (registros.length === 0) {
    const linha = document.createElement('tr');
    linha.innerHTML = '<td colspan="6" class="text-center text-slate-500 py-8">Nenhum cancelamento identificado para este plano.</td>';
    corpo.appendChild(linha);
    return;
  }

  [...registros].reverse().forEach((item) => {
    const linha = document.createElement('tr');
    const competencia = item.competencia
      ? new Date(`${item.competencia}T12:00:00`).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' })
      : '-';
    [
      competencia,
      item.cliente || '-',
      item.motivo || 'Motivo nao informado',
      item.tentativaRecuperacao || '-',
      String(item.quantidade || 0),
      formatCurrency(Number(item.valor || 0)),
    ].forEach((valor) => {
      const celula = document.createElement('td');
      celula.textContent = valor;
      linha.appendChild(celula);
    });
    corpo.appendChild(linha);
  });
}

function renderizarRelatorio(relatorio) {
  textoMetrica('titulo-plano', relatorio.nome);
  textoMetrica('metrica-valor', formatCurrency(Number(relatorio.valorPlano || 0)));
  textoMetrica('metrica-ativos', relatorio.quantidadeAtual);
  textoMetrica('metrica-projecao', formatCurrency(Number(relatorio.projecaoAtual || 0)));
  textoMetrica('metrica-novos', relatorio.novosMesAtual || 0);
  textoMetrica('metrica-cancelados', relatorio.totalCancelados || 0);
  textoMetrica('metrica-valor-cancelado', formatCurrency(Number(relatorio.valorTotalCancelado || 0)));
  document.getElementById('conteudo-relatorio-plano').hidden = false;

  criarGrafico('grafico-quantidade-plano', {
    type: 'line',
    data: { labels: relatorio.labels, datasets: [{ data: relatorio.quantidades, borderColor: '#2f5fa8', backgroundColor: 'rgba(47,95,168,.12)', fill: true, tension: .3, borderWidth: 2, pointRadius: 3 }] },
    options: opcoesGrafico(),
  });
  criarGrafico('grafico-novos-plano', {
    type: 'bar',
    data: { labels: relatorio.labels, datasets: [{ data: relatorio.novos, backgroundColor: '#f1c744', borderRadius: 5, maxBarThickness: 36 }] },
    options: opcoesGrafico(),
  });
  criarGrafico('grafico-cancelados-plano', {
    type: 'bar',
    data: { labels: relatorio.labels, datasets: [{ data: relatorio.cancelados, backgroundColor: '#dc2626', borderRadius: 5, maxBarThickness: 36 }] },
    options: opcoesGrafico(),
  });
  const opcoesValores = opcoesGrafico(true);
  opcoesValores.plugins.legend = { display: true, labels: { usePointStyle: true, pointStyle: 'circle' } };
  criarGrafico('grafico-valores-plano', {
    data: {
      labels: relatorio.labels,
      datasets: [
        { type: 'line', label: 'Valor mensal ativo', data: relatorio.valoresMensais, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', fill: true, tension: .3, borderWidth: 2 },
        { type: 'bar', label: 'Valor cancelado', data: relatorio.valoresCancelados, backgroundColor: '#dc2626', borderRadius: 5, maxBarThickness: 28 },
      ],
    },
    options: opcoesValores,
  });
  preencherCancelamentos(relatorio);
}

async function carregarRelatorioPlano() {
  const nomePlano = new URLSearchParams(window.location.search).get('plano');
  const mensagemErro = document.getElementById('relatorio-plano-erro');
  mostrarCarregamento('Carregando relatorio do plano...');
  try {
    if (!nomePlano) throw new Error('Nenhum plano foi selecionado.');
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
    if (!resposta.ok) throw new Error(resultado.mensagem || 'Nao foi possivel carregar o plano.');
    const relatorio = (resultado.relatoriosPlanos || []).find((item) => item.nome === nomePlano);
    if (!relatorio) throw new Error('O plano selecionado nao foi encontrado.');
    renderizarRelatorio(relatorio);
  } catch (erro) {
    mensagemErro.querySelector('span').textContent = erro.message;
    mensagemErro.hidden = false;
  } finally {
    ocultarCarregamento();
  }
}

document.addEventListener('DOMContentLoaded', carregarRelatorioPlano);
