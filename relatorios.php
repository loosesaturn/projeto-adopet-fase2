<?php
// AdoPET/relatorios.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Página de Relatórios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(string: $page_title); ?> - AdoPET</title>
    <link rel="stylesheet" href="static/css/style.css"> 
</head>
<body>

<div class="relatorio-page-wrapper"> <?php include 'templates/header.php'; ?>

    <main>
        <?php
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['message' => 'Por favor, faça login para acessar esta página.', 'type' => 'danger'];
            header(header: 'Location: login.php');
            exit();
        }
        ?>

        <section class="static-page-content">
            <h1>Página de Relatórios</h1>
            <p>Selecione um dos relatórios abaixo para visualizar os dados do sistema: </p>

            <ul>
                <li><a href="relatorio_animais_ong.php">Relatório de animais por ONG</a></li>
                <li><a href="relatorio_animais_especie.php">Relatório de animais por responsável</a></li>
                <li><a href="relatorio_animais_mais_tempo.php">Relatório de animais esperando há mais tempo</a></li>
                <li><a href="relatorio_animais_nao_castrados.php">Relatório de animais pendentes de castração</a></li>
            </ul>
        </section>
    </main>

    <?php include 'templates/footer.php'; ?>

</div>

</body>
</html>