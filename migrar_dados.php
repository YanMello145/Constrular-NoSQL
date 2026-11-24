<?php

// CONEXOES
require_once 'C:/xampp/htdocs/Sistema_Vendas/conexao.php';
require_once __DIR__ . '/conexao_mongodb.php';

use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;

// Converte valores DECIMAL do MySQL para Decimal128 no MongoDB
function decimalMongo($valor) {
    if ($valor === null) {
        return null;
    }

    return new Decimal128((string) $valor);
}

// Converte DATETIME do MySQL para data BSON
function dataMongo($valor) {
    if (empty($valor)) {
        return null;
    }

    $data = new DateTimeImmutable(
        $valor,
        new DateTimeZone('America/Sao_Paulo')
    );

    $utc = $data->setTimezone(new DateTimeZone('UTC'));

    return new UTCDateTime($utc->getTimestamp() * 1000);
}

// Insere ou atualiza documentos, preservando os IDs
function salvarDocumento($colecao, $documento) {
    $colecao->replaceOne(
        ['_id' => $documento['_id']],
        $documento,
        ['upsert' => true]
    );
}

// Migra tabelas simples
function migrarTabela($pdo, $database, $tabela, $colecao, $campoId) {
    $consulta = $pdo->query("SELECT * FROM `$tabela`");
    $destino = $database->selectCollection($colecao);

    $total = 0;

    while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
        $documento = $linha;

        $documento['_id'] = (int) $linha[$campoId];

        unset($documento[$campoId]);

        salvarDocumento($destino, $documento);

        $total++;
    }

    echo "$colecao: $total registros migrados.<br>";

    return $total;
}

try {

    echo "<h2>Migração do Constrular para MongoDB</h2>";

    $totalGeral = 0;

    // 1. CLIENTES
    $totalGeral += migrarTabela(
        $pdo, $database,
        'cliente', 'clientes', 'id_cliente'
    );

    // 2. FUNCIONÁRIOS
    $totalGeral += migrarTabela(
        $pdo, $database,
        'funcionario', 'funcionarios', 'id_funcionario'
    );

    // 3. CATEGORIAS
    $totalGeral += migrarTabela(
        $pdo, $database,
        'categoria', 'categorias', 'id_categoria'
    );

    // 4. FORNECEDORES
    $totalGeral += migrarTabela(
        $pdo, $database,
        'fornecedor', 'fornecedores', 'id_fornecedor'
    );

    // 5. PRODUTOS
    $consulta = $pdo->query("SELECT * FROM produto");
    $colecao = $database->selectCollection('produtos');
    $total = 0;

    while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {

        $documento = [
            '_id' => (int) $linha['id_produto'],
            'descricao' => $linha['descricao'],
            'preco' => decimalMongo($linha['preco']),
            'ultimo_preco_compra' => decimalMongo($linha['ultimo_preco_compra']),
            'estoque' => decimalMongo($linha['estoque']),
            'categoria_id' => $linha['FK_CATEGORIA_id_categoria'] !== null
                ? (int) $linha['FK_CATEGORIA_id_categoria'] : null,
            'fornecedor_id' => $linha['FK_FORNECEDOR_id_fornecedor'] !== null
                ? (int) $linha['FK_FORNECEDOR_id_fornecedor'] : null,
            'codigo' => $linha['codigo'],
            'unidade_medida' => $linha['unidade_medida'],
            'estoque_minimo' => decimalMongo($linha['estoque_minimo'])
        ];

        salvarDocumento($colecao, $documento);
        $total++;
    }

    echo "produtos: $total registros migrados.<br>";
    $totalGeral += $total;

    // 6. ENTRADAS DE ESTOQUE
    $consulta = $pdo->query("SELECT * FROM entrada_estoque");
    $colecao = $database->selectCollection('entradas_estoque');
    $total = 0;

    while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {

        $documento = [
            '_id' => (int) $linha['id_entrada'],
            'produto_id' => (int) $linha['FK_PRODUTO_id_produto'],
            'fornecedor_id' => (int) $linha['FK_FORNECEDOR_id_fornecedor'],
            'quantidade' => decimalMongo($linha['quantidade']),
            'preco_unitario' => decimalMongo($linha['preco_unitario']),
            'data_entrada' => dataMongo($linha['data_entrada']),
            'observacao' => $linha['observacao']
        ];

        salvarDocumento($colecao, $documento);
        $total++;
    }

    echo "entradas_estoque: $total registros migrados.<br>";
    $totalGeral += $total;

    // 7. VENDAS COM ITENS INCORPORADOS
    $sqlVendas = "
        SELECT * FROM venda
        ORDER BY id_venda
    ";

    $consultaVendas = $pdo->query($sqlVendas);
    $colecaoVendas = $database->selectCollection('vendas');

    $consultaItens = $pdo->query("
        SELECT
            iv.FK_VENDA_id_venda,
            iv.FK_PRODUTO_id_produto,
            iv.quantidade,
            iv.valor_unitario,
            p.descricao
        FROM item_venda iv
        INNER JOIN produto p
            ON p.id_produto = iv.FK_PRODUTO_id_produto
        ORDER BY iv.FK_VENDA_id_venda
    ");

    $itensPorVenda = [];

    while ($item = $consultaItens->fetch(PDO::FETCH_ASSOC)) {

        $idVenda = (int) $item['FK_VENDA_id_venda'];

        $itensPorVenda[$idVenda][] = [
            'produto_id' => (int) $item['FK_PRODUTO_id_produto'],
            'descricao_produto' => $item['descricao'],
            'quantidade' => (int) $item['quantidade'],
            'valor_unitario' => decimalMongo($item['valor_unitario'])
        ];
    }

    $total = 0;

    while ($linha = $consultaVendas->fetch(PDO::FETCH_ASSOC)) {

        $idVenda = (int) $linha['id_venda'];

        $documento = [
            '_id' => $idVenda,
            'data_venda' => dataMongo($linha['data_venda']),
            'cliente_id' => (int) $linha['FK_CLIENTE_id_cliente'],
            'funcionario_id' => (int) $linha['FK_FUNCIONARIO_id_funcionario'],
            'status_venda' => $linha['status_venda'],
            'data_cancelamento' => dataMongo($linha['data_cancelamento']),
            'itens' => $itensPorVenda[$idVenda] ?? []
        ];

        salvarDocumento($colecaoVendas, $documento);
        $total++;
    }

    echo "vendas: $total registros migrados.<br>";
    $totalGeral += $total;

    echo "<hr>";
    echo "<h3>Migração concluída!</h3>";
    echo "Total de documentos migrados: $totalGeral";

} catch (Throwable $erro) {

    echo "<h3>Erro durante a migração</h3>";
    echo htmlspecialchars($erro->getMessage());
}