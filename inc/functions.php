<?php
// ============================================================
// inc/functions.php — Funções utilitárias gerais
// Chamar com: require_once __DIR__ . '/../inc/functions.php';
// ============================================================

/**
 * Sanitiza string para exibição segura no HTML.
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata valor em reais. Ex: 19.90 → "R$ 19,90"
 */
function moeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata data do banco (Y-m-d ou Y-m-d H:i:s) para pt-BR.
 * Ex: "2026-03-21" → "21/03/2026"
 */
function data_br(string $data, bool $hora = false): string {
    if (empty($data) || $data === '0000-00-00') return '—';
    $fmt = $hora ? 'd/m/Y H:i' : 'd/m/Y';
    return date($fmt, strtotime($data));
}

/**
 * Gera slug a partir de um texto.
 * Ex: "Açaí Premium" → "acai-premium"
 */
function slugify(string $texto): string {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = trim(preg_replace('/[\s-]+/', '-', $texto), '-');
    return $texto;
}

/**
 * Redireciona para uma URL e encerra o script.
 */
function redirecionar(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Salva mensagem de feedback na sessão para exibir na próxima página.
 * Tipo: 'ok', 'erro', 'aviso'
 */
function flash(string $mensagem, string $tipo = 'ok'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['msg' => $mensagem, 'tipo' => $tipo];
}

/**
 * Lê e limpa a mensagem flash da sessão.
 * Retorna null se não houver mensagem.
 */
function flash_ler(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/**
 * Exibe o HTML do alerta flash, se houver.
 */
function flash_html(): void {
    $f = flash_ler();
    if (!$f) return;
    $classes = [
        'ok'    => 'background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d',
        'erro'  => 'background:#fff0f4;border:1px solid #f0e8ed;color:#be185d',
        'aviso' => 'background:#fefce8;border:1px solid #fde68a;color:#854d0e',
    ];
    $estilo = $classes[$f['tipo']] ?? $classes['ok'];
    echo '<div style="' . $estilo . ';padding:12px 16px;border-radius:12px;font-size:.85rem;font-weight:500;margin-bottom:20px;">'
       . h($f['msg'])
       . '</div>';
}

/**
 * Valida e-mail.
 */
function email_valido(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida telefone brasileiro (aceita formatos com ou sem máscara).
 * Retorna apenas dígitos ou false.
 */
function telefone_limpo(string $tel): string|false {
    $numeros = preg_replace('/\D/', '', $tel);
    // Aceita 10 ou 11 dígitos (com ou sem 9 no celular)
    if (strlen($numeros) < 10 || strlen($numeros) > 11) return false;
    return $numeros;
}

/**
 * Trunca texto em N caracteres adicionando "..." se necessário.
 */
function truncar(string $texto, int $max = 80): string {
    if (mb_strlen($texto) <= $max) return $texto;
    return mb_substr($texto, 0, $max) . '...';
}

/**
 * Retorna o IP real do visitante.
 */
function ip_real(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

/**
 * Verifica se a requisição é AJAX.
 */
function is_ajax(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

/**
 * Responde JSON e encerra. Usar em endpoints AJAX.
 */
function json_resposta(array $dados, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Gera URL completa a partir de um caminho relativo.
 * Ex: url('/public/login.php') → 'http://localhost/fast_food/public/login.php'
 */
function url(string $caminho = ''): string {
    return BASE_URL . '/' . ltrim($caminho, '/');
}

/**
 * Verifica se a URL atual contém um segmento.
 * Útil para marcar item ativo no menu.
 */
function url_ativa(string $segmento): bool {
    return strpos($_SERVER['REQUEST_URI'] ?? '', $segmento) !== false;
}