/**
 * Logica da pagina Pets.
 * Toda a estrutura visual (tabela) ja existe pronta no pets.html. Aqui a
 * gente so preenche os elementos existentes com os dados de js/data.js.
 * Nenhuma funcao deste arquivo cria HTML novo.
 */

/** Formata a idade do pet no singular ou plural */
function formatAge(age) {
  return age === 1 ? '1 ano' : `${age} anos`;
}

/**
 * Preenche as linhas (ja existentes no HTML) da tabela de pets.
 * So define texto/estilo dos elementos de cada linha - nao cria linhas novas.
 */
function preencherTabelaPets() {
  const linhas = document.querySelectorAll('#pets-body tr');
  const pets = MOCK_PETS.slice(0, linhas.length);

  linhas.forEach((linha, i) => {
    const pet = pets[i];

    linha.querySelector('.linha-avatar').textContent = pet.emoji;
    linha.querySelector('.linha-nome').textContent = pet.name;
    linha.querySelector('.linha-idade').textContent = formatAge(pet.age);
    linha.querySelector('.linha-raca').textContent = pet.breed;
    linha.querySelector('.linha-plano').textContent = pet.plan;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  preencherTabelaPets();
});
