# TudoPraPet: cadastro funcional com PHP e MySQL

Esta é a versão funcional instalada em `public_html/dados`. O cadastro deixou
de ser uma simulação em JavaScript e agora salva o tutor, seus pets e as
assinaturas no banco MySQL.

## Estado da instalação

O banco de testes `dadosplanilha` já está configurado em
`config/banco.php`. A conexão foi testada com sucesso no MySQL 5.7 da
hospedagem, e a coluna necessária `pets.nascimento` já foi adicionada.

Para publicar, envie o conteúdo da pasta `dados` ao servidor. O PHP precisa
ser versão 8 ou superior e ter a extensão `pdo_mysql` habilitada.

## Arquivos importantes

```text
dados/
├── api/
│   └── cadastrar.php       recebe, valida e grava o cadastro
├── config/
│   └── banco.php           conexão PDO com o banco de testes
├── sql/
│   ├── estrutura.sql       estrutura para uma instalação vazia
│   └── migrar_banco_existente.php
├── js/
│   └── cadastro.js         valida e envia o formulário com fetch
└── cadastro.html           formulário do tutor, pets e planos
```

As pastas `config` e `sql` possuem `.htaccess` para bloquear acesso HTTP
direto. Isso não impede o PHP de carregar internamente a conexão.

## Estrutura usada no banco

O sistema aproveita a estrutura que já existia na hospedagem:

- `clientes`: dados do tutor;
- `pets`: dados dos animais e o ID do cliente responsável;
- `planos`: catálogo com Bronze, Prata e Ouro;
- `assinaturas`: ligação entre um pet e um plano, com início e vencimento;
- `usuarios`: tabela já existente, ainda não usada pelo login demonstrativo.

O relacionamento do cadastro é:

```text
clientes 1 ─── N pets 1 ─── N assinaturas N ─── 1 planos
```

Um plano não é recriado a cada cadastro. O PHP procura o plano pelo nome e
salva seu ID em `assinaturas`.

## Como o envio funciona

O evento `submit` em `js/cadastro.js` executa estas etapas:

1. Impede o envio HTML tradicional com `preventDefault()`.
2. Valida os campos visíveis.
3. Percorre todos os blocos `[data-pet-item]`.
4. Monta um objeto com tutor e pets.
5. Converte o objeto para JSON.
6. Envia para `api/cadastrar.php` usando `fetch`.
7. Só limpa o formulário depois de receber HTTP `201` do servidor.
8. Mantém os dados preenchidos e mostra a mensagem se ocorrer um erro.

O corpo enviado tem este formato:

```json
{
  "nome": "Ana Ferreira",
  "cpf": "529.982.247-25",
  "telefone": "(11) 99999-9999",
  "email": "ana@example.com",
  "pets": [
    {
      "nome": "Thor",
      "especie": "Cachorro",
      "raca": "Vira-lata",
      "sexo": "Macho",
      "tipoIdade": "idade",
      "idade": "3",
      "nascimento": null,
      "peso": "10.5",
      "plano": "Bronze",
      "dataInicio": "2026-07-31",
      "dataVencimento": "2026-08-31"
    }
  ]
}
```

## O que o PHP valida

O navegador não é considerado uma fonte confiável. O endpoint repete as
validações no servidor:

- método HTTP e `Content-Type`;
- tamanho máximo da requisição;
- JSON válido;
- nome e limites de caracteres;
- dígitos verificadores do CPF;
- telefone com DDD;
- formato do e-mail;
- quantidade de pets;
- espécie, sexo e plano dentro das opções aceitas;
- idade entre 0 e 40 ou nascimento não futuro;
- peso positivo;
- datas válidas e vencimento posterior ao início.

As máscaras de CPF e telefone são removidas antes da gravação.

## Por que é usada uma transação

O cadastro grava informações em três pontos diferentes. O endpoint chama
`beginTransaction()` antes de inserir os dados e `commit()` somente no final.

Se a gravação de qualquer pet ou assinatura falhar, `rollBack()` desfaz também
o cliente e os pets anteriores. Assim, o banco não fica com um cadastro pela
metade.

As consultas usam `PDO::prepare()` e parâmetros, evitando concatenar os valores
recebidos diretamente no SQL.

## Respostas do endpoint

- `201`: cadastro concluído;
- `409`: CPF já cadastrado;
- `422`: campos inválidos;
- `405`: método HTTP incorreto;
- `415`: conteúdo diferente de JSON;
- `500`: falha inesperada no banco.

Os detalhes internos do banco são enviados apenas ao log do PHP. O navegador
recebe uma mensagem simples.

## Instalação em outro banco vazio

1. Crie um banco MySQL.
2. Importe `sql/estrutura.sql` pelo phpMyAdmin.
3. Edite host, porta, banco, usuário e senha em `config/banco.php`.
4. Confirme que o servidor possui PHP 8 e `pdo_mysql`.
5. Abra `login.html` pelo endereço HTTP do servidor.

Não abra `cadastro.html` com clique duplo, porque PHP só funciona quando a
página é servida por Apache, Nginx ou outro servidor HTTP configurado com PHP.

## Teste realizado

Foi executado um cadastro completo no banco remoto com:

- um cliente;
- um pet;
- uma assinatura Bronze;
- data de início e vencimento.

O endpoint respondeu com HTTP `201`, e uma consulta confirmou os
relacionamentos entre `clientes`, `pets`, `assinaturas` e `planos`. O registro
artificial criado para esse teste foi removido em seguida.

## Login e primeira troca de senha

O login agora consulta a tabela `usuarios` por meio de `api/login.php`. As
senhas são armazenadas com `password_hash()` e conferidas com
`password_verify()`.

Quando `usuarios.trocar_senha` vale `1`, o usuário é enviado para
`trocar-senha.html`. Depois que `api/alterar-senha.php` salva a nova senha, o
campo passa para `0` e o painel é liberado. O logout também encerra a sessão PHP.

O endpoint de cadastro exige uma sessão autenticada e impede o cadastro
enquanto a troca obrigatória estiver pendente.

## Dashboard com dados reais

O `index.html` não carrega mais os valores `MOCK_*` de `js/data.js`. O arquivo
`js/dashboard.js` consulta `api/dashboard.php`, que calcula diretamente no
MySQL:

- total de clientes e pets;
- assinaturas ativas, próximas do vencimento e vencidas;
- receita vigente, valores futuros, vencidos e cancelados;
- receita e novos clientes dos últimos seis meses;
- distribuição atual das assinaturas por status;
- nome, e-mail e iniciais do usuário autenticado.

Uma assinatura futura é mostrada como pendente. Uma assinatura fora da data de
vigência é mostrada como vencida. Cancelamentos usam o campo real
`assinaturas.status`, adicionado pela migração.

## Clientes, pets e planos reais

As três páginas de consulta também foram conectadas ao MySQL:

- `api/clientes.php` lista tutores, quantidade de pets, planos e status, além
  dos gráficos reais de novos clientes e assinaturas por status;
- `api/pets.php` lista pet, idade calculada, raça, tutor, plano mais recente e
  status;
- `api/planos.php` lista as assinaturas com cliente, pet, valor, vencimento e
  status, além das contratações dos últimos seis meses.

Os JavaScripts não carregam mais `js/data.js`, e o arquivo com os arrays
`MOCK_*` foi removido. Quando uma tabela estiver vazia, a interface informa que
nenhum registro foi cadastrado.
