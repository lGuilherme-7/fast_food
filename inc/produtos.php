<?php
/**
 * inc/produtos.php — Lógica de produtos
 *
 * Responsabilidade:
 *   - Buscar produtos (listagem, individual, destaque, ofertas)
 *   - Filtrar por categoria, busca textual, faixa de preço
 *   - Paginação
 *   - Buscar categorias
 *   - Registrar visualização
 *   - Buscar avaliações
 *   - Verificar estoque
 *   - Buscar imagens do produto
 *
 * Todas as queries usam prepared statements (PDO).
 * Nenhuma saída HTML aqui — apenas lógica e dados.
 */

// ──────────────────────────────────────────────────────────────────────────────
// buscarProdutos()
//
// Listagem paginada com filtros opcionais.
//
// @param PDO   $pdo
// @param array $filtros {
//   categoria_slug string  — slug da categoria
//   q             string  — busca textual (nome ou descrição)
//   oferta        bool    — apenas produtos com preco_promocional
//   preco_min     float
//   preco_max     float
//   ordem         string  — 'recente'|'menor_preco'|'maior_preco'|'nome'|'avaliacao'
//   pagina        int     — página atual (default 1)
//   por_pagina    int     — itens por página (default 12)
// }
//
// @return array { itens: array, total: int, paginas: int, pagina_atual: int }
// ──────────────────────────────────────────────────────────────────────────────
function buscarProdutos(PDO $pdo, array $filtros = []): array
{
    // Defaults
    $categSlug = trim($filtros['categoria_slug'] ?? '');
    $q         = trim($filtros['q']              ?? '');
    $oferta    = !empty($filtros['oferta']);
    $precoMin  = isset($filtros['preco_min']) ? (float) $filtros['preco_min'] : null;
    $precoMax  = isset($filtros['preco_max']) ? (float) $filtros['preco_max'] : null;
    $ordem     = $filtros['ordem']    ?? 'recente';
    $pagina    = max(1, (int) ($filtros['pagina']     ?? 1));
    $porPagina = max(1, min(48, (int) ($filtros['por_pagina'] ?? 12)));
    $offset    = ($pagina - 1) * $porPagina;

    // ── monta WHERE dinâmico ─────────────────────────────────────────────────
    $where  = ['p.ativo = 1', 'p.estoque > 0'];
    $params = [];

    if ($categSlug !== '') {
        $where[]                  = 'c.slug = :categoria_slug';
        $params[':categoria_slug'] = $categSlug;
    }

    if ($q !== '') {
        $where[]   = '(p.nome LIKE :q OR p.descricao_curta LIKE :q OR p.descricao LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    if ($oferta) {
        $where[] = 'p.preco_promocional IS NOT NULL';
    }

    if ($precoMin !== null) {
        $where[]           = 'COALESCE(p.preco_promocional, p.preco) >= :preco_min';
        $params[':preco_min'] = $precoMin;
    }

    if ($precoMax !== null) {
        $where[]           = 'COALESCE(p.preco_promocional, p.preco) <= :preco_max';
        $params[':preco_max'] = $precoMax;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // ── ORDER BY ─────────────────────────────────────────────────────────────
    $ordenacoes = [
        'recente'      => 'p.created_at DESC',
        'menor_preco'  => 'COALESCE(p.preco_promocional, p.preco) ASC',
        'maior_preco'  => 'COALESCE(p.preco_promocional, p.preco) DESC',
        'nome'         => 'p.nome ASC',
        'avaliacao'    => 'media_avaliacao DESC',
    ];
    $orderSql = $ordenacoes[$ordem] ?? $ordenacoes['recente'];

    // ── conta total ──────────────────────────────────────────────────────────
    $sqlCount = "
        SELECT COUNT(DISTINCT p.id)
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        {$whereSql}
    ";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total   = (int) $stmtCount->fetchColumn();
    $paginas = $total > 0 ? (int) ceil($total / $porPagina) : 1;

    // ── busca itens ──────────────────────────────────────────────────────────
    $sql = "
        SELECT
            p.id,
            p.nome,
            p.slug,
            p.preco,
            p.preco_promocional,
            p.descricao_curta,
            p.estoque,
            p.destaque,
            p.created_at,
            c.id   AS categoria_id,
            c.nome AS categoria_nome,
            c.slug AS categoria_slug,
            ROUND(AVG(a.nota), 1) AS media_avaliacao,
            COUNT(a.id)           AS total_avaliacoes,
            (
                SELECT pi.caminho
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id AND pi.principal = 1
                LIMIT 1
            ) AS imagem
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        LEFT  JOIN avaliacoes  a ON a.produto_id = p.id AND a.ativo = 1
        {$whereSql}
        GROUP BY p.id
        ORDER BY {$orderSql}
        LIMIT :limite OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // PDO não aceita bindValue com array para LIMIT/OFFSET — bind manual
    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
    $stmt->execute();

    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcula percentual de desconto em cada produto
    foreach ($itens as &$p) {
        $p['percentual_desconto'] = calcularPercentualDesconto(
            (float) $p['preco'],
            $p['preco_promocional'] !== null ? (float) $p['preco_promocional'] : null
        );
        $p['preco_exibir'] = $p['preco_promocional'] ?? $p['preco'];
    }
    unset($p);

    return [
        'itens'       => $itens,
        'total'       => $total,
        'paginas'     => $paginas,
        'pagina_atual'=> $pagina,
        'por_pagina'  => $porPagina,
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarProdutoPorId()
//
// Retorna dados completos de um produto pelo ID.
// Inclui imagens, avaliações resumidas e produtos relacionados.
//
// @param PDO $pdo
// @param int $id
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarProdutoPorId(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.nome,
            p.slug,
            p.preco,
            p.preco_promocional,
            p.descricao_curta,
            p.descricao,
            p.estoque,
            p.destaque,
            p.peso,
            p.created_at,
            c.id   AS categoria_id,
            c.nome AS categoria_nome,
            c.slug AS categoria_slug,
            ROUND(AVG(a.nota), 1) AS media_avaliacao,
            COUNT(a.id)           AS total_avaliacoes
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        LEFT  JOIN avaliacoes  a ON a.produto_id = p.id AND a.ativo = 1
        WHERE p.id = :id AND p.ativo = 1
        GROUP BY p.id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) return null;

    // Percentual de desconto
    $produto['percentual_desconto'] = calcularPercentualDesconto(
        (float) $produto['preco'],
        $produto['preco_promocional'] !== null ? (float) $produto['preco_promocional'] : null
    );
    $produto['preco_exibir'] = $produto['preco_promocional'] ?? $produto['preco'];

    // Imagens
    $produto['imagens'] = buscarImagensProduto($pdo, $id);

    // Imagem principal (primeira da lista)
    $produto['imagem_principal'] = !empty($produto['imagens'])
        ? $produto['imagens'][0]['caminho']
        : null;

    return $produto;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarProdutoPorSlug()
//
// @param PDO    $pdo
// @param string $slug
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarProdutoPorSlug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("SELECT id FROM produtos WHERE slug = :slug AND ativo = 1 LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? buscarProdutoPorId($pdo, (int) $row['id']) : null;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarImagensProduto()
//
// Retorna todas as imagens de um produto, principal primeiro.
//
// @param PDO $pdo
// @param int $produtoId
//
// @return array [ ['caminho'=>..., 'alt'=>..., 'principal'=>...], ... ]
// ──────────────────────────────────────────────────────────────────────────────
function buscarImagensProduto(PDO $pdo, int $produtoId): array
{
    $stmt = $pdo->prepare("
        SELECT caminho, alt, principal
        FROM produto_imagens
        WHERE produto_id = :id
        ORDER BY principal DESC, ordem ASC
    ");
    $stmt->execute([':id' => $produtoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarDestaques()
//
// Produtos com destaque = 1, em estoque.
//
// @param PDO $pdo
// @param int $limite
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function buscarDestaques(PDO $pdo, int $limite = 8): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.slug, p.preco, p.preco_promocional,
            p.descricao_curta, p.estoque,
            c.nome AS categoria_nome,
            c.slug AS categoria_slug,
            ROUND(AVG(a.nota), 1) AS media_avaliacao,
            (
                SELECT pi.caminho
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id AND pi.principal = 1
                LIMIT 1
            ) AS imagem
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        LEFT  JOIN avaliacoes  a ON a.produto_id = p.id AND a.ativo = 1
        WHERE p.ativo = 1 AND p.destaque = 1 AND p.estoque > 0
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT :limite
    ");
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return _adicionarDescontos($itens);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarOfertas()
//
// Produtos com preco_promocional, ordenados pelo maior desconto absoluto.
//
// @param PDO $pdo
// @param int $limite
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function buscarOfertas(PDO $pdo, int $limite = 6): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.slug, p.preco, p.preco_promocional,
            p.descricao_curta, p.estoque,
            c.nome AS categoria_nome,
            c.slug AS categoria_slug,
            (
                SELECT pi.caminho
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id AND pi.principal = 1
                LIMIT 1
            ) AS imagem
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1
          AND p.preco_promocional IS NOT NULL
          AND p.estoque > 0
        ORDER BY (p.preco - p.preco_promocional) DESC
        LIMIT :limite
    ");
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return _adicionarDescontos($itens);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarRelacionados()
//
// Produtos da mesma categoria, excluindo o produto atual.
//
// @param PDO $pdo
// @param int $produtoId
// @param int $categoriaId
// @param int $limite
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function buscarRelacionados(PDO $pdo, int $produtoId, int $categoriaId, int $limite = 4): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.slug, p.preco, p.preco_promocional,
            p.descricao_curta,
            (
                SELECT pi.caminho
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id AND pi.principal = 1
                LIMIT 1
            ) AS imagem
        FROM produtos p
        WHERE p.ativo        = 1
          AND p.estoque       > 0
          AND p.categoria_id  = :categoria_id
          AND p.id           != :produto_id
        ORDER BY RAND()
        LIMIT :limite
    ");
    $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
    $stmt->bindValue(':produto_id',   $produtoId,   PDO::PARAM_INT);
    $stmt->bindValue(':limite',        $limite,      PDO::PARAM_INT);
    $stmt->execute();

    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return _adicionarDescontos($itens);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarCategorias()
//
// Lista todas as categorias ativas com contagem de produtos.
//
// @param PDO $pdo
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function buscarCategorias(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            c.id,
            c.nome,
            c.slug,
            c.icone,
            c.cor_hex,
            COUNT(p.id) AS total_produtos
        FROM categorias c
        LEFT JOIN produtos p
               ON p.categoria_id = c.id
              AND p.ativo = 1
              AND p.estoque > 0
        WHERE c.ativo = 1
        GROUP BY c.id
        ORDER BY c.ordem ASC, c.nome ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarCategoriaPorSlug()
//
// @param PDO    $pdo
// @param string $slug
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarCategoriaPorSlug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, nome, slug, icone, cor_hex
        FROM categorias
        WHERE slug = :slug AND ativo = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarAvaliacoesProduto()
//
// Retorna avaliações aprovadas de um produto, paginadas.
//
// @param PDO $pdo
// @param int $produtoId
// @param int $pagina
// @param int $porPagina
//
// @return array { itens, total, media }
// ──────────────────────────────────────────────────────────────────────────────
function buscarAvaliacoesProduto(PDO $pdo, int $produtoId, int $pagina = 1, int $porPagina = 5): array
{
    $offset = ($pagina - 1) * $porPagina;

    // Total e média
    $stmtMeta = $pdo->prepare("
        SELECT COUNT(*) AS total, ROUND(AVG(nota), 1) AS media
        FROM avaliacoes
        WHERE produto_id = :id AND ativo = 1
    ");
    $stmtMeta->execute([':id' => $produtoId]);
    $meta = $stmtMeta->fetch(PDO::FETCH_ASSOC);

    // Itens paginados
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.nota,
            a.titulo,
            a.comentario,
            a.created_at,
            u.nome AS usuario_nome
        FROM avaliacoes a
        INNER JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.produto_id = :id AND a.ativo = 1
        ORDER BY a.created_at DESC
        LIMIT :limite OFFSET :offset
    ");
    $stmt->bindValue(':id',     $produtoId, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
    $stmt->execute();

    return [
        'itens'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'  => (int)   $meta['total'],
        'media'  => (float) $meta['media'],
        'pagina' => $pagina,
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// salvarAvaliacao()
//
// Insere ou atualiza avaliação de um usuário para um produto.
// Um usuário só pode avaliar um produto uma vez.
//
// @param PDO $pdo
// @param int $usuarioId
// @param int $produtoId
// @param int $nota       — 1 a 5
// @param string $titulo
// @param string $comentario
//
// @return true|string
// ──────────────────────────────────────────────────────────────────────────────
function salvarAvaliacao(
    PDO    $pdo,
    int    $usuarioId,
    int    $produtoId,
    int    $nota,
    string $titulo      = '',
    string $comentario  = ''
): bool|string {

    if ($nota < 1 || $nota > 5) {
        return 'Nota deve ser entre 1 e 5.';
    }

    // Verifica se usuário já avaliou
    $chk = $pdo->prepare("
        SELECT id FROM avaliacoes
        WHERE usuario_id = :u AND produto_id = :p
        LIMIT 1
    ");
    $chk->execute([':u' => $usuarioId, ':p' => $produtoId]);
    $existe = $chk->fetch();

    if ($existe) {
        // Atualiza avaliação existente
        $upd = $pdo->prepare("
            UPDATE avaliacoes
            SET nota = :nota, titulo = :titulo, comentario = :comentario,
                updated_at = NOW()
            WHERE id = :id
        ");
        $upd->execute([
            ':nota'       => $nota,
            ':titulo'     => trim($titulo),
            ':comentario' => trim($comentario),
            ':id'         => (int) $existe['id'],
        ]);
    } else {
        // Insere nova avaliação (ativo = 0 se quiser moderação, 1 para auto-aprovar)
        $ins = $pdo->prepare("
            INSERT INTO avaliacoes (usuario_id, produto_id, nota, titulo, comentario, ativo, created_at)
            VALUES (:u, :p, :nota, :titulo, :comentario, 1, NOW())
        ");
        $ins->execute([
            ':u'          => $usuarioId,
            ':p'          => $produtoId,
            ':nota'       => $nota,
            ':titulo'     => trim($titulo),
            ':comentario' => trim($comentario),
        ]);
    }

    return true;
}

// ──────────────────────────────────────────────────────────────────────────────
// registrarVisualizacao()
//
// Incrementa contador de visualizações do produto.
// Chamada em produto.php a cada acesso.
//
// @param PDO $pdo
// @param int $produtoId
// ──────────────────────────────────────────────────────────────────────────────
function registrarVisualizacao(PDO $pdo, int $produtoId): void
{
    $stmt = $pdo->prepare("
        UPDATE produtos
        SET visualizacoes = visualizacoes + 1
        WHERE id = :id
    ");
    $stmt->execute([':id' => $produtoId]);
}

// ──────────────────────────────────────────────────────────────────────────────
// verificarEstoque()
//
// Verifica se a quantidade desejada está disponível em estoque.
//
// @param PDO $pdo
// @param int $produtoId
// @param int $quantidade
//
// @return bool
// ──────────────────────────────────────────────────────────────────────────────
function verificarEstoque(PDO $pdo, int $produtoId, int $quantidade = 1): bool
{
    $stmt = $pdo->prepare("
        SELECT estoque FROM produtos
        WHERE id = :id AND ativo = 1
        LIMIT 1
    ");
    $stmt->execute([':id' => $produtoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row && (int) $row['estoque'] >= $quantidade;
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarFavoritos()
//
// Retorna produtos favoritados pelo usuário.
//
// @param PDO $pdo
// @param int $usuarioId
//
// @return array
// ──────────────────────────────────────────────────────────────────────────────
function buscarFavoritos(PDO $pdo, int $usuarioId): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.nome, p.slug, p.preco, p.preco_promocional,
            p.descricao_curta, p.estoque,
            c.nome AS categoria_nome,
            c.slug AS categoria_slug,
            (
                SELECT pi.caminho
                FROM produto_imagens pi
                WHERE pi.produto_id = p.id AND pi.principal = 1
                LIMIT 1
            ) AS imagem,
            f.created_at AS favoritado_em
        FROM favoritos f
        INNER JOIN produtos    p ON p.id = f.produto_id AND p.ativo = 1
        INNER JOIN categorias  c ON c.id = p.categoria_id
        WHERE f.usuario_id = :usuario_id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([':usuario_id' => $usuarioId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return _adicionarDescontos($itens);
}

// ──────────────────────────────────────────────────────────────────────────────
// toggleFavorito()
//
// Adiciona ou remove produto dos favoritos do usuário.
//
// @param PDO $pdo
// @param int $usuarioId
// @param int $produtoId
//
// @return array { favoritado: bool }
// ──────────────────────────────────────────────────────────────────────────────
function toggleFavorito(PDO $pdo, int $usuarioId, int $produtoId): array
{
    // Verifica se já existe
    $chk = $pdo->prepare("
        SELECT id FROM favoritos
        WHERE usuario_id = :u AND produto_id = :p
        LIMIT 1
    ");
    $chk->execute([':u' => $usuarioId, ':p' => $produtoId]);
    $existe = $chk->fetch();

    if ($existe) {
        // Remove
        $del = $pdo->prepare("DELETE FROM favoritos WHERE id = :id");
        $del->execute([':id' => (int) $existe['id']]);
        return ['favoritado' => false];
    } else {
        // Adiciona
        $ins = $pdo->prepare("
            INSERT INTO favoritos (usuario_id, produto_id, created_at)
            VALUES (:u, :p, NOW())
        ");
        $ins->execute([':u' => $usuarioId, ':p' => $produtoId]);
        return ['favoritado' => true];
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// produtosFavoritosIds()
//
// Retorna array de IDs de produtos favoritados pelo usuário.
// Usado para marcar o ícone de coração nos cards.
//
// @param PDO $pdo
// @param int $usuarioId
//
// @return int[]
// ──────────────────────────────────────────────────────────────────────────────
function produtosFavoritosIds(PDO $pdo, int $usuarioId): array
{
    $stmt = $pdo->prepare("
        SELECT produto_id FROM favoritos WHERE usuario_id = :u
    ");
    $stmt->execute([':u' => $usuarioId]);
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'produto_id');
}

// ──────────────────────────────────────────────────────────────────────────────
// calcularPercentualDesconto()
//
// @param float      $preco
// @param float|null $precoPromocional
//
// @return int  — percentual inteiro (ex: 25) ou 0 se sem promoção
// ──────────────────────────────────────────────────────────────────────────────
function calcularPercentualDesconto(float $preco, ?float $precoPromocional): int
{
    if ($precoPromocional === null || $precoPromocional >= $preco || $preco <= 0) {
        return 0;
    }
    return (int) round((1 - $precoPromocional / $preco) * 100);
}

// ──────────────────────────────────────────────────────────────────────────────
// _adicionarDescontos()  — helper interno
//
// Adiciona percentual_desconto e preco_exibir a cada item de um array.
// ──────────────────────────────────────────────────────────────────────────────
function _adicionarDescontos(array $itens): array
{
    foreach ($itens as &$p) {
        $p['percentual_desconto'] = calcularPercentualDesconto(
            (float) $p['preco'],
            $p['preco_promocional'] !== null ? (float) $p['preco_promocional'] : null
        );
        $p['preco_exibir'] = $p['preco_promocional'] ?? $p['preco'];
    }
    unset($p);
    return $itens;
}

// ──────────────────────────────────────────────────────────────────────────────
// formatarPreco()
//
// Helper de formatação para uso nas views.
//
// @param float $valor
// @return string  ex: "R$ 12,90"
// ──────────────────────────────────────────────────────────────────────────────
function formatarPreco(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// ──────────────────────────────────────────────────────────────────────────────
// gerarStarsHtml()
//
// Gera HTML das estrelas de avaliação.
//
// @param float $media  — 0.0 a 5.0
// @param bool  $mostrarNumero
//
// @return string HTML
// ──────────────────────────────────────────────────────────────────────────────
function gerarStarsHtml(float $media, bool $mostrarNumero = true): string
{
    $html = '<span class="stars" aria-label="Avaliação: ' . $media . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($media >= $i) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } elseif ($media >= $i - 0.5) {
            $html .= '<i class="bi bi-star-half text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-warning"></i>';
        }
    }
    if ($mostrarNumero && $media > 0) {
        $html .= ' <small class="text-muted">' . number_format($media, 1) . '</small>';
    }
    $html .= '</span>';
    return $html;
}