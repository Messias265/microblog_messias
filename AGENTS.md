# Referência de desenvolvimento — Microblog

Este arquivo resume a arquitetura e as convenções do projeto para orientar futuras alterações. Leia também o código da funcionalidade envolvida antes de modificá-la: algumas páginas ainda são provisórias e a documentação existente nem sempre reflete o comportamento atual.

## Visão geral

Microblog é um portal didático de notícias feito em PHP orientado a objetos, com MySQL/MariaDB acessado por PDO. A interface usa HTML, Bootstrap e JavaScript simples. O conteúdo e as mensagens da aplicação estão em português.

Há duas áreas:

- **Pública:** `index.php`, `noticia.php`, `noticias-por-categoria.php` e `resultados.php`; a busca dinâmica usa `js/busca.js`.
- **Administrativa:** páginas PHP em `admin/`, com autenticação por sessão, gerenciamento de conteúdo e perfil.

## Ambiente e execução

- O `README.md` documenta PHP 8.5+ e as extensões `pdo_mysql`, `mbstring` e `fileinfo`.
- É necessário um servidor PHP/Apache e MySQL ou MariaDB.
- O banco configurado no código chama-se `microblog_2026`. As configurações de conexão estão em `src/Database/ConexaoBD.php`; ajuste-as ao ambiente local, sem incluir credenciais reais em commits.
- O autoload do Composer usa PSR-4: `Microblog\` corresponde a `src/`. Páginas carregam `vendor/autoload.php`. Após alterar o mapeamento ou adicionar classes, use `composer dump-autoload`.
- O `.gitignore` exclui `vendor/`; dependências/autoload devem ser gerados pelo Composer quando necessário.
- Não há scripts de teste ou lint configurados no `composer.json`. Para verificar sintaxe de um arquivo PHP, use `php -l caminho/do/arquivo.php`.

## Organização do código

- `src/Auth/`: sessão, autenticação e autorização (`ControleDeAcesso`).
- `src/Database/`: conexão PDO reutilizável (`ConexaoBD`).
- `src/Enums/`: tipos de usuário e destaque (`TipoUsuario`, `Destaque`).
- `src/Helpers/`: normalização, escape, upload, hashing, logs e validações (`Utils`, `Validacoes`).
- `src/Models/`: objetos de domínio com propriedades tipadas privadas e getters (`Usuario`, `Categoria`, `Noticia`).
- `src/Services/`: consultas e operações de banco por entidade (`UsuarioServico`, `CategoriaServico`, `NoticiaServico`).
- `admin/js/`: confirmação de exclusão e contador de caracteres.
- `includes/`: cabeçalhos, rodapés e consultas/blocos compartilhados.
- `images/`: imagens usadas nas notícias e ícones.
- `_materiais/`: dumps SQL e materiais de apoio; podem conter versões antigas.
- `logs/erros.log`: log de exceções da aplicação.

## Convenções a preservar

- Use namespaces `Microblog\...` alinhados à pasta, classes em `PascalCase`, métodos e propriedades em `camelCase`. Os nomes das páginas PHP existentes usam minúsculas e hífens, por exemplo `noticia-atualiza.php`.
- Os nomes de classes/métodos e mensagens seguem o domínio em português; prefira manter esse padrão.
- Modelos recebem dados pelo construtor e expõem getters. Enums são armazenados no banco pelo seu `->value`; converta valores recebidos com `tryFrom` na validação antes de usar `from`.
- Serviços recebem modelos para inserir/atualizar e IDs escalares para operações por ID. Use PDO e consultas preparadas para valores variáveis. A conexão configura `PDO::ERRMODE_EXCEPTION` e `PDO::FETCH_ASSOC`.
- Páginas administrativas costumam inicializar variáveis de formulário, processar `POST`, validar, chamar o serviço, redirecionar após sucesso e exibir mensagens de erro amigáveis.
- Erros inesperados são registrados com `Utils::registrarLog($e)` e apresentados sem detalhes internos. Erros de validação geralmente usam `InvalidArgumentException`.
- Normalize entradas com `Utils::normalizarEntrada`; valide campos com `Validacoes`; valide IDs de URL com `Utils::verificarId`.
- Escape valores ao renderizar HTML usando `Utils::escaparHtml`. A normalização de texto remove espaços externos, mas não HTML-escapa nem remove conteúdo; o escape pertence à saída.
- Senhas novas devem ser armazenadas com `Utils::codificarSenha` (`password_hash`). Para alterações, o padrão é preservar o hash se o campo estiver vazio e usar `Utils::verificarSenha` quando preenchido.
- Upload de imagens deve passar por `Utils::upload`: arquivo recebido via upload, até 2 MB, formatos JPEG/GIF/PNG/WebP e nome de até 60 caracteres; os arquivos são salvos em `images/`.
- Faça mudanças pequenas e consistentes com os padrões próximos. Não introduza dependências ou abstrações sem necessidade.

## Banco de dados

O esquema operacional está descrito em `_materiais/microblog_2026_completo.sql`:

- `usuarios`: `id`, `nome`, `email`, `senha`, `tipo`; o e-mail é único e `tipo` admite `admin` ou `editor`.
- `categorias`: `id`, `nome`.
- `noticias`: `id`, `data` (padrão `CURRENT_TIMESTAMP`), `titulo`, `texto`, `resumo`, `imagem`, `destaque` (`sim`/`nao`), `usuario_id` e `categoria_id`.
- As referências de notícia a usuário e categoria podem ser nulas; as chaves estrangeiras usam `ON DELETE SET NULL`.

Há divergências entre materiais antigos e o código atual. Por exemplo, `modelagem-fisica.md` menciona `microblog_2025`, enquanto o código e o dump completo usam `microblog_2026`; esse documento também descreve um limite de resumo diferente do limite validado atualmente. Confirme o esquema efetivamente instalado e o código antes de alterar campos ou regras.

## Autenticação e permissões atuais

- `ControleDeAcesso::exigirLogin()` protege o cabeçalho administrativo; as sessões guardam `id`, `nome` e `tipo`.
- `ControleDeAcesso::exigirAdmin()` é usado pelas telas de gerenciamento de usuários e categorias.
- `admin/noticias.php` mostra todas as notícias ao administrador e limita editores às próprias notícias por `NoticiaServico::listarTodos()`. A inserção de notícia associa o usuário da sessão como autor.
- **Atenção à divergência documental:** o `README.md` diz que editores podem gerenciar categorias, mas as páginas atuais de categorias chamam `exigirAdmin()`. Ao alterar permissões, siga o requisito pedido e atualize a documentação correspondente.
- A exclusão da própria conta está bloqueada em `admin/usuario-exclui.php`.

## Estado funcional conhecido

- `UsuarioServico` e `CategoriaServico` implementam listagem, busca por ID, inserção, atualização e exclusão (além da busca de usuário por e-mail).
- `NoticiaServico` implementa listagem, busca por ID, inserção, atualização e exclusão. As operações de busca, atualização e exclusão devem preservar o escopo do administrador (qualquer notícia) e do editor (apenas notícias próprias).
- Em `admin/noticia-insere.php`, a validação de texto está repetida, não há chamada a `Validacoes::validarResumo()` e o valor padrão usado ao normalizar categoria merece correção caso essa página seja alterada.
- `Validacoes::validarResumo()` atualmente limita o resumo a 300 caracteres; o formulário de inserção também indica 300.

## Materiais de referência

- `README.md`: apresentação do projeto, estrutura e configuração local.
- `_materiais/microblog_2026_completo.sql`: dump atual com estrutura e dados de exemplo.
- `_materiais/microblog_2026_inicial.sql` e demais arquivos de `_materiais/`: versões e materiais didáticos; verifique a data/versão antes de usá-los como fonte do esquema.
- `resumo.md`: resumo de mudanças recentes na branch, útil como histórico, não como especificação completa.
