/**
 * Logica da pagina Cadastro.
 * Formulario ficticio: cadastra um cliente (tutor) com um ou mais pets,
 * cada pet com seu proprio plano. Valida os campos e mostra uma mensagem
 * de sucesso. Nenhuma chamada de rede ou persistencia real acontece aqui.
 */

let contadorPets = 0;

/** Formata o CPF digitado no padrao 000.000.000-00 conforme o usuario digita */
function formatarCpf(valor) {
  const digitos = valor.replace(/\D/g, '').slice(0, 11);
  return digitos
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

/** Formata o telefone digitado no padrao (00) 00000-0000 conforme o usuario digita */
function formatarTelefone(valor) {
  const digitos = valor.replace(/\D/g, '').slice(0, 11);
  return digitos
    .replace(/(\d{2})(\d)/, '($1) $2')
    .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}

/** Marca ou limpa o estado de erro visual de um campo do formulario */
function definirErroCampo(campo, mensagem) {
  const wrapper = campo.closest('.campo-form');
  const erro = wrapper.querySelector('.campo-erro');
  if (mensagem) {
    wrapper.classList.add('campo-invalido');
    erro.textContent = mensagem;
  } else {
    wrapper.classList.remove('campo-invalido');
    erro.textContent = '';
  }
}

/** Valida os dados do cliente (tutor); retorna true se estiver tudo ok */
function validarCliente(form) {
  let valido = true;

  const nome = form.querySelector('#campo-nome');
  if (!nome.value.trim()) {
    definirErroCampo(nome, 'Informe o nome do cliente.');
    valido = false;
  } else {
    definirErroCampo(nome, '');
  }

  const cpf = form.querySelector('#campo-cpf');
  const cpfDigitos = cpf.value.replace(/\D/g, '');
  if (cpfDigitos.length !== 11) {
    definirErroCampo(cpf, 'Informe um CPF valido (11 digitos).');
    valido = false;
  } else {
    definirErroCampo(cpf, '');
  }

  const telefone = form.querySelector('#campo-telefone');
  const telefoneDigitos = telefone.value.replace(/\D/g, '');
  if (telefoneDigitos.length < 10 || telefoneDigitos.length > 11) {
    definirErroCampo(telefone, 'Informe um telefone valido com DDD.');
    valido = false;
  } else {
    definirErroCampo(telefone, '');
  }

  const email = form.querySelector('#campo-email');
  const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
  if (!emailValido) {
    definirErroCampo(email, 'Informe um e-mail valido.');
    valido = false;
  } else {
    definirErroCampo(email, '');
  }

  return valido;
}

/** Valida os campos de um bloco de pet (dados do pet + plano); retorna true se estiver tudo ok */
function validarPet(petEl) {
  let valido = true;

  const nome = petEl.querySelector('[data-field="nome"]');
  if (!nome.value.trim()) {
    definirErroCampo(nome, 'Informe o nome do pet.');
    valido = false;
  } else {
    definirErroCampo(nome, '');
  }

  const especie = petEl.querySelector('[data-field="especie"]');
  if (!especie.value) {
    definirErroCampo(especie, 'Selecione a especie.');
    valido = false;
  } else {
    definirErroCampo(especie, '');
  }

  const raca = petEl.querySelector('[data-field="raca"]');
  if (!raca.value.trim()) {
    definirErroCampo(raca, 'Informe a raca do pet.');
    valido = false;
  } else {
    definirErroCampo(raca, '');
  }

  const sexo = petEl.querySelector('[data-field="sexo"]');
  if (!sexo.value) {
    definirErroCampo(sexo, 'Selecione o sexo.');
    valido = false;
  } else {
    definirErroCampo(sexo, '');
  }

  const tipoIdade = petEl.querySelector('[data-field="idade-tipo"]:checked').value;
  const idade = petEl.querySelector('[data-field="idade"]');
  const nascimento = petEl.querySelector('[data-field="nascimento"]');
  if (tipoIdade === 'idade') {
    if (idade.value === '' || Number(idade.value) < 0) {
      definirErroCampo(idade, 'Informe uma idade valida.');
      valido = false;
    } else {
      definirErroCampo(idade, '');
    }
  } else {
    if (!nascimento.value || nascimento.value > new Date().toISOString().slice(0, 10)) {
      definirErroCampo(nascimento, 'Informe uma data de nascimento valida.');
      valido = false;
    } else {
      definirErroCampo(nascimento, '');
    }
  }

  const peso = petEl.querySelector('[data-field="peso"]');
  if (peso.value !== '' && Number(peso.value) <= 0) {
    definirErroCampo(peso, 'Informe um peso valido.');
    valido = false;
  } else {
    definirErroCampo(peso, '');
  }

  const plano = petEl.querySelector('[data-field="plano"]');
  if (!plano.value) {
    definirErroCampo(plano, 'Selecione um plano.');
    valido = false;
  } else {
    definirErroCampo(plano, '');
  }

  const dataInicio = petEl.querySelector('[data-field="data-inicio"]');
  if (!dataInicio.value) {
    definirErroCampo(dataInicio, 'Informe a data de inicio.');
    valido = false;
  } else {
    definirErroCampo(dataInicio, '');
  }

  const dataVencimento = petEl.querySelector('[data-field="data-vencimento"]');
  if (!dataVencimento.value) {
    definirErroCampo(dataVencimento, 'Informe a data de vencimento.');
    valido = false;
  } else if (dataInicio.value && dataVencimento.value < dataInicio.value) {
    definirErroCampo(dataVencimento, 'A data de vencimento deve ser igual ou posterior a de inicio.');
    valido = false;
  } else {
    definirErroCampo(dataVencimento, '');
  }

  return valido;
}

/** Atualiza o titulo "Pet N" de cada bloco e habilita/desabilita o botao de remover */
function renumerarPets(container) {
  const blocos = container.querySelectorAll('[data-pet-item]');
  blocos.forEach((bloco, indice) => {
    bloco.querySelector('[data-pet-titulo]').textContent = `Pet ${indice + 1}`;
    bloco.querySelector('[data-acao-remover-pet]').disabled = blocos.length === 1;
  });
}

/** Cria e insere um novo bloco de pet no container, a partir do template da pagina */
function adicionarPet(container, template) {
  const idPet = contadorPets++;
  const fragmento = template.content.cloneNode(true);
  const bloco = fragmento.querySelector('[data-pet-item]');

  bloco.querySelectorAll('[data-label-for]').forEach((label) => {
    const idCampo = `pet-${idPet}-${label.dataset.labelFor}`;
    label.setAttribute('for', idCampo);
    const campo = bloco.querySelector(`[data-field="${label.dataset.labelFor}"]`);
    if (campo) campo.id = idCampo;
  });

  bloco.querySelectorAll('[data-field="idade-tipo"]').forEach((radio) => {
    radio.name = `pet-${idPet}-idade-tipo`;
  });

  const grupoIdade = bloco.querySelector('[data-grupo="idade"]');
  const grupoNascimento = bloco.querySelector('[data-grupo="nascimento"]');
  bloco.querySelectorAll('[data-field="idade-tipo"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      const usaIdade = bloco.querySelector('[data-field="idade-tipo"]:checked').value === 'idade';
      grupoIdade.classList.toggle('hidden', !usaIdade);
      grupoNascimento.classList.toggle('hidden', usaIdade);
    });
  });

  bloco.querySelector('[data-acao-remover-pet]').addEventListener('click', () => {
    if (container.querySelectorAll('[data-pet-item]').length <= 1) return;
    bloco.remove();
    renumerarPets(container);
  });

  container.appendChild(bloco);
  renumerarPets(container);
  if (window.lucide) lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-cadastro');
  const petsContainer = document.getElementById('pets-container');
  const templatePet = document.getElementById('template-pet-item');
  const botaoAdicionarPet = document.getElementById('botao-adicionar-pet');
  const campoCpf = document.getElementById('campo-cpf');
  const campoTelefone = document.getElementById('campo-telefone');
  const mensagemSucesso = document.getElementById('cadastro-sucesso');

  adicionarPet(petsContainer, templatePet);

  botaoAdicionarPet.addEventListener('click', () => {
    adicionarPet(petsContainer, templatePet);
  });

  campoCpf.addEventListener('input', () => {
    campoCpf.value = formatarCpf(campoCpf.value);
  });

  campoTelefone.addEventListener('input', () => {
    campoTelefone.value = formatarTelefone(campoTelefone.value);
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    mensagemSucesso.hidden = true;

    const clienteValido = validarCliente(form);
    let petsValidos = true;
    petsContainer.querySelectorAll('[data-pet-item]').forEach((bloco) => {
      if (!validarPet(bloco)) petsValidos = false;
    });

    if (!clienteValido || !petsValidos) return;

    // Cadastro ficticio: apenas exibe a confirmacao e reseta o formulario
    mensagemSucesso.hidden = false;
    form.reset();
    petsContainer.innerHTML = '';
    adicionarPet(petsContainer, templatePet);
  });
});
