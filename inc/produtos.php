<?php
// ============================================================
// inc/produtos.php — Queries de produtos e adicionais
// Chamar com: require_once __DIR__ . '/../inc/produtos.php';
// Requer $pdo disponível (inclua inc/db.php antes)
// ============================================================

/**
 * Retorna todos os produtos ativos com categoria.
 * Opcionalmente filtra por slug da categoria.
 */
function produtos_listar(PDO $pdo, string $cat_slug = ''): array {
    $sql = "
        SELECT p.id, p.nome, p.descricao, p.preco, p.imagem_url,
               p.estoque, c.slug AS cat_slug, c.nome AS cat_nome, c.id AS cat_id
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1 AND p.estoque > 0
    ";
    $params = [];

    if ($cat_slug !== '') {
        $sql .= " AND c.slug = ?";
        $params[] = $cat_slug;
    }

    $sql .= " ORDER BY c.ordem, p.nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retorna um produto pelo ID (apenas ativos).
 */
function produto_por_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.slug AS cat_slug, c.nome AS cat_nome
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.id = ? AND p.ativo = 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retorna os adicionais de um produto.
 * Se a tabela produto_adicionais não existir, retorna array vazio.
 */
function produto_adicionais(PDO $pdo, int $produto_id): array {
    try {
        $stmt = $pdo->prepare("
            SELECT id, nome, preco_extra
            FROM produto_adicionais
            WHERE produto_id = ? AND ativo = 1
            ORDER BY preco_extra ASC, nome ASC
        ");
        $stmt->execute([$produto_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Tabela ainda não existe — sem problema
        return [];
    }
}

/**
 * Adicionais padrão por categoria (fallback enquanto a tabela
 * produto_adicionais não estiver populada).
 */
function adicionais_padrao(string $cat_slug): array {
    $mapa = [
        'acai' => [
            ['nome' => 'Banana',           'preco_extra' => 0.00],
            ['nome' => 'Granola',          'preco_extra' => 0.00],
            ['nome' => 'Leite condensado', 'preco_extra' => 0.00],
            ['nome' => 'Morango',          'preco_extra' => 2.00],
            ['nome' => 'Kiwi',             'preco_extra' => 2.00],
            ['nome' => 'Nutella',          'preco_extra' => 3.00],
            ['nome' => 'Paçoca',           'preco_extra' => 1.50],
            ['nome' => 'Amendoim',         'preco_extra' => 1.00],
            ['nome' => 'Mel',              'preco_extra' => 1.00],
        ],
        'hamburguer' => [
            ['nome' => 'Bacon extra',                    'preco_extra' => 3.00],
            ['nome' => 'Queijo duplo',                   'preco_extra' => 2.00],
            ['nome' => 'Ovo',                            'preco_extra' => 2.00],
            ['nome' => 'Cheddar',                        'preco_extra' => 2.50],
            ['nome' => 'Sem cebola',                     'preco_extra' => 0.00],
            ['nome' => 'Ponto: ao ponto',                'preco_extra' => 0.00],
            ['nome' => 'Ponto: bem passado',             'preco_extra' => 0.00],
        ],
        'doces' => [
            ['nome' => 'Cobertura de Nutella', 'preco_extra' => 3.00],
            ['nome' => 'Granulado extra',      'preco_extra' => 0.00],
            ['nome' => 'Sem cobertura',        'preco_extra' => 0.00],
        ],
        'bebidas' => [
            ['nome' => 'Menos gelo',   'preco_extra' => 0.00],
            ['nome' => 'Sem gelo',     'preco_extra' => 0.00],
            ['nome' => 'Canudo extra', 'preco_extra' => 0.00],
        ],
    ];
    return $mapa[$cat_slug] ?? [];
}

/**
 * Retorna adicionais reais do banco ou fallback por categoria.
 * Esta é a função que as páginas devem chamar.
 */
function adicionais_do_produto(PDO $pdo, int $produto_id, string $cat_slug): array {
    $reais = produto_adicionais($pdo, $produto_id);
    return count($reais) > 0 ? $reais : adicionais_padrao($cat_slug);
}

/**
 * Retorna todas as categorias ativas ordenadas.
 */
function categorias_ativas(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT id, slug, nome, imagem_url, cor, descricao
        FROM categorias
        WHERE ativo = 1
        ORDER BY ordem
    ");
    return $stmt->fetchAll();
}

/**
 * Busca produtos pelo termo informado (nome ou descrição).
 */
function produtos_buscar(PDO $pdo, string $termo): array {
    $like = '%' . $termo . '%';
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome, p.descricao, p.preco, p.imagem_url,
               c.slug AS cat_slug, c.nome AS cat_nome
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1 AND p.estoque > 0
          AND (p.nome LIKE ? OR p.descricao LIKE ?)
        ORDER BY p.nome
        LIMIT 20
    ");
    $stmt->execute([$like, $like]);
    return $stmt->fetchAll();
}

/**
 * Decrementa estoque ao confirmar pedido.
 * Retorna true se deu certo, false se estoque insuficiente.
 */
function decrementar_estoque(PDO $pdo, int $produto_id, int $qtd): bool {
    // Checa se tem estoque suficiente
    $stmt = $pdo->prepare("SELECT estoque FROM produtos WHERE id = ? AND ativo = 1");
    $stmt->execute([$produto_id]);
    $row = $stmt->fetch();
    if (!$row || $row['estoque'] < $qtd) return false;

    $stmt = $pdo->prepare("
        UPDATE produtos SET estoque = estoque - ?
        WHERE id = ? AND estoque >= ?
    ");
    $stmt->execute([$qtd, $produto_id, $qtd]);
    return $stmt->rowCount() > 0;
}