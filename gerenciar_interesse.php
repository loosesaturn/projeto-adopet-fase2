<?php
// AdoPET/gerenciar_interesse.php
require_once 'mongo_connection.php';
require_once 'helpers.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}
function log_historico_mongo($database, $id_animal, $campo, $valor_anterior, $valor_novo, $id_usuario) {
    try {
        $log_collection = $database->historico_alteracoes;
        $log_collection->insertOne([
            '_idAnimal' => $id_animal,
            'campo_alterado' => $campo,
            'valor_anterior' => $valor_anterior,
            'valor_alterado' => $valor_novo,
            '_idUsuario' => $id_usuario,
            'data_alteracao' => new MongoDB\BSON\UTCDateTime()
        ]);
    } catch (Exception $e) {
        // f
    }
}

$interesse_id_string = $_POST['interesse_id'] ?? null;
$action = $_POST['action'] ?? null;
$user_id_string = $_SESSION['user_id'];

if (!$interesse_id_string || !$action) {
    set_flash_message(message: 'Requisição inválida.', type: 'danger');
    header(header: 'Location: dashboard.php');
    exit();
}

$interesse_id_object = new MongoDB\BSON\ObjectId($interesse_id_string);
$user_id_object = new MongoDB\BSON\ObjectId($user_id_string);


$interesses_col = $database->interesses;
$animais_col = $database->animais;

$interesse_info = $interesses_col->findOne(filter: ['_id' => $interesse_id_object]);

if (!$interesse_info) {
    set_flash_message(message: 'Interesse não encontrado.', type: 'danger');
    header(header: 'Location: dashboard.php');
    exit();
}

$animal_info = $animais_col->findOne(filter: ['_id' => $interesse_info['_idAnimal']]);

if (!$animal_info || $animal_info['_idDoador'] != $user_id_object) {
    set_flash_message(message: 'Você não tem permissão para gerenciar este interesse.', type: 'danger');
    header(header: 'Location: dashboard.php');
    exit();
}

$status_anterior = $interesse_info['status'];
$animal_id_object = $animal_info['_id'];
$id_interessado_object = $interesse_info['_idInteressado'];


$new_status = '';
$message = '';

switch ($action) {
    case 'aprovar':
        $new_status = 'Aprovado';
        $message = 'Interesse aprovado!';
        break;

    case 'rejeitar':
        $new_status = 'Rejeitado';
        $message = 'Interesse rejeitado.';
        break;

    case 'marcar_adotado':
        try {
            $animais_col->updateOne(
                filter: ['_id' => $animal_id_object],
                update: ['$set' => ['disponivel' => false]]
            );
            log_historico_mongo(database: $database, id_animal: $animal_id_object, campo: 'disponivel', valor_anterior: '1', valor_novo: '0', id_usuario: $user_id_object);

            $interesses_col->updateOne(
                filter: ['_id' => $interesse_id_object],
                update: ['$set' => ['status' => 'Adotado']]
            );
            log_historico_mongo(database: $database, id_animal: $animal_id_object, campo: 'status_interesse_final', valor_anterior: $status_anterior, valor_novo: 'Adotado', id_usuario: $user_id_object);
            
            $database->adocoes->insertOne(document: [
                '_idAnimal' => $animal_id_object,
                '_idUsuario' => $id_interessado_object,
                'observacoes' => 'Adoção registrada via painel',
                'data_adocao' => new MongoDB\BSON\UTCDateTime()
            ]);

            set_flash_message(message: 'Animal marcado como adotado e adoção registrada com sucesso!', type: 'success');
        
        } catch (Exception $e) {
            set_flash_message(message: 'Erro ao registrar adoção: ' . $e->getMessage(), type: 'danger');
        }
        
        header('Location: dashboard.php');
        exit();
}

if (!empty($new_status)) {
    try {
        $interesses_col->updateOne(
            filter: ['_id' => $interesse_id_object],
            update: ['$set' => ['status' => $new_status]]
        );
        
        log_historico_mongo(
            database: $database, 
            id_animal: $animal_id_object, 
            campo: 'status_interesse', 
            valor_anterior: $status_anterior, 
            valor_novo: $new_status, 
            id_usuario: $user_id_object
        );
        set_flash_message(message: $message, type: 'success');
        
    } catch (Exception $e) {
        set_flash_message(message: 'Erro ao atualizar status: ' . $e->getMessage(), type: 'danger');
    }
}

header(header: 'Location: dashboard.php');
exit();
?>