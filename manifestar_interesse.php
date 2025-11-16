<?php
// AdoPET/manifestar_interesse.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    set_flash_message(message: 'Faça login para manifestar interesse.', type: 'warning');
    header(header: 'Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(header: 'Location: animais.php');
    exit();
}

$animal_id = $_POST['animal_id'] ?? null;
$id_interessado = $_SESSION['user_id'];
$mensagem = $_POST['mensagem'] ?? '';

if (!$animal_id) {
    set_flash_message(message: 'ID do animal não especificado.', type: 'danger');
    header(header: 'Location: animais.php');
    exit();
}

if (empty($mensagem) || strlen(string: $mensagem) < 10) {
    set_flash_message(message: 'Por favor, escreva uma mensagem com pelo menos 10 caracteres para o doador.', type: 'danger');
    header(header: 'Location: animal_detalhes.php?id=' . $animal_id);
    exit();
}

try {
    $animal_id_object = new MongoDB\BSON\ObjectId($animal_id);
    $id_interessado_object = new MongoDB\BSON\ObjectId($id_interessado);

    $collection = $database->interesses;

    $filtro_check = [
        '_idAnimal' => $animal_id_object,
        '_idInteressado' => $id_interessado_object,
        'status' => ['$in' => ['Pendente', 'Aprovado']] 
    ];
    
    $interesseExistente = $collection->findOne(filter: $filtro_check);

    if ($interesseExistente) {
        set_flash_message(message: 'Você já tem um interesse ativo neste animal. Verifique seu painel.', type: 'info');
        header(header: 'Location: animal_detalhes.php?id=' . $animal_id);
        exit();
    }

    $novoInteresse = [
        '_idAnimal' => $animal_id_object,
        '_idInteressado' => $id_interessado_object,
        'mensagem_interessado' => $mensagem,
        'mensagem_doador' => null,
        'status' => 'Pendente',
        'data_interesse' => new MongoDB\BSON\UTCDateTime()
    ];

    $collection->insertOne($novoInteresse);

    set_flash_message('Seu interesse foi registrado com sucesso! O doador será notificado.', 'success');

} catch (Exception $e) {
    set_flash_message(message: 'Erro ao registrar interesse: ' . $e->getMessage(), type: 'danger');
}

header('Location: animal_detalhes.php?id=' . $animal_id);
exit();
?>