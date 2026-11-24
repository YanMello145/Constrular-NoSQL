
<?php
require_once __DIR__ . '/conexao_mongodb.php';

$colecao = $database->selectCollection('vendas');

$status = $_GET['status'] ?? 'TODOS';

$filtro = [];

if ($status !== 'TODOS') {
    $filtro['status_venda'] = $status;
}

$vendas = $colecao->find(
    $filtro,
    ['sort' => ['_id' => 1]]
);

function formatarData($data) {
    if ($data instanceof MongoDB\BSON\UTCDateTime) {
        return $data->toDateTime()
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y H:i');
    }

    return '-';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Consulta de Vendas - Constrular</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 30px;
}

.container {
    max-width: 1100px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
}

h1 {
    color: #e87522;
}

form {
    margin-bottom: 20px;
}

select, button {
    padding: 10px;
    font-size: 15px;
}

button {
    background: #e87522;
    color: white;
    border: none;
    cursor: pointer;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

th {
    background: #e87522;
    color: white;
}

a {
    color: #e87522;
}
</style>
</head>

<body>

<div class="container">

<h1>Consulta de Vendas</h1>

<p>Filtre as vendas pelo status:</p>

<form method="GET">

    <select name="status">
        <option value="TODOS"
            <?= $status === 'TODOS' ? 'selected' : '' ?>>
            Todas as vendas
        </option>

        <option value="CONCLUIDA"
            <?= $status === 'CONCLUIDA' ? 'selected' : '' ?>>
            Concluídas
        </option>

        <option value="CANCELADA"
            <?= $status === 'CANCELADA' ? 'selected' : '' ?>>
            Canceladas
        </option>
    </select>

    <button type="submit">Consultar</button>

</form>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Funcionário</th>
    <th>Data da venda</th>
    <th>Status</th>
    <th>Itens</th>
</tr>
</thead>

<tbody>

<?php
$quantidade = 0;

foreach ($vendas as $venda):
    $quantidade++;
?>

<tr>
    <td><?= htmlspecialchars((string)$venda['_id']) ?></td>
    <td><?= htmlspecialchars((string)$venda['cliente_id']) ?></td>
    <td><?= htmlspecialchars((string)$venda['funcionario_id']) ?></td>
    <td><?= formatarData($venda['data_venda'] ?? null) ?></td>
    <td><?= htmlspecialchars($venda['status_venda'] ?? '-') ?></td>
    <td><?= count($venda['itens'] ?? []) ?></td>
</tr>

<?php endforeach; ?>

<?php if ($quantidade === 0): ?>
<tr>
    <td colspan="6">Nenhuma venda encontrada.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

<p>Total de vendas encontradas: <strong><?= $quantidade ?></strong></p>

<p><a href="crud.php">Voltar para clientes</a></p>

</div>

</body>
</html>
