<?php
// AdoPET/index.php
require_once 'mongo_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(header: 'Content-Type: text/html; charset=utf-8');
$page_title = 'Seu Novo Melhor Amigo Espera Por Você!';
include 'templates/header.php';

$animais_destaque = [];
$stats = ['vidas_salvas' => 0, 'familias_felizes' => 0, 'parceiros' => 0];

try {
    
    // Conta animais adotados
    $stats['vidas_salvas'] = $database->animais->countDocuments(filter: ['disponivel' => false]);
    
    // Conta ONGs parceiras
    $stats['parceiros'] = $database->usuarios->countDocuments(filter: ['tipo_usuario' => 'ONG']);
    
    // Conta famílias felizes (total de adoções feitas)
    $stats['familias_felizes'] = $database->adocoes->countDocuments();
    
    $pipeline = [
        [
            '$match' => ['disponivel' => true] //$match = WHERE
        ],
        [
            '$lookup' => [
                'from' => 'usuarios', 
                'localField' => '_idDoador',
                'foreignField' => '_id',
                'as' => 'infoDoador'
            ] //$lookup = JOIN
        ],
        [
            '$unwind' => '$infoDoador'
        ],
        [
            '$sort' => ['data_cadastro' => -1] //$sort = ORDER BY
        ],
        [
            '$limit' => 6
        ]
    ];

    $cursor_animais = $database->animais->aggregate($pipeline);
    $animais_destaque = $cursor_animais->toArray();

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['message' => 'Não foi possível carregar os dados da página inicial: ' . $e->getMessage(), 'type' => 'danger'];
}
?>

<section class="hero hero-home">
    <div class="hero-content">
        <h1>Encontre o Amor de Quatro Patas na AdoPET!</h1>
        <p>Milhares de animais resgatados esperam por um lar cheio de carinho e segurança. Dê uma segunda chance, adote!</p>
        <a href="animais.php" class="btn-primary">Ver Animais Disponíveis</a>
    </div>
</section>

<section class="container section-padding how-it-works">
    <h2 class="section-heading">Adotar é Simples!</h2>
    <div class="steps-grid">
        <div class="step-item">
            <div class="step-icon"><i class="fas fa-search"></i></div>
            <div class="step-number">1</div>
            <h3>Explore e Encontre</h3>
            <p>Navegue pelos perfis de cães e gatos. Use nossos filtros para achar o pet com a sua vibe!</p>
        </div>
        <div class="step-item">
            <div class="step-icon"><i class="fas fa-heart"></i></div>
            <div class="step-number">2</div>
            <h3>Manifeste Interesse</h3>
            <p>Achou seu futuro amigo? Preencha um formulário simples para o doador conhecer um pouco sobre você.</p>
        </div>
        <div class="step-item">
            <div class="step-icon"><i class="fas fa-home"></i></div>
            <div class="step-number">3</div>
            <h3>Prepare o Lar</h3>
            <p>Após a aprovação, combine a entrega e prepare sua casa para receber o mais novo membro da família!</p>
        </div>
    </div>
</section>

<section class="bg-light-purple section-padding">
    <div class="container">
        <h2 class="section-heading">Nossos Anjinhos em Destaque</h2>
        <p class="section-subheading">Essas fofuras estão ansiosas por um sofá pra chamar de seu. Veja se não rola um match!</p>
        <div class="galeria-animais">
            
            <?php if (!empty($animais_destaque)): ?>
                <?php foreach ($animais_destaque as $animal): 
                    $animal_id_string = (string) $animal['_id'];
                ?>
                <div class="animal-card">
                        <img src="<?php echo $animal['foto_url'] ? 'uploads/' . htmlspecialchars(string: $animal['foto_url']) : 'static/img/placeholder.png'; ?>" alt="Foto do <?php echo htmlspecialchars(string: $animal['nome']); ?>">
                        
                        <h3><?php echo htmlspecialchars(string: $animal['nome']); ?></h3>
                        <p><?php echo htmlspecialchars(string: $animal['especie']); ?> - <?php echo htmlspecialchars(string: $animal['idade']); ?> anos</p>
                        
                        <p>Doador(a): <?php echo htmlspecialchars(string: $animal['infoDoador']['nome']); ?></p>
                        
                        <div class="card-actions">
                            <a href="animal_detalhes.php?id=<?php echo $animal_id_string; ?>" class="btn-primary btn-small">Ver Detalhes</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="info-message text-center">Nenhum animal em destaque no momento. <a href="animais.php">Veja todos os animais disponíveis!</a></p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5">
            <a href="animais.php" class="btn-primary btn-small">Ver Todos os Animais</a>
        </div>
    </div>
</section>

<section class="stats-section section-padding">
    <div class="container">
        <h2 class="section-heading light-text">Nossa Causa em Números</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-paw"></i></div>
                <h3 class="stat-number" data-target="<?php echo $stats['vidas_salvas']; ?>">0</h3>
                <p class="stat-label">Vidas Salvas</p>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <h3 class="stat-number" data-target="<?php echo $stats['familias_felizes']; ?>">0</h3>
                <p class="stat-label">Famílias Felizes</p>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-hands-helping"></i></div>
                <h3 class="stat-number" data-target="<?php echo $stats['parceiros']; ?>">0</h3>
                <p class="stat-label">Parceiros da Causa</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const inc = Math.ceil(target / speed);

                    if (count < target) {
                        counter.innerText = count + inc;
                        setTimeout(updateCount, 15);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        observer.observe(counter);
    });
});
</script>

<?php include 'templates/footer.php'; ?>