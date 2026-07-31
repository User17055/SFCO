# Sistema de planos pet

Esta pasta contém o sistema PHP/MySQL funcional. Ele não controla contas a
receber. O valor exibido é apenas a projeção mensal obtida pela soma do valor
individual dos planos ativos.

## Dados importados

A fonte foi a planilha `PLANOS 2026.xlsx`.

- dezembro/2025 e janeiro a junho/2026: histórico mensal da aba `DETAILS`;
- julho/2026: situação atual da aba `JULHO`;
- cancelamentos: 248 registros da aba `CANCELAMENTOS`;
- agosto a dezembro dos cancelamentos: tratados como 2025;
- janeiro a julho dos cancelamentos: tratados como 2026.

Julho/2026 foi importado com 1.013 assinaturas, 649 clientes da planilha, 970
pets, 17 nomes de plano ativos e projeção mensal de R$ 77.371,29. Quatro linhas
incompletas foram preservadas: plano ausente virou `PLANO NAO INFORMADO` e
valor ausente virou zero.

O histórico mensal possui 7.106 linhas. A linha de Regina Sykora sem nome de pet
foi preservada como `PET NAO INFORMADO`.

O cadastro fictício antigo e os nomes demonstrativos Bronze, Prata e Ouro foram
removidos. Os totais atuais são formados somente pelos dados da planilha.

## Funções disponíveis

- dashboard com projeção, quantidades, histórico mensal e cancelamentos;
- criar, editar, buscar e excluir clientes;
- criar, editar, buscar e excluir pets;
- criar, renomear, ativar ou desativar nomes de planos;
- editar o plano e o valor mensal de cada pet;
- cancelar e reativar uma assinatura com motivo;
- consultar os cancelamentos importados da planilha.

## Estrutura do banco

- `clientes`: dados do tutor;
- `pets`: animais ligados ao tutor;
- `planos`: catálogo de nomes e valor padrão;
- `assinaturas`: plano atual de cada pet e seu valor individual;
- `historico_planos`: fotografia mensal importada;
- `cancelamentos`: histórico da aba de cancelamentos;
- `usuarios`: login e troca obrigatória de senha.

O campo `planos.valor` é apenas o valor sugerido ao criar uma assinatura. A
projeção usa `assinaturas.valor_mensal`, pois o mesmo nome de plano possui
valores diferentes na planilha.

## Repetir a importação

Na raiz do projeto, gere o arquivo intermediário:

```powershell
python scripts/extrair_planos_2026.py "C:\caminho\PLANOS 2026.xlsx" "scripts\planos_2026_importacao.json"
```

Depois aplique a migração e importe:

```powershell
php public_html/dados/sql/migrar_banco_existente.php
php public_html/dados/sql/importar_planos_2026.php scripts/planos_2026_importacao.json
```

Na instalação local usada durante o desenvolvimento, o driver foi habilitado
somente para o comando com `php -d extension=pdo_mysql`. Em uma hospedagem PHP
normal, `pdo_mysql` deve estar habilitado permanentemente.

A importação é idempotente: repetir o processo atualiza os mesmos registros em
vez de duplicá-los. A planilha original nunca é modificada.

## Publicação

Envie a pasta `public_html/dados` para o servidor e acesse `login.html` por
HTTP/HTTPS. O servidor precisa de PHP 8+, MySQL e `pdo_mysql`. Em instalação
nova, execute primeiro `sql/estrutura.sql`.

Os usuários `marco@saofrancisco.vet.br` e `sac@saofrancisco.vet.br` estão
configurados para exigir a troca de senha no primeiro acesso.
