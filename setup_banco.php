<?php
// AdoPET/setup_banco.php

require_once 'mongo_connection.php';

echo "<h1>Inicializando banco de dados adoPET</h1>";

try {
    // 1. Configuração do usuário padrão
    $usuarios = $database->usuarios;
    $adminEmail = 'admin@adopet.com';
    $adminId = null;

    $usuarioExistente = $usuarios->findOne(filter: ['email' => $adminEmail]);

    if (!$usuarioExistente) {
        // Se não existe, cria
        $insertResult = $usuarios->insertOne(document: [
            'nome' => 'Admin ONG',
            'email' => $adminEmail,
            'senha' => password_hash(password: '123456', algo: PASSWORD_DEFAULT),
            'tipo_usuario' => 'ONG',
            'documento' => '00.000.000/0001-91',
            'descricao' => 'ONG Padrão do Sistema (Criada via Script)',
            'status' => 'ativo',
            'data_cadastro' => new MongoDB\BSON\UTCDateTime(),
            'endereco' => [
                'rua' => 'Rua do Admin',
                'numero' => '100',
                'bairro' => 'Centro',
                'cep' => '00000-000',
                'cidade' => 'Joinville',
                'estado' => 'SC'
            ],
            'telefones' => [
                ['ddd' => '47', 'numero' => '99999-9999']
            ]
        ]);
        $adminId = $insertResult->getInsertedId();
        echo "<p>Coleção <strong>'usuarios'</strong>: Usuário 'Admin ONG' criado com sucesso.</p>";
    } else {
        $adminId = $usuarioExistente['_id'];
        echo "<p>Coleção <strong>'usuarios'</strong>: Usuário 'Admin ONG' já existia.</p>";
    }

    // 2. Configuração de animal padrão (Vinculado ao Admin)
    $animais = $database->animais;

    if ($animais->countDocuments(['nome' => 'Rex do Admin']) == 0) {
        $animais->insertOne([
            '_idDoador' => $adminId,
            'nome' => 'Rex do Admin',
            'especie' => 'Cachorro',
            'raca' => 'SRD (Vira-lata)',
            'idade' => 3,
            'genero' => 'Macho',
            'porte' => 'Medio',
            'castrado' => 1,
            'vacinado' => 1,
            'vermifugado' => 1,
            'descricao' => 'Animal de teste no banco',
            'foto_url' => null,
            'disponivel' => true,
            'data_cadastro' => new MongoDB\BSON\UTCDateTime()
        ]);
        echo "<p>Coleção <strong>'animais'</strong>: Animal 'Rex do Admin' criado.</p>";
    } else {
        echo "<p>Coleção <strong>'animais'</strong>: Animal 'Rex do Admin' já existia.</p>";
    }

    // 3. Inicializar outras coleções (vazias)
    $colNames = ['interesses', 'adocoes', 'historico_alteracoes'];
    foreach ($colNames as $colName) {
        $collections = $database->listCollections(options: ['filter' => ['name' => $colName]]);
        $exists = false;
        foreach ($collections as $col) {
            $exists = true;
        }

        if (!$exists) {
            $database->createCollection(collectionName: $colName);
            echo "<p>Coleção <strong>'$colName'</strong> inicializada.</p>";
        } else {
            echo "<p>Coleção <strong>'$colName'</strong> já existia.</p>";
        }
    }

    echo "<hr><h3> Banco de Dados populado.</h3>";
    echo "<p>Login de teste: <strong>$adminEmail</strong> / Senha: <strong>123456</strong></p>";
    echo "<a href='index.php'>Clique aqui para ir para a Página Inicial</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Erro fatal no setup:</h3>";
    echo $e->getMessage();
}
?>