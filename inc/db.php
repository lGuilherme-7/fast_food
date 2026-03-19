<?php
/**
 * inc/db.php — Conexão com o banco de dados MySQL via PDO
 *
 * Responsabilidade:
 *   - Criar e retornar uma instância única de PDO (singleton simples)
 *   - Configurar charset, modo de erro e fetch padrão
 *   - Expor a variável $pdo para todos os arquivos que incluem este módulo
 *
 * Uso:
 *   require_once __DIR__ . '/../inc/db.php';
 *   // $pdo já está disponível
 *
 * Dependência:
 *   inc/config.php  → deve definir as constantes:
 *     DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
 */

// Garante que as constantes de configuração estão carregadas
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * getPDO() — Singleton de conexão PDO
 *
 * Cria a conexão na primeira chamada e reutiliza nas seguintes.
 * Lança PDOException em caso de falha (capturada abaixo).
 *
 * @return PDO
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $opcoes = [
        // Lança exceções em erros SQL — nunca silencia falhas
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        // Retorna linhas como arrays associativos por padrão
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Desativa emulação de prepared statements:
        // o MySQL recebe queries parametrizadas reais (mais seguro)
        PDO::ATTR_EMULATE_PREPARES   => false,

        // Mantém tipos nativos do MySQL (int vira int, não string)
        PDO::ATTR_STRINGIFY_FETCHES  => false,

        // Timeout de conexão em segundos
        PDO::ATTR_TIMEOUT            => 5,

        // Inicializa com UTF-8 e timezone do servidor
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci,
                                         time_zone = '-03:00'",
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);

    return $pdo;
}

// ── Instancia $pdo globalmente ────────────────────────────────────────────────
// Todos os arquivos que fazem require_once deste módulo terão $pdo disponível
// sem precisar chamar getPDO() manualmente.
try {

    $pdo = getPDO();

} catch (PDOException $e) {

    // Em produção: registra o erro em log e exibe mensagem amigável
    // Em desenvolvimento: exibe detalhes (controlado por APP_DEBUG em config.php)

    error_log('[SnackZone] Falha na conexão com o banco: ' . $e->getMessage());

    if (defined('APP_DEBUG') && APP_DEBUG === true) {
        // Modo debug — mostra detalhes técnicos
        http_response_code(500);
        echo '<div style="font-family:monospace;background:#1a1109;color:#ff4f18;'
           . 'padding:2rem;border-radius:8px;max-width:700px;margin:2rem auto;">';
        echo '<strong>⚠ Erro de conexão com o banco de dados</strong><br/><br/>';
        echo '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '<br/>';
        echo '<strong>Código:</strong> '   . $e->getCode() . '<br/>';
        echo '<strong>Arquivo:</strong> '  . $e->getFile() . ':' . $e->getLine();
        echo '</div>';
    } else {
        // Modo produção — mensagem genérica
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"/>'
           . '<title>Erro — SnackZone</title>'
           . '<style>body{font-family:sans-serif;display:flex;align-items:center;'
           . 'justify-content:center;min-height:100vh;margin:0;background:#faf9f7;}'
           . '.box{text-align:center;padding:3rem;}'
           . 'h1{color:#ff4f18;font-size:1.5rem;}'
           . 'p{color:#7a6f65;}'
           . 'a{color:#ff4f18;}'
           . '</style></head><body>'
           . '<div class="box">'
           . '<h1>⚠ Serviço temporariamente indisponível</h1>'
           . '<p>Estamos com instabilidade. Tente novamente em alguns instantes.</p>'
           . '<a href="/">Voltar ao início</a>'
           . '</div></body></html>';
    }

    exit;
}