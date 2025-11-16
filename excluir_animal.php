<?php
// AdoPET/excluir_animal.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header(header: 'Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id_string = $_POST['id_animal'] ?? null;
    $user_id_string = $_SESSION['user_id'];

    if (!$animal_id_string) {
        set_flash_message(message: 'ID do animal não fornecido.', type: 'danger');
        header(header: 'Location: dashboard.php');
        exit();
    }

    try {
        $animal_id_object = new MongoDB\BSON\ObjectId($animal_id_string);
        $user_id_object = new MongoDB\BSON\ObjectId($user_id_string);
        
        $collection = $database->animais;

        $filtro = [
            '_id' => $animal_id_object,
            '_idDoador' => $user_id_object
        ];

        $animal = $collection->findOne(filter: $filtro);

        if ($animal) {
            $deleteResult = $collection->deleteOne(filter: $filtro);

            if ($deleteResult->getDeletedCount() > 0) {

                if (!empty($animal['foto_url']) && file_exists(filename: 'uploads/' . $animal['foto_url'])) {
                    unlink(filename: 'uploads/' . $animal['foto_url']);
                }


                set_flash_message(message: 'Animal excluído com sucesso.', type: 'success');
            } else {
                set_flash_message(message: 'Erro ao excluir o animal.', type: 'danger');
            }
        } else {
            set_flash_message(message: 'Você não tem permissão para excluir este animal.', type: 'danger');
        }

    } catch (Exception $e) {
        set_flash_message(message: 'Erro ao processar exclusão: ' . $e->getMessage(), type: 'danger');
    }

    header(header: 'Location: dashboard.php');
    exit();
}
?>