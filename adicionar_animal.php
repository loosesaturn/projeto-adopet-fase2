<?php
// AdoPET/adicionar_animal.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(header: 'Content-Type: text/html; charset=utf-8');
$page_title = 'Adicionar Novo Animal';

if (!isset($_SESSION['user_id'])) {
    set_flash_message(message: 'Faça login para adicionar um animal.', type: 'warning');
    header(header: 'Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto_url = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir(filename: 'uploads')) {
            mkdir(directory: 'uploads', permissions: 0755, recursive: true);
        }
        $foto_tmp_name = $_FILES['foto']['tmp_name'];
        $foto_name = basename(path: $_FILES['foto']['name']);
        $file_extension = strtolower(string: pathinfo(path: $foto_name, flags: PATHINFO_EXTENSION));
        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];

        if (in_array(needle: $file_extension, haystack: $allowed_extensions)) {
            $filename = uniqid(prefix: 'animal_', more_entropy: true) . '.' . $file_extension;
            $upload_path = 'uploads/' . $filename;

            if (move_uploaded_file(from: $foto_tmp_name, to: $upload_path)) {
                $foto_url = $filename;
            } else {
                set_flash_message(message: 'Erro ao mover o arquivo de imagem.', type: 'danger');
            }
        } else {
            set_flash_message(message: 'Tipo de arquivo de imagem não permitido.', type: 'danger');
        }
    }

    $nome = $_POST['nome'];
    $especie_nome = $_POST['especie'];
    $raca = $_POST['raca'] ?: 'Não definida';
    $idade = (int) $_POST['idade'];
    $genero = $_POST['genero'];
    $porte = $_POST['porte'];
    $descricao = $_POST['descricao'];

    $castrado = isset($_POST['castrado']) ? 1 : 0;
    $vacinado = isset($_POST['vacinado']) ? 1 : 0;
    $vermifugado = isset($_POST['vermifugado']) ? 1 : 0;

    $id_usuario = $_SESSION['user_id'];
    $id_usuario_object = new MongoDB\BSON\ObjectId($id_usuario);

    $novoAnimal = [
        "nome" => $nome,
        "especie" => $especie_nome,
        "raca" => $raca,
        "idade" => $idade,
        "genero" => $genero,
        "porte" => $porte,
        "castrado" => $castrado,
        "vacinado" => $vacinado,
        "vermifugado" => $vermifugado,
        "descricao" => $descricao,
        "foto_url" => $foto_url,
        "_idDoador" => $id_usuario_object,
        "disponivel" => true,
        "data_cadastro" => new MongoDB\BSON\UTCDateTime()
    ];

    try {
        $collection = $database->animais;
        $collection->insertOne(document: $novoAnimal);
        
        set_flash_message(message: 'Animal cadastrado com sucesso!', type: 'success');
        header('Location: dashboard.php');
        exit();
    } catch (Exception $e) {
        set_flash_message(message: 'Erro ao cadastrar animal: ' . $e->getMessage(), type: 'danger');
    }
}

include 'templates/header.php';
?>

<section class="form-section">
    <h2>Adicionar Novo Animal</h2>
    <form method="POST" action="adicionar_animal.php" enctype="multipart/form-data">
        <label for="nome">Nome do Animal:</label>
        <input type="text" id="nome" name="nome" required>

        <label for="especie">Espécie:</label>
        <select name="especie" id="especie" required>
            <option value="">Selecione</option>
            <option value="Cachorro">Cachorro</option>
            <option value="Gato">Gato</option>
            <option value="Outro">Outro</option>
        </select>

        <label for="raca">Raça:</label>
        <input type="text" id="raca" name="raca" placeholder="Ex: SRD (Sem Raça Definida)">

        <label for="idade">Idade:</label>
        <input type="number" id="idade" name="idade" required min="0">

        <label for="genero">Gênero:</label>
        <select name="genero" id="genero" required>
            <option value="">Selecione</option>
            <option value="Macho">Macho</option>
            <option value="Fêmea">Fêmea</option>
        </select>

        <label for="porte">Porte:</label>
        <select name="porte" id="porte" required>
            <option value="">Selecione</option>
            <option value="Pequeno">Pequeno</option>
            <option value="Medio">Médio</option>
            <option value="Grande">Grande</option>
        </select>

        <div class="checkbox-group">
            <label><input type="checkbox" name="castrado"> Castrado</label>
            <label><input type="checkbox" name="vacinado"> Vacinado</label>
            <label><input type="checkbox"name="vermifugado"> Vermifugado</label>
        </div>

        <label for="descricao">Descrição e Personalidade:</label>
        <textarea id="descricao" name="descricao" rows="6" required></textarea>

        <label for="foto">Foto do Animal:</label>
        <input type="file" id="foto" name="foto" accept="image/*">
        <small class="help-text">Tipos permitidos: PNG, JPG, JPEG, GIF.</small>

        <button type="submit" class="btn-primary">Cadastrar Animal</button>
    </form>
</section>

<?php 
include 'templates/footer.php'; 
?>