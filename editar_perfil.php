<?php
// AdoPET/editar_perfil.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(header: 'Content-Type: text/html; charset=utf-8');
$page_title = 'Editar Perfil';

include 'templates/header.php';

if (!isset($_SESSION['user_id'])) {
    set_flash_message(message: 'Faça login para editar seu perfil.', type: 'warning');
    header(header: 'Location: login.php');
    exit();
}

$user_id_string = $_SESSION['user_id'];
$user_id_object = new MongoDB\BSON\ObjectId($user_id_string);
$user_type = $_SESSION['user_type'];

$collection = $database->usuarios;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = $_POST['nome'];
    $descricao = ($user_type == 'ONG') ? ($_POST['descricao'] ?? null) : null;
    
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';

    $ddd = $_POST['ddd'] ?? '';
    $numero_tel = $_POST['numero_telefone'] ?? '';

    $updateData = [
        'nome' => $nome,
        'endereco.rua' => $rua,
        'endereco.numero' => $numero,
        'endereco.bairro' => $bairro,
        'endereco.cep' => $cep,
        'endereco.cidade' => $cidade,
        'endereco.estado' => $estado,
        'telefones.0.ddd' => $ddd,
        'telefones.0.numero' => $numero_tel
    ];
    
    if ($user_type == 'ONG') {
        $updateData['descricao'] = $descricao;
    }

    try {
        $collection->updateOne(
            filter: ['_id' => $user_id_object],
            update: ['$set' => $updateData]
        );

        $_SESSION['user_name'] = $nome;
        set_flash_message(message: 'Perfil atualizado com sucesso!', type: 'success');
        
    } catch (Exception $e) {
        set_flash_message(message: 'Erro ao atualizar perfil: ' . $e->getMessage(), type: 'danger');
    }

    header(header: 'Location: dashboard.php');
    exit();
}

$usuario = $collection->findOne(filter: ['_id' => $user_id_object]);

if (!$usuario) {
    session_destroy();
    header(header: 'Location: login.php');
    exit();
}

$telefone = $usuario['telefones'][0] ?? ['ddd' => '', 'numero' => ''];
?>

<section class="form-section">
    <h2>Editar Perfil</h2>
    <form method="POST" action="editar_perfil.php">
        <label for="nome">Nome/Nome da ONG:</label>
        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars(string: $usuario['nome']); ?>">

        <label for="email">E-mail (não editável):</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars(string: $usuario['email']); ?>" disabled>

        <h3>Endereço</h3>
        <label for="rua">Rua:</label>
        <input type="text" id="rua" name="rua" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['rua'] ?? ''); ?>">

        <label for="numero">Número:</label>
        <input type="text" id="numero" name="numero" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['numero'] ?? ''); ?>">

        <label for="bairro">Bairro:</label>
        <input type="text" id="bairro" name="bairro" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['bairro'] ?? ''); ?>">

        <label for="cep">CEP:</label>
        <input type="text" id="cep" name="cep" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['cep'] ?? ''); ?>">

        <label for="cidade">Cidade:</label>
        <input type="text" id="cidade" name="cidade" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['cidade'] ?? ''); ?>">

        <label for="estado">Estado (UF):</label>
        <input type="text" id="estado" name="estado" maxlength="2" required value="<?php echo htmlspecialchars(string: $usuario['endereco']['estado'] ?? ''); ?>">

        <h3>Telefone</h3>
        <label for="ddd">DDD:</label>
        <input type="text" id="ddd" name="ddd" maxlength="3" required value="<?php echo htmlspecialchars(string: $telefone['ddd']); ?>">

        <label for="numero_telefone">Número:</label>
        <input type="text" id="numero_telefone" name="numero_telefone" required value="<?php echo htmlspecialchars(string: $telefone['numero']); ?>">

        <?php if (($usuario['tipo_usuario'] ?? '') == 'ONG'): ?>
            <label for="descricao">Descrição da ONG:</label>
            <textarea id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars(string: $usuario['descricao'] ?? ''); ?></textarea>
        <?php endif; ?>

        <button type="submit" class="btn-primary">Atualizar Perfil</button>
    </form>
</section>

<script src="https://unpkg.com/imask"></script>
<script>
    IMask(document.getElementById('cep'), { mask: '00000-000' });
    IMask(document.getElementById('ddd'), { mask: '00' });
    IMask(document.getElementById('numero_telefone'), {
        mask: [
            { mask: '0000-0000' },
            { mask: '00000-0000' }
        ]
    });
</script>

<?php include 'templates/footer.php'; ?>