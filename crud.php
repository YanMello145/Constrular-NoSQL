<?php

require_once __DIR__ . '/conexao_mongodb.php';

$colecao = $database->selectCollection('clientes');
$mensagem = '';

try {

    // CREATE - INSERIR CLIENTE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'inserir') {

        $ultimo = $colecao->findOne(
            [],
            ['sort' => ['_id' => -1]]
        );

        $novoId = $ultimo ? ((int) $ultimo['_id'] + 1) : 1;

        $colecao->insertOne([
            '_id' => $novoId,
            'nome' => trim($_POST['nome']),
            'cpf' => trim($_POST['cpf']),
            'telefone' => trim($_POST['telefone']),
            'email' => trim($_POST['email'])
        ]);

        $mensagem = "Cliente inserido com sucesso!";
    }

    // UPDATE - ATUALIZAR CLIENTE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'atualizar') {

        $resultado = $colecao->updateOne(
            ['_id' => (int) $_POST['id']],
            ['$set' => [
                'nome' => trim($_POST['nome']),
                'telefone' => trim($_POST['telefone']),
                'email' => trim($_POST['email'])
            ]]
        );

        $mensagem = $resultado->getMatchedCount() > 0
            ? "Cliente atualizado com sucesso!"
            : "Cliente não encontrado.";
    }

    // DELETE - EXCLUIR CLIENTE
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'excluir') {

        $resultado = $colecao->deleteOne([
            '_id' => (int) $_POST['id']
        ]);

        $mensagem = $resultado->getDeletedCount() > 0
            ? "Cliente excluído com sucesso!"
            : "Cliente não encontrado.";
    }

} catch (Throwable $erro) {
    $mensagem = "Erro: " . $erro->getMessage();
}

// READ - PESQUISAR E CONSULTAR CLIENTES
$busca = trim($_GET['busca'] ?? '');

$filtro = [];

if ($busca !== '') {
    $filtro['nome'] = [
        '$regex' => preg_quote($busca),
        '$options' => 'i'
    ];
}

$clientes = $colecao->find(
    $filtro,
    ['sort' => ['_id' => 1]]
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CRUD - Constrular NoSQL</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 30px;
        }

        .container {
            max-width: 950px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1, h2 {
            color: #d66b20;
        }

        form {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        input {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px 15px;
            background: #d66b20;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #b95816;
        }

        a {
            color: #d66b20;
            margin-left: 10px;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #d66b20;
            color: white;
        }

        .mensagem {
            padding: 12px;
            background: #e6f4ea;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .resultado {
            margin-bottom: 15px;
            color: #555;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Constrular - Sistema NoSQL</h1>

    <p>Gerenciamento de clientes utilizando MongoDB Atlas.</p>

    <?php if ($mensagem): ?>

        <div class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>


    <!-- CREATE -->

    <h2>1. Inserir cliente</h2>

    <form method="POST">

        <input type="hidden" name="acao" value="inserir">

        <input type="text" name="nome" placeholder="Nome" required>

        <input type="text" name="cpf" placeholder="CPF" required>

        <input type="text" name="telefone" placeholder="Telefone">

        <input type="email" name="email" placeholder="E-mail">

        <button type="submit">Cadastrar cliente</button>

    </form>


    <!-- UPDATE -->

    <h2>2. Atualizar cliente</h2>

    <form method="POST">

        <input type="hidden" name="acao" value="atualizar">

        <input type="number" name="id" placeholder="ID do cliente" required>

        <input type="text" name="nome" placeholder="Novo nome" required>

        <input type="text" name="telefone" placeholder="Novo telefone">

        <input type="email" name="email" placeholder="Novo e-mail">

        <button type="submit">Atualizar cliente</button>

    </form>


    <!-- DELETE -->

    <h2>3. Excluir cliente</h2>

    <form method="POST"
          onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">

        <input type="hidden" name="acao" value="excluir">

        <input type="number" name="id" placeholder="ID do cliente" required>

        <button type="submit">Excluir cliente</button>

    </form>


    <!-- READ -->

    <h2>4. Consultar clientes</h2>

    <form method="GET">

        <input
            type="text"
            name="busca"
            placeholder="Digite o nome do cliente"
            value="<?= htmlspecialchars($busca) ?>"
        >

        <button type="submit">Pesquisar</button>

        <a href="crud.php">Limpar pesquisa</a>

    </form>

    <div class="resultado">

        <?php if ($busca !== ''): ?>

            Resultados para:
            <strong><?= htmlspecialchars($busca) ?></strong>

        <?php else: ?>

            Todos os clientes cadastrados.

        <?php endif; ?>

    </div>


    <table>

        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>CPF</th>
            <th>Telefone</th>
            <th>E-mail</th>
        </tr>

        <?php

        $quantidade = 0;

        foreach ($clientes as $cliente):

            $quantidade++;

        ?>

            <tr>

                <td><?= htmlspecialchars((string) $cliente['_id']) ?></td>

                <td><?= htmlspecialchars($cliente['nome'] ?? '') ?></td>

                <td><?= htmlspecialchars($cliente['cpf'] ?? '') ?></td>

                <td><?= htmlspecialchars($cliente['telefone'] ?? '') ?></td>

                <td><?= htmlspecialchars($cliente['email'] ?? '') ?></td>

            </tr>

        <?php endforeach; ?>

        <?php if ($quantidade === 0): ?>

            <tr>
                <td colspan="5">Nenhum cliente encontrado.</td>
            </tr>

        <?php endif; ?>

    </table>

    <p>
        Total de clientes encontrados: <strong><?= $quantidade ?></strong>
    </p>

</div>

</body>
</html>