<?php
// AdoPET/animais.php
require_once 'helpers.php';
require_once 'mongo_connection.php';
$page_title = 'Animais Disponíveis para Adoção';
include 'templates/header.php';

$filtro_match = [];
$filtro_match['disponivel'] = true;

$search_term = $_GET['search'] ?? '';
if (!empty($search_term)) {
    $filtro_match['$or'] = [
        ['nome' => ['$regex' => $search_term, '$options' => 'i']],
        ['descricao' => ['$regex' => $search_term, '$options' => 'i']]
    ];
}

$especie_sel = $_GET['especie'] ?? '';
if ($especie_sel) {
    $filtro_match['especie'] = $especie_sel;
}

$porte_sel = $_GET['porte'] ?? '';
if ($porte_sel) {
    $filtro_match['porte'] = $porte_sel;
}

$genero_sel = $_GET['genero'] ?? '';
if ($genero_sel) {
    $filtro_match['genero'] = $genero_sel;
}

$pipeline = [
    [
        '$match' => $filtro_match
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
    ],
    [
        '$sort' => ['data_cadastro' => -1]
    ],
];

$cursor = $database->animais->aggregate(pipeline: $pipeline);
$todos_animais = $cursor->toArray();

?>

<section class="container" style="padding-top: 20px;">
    <h2 class="section-heading">Animais para Adoção</h2>

    <div class="filtros">
        <h3>Encontre seu novo amigo</h3>
        <form action="animais.php" method="GET">
            <div class="filter-group search-bar" style="grid-column: 1 / -1;">
                <label for="search" style="display: none;">Buscar</label>
                <input type="text" name="search" id="search" placeholder="Busque por nome ou palavra-chave..."
                    value="<?php echo htmlspecialchars($search_term); ?>">
            </div>

            <div class="filter-group">
                <label for="especie">Espécie:</label>
                <select name="especie" id="especie">
                    <option value="">Todas</option>
                    <option value="Cachorro" <?php echo ($especie_sel == 'Cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                    <option value="Gato" <?php echo ($especie_sel == 'Gato') ? 'selected' : ''; ?>>Gato</option>
                    <option value="Outro" <?php echo ($especie_sel == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="porte">Porte:</label>
                <select name="porte" id="porte">
                    <option value="">Todos</option>
                    <option value="Pequeno" <?php echo ($porte_sel == 'Pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                    <option value="Medio" <?php echo ($porte_sel == 'Medio') ? 'selected' : ''; ?>>Médio</option>
                    <option value="Grande" <?php echo ($porte_sel == 'Grande') ? 'selected' : ''; ?>>Grande</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="genero">Gênero:</label>
                <select name="genero" id="genero">
                    <option value="">Ambos</option>
                    <option value="Macho" <?php echo ($genero_sel == 'Macho') ? 'selected' : ''; ?>>Macho</option>
                    <option value="Fêmea" <?php echo ($genero_sel == 'Fêmea') ? 'selected' : ''; ?>>Fêmea</option>
                </select>
            </div>

            <button type="submit" class="btn-secondary" style="grid-column: 1 / -1;">Filtrar e Buscar</button>
        </form>
    </div>

    <div class="galeria-animais">
        <?php if (!empty($todos_animais)): ?>
            <?php foreach ($todos_animais as $animal):
                $animal_id_string = (string) $animal['_id'];
                $nome_especie = $animal['especie'];
                $nome_doador = $animal['infoDoador']['nome'];
                ?>
                <div class="animal-card">
                    <img src="<?php echo $animal['foto_url']
                        ? 'uploads/' . htmlspecialchars(string: $animal['foto_url'])
                        : 'static/img/placeholder.png'; ?>"
                        alt="Foto do <?php echo htmlspecialchars(string: $animal['nome']); ?>">
                    <h3><?php echo htmlspecialchars(string: $animal['nome']); ?></h3>
                    <p><?php echo htmlspecialchars(string: $nome_especie); ?> - Idade:
                        <?php echo htmlspecialchars(string: $animal['idade']); ?></p>
                    <p>Doador(a): <?php echo htmlspecialchars(string: $nome_doador); ?></p>
                    <div class="card-actions">
                        <a href="animal_detalhes.php?id=<?php echo $animal_id_string; ?>" class="btn-primary btn-small">Ver
                            Detalhes</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1;">Nenhum animal encontrado com os filtros selecionados.</p>
        <?php endif; ?>
    </div>
</section>

<?php include 'templates/footer.php'; ?>