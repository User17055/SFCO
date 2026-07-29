/**
 * Funcoes utilitarias que geram HTML para componentes reutilizaveis.
 * Substituem os componentes React/Shadcn (Card, Badge, Table, etc.) em versao estatica.
 */

const STATUS_BADGE_MAP = {
  'Ativo': 'selo-verde',
  'Pendente': 'selo-amarelo',
  'Vencido': 'selo-vermelho',
  'Cancelado': 'selo-cinza',
};

function formatCurrency(value) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatDate(isoDate) {
  const [y, m, d] = isoDate.split('-');
  return `${d}/${m}/${y}`;
}

function getInitials(fullName) {
  return fullName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(n => n[0].toUpperCase())
    .join('');
}

/** Gera o HTML de um badge de status colorido com um ponto indicador */
function renderStatusBadge(status) {
  const cls = STATUS_BADGE_MAP[status] || 'selo-cinza';
  return `<span class="selo ${cls}"><span class="selo-ponto"></span>${status}</span>`;
}

/** Gera o HTML de um card de estatistica (KPI) para o dashboard */
function renderStatCard({ label, value, icon, accent, delay }) {
  return `
    <div class="cartao cartao-hover animar-entrada ${delay || ''} p-5 flex items-start justify-between">
      <div>
        <p class="text-sm text-slate-500 font-medium">${label}</p>
        <p class="text-2xl font-semibold text-slate-900 mt-1.5">${value}</p>
      </div>
      <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:${accent}1a;">
        <i data-lucide="${icon}" class="w-5 h-5" style="color:${accent};"></i>
      </div>
    </div>
  `;
}

/** Gera o HTML de um card financeiro (valor em R$ + selo de tendencia opcional) */
function renderFinanceCard({ label, value, icon, accent, trend, delay }) {
  const trendHtml = trend ? `
    <div class="mt-3">
      <span class="selo ${trend.sentiment === 'good' ? 'selo-verde' : 'selo-vermelho'}">
        <i data-lucide="${trend.direction === 'up' ? 'trending-up' : 'trending-down'}" class="w-3 h-3"></i>
        ${trend.value}
      </span>
    </div>
  ` : '';

  return `
    <div class="cartao cartao-hover animar-entrada ${delay || ''} p-5">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-sm text-slate-500 font-medium">${label}</p>
          <p class="text-2xl font-semibold text-slate-900 mt-1.5">${formatCurrency(value)}</p>
        </div>
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:${accent}1a;">
          <i data-lucide="${icon}" class="w-5 h-5" style="color:${accent};"></i>
        </div>
      </div>
      ${trendHtml}
    </div>
  `;
}

/** Gera um avatar circular com iniciais, cor derivada do nome */
function renderAvatar(name, size = 36) {
  const colors = ['#2563eb', '#16a34a', '#d97706', '#db2777', '#7c3aed', '#0891b2'];
  const idx = name.charCodeAt(0) % colors.length;
  return `
    <div class="rounded-full flex items-center justify-center font-semibold text-white shrink-0"
         style="width:${size}px;height:${size}px;background:${colors[idx]};font-size:${size * 0.38}px;">
      ${getInitials(name)}
    </div>
  `;
}
