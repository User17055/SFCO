<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/config/banco.php';

function responder(int $status, array $conteudo): never
{
    http_response_code($status);
    echo json_encode($conteudo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

function cpfValido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($tamanho = 9; $tamanho < 11; $tamanho++) {
        $soma = 0;
        for ($indice = 0; $indice < $tamanho; $indice++) {
            $soma += (int) $cpf[$indice] * (($tamanho + 1) - $indice);
        }

        $digito = (10 * $soma) % 11;
        if ($digito === 10) {
            $digito = 0;
        }
        if ((int) $cpf[$tamanho] !== $digito) {
            return false;
        }
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, [
        'sucesso' => false,
        'mensagem' => 'Metodo nao permitido.',
    ]);
}

$tipoConteudo = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (!str_starts_with($tipoConteudo, 'application/json')) {
    responder(415, [
        'sucesso' => false,
        'mensagem' => 'Envie os dados no formato JSON.',
    ]);
}

$tamanhoRequisicao = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($tamanhoRequisicao > 262144) {
    responder(413, [
        'sucesso' => false,
        'mensagem' => 'Os dados enviados excedem o tamanho permitido.',
    ]);
}

try {
    $entrada = json_decode(
        file_get_contents('php://input'),
        true,
        32,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    responder(400, [
        'sucesso' => false,
        'mensagem' => 'JSON invalido.',
    ]);
}

if (!is_array($entrada)) {
    responder(400, [
        'sucesso' => false,
        'mensagem' => 'Dados de cadastro invalidos.',
    ]);
}

$nome = texto($entrada['nome'] ?? '');
$cpf = somenteDigitos($entrada['cpf'] ?? '');
$telefone = somenteDigitos($entrada['telefone'] ?? '');
$email = texto($entrada['email'] ?? '');
$pets = $entrada['pets'] ?? null;
$erros = [];

if ($nome === '' || mb_strlen($nome) > 150) {
    $erros[] = 'Informe um nome valido.';
}
if (!cpfValido($cpf)) {
    $erros[] = 'Informe um CPF valido.';
}
if (strlen($telefone) < 10 || strlen($telefone) > 11) {
    $erros[] = 'Informe um telefone valido com DDD.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $erros[] = 'Informe um e-mail valido.';
}
if (!is_array($pets) || count($pets) < 1 || count($pets) > 20) {
    $erros[] = 'Cadastre entre 1 e 20 pets.';
}

$petsValidados = [];
$especies = ['Cachorro', 'Gato', 'Ave', 'Roedor', 'Outro'];
$sexos = ['Macho', 'Femea'];
$nomesPlanos = ['Bronze', 'Prata', 'Ouro'];

if (is_array($pets)) {
    foreach ($pets as $indice => $pet) {
        if (!is_array($pet)) {
            $erros[] = 'Os dados de um pet sao invalidos.';
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
            $erros[] = "Informe um nome valido para o pet {$numero}.";
        }
        if (!in_array($especie, $especies, true)) {
            $erros[] = "Especie invalida no pet {$numero}.";
        }
        if ($raca === '' || mb_strlen($raca) > 100) {
            $erros[] = "Informe a raca do pet {$numero}.";
        }
        if (!in_array($sexo, $sexos, true)) {
            $erros[] = "Sexo invalido no pet {$numero}.";
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
            $erros[] = "Idade ou nascimento invalido no pet {$numero}.";
        }

        $pesoBanco = null;
        if ($peso !== null && $peso !== '') {
            if (!is_numeric($peso) || (float) $peso <= 0 || (float) $peso > 9999) {
                $erros[] = "Peso invalido no pet {$numero}.";
            } else {
                $pesoBanco = round((float) $peso, 2);
            }
        }

        if (!in_array($plano, $nomesPlanos, true)) {
            $erros[] = "Plano invalido no pet {$numero}.";
        }
        if (!dataValida($inicio)) {
            $erros[] = "Data de inicio invalida no pet {$numero}.";
        }
        if (!dataValida($vencimento) || $vencimento < $inicio) {
            $erros[] = "Data de vencimento invalida no pet {$numero}.";
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
        'mensagem' => 'Cliente e pets cadastrados com sucesso!',
        'clienteId' => $clienteId,
    ]);
} catch (PDOException $erro) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (($erro->errorInfo[1] ?? null) === 1062) {
        responder(409, [
            'sucesso' => false,
            'mensagem' => 'Este CPF ja esta cadastrado.',
        ]);
    }

    error_log($erro->getMessage());
    responder(500, [
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel concluir o cadastro.',
    ]);
}
