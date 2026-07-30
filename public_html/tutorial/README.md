# Tutorial: transformar o cadastro fictício em PHP

Este guia foi escrito a partir da análise da pasta `public_html/dados`. Ele ensina
como fazer o formulário de `cadastro.html` gravar clientes, pets e planos em um
banco MySQL usando PHP, sem alterar o projeto original.

## 1. Como o cadastro funciona atualmente

Os arquivos envolvidos são:

- `dados/cadastro.html`: contém o formulário do tutor e o template usado para
  adicionar um ou mais pets;
- `dados/js/cadastro.js`: cria os blocos de pets, aplica máscaras, valida os
  campos e intercepta o envio do formulário;
- `dados/js/data.js`: contém somente dados fictícios usados nas outras telas;
- `dados/js/auth.js`: controla uma autenticação fictícia pelo `sessionStorage`.

No final de `dados/js/cadastro.js`, o evento `submit` usa
`event.preventDefault()`. Depois da validação, ele apenas mostra a mensagem de
sucesso e executa `form.reset()`. Nenhum dado é enviado para um servidor.

Para transformar isso em um cadastro real, o fluxo deve ficar assim:

1. O JavaScript valida e reúne os campos.
2. O navegador envia JSON para um arquivo PHP usando `fetch`.
3. O PHP valida novamente os dados.
4. O PHP abre uma transação no MySQL.
5. O cliente é inserido e seu ID é recuperado.
6. Cada pet é inserido ligado ao cliente.
7. O plano de cada pet é inserido ligado ao pet.
8. Se qualquer operação falhar, toda a transação é desfeita.

> A validação do JavaScript melhora a experiência do usuário, mas não protege o
> sistema. Todos os dados precisam ser validados novamente no PHP.

## 2. Estrutura recomendada

Quando você for fazer a conversão, crie esta estrutura dentro de `dados`:

```text
dados/
├── api/
│   └── cadastrar.php
├── config/
│   └── banco.php
├── sql/
│   └── estrutura.sql
├── cadastro.html
└── js/
    └── cadastro.js
```

Em produção, o ideal é deixar a configuração do banco fora de `public_html`.
Para um exercício local com XAMPP, a estrutura acima é suficiente, desde que
o arquivo de configuração não seja publicado em um repositório público.

## 3. Criar o banco de dados

No phpMyAdmin, abra a aba **SQL** e execute:

```sql
CREATE DATABASE tudoprapet
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tudoprapet;

CREATE TABLE clientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone VARCHAR(11) NOT NULL,
    email VARCHAR(190) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(30) NOT NULL,
    raca VARCHAR(100) NOT NULL,
    sexo ENUM('Macho', 'Femea') NOT NULL,
    idade TINYINT UNSIGNED NULL,
    nascimento DATE NULL,
    peso DECIMAL(6,2) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pets_cliente
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE planos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    nome ENUM('Bronze', 'Prata', 'Ouro') NOT NULL,
    data_inicio DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('Ativo', 'Pendente', 'Vencido', 'Cancelado')
      NOT NULL DEFAULT 'Ativo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_planos_pet
      FOREIGN KEY (pet_id) REFERENCES pets(id)
      ON DELETE CASCADE
) ENGINE=InnoDB;
```

O CPF é armazenado somente com 11 números. A máscara `000.000.000-00` continua
aparecendo na tela, mas deve ser removida antes da gravação.

## 4. Fazer a conexão PHP com PDO

O conteúdo de `dados/config/banco.php` pode ser:

```php
<?php
declare(strict_types=1);

function conectarBanco(): PDO
{
    $host = 'localhost';
    $porta = '3306';
    $banco = 'tudoprapet';
    $usuario = 'root';
    $senha = ''; // No XAMPP local, normalmente começa vazia.

    $dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

    return new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
```

No servidor de hospedagem, substitua usuário, senha e nome do banco pelos dados
fornecidos no painel da hospedagem. Não mostre erros ou senhas do banco na
resposta enviada ao navegador.

## 5. Dar nomes aos campos dinâmicos

Os campos do tutor já possuem `name`, mas os campos de pet usam apenas
`data-field`. Isso funciona porque o JavaScript vai montar um objeto JSON.
Não é necessário transformar esses campos em `pets[0][nome]`, desde que todos
sejam coletados antes do `fetch`.

Acrescente esta função em `dados/js/cadastro.js`, antes do
`document.addEventListener`:

```javascript
function montarDadosCadastro(form, petsContainer) {
  const pets = Array.from(
    petsContainer.querySelectorAll('[data-pet-item]')
  ).map((bloco) => {
    const tipoIdade = bloco
      .querySelector('[data-field="idade-tipo"]:checked').value;

    return {
      nome: bloco.querySelector('[data-field="nome"]').value.trim(),
      especie: bloco.querySelector('[data-field="especie"]').value,
      raca: bloco.querySelector('[data-field="raca"]').value.trim(),
      sexo: bloco.querySelector('[data-field="sexo"]').value,
      tipoIdade,
      idade: tipoIdade === 'idade'
        ? bloco.querySelector('[data-field="idade"]').value
        : null,
      nascimento: tipoIdade === 'nascimento'
        ? bloco.querySelector('[data-field="nascimento"]').value
        : null,
      peso: bloco.querySelector('[data-field="peso"]').value || null,
      plano: bloco.querySelector('[data-field="plano"]').value,
      dataInicio: bloco.querySelector('[data-field="data-inicio"]').value,
      dataVencimento: bloco
        .querySelector('[data-field="data-vencimento"]').value,
    };
  });

  return {
    nome: form.querySelector('#campo-nome').value.trim(),
    cpf: form.querySelector('#campo-cpf').value,
    telefone: form.querySelector('#campo-telefone').value,
    email: form.querySelector('#campo-email').value.trim(),
    pets,
  };
}
```

## 6. Criar o endpoint de cadastro

O arquivo `dados/api/cadastrar.php` recebe o JSON, valida os dados e grava tudo
em uma única transação:

```php
<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/config/banco.php';

function responder(int $status, array $conteudo): never
{
    http_response_code($status);
    echo json_encode($conteudo, JSON_UNESCAPED_UNICODE);
    exit;
}

function somenteDigitos(mixed $valor): string
{
    return preg_replace('/\D+/', '', (string) $valor) ?? '';
}

function texto(mixed $valor): string
{
    return trim((string) $valor);
}

function dataValida(string $data): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $objeto !== false && $objeto->format('Y-m-d') === $data;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);

if (!is_array($entrada)) {
    responder(400, ['sucesso' => false, 'mensagem' => 'JSON inválido.']);
}

$nome = texto($entrada['nome'] ?? '');
$cpf = somenteDigitos($entrada['cpf'] ?? '');
$telefone = somenteDigitos($entrada['telefone'] ?? '');
$email = texto($entrada['email'] ?? '');
$pets = $entrada['pets'] ?? null;

$erros = [];

if ($nome === '' || mb_strlen($nome) > 150) {
    $erros[] = 'Informe um nome válido.';
}
if (strlen($cpf) !== 11) {
    $erros[] = 'Informe um CPF com 11 dígitos.';
}
if (strlen($telefone) < 10 || strlen($telefone) > 11) {
    $erros[] = 'Informe um telefone válido com DDD.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $erros[] = 'Informe um e-mail válido.';
}
if (!is_array($pets) || count($pets) < 1 || count($pets) > 20) {
    $erros[] = 'Cadastre entre 1 e 20 pets.';
}

$petsValidados = [];
$especies = ['Cachorro', 'Gato', 'Ave', 'Roedor', 'Outro'];
$sexos = ['Macho', 'Femea'];
$planos = ['Bronze', 'Prata', 'Ouro'];

if (is_array($pets)) {
    foreach ($pets as $indice => $pet) {
        if (!is_array($pet)) {
            $erros[] = 'Os dados de um pet são inválidos.';
            continue;
        }

        $numero = $indice + 1;
        $petNome = texto($pet['nome'] ?? '');
        $especie = texto($pet['especie'] ?? '');
        $raca = texto($pet['raca'] ?? '');
        $sexo = texto($pet['sexo'] ?? '');
        $tipoIdade = texto($pet['tipoIdade'] ?? '');
        $idade = $pet['idade'] ?? null;
        $nascimento = texto($pet['nascimento'] ?? '');
        $peso = $pet['peso'] ?? null;
        $plano = texto($pet['plano'] ?? '');
        $inicio = texto($pet['dataInicio'] ?? '');
        $vencimento = texto($pet['dataVencimento'] ?? '');

        if ($petNome === '' || mb_strlen($petNome) > 100) {
            $erros[] = "Informe um nome válido para o pet {$numero}.";
        }
        if (!in_array($especie, $especies, true)) {
            $erros[] = "Espécie inválida no pet {$numero}.";
        }
        if ($raca === '' || mb_strlen($raca) > 100) {
            $erros[] = "Informe a raça do pet {$numero}.";
        }
        if (!in_array($sexo, $sexos, true)) {
            $erros[] = "Sexo inválido no pet {$numero}.";
        }

        $idadeBanco = null;
        $nascimentoBanco = null;
        if (
            $tipoIdade === 'idade'
            && filter_var(
                $idade,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 40]]
            ) !== false
        ) {
            $idadeBanco = (int) $idade;
        } elseif (
            $tipoIdade === 'nascimento'
            && dataValida($nascimento)
            && $nascimento <= date('Y-m-d')
        ) {
            $nascimentoBanco = $nascimento;
        } else {
            $erros[] = "Idade ou nascimento inválido no pet {$numero}.";
        }

        $pesoBanco = null;
        if ($peso !== null && $peso !== '') {
            if (!is_numeric($peso) || (float) $peso <= 0 || (float) $peso > 9999) {
                $erros[] = "Peso inválido no pet {$numero}.";
            } else {
                $pesoBanco = (float) $peso;
            }
        }

        if (!in_array($plano, $planos, true)) {
            $erros[] = "Plano inválido no pet {$numero}.";
        }
        if (!dataValida($inicio)) {
            $erros[] = "Data de início inválida no pet {$numero}.";
        }
        if (!dataValida($vencimento) || $vencimento < $inicio) {
            $erros[] = "Data de vencimento inválida no pet {$numero}.";
        }

        $petsValidados[] = [
            'nome' => $petNome,
            'especie' => $especie,
            'raca' => $raca,
            'sexo' => $sexo,
            'idade' => $idadeBanco,
            'nascimento' => $nascimentoBanco,
            'peso' => $pesoBanco,
            'plano' => $plano,
            'inicio' => $inicio,
            'vencimento' => $vencimento,
        ];
    }
}

if ($erros !== []) {
    responder(422, [
        'sucesso' => false,
        'mensagem' => 'Revise os dados enviados.',
        'erros' => $erros,
    ]);
}

$pdo = null;

try {
    $pdo = conectarBanco();
    $pdo->beginTransaction();

    $inserirCliente = $pdo->prepare(
        'INSERT INTO clientes (nome, cpf, telefone, email)
         VALUES (:nome, :cpf, :telefone, :email)'
    );
    $inserirCliente->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'telefone' => $telefone,
        'email' => $email,
    ]);
    $clienteId = (int) $pdo->lastInsertId();

    $inserirPet = $pdo->prepare(
        'INSERT INTO pets
          (cliente_id, nome, especie, raca, sexo, idade, nascimento, peso)
         VALUES
          (:cliente_id, :nome, :especie, :raca, :sexo, :idade, :nascimento, :peso)'
    );

    $inserirPlano = $pdo->prepare(
        'INSERT INTO planos (pet_id, nome, data_inicio, data_vencimento)
         VALUES (:pet_id, :nome, :data_inicio, :data_vencimento)'
    );

    foreach ($petsValidados as $pet) {
        $inserirPet->execute([
            'cliente_id' => $clienteId,
            'nome' => $pet['nome'],
            'especie' => $pet['especie'],
            'raca' => $pet['raca'],
            'sexo' => $pet['sexo'],
            'idade' => $pet['idade'],
            'nascimento' => $pet['nascimento'],
            'peso' => $pet['peso'],
        ]);
        $petId = (int) $pdo->lastInsertId();

        $inserirPlano->execute([
            'pet_id' => $petId,
            'nome' => $pet['plano'],
            'data_inicio' => $pet['inicio'],
            'data_vencimento' => $pet['vencimento'],
        ]);
    }

    $pdo->commit();

    responder(201, [
        'sucesso' => true,
        'mensagem' => 'Cliente e pets cadastrados com sucesso.',
        'clienteId' => $clienteId,
    ]);
} catch (PDOException $erro) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // O código 1062 do MySQL representa uma chave única duplicada.
    if (($erro->errorInfo[1] ?? null) === 1062) {
        responder(409, [
            'sucesso' => false,
            'mensagem' => 'Este CPF já está cadastrado.',
        ]);
    }

    error_log($erro->getMessage());
    responder(500, [
        'sucesso' => false,
        'mensagem' => 'Não foi possível concluir o cadastro.',
    ]);
}
```

As consultas preparadas evitam injeção de SQL. A transação evita salvar somente
uma parte do cadastro: ou cliente, pets e planos são gravados juntos, ou nada é.

## 7. Trocar o cadastro fictício pelo envio ao PHP

No final de `dados/js/cadastro.js`, localize:

```javascript
form.addEventListener('submit', (event) => {
```

Troque o evento inteiro por:

```javascript
form.addEventListener('submit', async (event) => {
  event.preventDefault();
  mensagemSucesso.hidden = true;

  const clienteValido = validarCliente(form);
  let petsValidos = true;

  petsContainer.querySelectorAll('[data-pet-item]').forEach((bloco) => {
    if (!validarPet(bloco)) petsValidos = false;
  });

  if (!clienteValido || !petsValidos) return;

  const botaoEnviar = form.querySelector('[type="submit"]');
  const textoOriginal = botaoEnviar.innerHTML;
  botaoEnviar.disabled = true;
  botaoEnviar.textContent = 'Cadastrando...';

  try {
    const resposta = await fetch('api/cadastrar.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(montarDadosCadastro(form, petsContainer)),
    });

    const resultado = await resposta.json();

    if (!resposta.ok) {
      throw new Error(resultado.mensagem || 'Não foi possível cadastrar.');
    }

    mensagemSucesso.querySelector('span').textContent = resultado.mensagem;
    mensagemSucesso.hidden = false;
    form.reset();
    petsContainer.innerHTML = '';
    adicionarPet(petsContainer, templatePet);
  } catch (erro) {
    alert(erro.message);
  } finally {
    botaoEnviar.disabled = false;
    botaoEnviar.innerHTML = textoOriginal;
    if (window.lucide) lucide.createIcons();
  }
});
```

O formulário só deve ser apagado depois que o servidor confirmar o cadastro.
Se houver erro de rede, CPF repetido ou falha no banco, os dados digitados
permanecem na tela.

## 8. Sobre a autenticação atual

O projeto usa:

```javascript
sessionStorage.getItem('tpp-auth')
```

Isso não é autenticação segura, pois qualquer pessoa pode alterar o valor pelo
console do navegador. Para um sistema real, o login também precisa ser
convertido para PHP, usando:

- tabela de usuários;
- senhas armazenadas com `password_hash`;
- conferência com `password_verify`;
- sessão iniciada por `session_start`;
- verificação da sessão em todos os endpoints PHP.

O cadastro do tutor pode ser aprendido e testado primeiro, mas não publique o
sistema com dados reais enquanto a autenticação continuar no `sessionStorage`.

## 9. Como testar no XAMPP

1. Coloque o projeto dentro de `C:\xampp\htdocs`.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Crie o banco e as tabelas pelo phpMyAdmin.
4. Confira usuário e senha em `config/banco.php`.
5. Acesse o projeto por `http://localhost/...`; não abra o HTML com clique duplo.
6. Entre no painel, preencha o cadastro e envie.
7. No phpMyAdmin, confira as tabelas `clientes`, `pets` e `planos`.
8. Tente cadastrar o mesmo CPF novamente e confirme que o sistema informa a
   duplicidade sem criar registros parciais.

Também teste:

- um cliente com dois ou mais pets;
- idade igual a zero;
- data de nascimento futura;
- vencimento anterior ao início;
- telefone com 10 e com 11 dígitos;
- interrupção do MySQL durante o envio;
- campos enviados manualmente fora das opções permitidas.

## 10. Próxima etapa

Depois que o cadastro estiver persistindo, `clientes.js`, `pets.js`,
`planos.js` e `data.js` ainda continuarão exibindo dados fictícios. A próxima
conversão é criar endpoints PHP de consulta e substituir os arrays `MOCK_*`
por chamadas `fetch`. Essa mudança é separada do cadastro e deve ser feita
somente depois de confirmar que as três tabelas estão recebendo os dados
corretamente.
