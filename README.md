# adoPET (Fase 2 - MongoDB)

## Instalação

### Requisitos

1. XAMPP

Necessário para executar a aplicação PHP.
Versão recomendada: XAMPP 8.2+ (inclui PHP 8.2+)
Download: https://www.apachefriends.org/pt_br/index.html

2. Navegador Web

Chrome, Firefox, Edge ou qualquer navegador moderno

3. MongoDB Community Server
O nosso novo banco de dados NoSQL.
Download: https://www.mongodb.com/try/download/community
Importante: Durante a instalação, certifique-se de selecionar o MongoDB Compass (ele é o nosso novo "phpMyAdmin").

4. Composer
Necessário para instalar o "driver" (biblioteca) do MongoDB para PHP.
Download: https://getcomposer.org/

5. Extensão PHP MongoDB
O "tradutor" que permite o PHP conversar com o MongoDB.
Download: https://pecl.php.net/package/mongodb

### Configuração do Ambiente

1. Instalação do XAMPP

Faça o download e instale o XAMPP.
Durante a instalação, certifique-se de selecionar os componentes:
Apache
PHP

2. Configuração do Banco de Dados MongoDB
Instale o MongoDB Community Server (pode seguir o instalador padrão).

Certifique-se de que o serviço "MongoDB Server (MongoDB)" está em execução (você pode checar em "Serviços" no Windows). Não é preciso criar tabelas ou importar arquivos .sql.

3. Configuração do PHP (A Extensão)
Baixe a Extensão: Vá ao site do PECL, clique na versão mais recente e em "DLL". Baixe o ZIP 8.2 Thread Safe (TS) x64.

Copie o .dll: Abra o ZIP e copie o arquivo php_mongodb.dll para a pasta de extensões do XAMPP (Caminho: C:\xampp\php\ext\).

Edite o php.ini: Abra o Painel do XAMPP, clique em "Config" (na linha do Apache) e em "PHP (php.ini)". Adicione a linha extension=mongodb no final do arquivo e salve.

Reinicie o Apache: No Painel do XAMPP, clique em "Stop" e depois em "Start" no Apache.

4.Instalação do Composer
Execute o instalador Composer-Setup.exe. Ele deve encontrar automaticamente o PHP do seu XAMPP.


### Credenciais padrão do MongoDB:

Host: localhost (ou 127.0.0.1)
Porta: 27017
Usuário: (não necessário por padrão)
Senha: (não necessário por padrão)
Banco de dados: adopet (será criado automaticamente no primeiro cadastro)
Nota: A conexão é feita no arquivo mongo_connection.php.

### Clonagem do Projeto

1. Clone o repositório:
git clone [https://github.com/loosesaturn/projeto-adopet-final.git]

2. Mova os arquivos para a pasta do XAMPP:

Copie a pasta do projeto para o diretório htdocs do XAMPP
Caminho padrão: C:\xampp\htdocs\ (Windows) ou /opt/lampp/htdocs/ (Linux)

3. Instale as dependências (Driver do Mongo):

Abra um terminal (Prompt de Comando) dentro da pasta do projeto:

cd C:\xampp\htdocs\adopet

Rode o Composer:

composer install

### Execução do Projeto

1. Inicie os serviços:

Abra o Painel de Controle do XAMPP e clique em "Start" no módulo Apache.

Vá em "Serviços" do Windows e garanta que o "MongoDB Server (MongoDB)" está "Em execução".

2. Acesse a aplicação:

Abra seu navegador

Digite na barra de endereços: http://localhost/adopet

3. Comece a usar:

A página inicial da aplicação será carregada.
Você pode fazer login ou criar uma nova conta (os dados serão salvos no MongoDB).

### Solução de Problemas
Apache não inicia:

Verifique se a porta 80 não está sendo usada por outro programa

Página dá erro de "Classe 'MongoDB\Client' não encontrada":
Isso significa que o composer install não foi executado. Rode o comando na pasta do projeto.

Página dá erro de "Classe 'MongoDB\Driver\Manager' não encontrada":
Isso significa que a extensão .dll não está carregada. Verifique se o php_mongodb.dll está na pasta ext/ e se a linha extension=mongodb está correta no php.ini. Lembre-se de reiniciar o Apache após editar o php.ini.

Página dá erro de "Conexão recusada" (Connection refused):
Confirme que o serviço "MongoDB Server (MongoDB)" está rodando nos Serviços do Windows.
