<?php
// ============================================================
// inc/auth.php — Funções de autenticação (cliente público)
// Chamar com: require_once __DIR__ . '/../inc/auth.php';
// Para o ADMIN use includes/auth.php (já existente)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

// ── CLIENTE ───────────────────────────────────────────────────

/**
 * Verifica se o cliente está logado.
 */
function cliente_logado(): bool {
    return isset($_SESSION['cliente_id']) && (int)$_SESSION['cliente_id'] > 0;
}

/**
 * Retorna os dados da sessão do cliente logado ou null.
 */
function cliente_sessao(): ?array {
    if (!cliente_logado()) return null;
    return [
        'id'    => $_SESSION['cliente_id'],
        'nome'  => $_SESSION['cliente_nome']  ?? '',
        'email' => $_SESSION['cliente_email'] ?? '',
    ];
}

/**
 * Inicia sessão do cliente após login bem-sucedido.
 */
function cliente_login_sessao(array $cliente): void {
    session_regenerate_id(true);
    $_SESSION['cliente_id']    = $cliente['id'];
    $_SESSION['cliente_nome']  = $cliente['nome'];
    $_SESSION['cliente_email'] = $cliente['email'];
}

/**
 * Encerra sessão do cliente.
 */
function cliente_logout(): void {
    unset($_SESSION['cliente_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_email']);
}

/**
 * Redireciona para login se o cliente não estiver logado.
 * Salva a URL atual para redirecionar de volta após o login.
 */
function exigir_login_cliente(string $redirect = ''): void {
    if (!cliente_logado()) {
        $volta = $redirect ?: ($_SERVER['REQUEST_URI'] ?? '/public/index.php');
        $_SESSION['redirect_apos_login'] = $volta;
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
}

// ── ADMIN ─────────────────────────────────────────────────────

/**
 * Verifica se o admin está logado.
 */
function admin_logado(): bool {
    return isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] > 0;
}

/**
 * Redireciona para login do admin se não estiver autenticado.
 * Use em todas as páginas do painel.
 */
function exigir_login_admin(): void {
    if (!admin_logado()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

// ── SEGURANÇA ─────────────────────────────────────────────────

/**
 * Verifica token CSRF.
 * Gera um token na sessão se não existir e retorna ele.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF enviado pelo formulário.
 * Chame no topo de todo POST.
 */
function csrf_valido(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Encerra com erro 403 se o CSRF for inválido.
 */
function csrf_verificar(): void {
    if (!csrf_valido()) {
        http_response_code(403);
        die('Requisição inválida. Recarregue a página e tente novamente.');
    }
}