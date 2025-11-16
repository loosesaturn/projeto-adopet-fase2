<?php
// AdoPET/editar_animal.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Editar Animal';

include 'templates/header.php';

if (!isset($_SESSION['user_id'])) {
    set_flash_message(message: 'Faça login para editar um animal.', type: 'warning');
    header(header: 'Location: login.php');
    exit();
}

$animal_id_string = $_GET['id'] ?? null;
$user_id_string = $_SESSION['user_id'];

if (!$animal_id_string) {
    header(header: 'Location: dashboard.php');
    exit();
}

try {
    $animal_id_object = new MongoDB\BSON\ObjectId($animal_id_string);
    $user_id_object = new MongoDB\BSON\ObjectId($user_id_string);
} catch (Exception $e) {
    set_flash_message(message: 'Animal não encontrado.', type: 'danger');
    header(header: 'Location: dashboard.php');
    exit();
}

$collection = $database->animais;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = $_POST['nome'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'] ?: 'Não definida';
    $idade = (int) $_POST['idade'];
    $genero = $_POST['genero'];
    $porte = $_POST['porte'];
    $descricao = $_POST['descricao'];
    
    $disponivel = ($_POST['disponivel'] == '1');
    $castrado = isset($_POST['castrado']) ? 1 : 0;
    $vacinado = isset($_POST['vacinado']) ? 1 : 0;
    $vermifugado = isset($_POST['vermifugado']) ? 1 : 0;

    $animal_atual = $collection->findOne(filter: ['_id' => $animal_id_object]);
    $foto_url = $animal_atual['foto_url'] ?? null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir(filename: 'uploads')) { mkdir(directory: 'uploads', permissions: 0755, recursive: true); }
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

    $updateData = [
        'nome' => $nome,
        'especie' => $especie,
        'raca' => $raca,
        'idade' => $idade,
        'genero' => $genero,
        'porte' => $porte,
        'castrado' => $castrado,
        'vacinado' => $vacinado,
        'vermifugado' => $vermifugado,
        'disponivel' => $disponivel,
        'descricao' => $descricao,
        'foto_url' => $foto_url
    ];

    try {
        $collection->updateOne(
            filter: ['_id' => $animal_id_object, '_idDoador' => $user_id_object],
            update: ['$set' => $updateData]
        );
        set_flash_message(message: 'Animal atualizado com sucesso!', type: 'success');
        header(header: 'Location: dashboard.php');
        exit();
        
    } catch (Exception $e) {
        set_flash_message(message: 'Erro ao atualizar animal: ' . $e->getMessage(), type: 'danger');
    }
    header(header: "Location: editar_animal.php?id=" . $animal_id_string);
    exit();
}

$filtro_busca = [
    '_id' => $animal_id_object,
    '_idDoador' => $user_id_object
];
$animal = $collection->findOne(filter: $filtro_busca);

if (!$animal) {
    set_flash_message(message: 'Animal não encontrado ou você não tem permissão para editá-lo.', type: 'danger');
    header(header: 'Location: dashboard.php');
    exit();
}

?>

<section class="form-section">
    <h2>Editar Animal: <?php echo htmlspecialchars(string: $animal['nome']); ?></h2>
    <form method="POST" action="editar_animal.php?id=<?php echo $animal_id_string; ?>" enctype="multipart/form-data">
        <label for="nome">Nome do Animal:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars(string: $animal['nome']); ?>" required>

        <label for="especie">Espécie:</label>
        <select name="especie" id="especie" required>
            <option value="Cachorro" <?php if ($animal['especie'] == 'Cachorro') echo 'selected'; ?>>Cachorro</option>
            <option value="Gato" <?php if ($animal['especie'] == 'Gato') echo 'selected'; ?>>Gato</option>
            <option value="Outro" <?php if ($animal['especie'] == 'Outro') echo 'selected'; ?>>Outro</option>
        </select>

        <label for="raca">Raça:</label>
        <input type="text" id="raca" name="raca" value="<?php echo htmlspecialchars(string: $animal['raca'] ?? ''); ?>">

        <label for="idade">Idade (anos):</label>
        <input type="number" id="idade" name="idade" required value="<?php echo htmlspecialchars(string: $animal['idade']); ?>">

        <label for="genero">Gênero:</label>
        <select name="genero" id="genero" required>
            <option value="Macho" <?php if ($animal['genero'] == 'Macho') echo 'selected'; ?>>Macho</option>
            <option value="Fêmea" <?php if ($animal['genero'] == 'Fêmea') echo 'selected'; ?>>Fêmea</option>
        </select>

        <label for="porte">Porte:</label>
        <select name="porte" id="porte" required>
            <option value="Pequeno" <?php if ($animal['porte'] == 'Pequeno') echo 'selected'; ?>>Pequeno</option>
            <option value="Medio" <?php if ($animal['porte'] == 'Medio') echo 'selected'; ?>>Médio</option>
            <option value="Grande" <?php if ($animal['porte'] == 'Grande') echo 'selected'; ?>>Grande</option>
        </select>

        <label for="disponivel">Disponibilidade:</label>
        <select name="disponivel" id="disponivel" required>
            <option value="1" <?php if ($animal['disponivel']) echo 'selected'; ?>>Disponível</option>
            <option value="0" <?php if (!$animal['disponivel']) echo 'selected'; ?>>Adotado/Indisponível</option>
        </select>

        <div class="checkbox-group">
            <label><input type="checkbox" name="castrado" <?php if ($animal['castrado']) echo 'checked'; ?>> Castrado</label>
            <label><input type="checkbox" name="vacinado" <?php if ($animal['vacinado']) echo 'checked'; ?>> Vacinado</label>
            <label><input type="checkbox" name="vermifugado" <?php if ($animal['vermifugado']) echo 'checked'; ?>> Vermifugado</label>
        </div>

        <label for="descricao">Descrição e Personalidade:</label>
        <textarea id="descricao" name="descricao" rows="6" required><?php echo htmlspecialchars(string: $animal['descricao']); ?></textarea>

        <label for="foto">Alterar Foto do Animal:</label>
        <?php if (!empty($animal['foto_url'])): ?>
            <img src="uploads/<?php echo htmlspecialchars(string: $animal['foto_url']); ?>" alt="Foto atual" style="max-width: 150px; display: block; margin-bottom: 10px;">
        <?php endif; ?>
        <input type="file" id="foto" name="foto" accept="image/*">
        <small class="help-text">Deixe em branco para manter a foto atual.</small>

        <button type="submit" class="btn-primary">Atualizar Animal</button>
    </form>
    
    <hr style="margin-top: 40px;">
    <h3 style="color: #dc3545;">Zona de Perigo</h3>
    <p>A exclusão de um animal é uma ação permanente e não pode ser desfeita.</p>
    <form action="excluir_animal.php" method="POST" onsubmit="return confirm('Tem certeza ABSOLUTA que deseja excluir este animal? Esta ação não pode ser desfeita.');">
        <input type="hidden" name="id_animal" value="<?php echo $animal_id_string; ?>">
        <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Excluir Animal</button>
    </form>
</section>

<?php 
include 'templates/footer.php'; 
?>