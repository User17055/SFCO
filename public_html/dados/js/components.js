/**
 * Funcoes utilitarias puras.
 * Nenhuma funcao aqui cria ou retorna HTML - todas recebem dados e
 * devolvem valores simples (texto, numero, cor) para o dashboard.js usar
 * ao preencher os elementos que ja existem no index.html.
 */

/** Classe CSS do selo (badge) de acordo com o status do plano */
const STATUS_BADGE_MAP = {
  'Ativo': 'selo-verde',
  'Pendente': 'selo-cinza',
  'Vencido': 'selo-vermelho',
  'Cancelado': 'selo-vermelho',
};

/** Paleta usada para colorir o fundo dos avatares de iniciais */
const CORES_AVATAR = ['#0c387e', '#2f5fa8', '#64748b', '#1c3f78', '#94a3b8', '#245095'];

/** Formata um numero como moeda brasileira (R$) */
function formatCurrency(value) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

/** Converte uma data no formato ISO (AAAA-MM-DD) para o formato BR (DD/MM/AAAA) */
function formatDate(isoDate) {
  const [y, m, d] = String(isoDate).slice(0, 10).split('-');
  if (!y || !m || !d) return '-';
  return `${d}/${m}/${y}`;
}

/** Extrai as iniciais (ate 2 letras) de um nome completo */
function getInitials(fullName) {
  return fullName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(n => n[0].toUpperCase())
    .join('');
}

/** Escolhe uma cor de fundo para o avatar com base na primeira letra do nome */
function corAvatar(name) {
  const indice = name.charCodeAt(0) % CORES_AVATAR.length;
  return CORES_AVATAR[indice];
}
