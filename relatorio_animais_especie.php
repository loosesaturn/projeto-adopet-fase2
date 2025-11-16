<?php
// AdoPET/relatorio_animais_especie.php
require_once 'mongo_connection.php';
session_start();
$page_title = 'Relatório geral de animais por responsável';
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
            header('Location: login.php');
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
                '$group' => [
                    '_id' => [
                        'especie' => '$especie',
                        'responsavel' => '$infoDoador.nome',
                        'tipo' => '$infoDoador.tipo_usuario'
                    ],
                    'total_animais' => ['$sum' => 1] 
                ]
            ],
            [
                '$sort' => [
                    '_id.especie' => 1,
                    '_id.responsavel' => 1
                ]
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'nome_especie' => '$_id.especie',
                    'nome_responsavel' => '$_id.responsavel',
                    'tipo_responsavel' => '$_id.tipo',
                    'total_animais' => '$total_animais'
                ]
            ]
        ];

        $cursor = $database->animais->aggregate(pipeline: $pipeline);
        $relatorio_data = $cursor->toArray();

        ?>

        <section class="container" style="padding-top: 40px; padding-bottom: 40px;">
            <h2>Relatório geral de animais por responsável</h2>
            <p style="margin-bottom: 20px;">Este relatório mostra a quantidade de animais por espécie cadastrados por cada responsável (ONGs e Pessoas Físicas).</p>

            <table border="1" style="width:90%; border-collapse: collapse; margin: auto;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; text-align: left;">Espécie</th>
                        <th style="padding: 10px; text-align: left;">Nome do Responsável</th>
                        <th style="padding: 10px; text-align: left;">Tipo</th>
                        <th style="padding: 10px; text-align: right;">Total de Animais</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($relatorio_data)): ?>
                        <?php foreach ($relatorio_data as $row): ?>
                            <tr>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['nome_especie']); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['nome_responsavel']); ?></td>
                                <td style="padding: 8px;"><?php echo htmlspecialchars(string: $row['tipo_responsavel']); ?></td>
                                <td style="padding: 8px; text-align: right;"><?php echo $row['total_animais']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 8px; text-align: center;">Nenhum animal foi encontrado no banco de dados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php
    include 'templates/footer.php';
    ?>
</div>
</body>
</html>