<?php
// AdoPET/excluir_usuario.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(header: 'Location: login.php');
    exit();
}

$id_usuario_a_excluir_string = $_POST['id_usuario'] ?? null;
$user_id_sessao_string = $_SESSION['user_id'];

if (empty($id_usuario_a_excluir_string) || $id_usuario_a_excluir_string !== $user_id_sessao_string) {
    set_flash_message(message: 'Requisição de exclusão inválida ou não autorizada.', type: 'danger');
    header(header: "Location: dashboard.php");
    exit();
}

try {
    $user_id_object = new MongoDB\BSON\ObjectId($user_id_sessao_string);
    
    $animais_do_usuario = $database->animais->find(filter: ['_idDoador' => $user_id_object]);
    foreach ($animais_do_usuario as $animal) {
        if (!empty($animal['foto_url']) && file_exists(filename: 'uploads/' . $animal['foto_url'])) {
            unlink(filename: 'uploads/' . $animal['foto_url']);
        }
        
        $database->interesses->deleteMany(filter: ['_idAnimal' => $animal['_id']]);
    }

    $database->animais->deleteMany(filter: ['_idDoador' => $user_id_object]);
    $database->interesses->deleteMany(filter: ['_idInteressado' => $user_id_object]);

    $database->adocoes->deleteMany(filter: ['_idUsuario' => $user_id_object]);

    $database->historico_alteracoes->deleteMany(filter: ['_idUsuario' => $user_id_object]);

    $deleteResult = $database->usuarios->deleteOne(filter: ['_id' => $user_id_object]);

    if ($deleteResult->getDeletedCount() > 0) {
        session_destroy();
        set_flash_message(message: 'Sua conta e todos os seus dados (animais, interesses) foram excluídos com sucesso.', type: 'success');
        header(header: "Location: index.php");
        exit();
    } else {
        set_flash_message(message: 'Usuário não encontrado para exclusão.', type: 'danger');
        header(header: "Location: dashboard.php");
        exit();
    }

} catch (Exception $e) {
    set_flash_message(message: 'Erro ao excluir conta: ' . $e->getMessage(), type: 'danger');
    header(header: "Location: dashboard.php");
    exit();
}
?>