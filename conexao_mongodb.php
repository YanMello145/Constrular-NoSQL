<?php

require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

$uri = getenv('MONGODB_URI');

if (!$uri) {
    die("Erro: variável MONGODB_URI não configurada.");
}

try {
    $client = new Client($uri);

    $database = $client->selectDatabase('constrular_nosql');

} catch (Throwable $e) {

    error_log("Erro MongoDB: " . $e->getMessage());

    die("Não foi possível conectar ao banco de dados.");
}