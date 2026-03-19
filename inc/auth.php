<?php
/**
 * inc/auth.php — Autenticação de usuários
 *
 * Responsabilidade:
 *   - Iniciar e gerenciar sessão
 *   - Login (verificar credenciais, criar sessão)
 *   - Logout (destruir sessão)
 *   - Verificar se usuário está logado
 *   - Proteger páginas que exigem login
 *   - Verificar token CSRF
 *
 * Todas as funções usam prepared statements (PDO).
 * Senhas verificadas com password_verify() — nunca em texto puro.
 */

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JS não acessa o cookie
        'cookie_secure'   => false,  // true em produção com HTTPS
        'cookie_samesite' => 'Lax',  // proteção CSRF básica via cookie
        'use_strict_mode' => true,   // evita session fixation
    ]);
}

// ──────────────────────────────────────────────────────────────────────────────
// processarLogin()
//
// Busca o usuário pelo e-mail, verifica senha com password_verify(),
// regenera o ID da sessão (segurança) e persiste os dados na sessão.
//
// @param PDO    $pdo
// @param string $email   — e-mail digitado pelo usuário
// @param string $senha   — senha em texto puro (será comparada ao hash)
//
// @return true|string    — true em sucesso; string com mensagem de erro
// ──────────────────────────────────────────────────────────────────────────────
function processarLogin(PDO $pdo, string $email, string $senha): bool|string
{
    // Sanitiza e-mail
    $email = strtolower(trim($email));

    // Busca usuário ativo pelo e-mail
    $stmt = $pdo->prepare("
        SELECT id, nome, email, senha, ativo
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usuário não encontrado — mensagem genérica (não revela se e-mail existe)
    if (!$usuario) {
        return 'E-mail ou senha incorretos.';
    }

    // Conta desativada
    if ((int) $usuario['ativo'] !== 1) {
        return 'Sua conta está desativada. Entre em contato com o suporte.';
    }

    // Verifica senha contra o hash bcrypt armazenado
    if (!password_verify($senha, $usuario['senha'])) {
        return 'E-mail ou senha incorretos.';
    }

    // ── Login bem-sucedido ────────────────────────────────────────────────────

    // Regenera ID de sessão para prevenir session fixation
    session_regenerate_id(true);

    // Grava dados mínimos na sessão (nunca gravar a senha)
    $_SESSION['usuario_id']   = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email']= $usuario['email'];
    $_SESSION['logado_em']    = time();

    // Atualiza timestamp do último login no banco
    $upd = $pdo->prepare("
        UPDATE usuarios
        SET ultimo_login = NOW()
        WHERE id = :id
    ");
    $upd->execute([':id' => $usuario['id']]);

    // Rehash automático: se o algoritmo/custo mudou, atualiza o hash
    if (password_needs_rehash($usuario['senha'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $novoHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $rh = $pdo->prepare("UPDATE usuarios SET senha = :s WHERE id = :id");
        $rh->execute([':s' => $novoHash, ':id' => $usuario['id']]);
    }

    return true;
}

// ──────────────────────────────────────────────────────────────────────────────
// processarCadastro()
//
// Valida dados, cria hash bcrypt e insere novo usuário no banco.
//
// @param PDO   $pdo
// @param array $dados  — ['nome', 'email', 'senha', 'telefone' (opcional)]
//
// @return true|string  — true em sucesso; string com mensagem de erro
// ──────────────────────────────────────────────────────────────────────────────
function processarCadastro(PDO $pdo, array $dados): bool|string
{
    $nome     = trim($dados['nome']     ?? '');
    $email    = strtolower(trim($dados['email']    ?? ''));
    $senha    = $dados['senha']    ?? '';
    $telefone = trim($dados['telefone'] ?? '');

    // Validações básicas
    if (strlen($nome) < 2) {
        return 'Nome deve ter pelo menos 2 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'E-mail inválido.';
    }
    if (strlen($senha) < 6) {
        return 'A senha deve ter pelo menos 6 caracteres.';
    }

    // Verifica se e-mail já existe
    $chk = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
    $chk->execute([':email' => $email]);
    if ($chk->fetch()) {
        return 'Este e-mail já está cadastrado. Faça login ou recupere sua senha.';
    }

    // Hash bcrypt com custo 12
    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insere usuário
    $ins = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, telefone, ativo, created_at)
        VALUES (:nome, :email, :senha, :telefone, 1, NOW())
    ");
    $ins->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':senha'    => $hash,
        ':telefone' => $telefone ?: null,
    ]);

    return true;
}

// ──────────────────────────────────────────────────────────────────────────────
// usuarioLogado()
//
// Verifica se há uma sessão ativa válida.
//
// @return array|null — dados do usuário da sessão, ou null se não logado
// ──────────────────────────────────────────────────────────────────────────────
function usuarioLogado(): ?array
{
    if (
        isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'], $_SESSION['usuario_email'])
        && (int) $_SESSION['usuario_id'] > 0
    ) {
        return [
            'id'    => (int) $_SESSION['usuario_id'],
            'nome'  => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
        ];
    }
    return null;
}

// ──────────────────────────────────────────────────────────────────────────────
// exigirLogin()
//
// Redireciona para login.php se o usuário não estiver autenticado.
// Útil no topo de páginas protegidas (carrinho, checkout, pedidos).
//
// @param string $next — URL de retorno após login (padrão: página atual)
// ──────────────────────────────────────────────────────────────────────────────
function exigirLogin(string $next = ''): void
{
    if (!usuarioLogado()) {
        $destino = $next ?: basename($_SERVER['PHP_SELF'])
                          . (!empty($_SERVER['QUERY_STRING'])
                             ? '?' . $_SERVER['QUERY_STRING']
                             : '');
        header('Location: login.php?next=' . urlencode($destino));
        exit;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// logout()
//
// Destrói a sessão de forma segura e redireciona para login.
// ──────────────────────────────────────────────────────────────────────────────
function logout(): void
{
    // Limpa todas as variáveis de sessão
    $_SESSION = [];

    // Remove o cookie de sessão do navegador
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destrói a sessão no servidor
    session_destroy();

    header('Location: login.php?logout=ok');
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// gerarCsrfToken()
//
// Cria (ou retorna existente) token CSRF na sessão.
//
// @return string
// ──────────────────────────────────────────────────────────────────────────────
function gerarCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ──────────────────────────────────────────────────────────────────────────────
// validarCsrfToken()
//
// Compara token enviado via POST com o token armazenado na sessão.
// Usa hash_equals() para evitar timing attacks.
//
// @param string $token — token vindo do campo hidden do formulário
//
// @return bool
// ──────────────────────────────────────────────────────────────────────────────
function validarCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ──────────────────────────────────────────────────────────────────────────────
// buscarUsuarioPorId()
//
// Retorna dados completos do usuário a partir do ID da sessão.
// Útil para páginas de perfil e checkout.
//
// @param PDO $pdo
// @param int $id
//
// @return array|null
// ──────────────────────────────────────────────────────────────────────────────
function buscarUsuarioPorId(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, nome, email, telefone, ativo, created_at, ultimo_login
        FROM usuarios
        WHERE id = :id AND ativo = 1
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ──────────────────────────────────────────────────────────────────────────────
// alterarSenha()
//
// Atualiza a senha do usuário após verificar a senha atual.
//
// @param PDO    $pdo
// @param int    $usuarioId
// @param string $senhaAtual   — senha atual em texto puro
// @param string $novaSenha    — nova senha em texto puro
//
// @return true|string
// ──────────────────────────────────────────────────────────────────────────────
function alterarSenha(PDO $pdo, int $usuarioId, string $senhaAtual, string $novaSenha): bool|string
{
    if (strlen($novaSenha) < 6) {
        return 'A nova senha deve ter pelo menos 6 caracteres.';
    }

    // Busca hash atual
    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
        return 'Senha atual incorreta.';
    }

    $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
    $upd = $pdo->prepare("UPDATE usuarios SET senha = :s, updated_at = NOW() WHERE id = :id");
    $upd->execute([':s' => $novoHash, ':id' => $usuarioId]);

    return true;
}