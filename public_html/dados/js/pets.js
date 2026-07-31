const EMOJI_ESPECIE = {
  Cachorro: '🐕',
  Gato: '🐈',
  Ave: '🐦',
  Roedor: '🐹',
  Outro: '🐾',
};

function idadePet(pet) {
  if (pet.idade !== null) return pet.idade === 1 ? '1 ano' : `${pet.idade} anos`;
  if (!pet.nascimento) return '-';

  const [ano, mes, dia] = pet.nascimento.split('-').map(Number);
  const hoje = new Date();
  let idade = hoje.getFullYear() - ano;
  if (hoje.getMonth() + 1 < mes || (hoje.getMonth() + 1 === mes && hoje.getDate() < dia)) idade--;
  return idade === 1 ? '1 ano' : `${Math.max(idade, 0)} anos`;
}

function td(texto, classe = 'text-slate-600') {
  const elemento = document.createElement('td');
  elemento.className = classe;
  elemento.textContent = texto;
  return elemento;
}

function preencherPets(pets) {
  const corpo = document.getElementById('pets-body');
  corpo.innerHTML = '';

  if (pets.length === 0) {
    const linha = document.createElement('tr');
    const vazio = td('Nenhum pet cadastrado.', 'text-center text-slate-500 py-8');
    vazio.colSpan = 6;
    linha.appendChild(vazio);
    corpo.appendChild(linha);
    return;
  }

  pets.forEach((pet) => {
    const linha = document.createElement('tr');
    const petCelula = document.createElement('td');
    const grupo = document.createElement('div');
    grupo.className = 'flex items-center gap-3';
    const avatar = document.createElement('div');
    avatar.className = 'avatar-iniciais';
    avatar.style.cssText = 'width:32px;height:32px;font-size:16px;background:#f1f5f9;';
    avatar.textContent = EMOJI_ESPECIE[pet.especie] || '🐾';
    const nome = document.createElement('span');
    nome.className = 'font-medium';
    nome.textContent = pet.nome;
    grupo.append(avatar, nome);
    petCelula.appendChild(grupo);
    linha.appendChild(petCelula);
    linha.appendChild(td(idadePet(pet)));
    linha.appendChild(td(pet.raca || '-'));
    linha.appendChild(td(pet.tutor));
    linha.appendChild(td(pet.plano));

    const statusCelula = document.createElement('td');
    const status = document.createElement('span');
    status.className = `selo ${STATUS_BADGE_MAP[pet.status] || 'selo-cinza'}`;
    const ponto = document.createElement('span');
    ponto.className = 'selo-ponto';
    status.append(ponto, document.createTextNode(pet.status));
    statusCelula.appendChild(status);
    linha.appendChild(statusCelula);
    corpo.appendChild(linha);
  });
}

async function carregarPets() {
  try {
    const resposta = await fetch('api/pets.php', { headers: { Accept: 'application/json' }, cache: 'no-store' });
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
    preencherPets(resultado.pets);
  } catch (erro) {
    const corpo = document.getElementById('pets-body');
    corpo.innerHTML = '<tr><td colspan="6" class="text-center text-red-700 py-8"></td></tr>';
    corpo.querySelector('td').textContent = erro.message || 'Não foi possível carregar os pets.';
  }
}

document.addEventListener('DOMContentLoaded', carregarPets);
