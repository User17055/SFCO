/**
 * Dados ficticios (mock) para simular um backend.
 * Nenhuma chamada de rede, banco de dados ou persistencia real acontece aqui.
 */

const MOCK_CLIENTS = [
  { id: 1, name: 'Ana Beatriz Ferreira', phone: '(11) 98221-4432', cpf: '385.221.440-11', petsCount: 2, plan: 'Plano Gold', status: 'Ativo' },
  { id: 2, name: 'Carlos Eduardo Souza', phone: '(11) 97744-1029', cpf: '221.554.870-02', petsCount: 1, plan: 'Plano Prata', status: 'Ativo' },
  { id: 3, name: 'Marina Costa Lima', phone: '(21) 96633-7712', cpf: '109.887.330-45', petsCount: 3, plan: 'Plano Gold', status: 'Pendente' },
  { id: 4, name: 'Fernando Henrique Alves', phone: '(31) 99887-2210', cpf: '774.221.900-33', petsCount: 1, plan: 'Plano Basico', status: 'Vencido' },
  { id: 5, name: 'Juliana Pereira Santos', phone: '(11) 98765-4321', cpf: '556.332.110-88', petsCount: 2, plan: 'Plano Prata', status: 'Ativo' },
  { id: 6, name: 'Rodrigo Martins Silva', phone: '(41) 99123-5566', cpf: '229.887.001-22', petsCount: 1, plan: 'Plano Basico', status: 'Cancelado' },
  { id: 7, name: 'Patricia Gomes Rocha', phone: '(11) 97711-2288', cpf: '332.665.990-77', petsCount: 4, plan: 'Plano Gold', status: 'Ativo' },
  { id: 8, name: 'Lucas Almeida Barros', phone: '(19) 98456-3321', cpf: '667.220.550-19', petsCount: 1, plan: 'Plano Prata', status: 'Pendente' },
  { id: 9, name: 'Camila Rodrigues Dias', phone: '(11) 96622-9987', cpf: '441.998.220-66', petsCount: 2, plan: 'Plano Gold', status: 'Ativo' },
  { id: 10, name: 'Bruno Cesar Nogueira', phone: '(51) 99887-6655', cpf: '118.443.220-90', petsCount: 1, plan: 'Plano Basico', status: 'Vencido' },
];

const MOCK_PETS = [
  { id: 1, name: 'Thor', species: 'Cachorro', breed: 'Golden Retriever', tutor: 'Ana Beatriz Ferreira', plan: 'Plano Gold', emoji: '🐕' },
  { id: 2, name: 'Mimi', species: 'Gato', breed: 'Siames', tutor: 'Ana Beatriz Ferreira', plan: 'Plano Gold', emoji: '🐈' },
  { id: 3, name: 'Bolt', species: 'Cachorro', breed: 'Vira-lata', tutor: 'Carlos Eduardo Souza', plan: 'Plano Prata', emoji: '🐕' },
  { id: 4, name: 'Nina', species: 'Gato', breed: 'Persa', tutor: 'Marina Costa Lima', plan: 'Plano Gold', emoji: '🐈' },
  { id: 5, name: 'Rex', species: 'Cachorro', breed: 'Labrador', tutor: 'Marina Costa Lima', plan: 'Plano Gold', emoji: '🐕' },
  { id: 6, name: 'Fifi', species: 'Cachorro', breed: 'Poodle', tutor: 'Marina Costa Lima', plan: 'Plano Gold', emoji: '🐩' },
  { id: 7, name: 'Simba', species: 'Gato', breed: 'Maine Coon', tutor: 'Fernando Henrique Alves', plan: 'Plano Basico', emoji: '🐈' },
  { id: 8, name: 'Luna', species: 'Cachorro', breed: 'Beagle', tutor: 'Juliana Pereira Santos', plan: 'Plano Prata', emoji: '🐕' },
  { id: 9, name: 'Belinha', species: 'Coelho', breed: 'Angora', tutor: 'Juliana Pereira Santos', plan: 'Plano Prata', emoji: '🐇' },
  { id: 10, name: 'Zeus', species: 'Cachorro', breed: 'Pastor Alemao', tutor: 'Patricia Gomes Rocha', plan: 'Plano Gold', emoji: '🐕' },
];

const MOCK_PLANS = [
  { id: 1, clientName: 'Ana Beatriz Ferreira', petName: 'Thor', planName: 'Plano Gold', value: 189.9, startDate: '2026-01-10', dueDate: '2026-08-10', status: 'Ativo' },
  { id: 2, clientName: 'Carlos Eduardo Souza', petName: 'Bolt', planName: 'Plano Prata', value: 129.9, startDate: '2025-12-01', dueDate: '2026-08-01', status: 'Ativo' },
  { id: 3, clientName: 'Marina Costa Lima', petName: 'Nina', planName: 'Plano Gold', value: 189.9, startDate: '2025-11-15', dueDate: '2026-08-05', status: 'Pendente' },
  { id: 4, clientName: 'Fernando Henrique Alves', petName: 'Simba', planName: 'Plano Basico', value: 79.9, startDate: '2025-06-20', dueDate: '2026-06-20', status: 'Vencido' },
  { id: 5, clientName: 'Juliana Pereira Santos', petName: 'Luna', planName: 'Plano Prata', value: 129.9, startDate: '2026-02-01', dueDate: '2026-09-01', status: 'Ativo' },
  { id: 6, clientName: 'Rodrigo Martins Silva', petName: 'Max', planName: 'Plano Basico', value: 79.9, startDate: '2025-05-10', dueDate: '2026-05-10', status: 'Cancelado' },
  { id: 7, clientName: 'Patricia Gomes Rocha', petName: 'Zeus', planName: 'Plano Gold', value: 189.9, startDate: '2026-03-01', dueDate: '2026-08-15', status: 'Ativo' },
  { id: 8, clientName: 'Lucas Almeida Barros', petName: 'Kiara', planName: 'Plano Prata', value: 129.9, startDate: '2025-10-05', dueDate: '2026-08-02', status: 'Pendente' },
  { id: 9, clientName: 'Camila Rodrigues Dias', petName: 'Amora', planName: 'Plano Gold', value: 189.9, startDate: '2026-01-20', dueDate: '2026-08-20', status: 'Ativo' },
  { id: 10, clientName: 'Bruno Cesar Nogueira', petName: 'Pingo', planName: 'Plano Basico', value: 79.9, startDate: '2025-04-18', dueDate: '2026-04-18', status: 'Vencido' },
];

/** Series ficticia de novos clientes por mes (para o grafico de barras do dashboard) */
const MOCK_NEW_CLIENTS_SERIES = {
  labels: ['Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul'],
  values: [8, 12, 10, 15, 13, 18],
};

/** Contagem ficticia de planos por status (para o grafico de rosca do dashboard) */
const MOCK_PLAN_STATUS_SERIES = {
  labels: ['Ativo', 'Pendente', 'Vencido', 'Cancelado'],
  values: [148, 21, 14, 9],
  colors: ['#16a34a', '#d97706', '#dc2626', '#94a3b8'],
};

/** Calcula os cards de KPI do dashboard a partir dos dados ficticios acima */
function getDashboardStats() {
  return {
    totalClients: 192,
    totalPets: 261,
    activePlans: 148,
    plansDueSoon: 21,
    overduePlans: 14,
  };
}

/** Valores ficticios de receita/prejuizo, proporcionais aos planos do getDashboardStats() */
function getFinanceStats() {
  return {
    activeRevenue: 24850.30,
    pendingRevenue: 2980.10,
    overdueLoss: 1740.60,
    canceledLoss: 890.20,
  };
}

/** Series ficticia de receita mensal (planos ativos) para o grafico financeiro */
const MOCK_REVENUE_SERIES = {
  labels: ['Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul'],
  values: [18200, 19850, 21100, 22400, 23600, 24850],
};
