<?php
// AdoPET/relatorio_animais_mais_tempo.php
require_once 'mongo_connection.php'; 
session_start();
$page_title = 'Relatório: Animais sem interesse de adoção';
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
<div class="relatorio-page-wrapper">
    <?php include 'templates/header.php'; ?>
    <main>
        <?php
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = ['message' => 'Por favor, faça login para acessar esta página.', 'type' => 'danger'];
            header(header: 'Location: login.php');
            exit();
        }
        

        $pipeline = [
            [
                '$lookup' => [
                    'from' => 'usuarios',
                    'localField' => '_idDoador',
                    'foreignField' => '_id',
                    'as' => 'infoDoador'
                ]
            ],
            [
                '$unwind' => '$infoDoador'
            ],
            [
                '$match' => ['infoDoador.tipo_usuario' => 'ONG']
            ],
            [
                '$lookup' => [
                    'from' => 'interesses',
                    'localField' => '_id',
                    'foreignField' => '_idAnimal',
                    'as' => 'lista_interesses'
                ]
            ],
            [
                '$match' => ['lista_interesses' => ['$size' => 0]]
            ],
            [
                '$sort' => ['data_cadastro' => 1]
            ],
            [
                '$limit' => 3
            ]
        ];

        $cursor = $database->animais->aggregate(pipeline: $pipeline);
        $relatorio_data = $cursor->toArray();

        ?>

        <section class="container" style="padding-top: 40px; padding-bottom: 40px;">
            <h2>Relatório: Animais que estão esperando há mais tempo por uma família</h2>
            <p style="margin-bottom: 20px;">
                Este relatório mostra os 3 animais mais antigos no sistema (cadastrados por ONGs) que ainda não receberam nenhuma manifestação de interesse.
            </p>

            <table border="1" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; text-align: left;">ONG</th>
                        <th style="padding: 10px; text-align: left;">Espécie</th>
                        <th style="padding: 10px; text-align: left;">Nome do Animal</th>
                        <th style="padding: 10px; text-align: left;">Raça</th>
                        <th style="padding: 10px; text-align: left;">Data de Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($relatorio_data)): ?>
                        <?php foreach ($relatorio_data as $row): ?>
                            <tr>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['infoDoador']['nome']); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['especie']); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['nome']); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['raca']); ?></td>
                                <td style="padding: 8px;">
                                    <?php echo htmlspecialchars(string: $row['data_cadastro']->toDateTime()->format('d/m/Y')); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 8px; text-align: center;">
                                Todos os animais cadastrados por ONGs já receberam ao menos um interesse!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php
    include 'templates/footer.php';
    ?>

</div> </body>
</html>