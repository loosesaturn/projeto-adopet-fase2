<?php
// AdoPET/login.php
require_once 'mongo_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(header: 'Content-Type: text/html; charset=utf-8');
$page_title = 'Login na AdoPET';

function set_flash_message($message, $type) {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $collection = $database->usuarios;
    $usuario = $collection->findOne(filter: ['email' => $email]);

    if ($usuario && password_verify(password: $senha, hash: $usuario['senha'])) {
        
        session_regenerate_id(); 
        
        $_SESSION['user_id'] = (string) $usuario['_id']; 
        $_SESSION['user_name'] = $usuario['nome'];
        
        $_SESSION['user_type'] = $usuario['tipo_usuario']; 
        
        set_flash_message(message: 'Bem-vindo(a), ' . $usuario['nome'] . '!', type: 'success');
        header(header: "Location: dashboard.php");
        exit();
    } else {
        set_flash_message(message: 'Email ou senha inválidos.', type: 'danger');
        header(header: "Location: login.php");
        exit();
    }
}

include 'templates/header.php';
?>
<section class="form-section">
    <h2>Acesse sua conta</h2>
    <form method="POST" action="login.php">
        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>

        <button type="submit" class="btn-primary">Entrar</button>
    </form>
    <p>Ainda não tem conta? <a href="cadastro.php">Cadastre-se aqui!</a></p>
</section>

<?php include 'templates/footer.php'; ?>
