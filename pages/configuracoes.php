<?php
// admin/pages/configuracoes.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$mensagem = '';
$erro     = '';

// ── CARREGA TODAS AS CONFIGURAÇÕES DO BANCO ──────────────────
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
$cfg  = [];
foreach ($stmt->fetchAll() as $row) {
    $cfg[$row['chave']] = $row['valor'];
}

function cfg(array $cfg, string $chave, string $padrao = ''): string {
    return $cfg[$chave] ?? $padrao;
}
function cfgBool(array $cfg, string $chave, bool $padrao = false): bool {
    if (!isset($cfg[$chave])) return $padrao;
    return in_array($cfg[$chave], ['1', 'true', 'on', 'yes'], true);
}

// ── SEÇÃO ATIVA ───────────────────────────────────────────────
$secao = in_array($_GET['secao'] ?? '', ['loja','funcionamento','entrega','pagamentos','notificacoes','conta'])
    ? $_GET['secao'] : 'loja';

// ── SALVAR CONFIGURAÇÕES ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {

    // IMPORTANTE: local_ativo adicionado aqui
    $bool_keys = [
        'func_seg','func_ter','func_qua','func_qui','func_sex','func_sab','func_dom',
        'entrega_ativa','retirada_ativa','local_ativo',
        'pag_dinheiro','pag_cartao','pag_pix',
        'notif_pedido','notif_email','notif_wpp',
    ];

    $campos_texto = [
        'loja_nome','loja_descricao','loja_cnpj','loja_email',
        'loja_telefone','loja_whatsapp','loja_endereco','loja_bairro',
        'loja_cidade','loja_estado','loja_cep',
        'func_seg_abre','func_seg_fecha','func_ter_abre','func_ter_fecha',
        'func_qua_abre','func_qua_fecha','func_qui_abre','func_qui_fecha',
        'func_sex_abre','func_sex_fecha','func_sab_abre','func_sab_fecha',
        'func_dom_abre','func_dom_fecha',
        'entrega_taxa','entrega_gratis','entrega_tempo','entrega_raio','retirada_tempo',
        'pix_chave','pix_tipo','pix_nome',
        'notif_email_dest',
    ];

    $salvar = [];
    foreach ($campos_texto as $chave) {
        if (isset($_POST[$chave])) {
            $salvar[$chave] = trim($_POST[$chave]);
        }
    }

    // Booleanos presentes no formulário atual: salva 1 ou 0
    // Booleanos AUSENTES do formulário atual (outra seção): NÃO toca, preserva valor do banco
    // Para isso, filtra apenas os bool_keys que realmente foram "enviáveis" neste formulário
    $bool_por_secao = [
        'loja'          => [],
        'funcionamento' => ['func_seg','func_ter','func_qua','func_qui','func_sex','func_sab','func_dom'],
        'entrega'       => ['entrega_ativa','retirada_ativa','local_ativo'],
        'pagamentos'    => ['pag_dinheiro','pag_cartao','pag_pix'],
        'notificacoes'  => ['notif_pedido','notif_email','notif_wpp'],
        'conta'         => [],
    ];

    foreach (($bool_por_secao[$secao] ?? []) as $chave) {
        $salvar[$chave] = isset($_POST[$chave]) ? '1' : '0';
    }

    $stmt = $pdo->prepare("
        INSERT INTO configuracoes (chave, valor)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
    ");

    $pdo->beginTransaction();
    try {
        foreach ($salvar as $chave => $valor) {
            $stmt->execute([$chave, $valor]);
        }
        $pdo->commit();
        $mensagem = 'Configurações salvas com sucesso!';

        $stmt2 = $pdo->query("SELECT chave, valor FROM configuracoes");
        $cfg   = [];
        foreach ($stmt2->fetchAll() as $row) $cfg[$row['chave']] = $row['valor'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro = 'Erro ao salvar. Tente novamente.';
    }
}

// ── ALTERAR SENHA ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova  = $_POST['senha_nova']  ?? '';
    $senha_conf  = $_POST['senha_conf']  ?? '';
    $admin_id    = $_SESSION['admin_id'] ?? 0;

    if (empty($senha_atual) || empty($senha_nova)) {
        $erro = 'Preencha a senha atual e a nova senha.';
    } elseif ($senha_nova !== $senha_conf) {
        $erro = 'A nova senha e a confirmação não coincidem.';
    } elseif (strlen($senha_nova) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } else {
        $stmt = $pdo->prepare("SELECT senha_hash FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $hash_atual = $stmt->fetchColumn();
        if (!$hash_atual || !password_verify($senha_atual, $hash_atual)) {
            $erro = 'Senha atual incorreta.';
        } else {
            $pdo->prepare("UPDATE admins SET senha_hash = ? WHERE id = ?")
                ->execute([password_hash($senha_nova, PASSWORD_BCRYPT), $admin_id]);
            $mensagem = 'Senha alterada com sucesso!';
        }
    }
}

// ── ATUALIZAR CONTA ───────────────────────────────────────────
$admin_nome  = $_SESSION['admin_nome']  ?? 'Administrador';
$admin_email = $_SESSION['admin_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_conta'])) {
    $novo_nome  = trim($_POST['admin_nome']  ?? '');
    $novo_email = trim($_POST['admin_email'] ?? '');
    $admin_id   = $_SESSION['admin_id'] ?? 0;
    if (empty($novo_nome) || empty($novo_email)) {
        $erro = 'Nome e e-mail são obrigatórios.';
    } else {
        $pdo->prepare("UPDATE admins SET nome = ?, email = ? WHERE id = ?")->execute([$novo_nome, $novo_email, $admin_id]);
        $_SESSION['admin_nome']  = $novo_nome;
        $_SESSION['admin_email'] = $novo_email;
        $admin_nome  = $novo_nome;
        $admin_email = $novo_email;
        $mensagem = 'Dados da conta atualizados!';
    }
}

$dias = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        .config-layout { display:grid; grid-template-columns:200px 1fr; gap:24px; align-items:start; }
        .config-nav { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); padding:12px; position:sticky; top:76px; }
        .config-nav-titulo { font-size:.68rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--cinza); padding:4px 8px 10px; }
        .config-nav-item { display:flex; align-items:center; gap:9px; padding:9px 12px; border-radius:8px; font-size:.83rem; font-weight:500; color:var(--cinza); transition:background .15s, color .15s; text-decoration:none; margin-bottom:2px; }
        .config-nav-item:hover { background:var(--rosa-claro); color:var(--rosa); }
        .config-nav-item.ativo { background:var(--rosa); color:#fff; }
        .config-nav-item svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }
        .sec-card { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); overflow:hidden; margin-bottom:16px; }
        .sec-head  { display:flex; align-items:center; gap:14px; padding:16px 20px; background:var(--bg); border-bottom:1px solid var(--borda); }
        .sec-head-icone { width:36px; height:36px; border-radius:9px; background:var(--rosa-claro); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .sec-head-icone svg { width:16px; height:16px; stroke:var(--rosa); fill:none; stroke-width:2; }
        .sec-head-texto h2 { font-family:var(--f-titulo); font-size:.95rem; font-weight:700; }
        .sec-head-texto p  { font-size:.76rem; color:var(--cinza); margin-top:2px; }
        .sec-body  { padding:20px; }
        .campos-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
        .campos-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px; }
        .campo-full { margin-bottom:14px; }
        .campo-sep  { border:none; border-top:1px solid var(--borda); margin:16px 0; }
        .campo-hint { font-size:.74rem; color:var(--cinza); margin-top:3px; display:block; }
        .info-box { display:flex; align-items:flex-start; gap:8px; background:var(--rosa-claro); border:1px solid var(--rosa-borda); border-radius:8px; padding:10px 12px; margin-top:10px; font-size:.8rem; color:var(--rosa); font-weight:500; }
        .info-box svg { width:14px; height:14px; stroke:var(--rosa); fill:none; stroke-width:2; flex-shrink:0; margin-top:1px; }
        .dia-row { display:flex; align-items:center; gap:14px; padding:10px 0; border-bottom:1px solid var(--borda); flex-wrap:wrap; }
        .dia-row:last-child { border-bottom:none; }
        .dia-nome  { width:80px; font-size:.85rem; font-weight:600; flex-shrink:0; }
        .horas-wrap { display:flex; align-items:center; gap:8px; flex:1; }
        .horas-wrap input[type="time"] { padding:7px 10px; border-radius:8px; border:1px solid var(--borda); font-family:var(--f-corpo); font-size:.85rem; background:var(--branco); color:var(--escuro); }
        .horas-wrap.fechado input[type="time"] { opacity:.3; pointer-events:none; }
        .horas-sep { font-size:.8rem; color:var(--cinza); }
        .toggle-row { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid var(--borda); }
        .toggle-row:last-child { border-bottom:none; }
        .toggle-info { flex:1; min-width:0; }
        .t-titulo { font-size:.88rem; font-weight:600; }
        .t-sub    { font-size:.76rem; color:var(--cinza); margin-top:2px; }
        .pagto-grid { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .pagto-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border:1.5px solid var(--borda); border-radius:var(--r); cursor:pointer; min-width:140px; transition:all .2s; flex:1; }
        .pagto-card:has(input:checked) { border-color:var(--rosa); background:var(--rosa-claro); }
        .pagto-card input[type="checkbox"] { accent-color:var(--rosa); width:16px; height:16px; flex-shrink:0; }
        .pagto-card svg { width:18px; height:18px; stroke:var(--cinza); fill:none; stroke-width:1.8; flex-shrink:0; }
        .pagto-card:has(input:checked) svg { stroke:var(--rosa); }
        .p-titulo { font-size:.88rem; font-weight:600; }
        .p-sub    { font-size:.74rem; color:var(--cinza); }
        .senha-field { position:relative; }
        .senha-field input { padding-right:40px; width:100%; }
        .senha-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:4px; display:flex; align-items:center; }
        .senha-toggle svg { width:15px; height:15px; stroke:var(--cinza); fill:none; stroke-width:2; }
        .save-bar { background:var(--branco); border:1px solid var(--borda); border-radius:var(--r); padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; position:sticky; bottom:20px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-top:4px; }
        .save-bar p { font-size:.82rem; color:var(--cinza); }
        @media (max-width:860px) { .config-layout { grid-template-columns:1fr; } .config-nav { position:static; display:flex; gap:4px; flex-wrap:wrap; } .config-nav-titulo { display:none; } }
        @media (max-width:600px) { .campos-grid-2, .campos-grid-3 { grid-template-columns:1fr; } .pagto-grid { flex-direction:column; } }
    </style>
</head>
<body>
<div class="admin-wrap">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="../public/index.php">Sabor<span>&</span>Cia</a>
            <p>Painel administrativo</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Geral</div>
            <a href="dashboard.php"  class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
            <a href="pedidos.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"     class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php" class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"    class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"     class="nav-item"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item ativo"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($admin_nome, 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-nome"><?= htmlspecialchars($admin_nome) ?></div>
                    <div class="user-role">admin</div>
                </div>
            </div>
            <a href="../public/index.php"><button class="btn-logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair do painel</button></a>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu"><svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
                <div>
                    <div class="topbar-titulo">Configurações</div>
                    <div class="topbar-breadcrumb">Personalize a loja e o sistema</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../public/index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <div class="conteudo">
            <?php if ($mensagem): ?>
            <div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
            <div class="alerta alerta-err"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="config-layout">
                <nav class="config-nav">
                    <div class="config-nav-titulo">Seções</div>
                    <a href="?secao=loja"          class="config-nav-item <?= $secao==='loja'          ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Dados da loja</a>
                    <a href="?secao=funcionamento"  class="config-nav-item <?= $secao==='funcionamento' ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Funcionamento</a>
                    <a href="?secao=entrega"        class="config-nav-item <?= $secao==='entrega'        ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Entrega</a>
                    <a href="?secao=pagamentos"     class="config-nav-item <?= $secao==='pagamentos'     ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Pagamentos</a>
                    <a href="?secao=notificacoes"   class="config-nav-item <?= $secao==='notificacoes'   ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>Notificações</a>
                    <a href="?secao=conta"          class="config-nav-item <?= $secao==='conta'          ?'ativo':'' ?>"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Minha conta</a>
                </nav>

                <div class="config-conteudo">

                <?php if ($secao === 'loja'): ?>
                <form method="POST" action="configuracoes.php?secao=loja">
                    <input type="hidden" name="salvar" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                            <div class="sec-head-texto"><h2>Identidade da loja</h2><p>Nome, descrição e dados gerais</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>Nome da loja</label><input type="text" name="loja_nome" value="<?= htmlspecialchars(cfg($cfg,'loja_nome','Sabor & Cia')) ?>"></div>
                                <div class="campo"><label>CNPJ</label><input type="text" name="loja_cnpj" value="<?= htmlspecialchars(cfg($cfg,'loja_cnpj')) ?>"></div>
                            </div>
                            <div class="campo-full">
                                <div class="campo"><label>Descrição curta</label><textarea name="loja_descricao" rows="2"><?= htmlspecialchars(cfg($cfg,'loja_descricao')) ?></textarea></div>
                            </div>
                        </div>
                    </div>
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.6 3.44 2 2 0 0 1 3.57 1.25h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.85a16 16 0 0 0 6.05 6.05l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                            <div class="sec-head-texto"><h2>Contato</h2><p>E-mail, telefone e WhatsApp</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>E-mail de contato</label><input type="email" name="loja_email" value="<?= htmlspecialchars(cfg($cfg,'loja_email')) ?>"></div>
                                <div class="campo"><label>Telefone</label><input type="text" name="loja_telefone" value="<?= htmlspecialchars(cfg($cfg,'loja_telefone')) ?>"></div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>WhatsApp (recebimento de pedidos)</label>
                                    <input type="text" name="loja_whatsapp" value="<?= htmlspecialchars(cfg($cfg,'loja_whatsapp','5581987028550')) ?>">
                                    <span class="campo-hint">Só números com código do país. Ex: 5581987028550</span>
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                            <div class="sec-head-texto"><h2>Endereço</h2><p>Localização física da loja</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>Rua / Avenida</label><input type="text" name="loja_endereco" value="<?= htmlspecialchars(cfg($cfg,'loja_endereco')) ?>"></div>
                                <div class="campo"><label>Bairro</label><input type="text" name="loja_bairro" value="<?= htmlspecialchars(cfg($cfg,'loja_bairro')) ?>"></div>
                            </div>
                            <div class="campos-grid-3">
                                <div class="campo"><label>Cidade</label><input type="text" name="loja_cidade" value="<?= htmlspecialchars(cfg($cfg,'loja_cidade','Recife')) ?>"></div>
                                <div class="campo"><label>Estado</label><input type="text" name="loja_estado" value="<?= htmlspecialchars(cfg($cfg,'loja_estado','PE')) ?>" maxlength="2"></div>
                                <div class="campo"><label>CEP</label><input type="text" name="loja_cep" value="<?= htmlspecialchars(cfg($cfg,'loja_cep')) ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="save-bar">
                        <p>Mudanças aplicadas imediatamente ao site.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=loja"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar</button>
                        </div>
                    </div>
                </form>

                <?php elseif ($secao === 'funcionamento'): ?>
                <form method="POST" action="configuracoes.php?secao=funcionamento">
                    <input type="hidden" name="salvar" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                            <div class="sec-head-texto"><h2>Horários de atendimento</h2><p>Defina os dias e horários de funcionamento</p></div>
                        </div>
                        <div class="sec-body">
                            <?php foreach ($dias as $sigla => $nome):
                                $aberto = cfgBool($cfg, 'func_' . $sigla, $sigla !== 'dom');
                                $abre   = cfg($cfg, 'func_' . $sigla . '_abre',  in_array($sigla,['sab','dom']) ? '12:00' : '11:00');
                                $fecha  = cfg($cfg, 'func_' . $sigla . '_fecha', $sigla === 'dom' ? '22:00' : '23:00');
                            ?>
                            <div class="dia-row">
                                <div class="toggle-wrap" style="flex-shrink:0">
                                    <input type="checkbox" class="toggle-inp" id="func_<?= $sigla ?>"
                                        name="func_<?= $sigla ?>"
                                        <?= $aberto ? 'checked' : '' ?>
                                        onchange="toggleDia('<?= $sigla ?>')">
                                    <label class="toggle-label" for="func_<?= $sigla ?>"></label>
                                </div>
                                <div class="dia-nome"><?= $nome ?></div>
                                <div class="horas-wrap <?= !$aberto ? 'fechado' : '' ?>" id="horas_<?= $sigla ?>">
                                    <input type="time" name="func_<?= $sigla ?>_abre"  value="<?= htmlspecialchars($abre) ?>">
                                    <span class="horas-sep">até</span>
                                    <input type="time" name="func_<?= $sigla ?>_fecha" value="<?= htmlspecialchars($fecha) ?>">
                                </div>
                                <div style="font-size:.78rem;font-weight:600;<?= $aberto ? 'color:#16a34a' : 'color:var(--cinza)' ?>" id="label_<?= $sigla ?>">
                                    <?= $aberto ? 'Aberto' : 'Fechado' ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="save-bar">
                        <p>Mudanças aplicadas imediatamente ao site.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=funcionamento"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar</button>
                        </div>
                    </div>
                </form>

                <?php elseif ($secao === 'entrega'): ?>
                <form method="POST" action="configuracoes.php?secao=entrega">
                    <input type="hidden" name="salvar" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                            <div class="sec-head-texto"><h2>Delivery</h2><p>Taxa, prazo e raio de entrega</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Entrega ativa</div><div class="t-sub">Permite pedidos com entrega no site</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="entrega_ativa" name="entrega_ativa" <?= cfgBool($cfg,'entrega_ativa',true) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="entrega_ativa"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2" style="margin-top:14px">
                                <div class="campo">
                                    <label>Taxa de entrega (R$)</label>
                                    <input type="text" name="entrega_taxa" value="<?= number_format((float)cfg($cfg,'entrega_taxa','5.00'), 2, ',', '.') ?>">
                                    <span class="campo-hint">Use vírgula. Ex: 5,00</span>
                                </div>
                                <div class="campo">
                                    <label>Entrega grátis acima de (R$)</label>
                                    <input type="text" name="entrega_gratis" value="<?= number_format((float)cfg($cfg,'entrega_gratis','50.00'), 2, ',', '.') ?>">
                                    <span class="campo-hint">0 = entrega grátis sempre</span>
                                </div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Tempo estimado (min)</label>
                                    <input type="number" name="entrega_tempo" min="1" value="<?= (int)cfg($cfg,'entrega_tempo','40') ?>">
                                </div>
                                <div class="campo">
                                    <label>Raio de entrega (km)</label>
                                    <input type="number" name="entrega_raio" min="1" value="<?= (int)cfg($cfg,'entrega_raio','5') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                            <div class="sec-head-texto"><h2>Retirada no local</h2><p>Opção para o cliente retirar pessoalmente</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Retirada ativa</div><div class="t-sub">Permite pedidos com retirada no local</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="retirada_ativa" name="retirada_ativa" <?= cfgBool($cfg,'retirada_ativa',true) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="retirada_ativa"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2" style="margin-top:14px">
                                <div class="campo">
                                    <label>Tempo de preparo para retirada (min)</label>
                                    <input type="number" name="retirada_tempo" min="1" value="<?= (int)cfg($cfg,'retirada_tempo','15') ?>">
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CORREÇÃO: local_ativo agora existe no formulário -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M3 2h18v20H3z"/><rect x="7" y="6" width="3" height="3"/><rect x="14" y="6" width="3" height="3"/><rect x="7" y="12" width="3" height="3"/><rect x="14" y="12" width="3" height="3"/></svg></div>
                            <div class="sec-head-texto"><h2>Consumo no local</h2><p>Opção para o cliente comer no estabelecimento</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Comer aqui ativo</div><div class="t-sub">Permite pedidos para consumo no local (mesa)</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="local_ativo" name="local_ativo" <?= cfgBool($cfg,'local_ativo',true) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="local_ativo"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="save-bar">
                        <p>Mudanças aplicadas imediatamente ao checkout.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=entrega"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar</button>
                        </div>
                    </div>
                </form>

                <?php elseif ($secao === 'pagamentos'): ?>
                <form method="POST" action="configuracoes.php?secao=pagamentos">
                    <input type="hidden" name="salvar" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                            <div class="sec-head-texto"><h2>Formas de pagamento</h2><p>Métodos aceitos na entrega e retirada</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="pagto-grid">
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_dinheiro" <?= cfgBool($cfg,'pag_dinheiro',true) ? 'checked' : '' ?>>
                                    <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                    <div class="pagto-card-info"><div class="p-titulo">Dinheiro</div><div class="p-sub">Pagamento na entrega</div></div>
                                </label>
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_cartao" <?= cfgBool($cfg,'pag_cartao',true) ? 'checked' : '' ?>>
                                    <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    <div class="pagto-card-info"><div class="p-titulo">Cartão</div><div class="p-sub">Débito ou crédito</div></div>
                                </label>
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_pix" <?= cfgBool($cfg,'pag_pix',true) ? 'checked' : '' ?>>
                                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                    <div class="pagto-card-info"><div class="p-titulo">Pix</div><div class="p-sub">Pagamento imediato</div></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
                            <div class="sec-head-texto"><h2>Configuração do Pix</h2><p>Chave e dados para recebimento</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Tipo de chave</label>
                                    <select name="pix_tipo">
                                        <?php foreach (['telefone'=>'Telefone','cpf'=>'CPF','cnpj'=>'CNPJ','email'=>'E-mail','aleatoria'=>'Chave aleatória'] as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= cfg($cfg,'pix_tipo','telefone') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="campo">
                                    <label>Chave Pix</label>
                                    <input type="text" name="pix_chave" value="<?= htmlspecialchars(cfg($cfg,'pix_chave')) ?>">
                                </div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Nome do beneficiário</label>
                                    <input type="text" name="pix_nome" value="<?= htmlspecialchars(cfg($cfg,'pix_nome')) ?>">
                                    <span class="campo-hint">Nome que aparece no app do cliente</span>
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>
                    <div class="save-bar">
                        <p>Mudanças aplicadas imediatamente ao checkout.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=pagamentos"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar</button>
                        </div>
                    </div>
                </form>

                <?php elseif ($secao === 'notificacoes'): ?>
                <form method="POST" action="configuracoes.php?secao=notificacoes">
                    <input type="hidden" name="salvar" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                            <div class="sec-head-texto"><h2>Alertas de pedidos</h2><p>Quando e como ser notificado</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Notificar novos pedidos</div><div class="t-sub">Receber alerta sempre que um pedido chegar</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_pedido" name="notif_pedido" <?= cfgBool($cfg,'notif_pedido',true) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_pedido"></label>
                                </div>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Notificação por e-mail</div><div class="t-sub">Enviar e-mail para cada novo pedido</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_email" name="notif_email" <?= cfgBool($cfg,'notif_email',true) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_email"></label>
                                </div>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info"><div class="t-titulo">Resumo diário por WhatsApp</div><div class="t-sub">Receber resumo dos pedidos do dia às 23h</div></div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_wpp" name="notif_wpp" <?= cfgBool($cfg,'notif_wpp',false) ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_wpp"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>E-mail para notificações</label>
                                    <input type="email" name="notif_email_dest" value="<?= htmlspecialchars(cfg($cfg,'notif_email_dest')) ?>">
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>
                    <div class="save-bar">
                        <p>Mudanças aplicadas imediatamente.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=notificacoes"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar</button>
                        </div>
                    </div>
                </form>

                <?php elseif ($secao === 'conta'): ?>
                <form method="POST" action="configuracoes.php?secao=conta">
                    <input type="hidden" name="atualizar_conta" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                            <div class="sec-head-texto"><h2>Dados do administrador</h2><p>Informações da sua conta de acesso</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>Nome</label><input type="text" name="admin_nome" value="<?= htmlspecialchars($admin_nome) ?>" required></div>
                                <div class="campo"><label>E-mail de acesso</label><input type="email" name="admin_email" value="<?= htmlspecialchars($admin_email) ?>" required></div>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
                        <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Salvar dados</button>
                    </div>
                </form>
                <form method="POST" action="configuracoes.php?secao=conta">
                    <input type="hidden" name="alterar_senha" value="1">
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
                            <div class="sec-head-texto"><h2>Alterar senha</h2><p>Use uma senha forte com letras, números e símbolos</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Senha atual</label>
                                    <div class="senha-field">
                                        <input type="password" name="senha_atual" id="senhaAtual" placeholder="••••••••">
                                        <button type="button" class="senha-toggle" onclick="toggleSenha('senhaAtual')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                    </div>
                                </div>
                                <div class="campo"></div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Nova senha</label>
                                    <div class="senha-field">
                                        <input type="password" name="senha_nova" id="senhaNova" placeholder="••••••••">
                                        <button type="button" class="senha-toggle" onclick="toggleSenha('senhaNova')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                    </div>
                                </div>
                                <div class="campo">
                                    <label>Confirmar nova senha</label>
                                    <div class="senha-field">
                                        <input type="password" name="senha_conf" id="senhaConf" placeholder="••••••••">
                                        <button type="button" class="senha-toggle" onclick="toggleSenha('senhaConf')"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                    </div>
                                </div>
                            </div>
                            <div class="info-box">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Mínimo de 8 caracteres.
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
                        <button type="submit" class="btn btn-rosa"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Alterar senha</button>
                    </div>
                </form>
                <div class="sec-card" style="border-color:#fca5a5">
                    <div class="sec-head" style="background:#fff5f5">
                        <div class="sec-head-icone" style="background:#fff;border:1px solid #fca5a5"><svg viewBox="0 0 24 24" style="stroke:#dc2626"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                        <div class="sec-head-texto"><h2 style="color:#dc2626">Zona de perigo</h2><p style="color:#dc2626;opacity:.7">Ações irreversíveis</p></div>
                    </div>
                    <div class="sec-body">
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <div class="t-titulo">Encerrar todas as sessões</div>
                                <div class="t-sub">Desconecta todos os dispositivos logados</div>
                            </div>
                            <form method="POST" action="../logout.php">
                                <input type="hidden" name="encerrar_tudo" value="1">
                                <button type="submit" class="btn btn-cinza">Encerrar sessões</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<div id="overlayMobile" onclick="fecharMenu()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
    document.getElementById('btnMenu').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('aberta');
        document.getElementById('overlayMobile').style.display =
            document.getElementById('sidebar').classList.contains('aberta') ? 'block' : 'none';
    });
    function fecharMenu() {
        document.getElementById('sidebar').classList.remove('aberta');
        document.getElementById('overlayMobile').style.display = 'none';
    }
    function toggleDia(sigla) {
        var cb    = document.getElementById('func_' + sigla);
        var horas = document.getElementById('horas_' + sigla);
        var label = document.getElementById('label_' + sigla);
        if (cb.checked) {
            horas.classList.remove('fechado');
            label.textContent = 'Aberto';
            label.style.color = '#16a34a';
        } else {
            horas.classList.add('fechado');
            label.textContent = 'Fechado';
            label.style.color = 'var(--cinza)';
        }
    }
    function toggleSenha(id) {
        var inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }
</script>
</body>
</html>