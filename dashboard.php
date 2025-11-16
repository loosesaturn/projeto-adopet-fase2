<?php
// AdoPET/dashboard.php
require_once 'mongo_connection.php'; 
require_once 'helpers.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Meu Painel - AdoPET';

include 'templates/header.php';

if (!isset($_SESSION['user_id'])) {
    set_flash_message(message: 'Por favor, faça login para acessar esta página.', type: 'warning');
    header(header: 'Location: login.php');
    exit();
}

$user_id_string = $_SESSION['user_id'];
$user_id_object = new MongoDB\BSON\ObjectId($user_id_string);

$collection = $database->usuarios;
$perfil_usuario = $collection->findOne(filter: ['_id' => $user_id_object]);

if (!$perfil_usuario) {
    die("Erro: Usuário não encontrado no MongoDB.");
}

$user_type = $perfil_usuario['tipo_usuario'];
$telefone_doc = $perfil_usuario['telefones'][0] ?? null;
$telefone = $telefone_doc ? $telefone_doc['ddd'] . ' ' . $telefone_doc['numero'] : 'Não informado';
$endereco_doc = $perfil_usuario['endereco'] ?? null;
$endereco = $endereco_doc ? $endereco_doc['rua'] . ', ' . $endereco_doc['numero'] : 'Não informado';


$meus_animais = [];
$interesses_recebidos = [];
$meus_interesses_enviados = [];

if ($user_type == 'ONG' || $user_type == 'Pessoa Fisica') {
    
    $animais_collection = $database->animais;
    $filtro_animais = ['_idDoador' => $user_id_object];
    $opcoes_sort = ['sort' => ['data_cadastro' => -1]];
    $cursor_animais = $animais_collection->find(filter: $filtro_animais, options: $opcoes_sort);
    $meus_animais = $cursor_animais->toArray();


    $pipeline_recebidos = [
        [
            '$lookup' => [
                'from' => 'animais',
                'localField' => '_idAnimal',
                'foreignField' => '_id',
                'as' => 'animalInfo'
            ]
        ],
        ['$unwind' => '$animalInfo'],
        [
            '$match' => ['animalInfo._idDoador' => $user_id_object]
        ],
        [
            '$lookup' => [
                'from' => 'usuarios',
                'localField' => '_idInteressado',
                'foreignField' => '_id',
                'as' => 'interessadoInfo'
            ]
        ],
        ['$unwind' => '$interessadoInfo'],
        [
            '$sort' => ['data_interesse' => -1]
        ]
    ];
    $cursor_recebidos = $database->interesses->aggregate($pipeline_recebidos);
    $interesses_recebidos = $cursor_recebidos->toArray();
}

if ($user_type == 'Pessoa Fisica') {
    
     $pipeline_enviados = [
        [
            '$match' => ['_idInteressado' => $user_id_object]
        ],
        [
            '$lookup' => [
                'from' => 'animais',
                'localField' => '_idAnimal',
                'foreignField' => '_id',
                'as' => 'animalInfo'
            ]
        ],
        ['$unwind' => '$animalInfo'],
        [
            '$lookup' => [
                'from' => 'usuarios',
                'localField' => 'animalInfo._idDoador',
                'foreignField' => '_id',
                'as' => 'doadorInfo'
            ]
        ],
        ['$unwind' => '$doadorInfo'],
        [
            '$sort' => ['data_interesse' => -1]
        ]
    ];
    $cursor_enviados = $database->interesses->aggregate($pipeline_enviados);
    $meus_interesses_enviados = $cursor_enviados->toArray();
}

?>

<section class="dashboard">
    <h1>Olá, <?php echo htmlspecialchars(string: $_SESSION['user_name']); ?>!</h1>
    
    <div class="profile-info-summary">
        <p><strong>Tipo de Usuário:</strong> <?php echo htmlspecialchars(string: $user_type); ?></p>
        <p><strong>E-mail:</strong> <?php echo htmlspecialchars(string: $perfil_usuario['email']); ?></p>
        <p><strong>Telefone:</strong> <?php echo htmlspecialchars(string: $telefone); ?></p>
        <p><strong>Endereço:</strong> <?php echo htmlspecialchars(string: $endereco); ?></p>
        <?php if ($user_type == 'ONG'): ?>
            <p><strong>Descrição da ONG:</strong> <?php echo htmlspecialchars(string: $perfil_usuario['descricao'] ?: 'Não informada'); ?></p>
        <?php endif; ?>
        <a href="editar_perfil.php" class="btn-secondary">Editar Perfil</a>
            <form action="excluir_usuario.php" method="POST" style="display:inline-block; margin-left: 10px;"
            onsubmit="return confirm('ATENÇÃO: Você tem certeza que deseja excluir sua conta? Esta ação é irreversível e apagará seus dados e animais cadastrados (dependendo das regras do banco).');">
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars(string: $user_id_string); ?>">
            <button type="submit" class="btn-danger btn-secondary">Excluir Conta</button>
        </form>
    </div>

    <?php if ($user_type == 'ONG' || $user_type == 'Pessoa Fisica'): ?>
        <hr>
        <h2>Meus Animais Cadastrados</h2>
        <div class="dashboard-actions">
            <a href="adicionar_animal.php" class="btn-primary">Adicionar Novo Animal</a>
        </div>
        <div class="galeria-animais">
            <?php if (!empty($meus_animais)): ?>
                <?php foreach ($meus_animais as $animal): 
                    $animal_id_string = (string) $animal['_id'];
                ?>
                    <div class="animal-card <?php echo !$animal['disponivel'] ? 'card-adotado' : ''; ?>">
                        <img src="<?php echo $animal['foto_url'] ? 'uploads/' . htmlspecialchars($animal['foto_url']) : 'static/img/placeholder.png'; ?>" alt="Foto do <?php echo htmlspecialchars($animal['nome']); ?>">
                        <h3><?php echo htmlspecialchars(string: $animal['nome']); ?></h3>
                        <p>
                            <?php echo htmlspecialchars(string: $animal['especie']); ?> - 
                            <?php echo !$animal['disponivel'] ? '<span class="status-badge status-adotado">Adotado</span>' : '<span class="status-badge status-disponivel">Disponível</span>'; ?>
                        </p>
                        <div class="card-actions">
                            <a href="animal_detalhes.php?id=<?php echo $animal_id_string; ?>" class="btn-small btn-secondary">Ver</a>
                            <a href="editar_animal.php?id=<?php echo $animal_id_string; ?>" class="btn-small btn-secondary">Editar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="info-message">Você ainda não cadastrou nenhum animal.</p>
            <?php endif; ?>
        </div>

        <hr>
        <h2>Interesses Recebidos</h2>
        <?php if (!empty($interesses_recebidos)): ?>
            <div class="interesses-lista">
                <?php foreach ($interesses_recebidos as $interesse): 
                    $interesse_id_string = (string) $interesse['_id'];
                    $status_class = strtolower(string: str_replace(search: ' ', replace: '_', subject: $interesse['status']));
                    
                    $tel_interessado = 'Não informado';
                    if (isset($interesse['interessadoInfo']['telefones']) && !empty($interesse['interessadoInfo']['telefones'])) {
                        $tel_doc = $interesse['interessadoInfo']['telefones'][0];
                        $tel_interessado = $tel_doc['ddd'] . ' ' . $tel_doc['numero'];
                    }
                ?>
                    <div class="interesse-card interesse-status-<?php echo $status_class; ?>">
                        <h4>Interesse em "<?php echo htmlspecialchars(string: $interesse['animalInfo']['nome']); ?>"</h4>
                        <p><strong>De:</strong> <?php echo htmlspecialchars(string: $interesse['interessadoInfo']['nome']); ?> (<?php echo htmlspecialchars($interesse['interessadoInfo']['email']); ?>)</p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars(string: $tel_interessado); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($interesse['status']); ?></span></p>
                        <p><strong>Mensagem:</strong> <?php echo nl2br(string: htmlspecialchars(string: $interesse['mensagem_interessado'] ?: 'Nenhuma mensagem.')); ?></p>
                        
                        <?php if ($interesse['status'] == 'Pendente'): ?>
                        <div class="interest-actions">
                            <form action="gerenciar_interesse.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="interesse_id" value="<?php echo $interesse_id_string; ?>">
                                <input type="hidden" name="action" value="aprovar">
                                <button type="submit" class="btn-small btn-approve">Aprovar</button>
                            </form>
                            <form action="gerenciar_interesse.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="interesse_id" value="<?php echo $interesse_id_string; ?>">
                                <input type="hidden" name="action" value="rejeitar">
                                <button type="submit" class="btn-small btn-reject">Rejeitar</button>
                            </form>
                        </div>
                        <?php elseif ($interesse['status'] == 'Aprovado'): ?>
                            <form action="gerenciar_interesse.php" method="POST">
                                    <input type="hidden" name="interesse_id" value="<?php echo $interesse_id_string; ?>">
                                    <input type="hidden" name="action" value="marcar_adotado">
                                    <button type="submit" class="btn-small btn-adopt">Marcar como Adotado</button>
                                </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="info-message">Nenhum interesse recebido ainda.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($user_type == 'Pessoa Fisica'): ?>
        <hr>
        <h2>Meus Interesses Enviados</h2>
        <?php if (!empty($meus_interesses_enviados)): ?>
            <div class="interesses-lista">
                <?php foreach ($meus_interesses_enviados as $interesse): 
                    $status_class = strtolower(string: str_replace(search: ' ', replace: '_', subject: $interesse['status']));
                    $animal_id_string = (string) $interesse['animalInfo']['_id'];
                ?>
                    <div class="interesse-card interesse-status-<?php echo $status_class; ?>">
                        <h4>Interesse em "<?php echo htmlspecialchars(string: $interesse['animalInfo']['nome']); ?>"</h4>
                        <p><strong>Doador:</strong> <?php echo htmlspecialchars(string: $interesse['doadorInfo']['nome']); ?></p>
                        <p><strong>Seu Status:</strong> <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars(string: $interesse['status']); ?></span></p>
                        <p><strong>Sua Mensagem:</strong> <?php echo nl2br(string: htmlspecialchars(string: $interesse['mensagem_interessado'] ?: 'Nenhuma mensagem.')); ?></p>
                        <a href="animal_detalhes.php?id=<?php echo $animal_id_string; ?>" class="btn-small btn-secondary">Ver Animal</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="info-message">Você ainda não manifestou interesse em nenhum animal.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include 'templates/footer.php'; ?>