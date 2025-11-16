<?php
// AdoPET/animal_detalhes.php
require_once 'mongo_connection.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header(header: 'Content-Type: text/html; charset=utf-8');

$animal_id_string = $_GET['id'] ?? null;
if (!$animal_id_string) {
    header(header: "Location: animais.php");
    exit();
}

try {
    $animal_id_object = new MongoDB\BSON\ObjectId($animal_id_string);

    $pipeline = [
        [
            '$match' => ['_id' => $animal_id_object]
        ],
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
        ]
    ];
    
    $cursor = $database->animais->aggregate(pipeline: $pipeline);
    
    $animal = $cursor->toArray()[0] ?? null; 

} catch (Exception $e) {
    $animal = null;
}

if (!$animal) {
    set_flash_message(message: 'Animal não encontrado.', type: 'danger');
    header("Location: animais.php");
    exit();
}

$telefone_doador = 'Não informado';
if (isset($animal['infoDoador']['telefones']) && !empty($animal['infoDoador']['telefones'])) {
    $tel = $animal['infoDoador']['telefones'][0];
    $telefone_doador = $tel['ddd'] . ' ' . $tel['numero'];
}


$page_title = htmlspecialchars(string: $animal['nome']) . ' - Detalhes';
include 'templates/header.php';
?>

<section class="animal-detalhes">
    <div class="detalhes-container">
        <div class="detalhes-coluna-esquerda">
            <div class="detalhes-imagem">
                <img src="<?php echo $animal['foto_url'] ? 'uploads/' . htmlspecialchars(string: $animal['foto_url']) : 'static/img/placeholder.png'; ?>" alt="Foto do <?php echo htmlspecialchars(string: $animal['nome']); ?>">
            </div>
            <div class="info-card descricao-animal">
                <h3>Sobre mim</h3>
                <p><?php echo nl2br(string: htmlspecialchars(string: $animal['descricao'])); ?></p>
            </div>
        </div>
        <div class="detalhes-info">
            <h1><?php echo htmlspecialchars(string: $animal['nome']); ?></h1>
            
            <p><strong>Espécie:</strong> <?php echo htmlspecialchars(string: $animal['especie']); ?> - 
               <strong>Raça:</strong> <?php echo htmlspecialchars(string: $animal['raca'] ?: 'Não informada'); ?></p>

            <div class="info-card">
                <h3>Detalhes</h3>
                <div class="caracteristicas-grid">
                    <div class="caracteristica-item">
                        <strong>Idade</strong>
                        <p><?php echo htmlspecialchars(string: $animal['idade']); ?></p>
                    </div>
                    <div class="caracteristica-item">
                        <strong>Gênero</strong>
                        <p><?php echo htmlspecialchars(string: $animal['genero']); ?></p>
                    </div>
                    <div class="caracteristica-item">
                        <strong>Porte</strong>
                        <p><?php echo htmlspecialchars(string: $animal['porte']); ?></p>
                    </div>
                </div>

                <h3 style="margin-top: 20px;">Saúde</h3>
                <div class="saude-badges">
                    <p><strong>Castrado:</strong> <?php echo $animal['castrado'] ? 'Sim' : 'Não'; ?></p>
                    <p><strong>Vacinado:</strong> <?php echo $animal['vacinado'] ? 'Sim' : 'Não'; ?></p>
                    <p><strong>Vermifugado:</strong> <?php echo $animal['vermifugado'] ? 'Sim' : 'Não'; ?></p>
                </div>
            </div>

            <div class="info-card">
                <h3><?php echo $animal['disponivel'] ? 'Quero Adotar!' : 'Status'; ?></h3>

                <?php if ($animal['disponivel']): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <p><strong>Doador(a):</strong> <?php echo htmlspecialchars(string: $animal['infoDoador']['nome']); ?></p>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars(string: $animal['infoDoador']['email']); ?>"><?php echo htmlspecialchars($animal['infoDoador']['email']); ?></a></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars(string: $telefone_doador); ?></p>

                        <?php 
                        $user_id_string = $_SESSION['user_id'];
                        $doador_id_string = (string) $animal['_idDoador'];
                        
                        ?>
                        <?php if ($_SESSION['user_type'] == 'Pessoa Fisica' && $user_id_string != $doador_id_string): ?>
                            <div class="manifestar-interesse-form">
                                <form action="manifestar_interesse.php" method="POST">
                                    
                                    <input type="hidden" name="animal_id" value="<?php echo (string) $animal['_id']; ?>">
                                    
                                    <label for="mensagem">Deixe uma mensagem para o doador:</label>
                                    <textarea id="mensagem" name="mensagem" rows="4" placeholder="Conte um pouco sobre você e por que gostaria de adotar este animal." required></textarea>
                                    <button type="submit" class="btn-primary">Enviar Interesse</button>
                                </form>
                            </div>
                        <?php elseif ($user_id_string == $doador_id_string): ?>
                            <p class="info-message">Este é um dos seus animais. Acesse o <a href="dashboard.php">painel</a> para gerenciá-lo.</p>
                        <?php endif; ?>

                    <?php else: ?>
                        <p>Faça <a href="login.php">login</a> para ver os dados de contato do doador e manifestar interesse.</p>
                    <?php endif; ?>
                <?php else: ?>
                   <p><strong>Status:</strong> Adotado/Indisponível</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>