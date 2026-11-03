# Resumo do histórico do projeto

Este resumo reúne os principais pontos registrados no histórico Git da branch `01-aula-0311`, até o commit `52dbe9d` (`Update usuarios.php`), de 3 de novembro de 2026.

## Principais etapas

1. **Estrutura inicial do Microblog (`bbb8d28`)**
   - Projeto didático de portal de notícias em PHP, com áreas pública e administrativa.
   - Inclusão das páginas, componentes compartilhados, estilos, imagens e scripts iniciais.
   - Inclusão dos modelos, conexão PDO, enums, helpers, scripts SQL e materiais de apoio.
   - A listagem administrativa de usuários ainda exibia dados estáticos de exemplo.

2. **Ajustes na página inicial (`3193500`)**
   - Pequena reorganização da abertura do arquivo `index.php`, sem mudança funcional aparente.

3. **Organização de namespaces e autoload (`7901f1a`)**
   - Configuração do Composer com mapeamento PSR-4 `Microblog\` para `src/`.
   - Inclusão de `/vendor/` no `.gitignore`.
   - Adoção de namespaces nos helpers e modelos de notícia e usuário, com referências aos enums correspondentes.

4. **Início do serviço de usuários (`43edde8`, `a07f181`)**
   - Criação de `UsuarioServico`, responsável por obter a conexão PDO.
   - Implementação de `listarTodos()`, que consulta os usuários e os ordena pelo nome.
   - Tratamento de falhas de banco de dados com registro em log e exceção com mensagem genérica.

5. **Integração da listagem no painel (`1024883`, `52dbe9d`)**
   - A página administrativa carrega o autoload e solicita os usuários ao serviço.
   - A tabela passou a ser preenchida com os registros retornados, em vez de dados fixos.
   - Nomes, e-mails, tipos e IDs exibidos são escapados com o helper de HTML.
   - A quantidade de usuários passou a ser calculada dinamicamente; em caso de falha, a página mostra uma mensagem e não exibe a tabela.

## Estado registrado

Até o último commit analisado, o avanço mais recente está concentrado na leitura e exibição dos usuários no painel administrativo. O histórico não registra, nessa sequência, implementação equivalente de operações CRUD dinâmicas para usuários, nem alterações posteriores ao commit `52dbe9d`.
