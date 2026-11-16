# Resumo: atualização e exclusão de notícias

## Alterações implementadas

O fluxo administrativo de notícias passou de páginas provisórias para operações de consulta, atualização e exclusão com controle por perfil.

### Atualização

A página `admin/noticia-atualiza.php` recebe o ID da notícia pela URL no `GET` e por um campo oculto no `POST`. Valida o ID, carrega as categorias e busca a notícia de acordo com o perfil e o ID do usuário logado.

No envio do formulário, normaliza e valida título, texto, resumo, destaque e categoria. A categoria também é confirmada no banco. Se nenhuma imagem nova for enviada, mantém a imagem atual; se houver substituição, usa `Utils::upload()`. Quando há erro de validação, os valores digitados permanecem no formulário. Após salvar, redireciona para `noticias.php`.

O método `atualizar()` em `src/Services/NoticiaServico.php` usa este SQL:

```sql
UPDATE noticias
SET titulo = :titulo,
    texto = :texto,
    resumo = :resumo,
    imagem = :imagem,
    destaque = :destaque,
    categoria_id = :categoria_id
WHERE id = :id
```

Para usuários do tipo `EDITOR`, o serviço acrescenta:

```sql
AND usuario_id = :usuario_id
```

Os parâmetros são vinculados com PDO (`bindValue()`), em vez de concatenados ao SQL. A autoria (`usuario_id`) e a data (`data`) não são alteradas durante a atualização.

### Exclusão

O link de exclusão em `admin/noticias.php` já enviava o ID para `admin/noticia-exclui.php`. Essa página agora valida o ID, exige login, chama o serviço e redireciona para a listagem depois da exclusão.

O método `excluir()` em `src/Services/NoticiaServico.php` executa:

```sql
DELETE FROM noticias
WHERE id = :id
```

Para um editor, acrescenta a restrição:

```sql
AND usuario_id = :usuario_id
```

Se nenhuma linha for excluída — porque a notícia não existe ou o usuário não tem permissão — o serviço sinaliza erro. A página registra a exceção e apresenta uma mensagem genérica.

## Regras de acesso

- **Administrador:** pode consultar, atualizar e excluir notícias de qualquer autor.
- **Editor:** pode consultar, atualizar e excluir somente as próprias notícias.
- A autorização de atualização e exclusão é aplicada também no SQL, além de ser verificada durante o carregamento da notícia.

## Model

`src/Models/Noticia.php` passou a aceitar `usuarioId` nulo. Isso reflete o esquema do banco, no qual `noticias.usuario_id` pode ser `NULL` quando o usuário relacionado é removido. A atualização não muda esse campo.

## Verificações realizadas

- A sintaxe PHP dos arquivos alterados passou na verificação `php -l`.
- Os fluxos de atualização e exclusão foram testados em transações revertidas:
  - administrador atualiza/exclui notícia de outro autor;
  - editor atualiza/exclui notícia própria;
  - editor não consegue atualizar/excluir notícia de outro autor.
- As transações foram revertidas ao final dos testes para não persistir alterações de teste no banco.

## Observação de segurança sobre a exclusão

A listagem exibe uma confirmação JavaScript antes de navegar ao link de exclusão, mas a operação atualmente é acionada por `GET`. A confirmação no navegador é apenas uma proteção de interface; acessar diretamente a URL ainda executa a exclusão. Como melhoria futura, recomenda-se migrar essa operação para `POST` e adicionar proteção contra CSRF.