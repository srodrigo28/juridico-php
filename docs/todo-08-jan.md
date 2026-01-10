Entendido. Plano: criar um script completo para inicializar o banco local com todas as tabelas e um usuário administrador com licença ativa, para que você consiga logar e testar em modo administrador.

Arquivos criados:
scripts/criar_banco.sql: cria o banco juridico e todas as tabelas (autenticação/licenças e aplicação).
scripts/criar_admin.php: configura tabelas via PHP e cria o usuário admin com senha e licença ativa.
Como usar:

Opção SQL (apenas estrutura + admin sem senha):
Depois, execute o PHP para definir a senha do admin:

Opção PHP (estrutura da app + senha do admin):
Abra no navegador:
http://localhost/www/juridico-php/scripts/criar_admin.php
Ele cria/atualiza:

Usuário: admin@local.test
Senha: Admin123!
Licença ativa: produto 5776734 (Precifex Jurídico)
Após isso:

Acesse http://localhost/www/juridico-php/login.php
Faça login com admin@local.test / Admin123!
Será redirecionado para http://localhost/www/juridico-php/index.php?aba=dashboard
Se preferir outros dados (email/senha), me diga que ajusto o script.