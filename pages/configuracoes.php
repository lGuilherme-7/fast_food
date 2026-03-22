<?php
// admin/pages/configuracoes.php
require_once '../includes/auth.php';
require_once '../../config/db.php';

$mensagem = '';
$erro     = '';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$config = [
    // Loja
    'loja_nome'        => 'Sabor & Cia',
    'loja_descricao'   => 'Açaí, hambúrgueres artesanais, doces e bebidas geladas.',
    'loja_cnpj'        => '12.345.678/0001-99',
    'loja_email'       => 'contato@saborecia.com',
    'loja_telefone'    => '(81) 98702-8550',
    'loja_whatsapp'    => '5581987028550',
    'loja_endereco'    => 'Rua das Flores, 123',
    'loja_bairro'      => 'Centro',
    'loja_cidade'      => 'Recife',
    'loja_estado'      => 'PE',
    'loja_cep'         => '50010-000',

    // Funcionamento
    'func_seg'  => true,  'func_seg_abre'  => '11:00', 'func_seg_fecha'  => '23:00',
    'func_ter'  => true,  'func_ter_abre'  => '11:00', 'func_ter_fecha'  => '23:00',
    'func_qua'  => true,  'func_qua_abre'  => '11:00', 'func_qua_fecha'  => '23:00',
    'func_qui'  => true,  'func_qui_abre'  => '11:00', 'func_qui_fecha'  => '23:00',
    'func_sex'  => true,  'func_sex_abre'  => '11:00', 'func_sex_fecha'  => '23:00',
    'func_sab'  => true,  'func_sab_abre'  => '12:00', 'func_sab_fecha'  => '00:00',
    'func_dom'  => false, 'func_dom_abre'  => '12:00', 'func_dom_fecha'  => '22:00',

    // Entrega
    'entrega_ativa'    => true,
    'entrega_taxa'     => 5.00,
    'entrega_gratis'   => 50.00,
    'entrega_tempo'    => 40,
    'entrega_raio'     => 5,
    'retirada_ativa'   => true,
    'retirada_tempo'   => 15,

    // Pagamentos
    'pag_dinheiro' => true,
    'pag_cartao'   => true,
    'pag_pix'      => true,
    'pix_chave'    => '5581987028550',
    'pix_tipo'     => 'telefone',
    'pix_nome'     => 'Sabor e Cia LTDA',

    // Notificações
    'notif_pedido'     => true,
    'notif_email'      => true,
    'notif_wpp'        => false,
    'notif_email_dest' => 'admin@saborecia.com',

    // Admin
    'admin_nome'   => 'Administrador',
    'admin_email'  => 'admin@saborecia.com',
];

// Seção ativa
$secao = $_GET['secao'] ?? 'loja';
$secoes = [
    'loja'          => ['label'=>'Loja',         'icon'=>'store'],
    'funcionamento' => ['label'=>'Funcionamento', 'icon'=>'clock'],
    'entrega'       => ['label'=>'Entrega',       'icon'=>'truck'],
    'pagamentos'    => ['label'=>'Pagamentos',    'icon'=>'credit'],
    'notificacoes'  => ['label'=>'Notificações',  'icon'=>'bell'],
    'conta'         => ['label'=>'Minha conta',   'icon'=>'user'],
];

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    // TODO: UPDATE configuracoes SET ... para cada campo recebido
    $mensagem = 'Configurações salvas com sucesso!';
    // Simular atualização dos dados
    foreach ($_POST as $k => $v) {
        if (array_key_exists($k, $config)) {
            $config[$k] = is_array($v) ? $v : trim($v);
        }
    }
    // Checkboxes (não vêm no POST quando desmarcados)
    $bool_keys = ['func_seg','func_ter','func_qua','func_qui','func_sex','func_sab','func_dom',
                  'entrega_ativa','retirada_ativa','pag_dinheiro','pag_cartao','pag_pix',
                  'notif_pedido','notif_email','notif_wpp'];
    foreach ($bool_keys as $k) {
        $config[$k] = isset($_POST[$k]);
    }
}

$dias = [
    'seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta',
    'qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'
];
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
    
</head>
<body>
<div class="admin-wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="../index.php">Sabor<span>&</span>Cia</a>
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
                <div class="user-avatar">AD</div>
                <div class="user-info"><div class="user-nome">Administrador</div><div class="user-role">admin</div></div>
            </div>
            <a href="../logout.php"><button class="btn-logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair do painel</button></a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-esq">
                <button class="btn-menu" id="btnMenu"><svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
                <div>
                    <div class="topbar-titulo">Configurações</div>
                    <div class="topbar-breadcrumb"><?= $secoes[$secao]['label'] ?></div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
            </div>
        </div>

        <div class="conteudo">

            <?php if ($mensagem): ?>
            <div class="alerta alerta-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="config-layout">

                <!-- NAV LATERAL DE SEÇÕES -->
                <nav class="config-nav">
                    <div class="config-nav-titulo">Seções</div>

                    <a href="?secao=loja" class="config-nav-item <?= $secao==='loja' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Dados da loja
                    </a>
                    <a href="?secao=funcionamento" class="config-nav-item <?= $secao==='funcionamento' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Funcionamento
                    </a>
                    <a href="?secao=entrega" class="config-nav-item <?= $secao==='entrega' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Entrega e retirada
                    </a>
                    <a href="?secao=pagamentos" class="config-nav-item <?= $secao==='pagamentos' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Pagamentos
                    </a>
                    <a href="?secao=notificacoes" class="config-nav-item <?= $secao==='notificacoes' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Notificações
                    </a>
                    <a href="?secao=conta" class="config-nav-item <?= $secao==='conta' ?'ativo':'' ?>">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Minha conta
                    </a>
                </nav>

                <!-- CONTEÚDO DA SEÇÃO -->
                <form method="POST" action="configuracoes.php?secao=<?= $secao ?>" class="config-conteudo">
                    <input type="hidden" name="salvar" value="1">

                    <?php if ($secao === 'loja'): ?>
                    <!-- ================================
                         DADOS DA LOJA
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                            <div class="sec-head-texto"><h2>Identidade da loja</h2><p>Nome, descrição e dados gerais</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>Nome da loja</label><input type="text" name="loja_nome" value="<?= htmlspecialchars($config['loja_nome']) ?>"></div>
                                <div class="campo"><label>CNPJ</label><input type="text" name="loja_cnpj" value="<?= htmlspecialchars($config['loja_cnpj']) ?>"></div>
                            </div>
                            <div class="campo-full">
                                <div class="campo"><label>Descrição curta</label><textarea name="loja_descricao"><?= htmlspecialchars($config['loja_descricao']) ?></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.6 3.44 2 2 0 0 1 3.57 1.25h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.85a16 16 0 0 0 6.05 6.05l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                            <div class="sec-head-texto"><h2>Contato</h2><p>Email, telefone e WhatsApp</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>E-mail de contato</label><input type="email" name="loja_email" value="<?= htmlspecialchars($config['loja_email']) ?>"></div>
                                <div class="campo"><label>Telefone</label><input type="text" name="loja_telefone" value="<?= htmlspecialchars($config['loja_telefone']) ?>"></div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Número WhatsApp</label>
                                    <input type="text" name="loja_whatsapp" value="<?= htmlspecialchars($config['loja_whatsapp']) ?>">
                                    <span class="campo-hint">Somente números, com código do país. Ex: 5581987028550</span>
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
                                <div class="campo"><label>Rua / Avenida</label><input type="text" name="loja_endereco" value="<?= htmlspecialchars($config['loja_endereco']) ?>"></div>
                                <div class="campo"><label>Bairro</label><input type="text" name="loja_bairro" value="<?= htmlspecialchars($config['loja_bairro']) ?>"></div>
                            </div>
                            <div class="campos-grid-3">
                                <div class="campo"><label>Cidade</label><input type="text" name="loja_cidade" value="<?= htmlspecialchars($config['loja_cidade']) ?>"></div>
                                <div class="campo"><label>Estado</label><input type="text" name="loja_estado" value="<?= htmlspecialchars($config['loja_estado']) ?>" maxlength="2"></div>
                                <div class="campo"><label>CEP</label><input type="text" name="loja_cep" value="<?= htmlspecialchars($config['loja_cep']) ?>"></div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($secao === 'funcionamento'): ?>
                    <!-- ================================
                         FUNCIONAMENTO
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                            <div class="sec-head-texto"><h2>Horários de funcionamento</h2><p>Configure os dias e horários de atendimento</p></div>
                        </div>
                        <div class="sec-body">
                            <?php foreach ($dias as $sigla => $nome): ?>
                            <div class="dia-row" id="row_<?= $sigla ?>">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="toggle-wrap">
                                        <input type="checkbox" class="toggle-inp" id="func_<?= $sigla ?>" name="func_<?= $sigla ?>"
                                            <?= $config['func_'.$sigla] ? 'checked' : '' ?>
                                            onchange="toggleDia('<?= $sigla ?>')">
                                        <label class="toggle-label" for="func_<?= $sigla ?>"></label>
                                    </div>
                                    <span class="dia-nome"><?= $nome ?></span>
                                </div>
                                <div class="dia-horas <?= !$config['func_'.$sigla] ? 'fechado' : '' ?>" id="horas_<?= $sigla ?>">
                                    <input type="time" name="func_<?= $sigla ?>_abre"  value="<?= $config['func_'.$sigla.'_abre']  ?>">
                                    <span class="dia-sep">até</span>
                                    <input type="time" name="func_<?= $sigla ?>_fecha" value="<?= $config['func_'.$sigla.'_fecha'] ?>">
                                </div>
                                <div style="font-size:.78rem;color:<?= $config['func_'.$sigla] ? '#16a34a' : 'var(--cinza)' ?>">
                                    <?= $config['func_'.$sigla] ? 'Aberto' : 'Fechado' ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php elseif ($secao === 'entrega'): ?>
                    <!-- ================================
                         ENTREGA
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                            <div class="sec-head-texto"><h2>Entrega em domicílio</h2><p>Taxa, tempo estimado e raio de cobertura</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Entrega ativa</div>
                                    <div class="t-sub">Permitir pedidos com entrega em domicílio</div>
                                </div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="entrega_ativa" name="entrega_ativa" <?= $config['entrega_ativa'] ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="entrega_ativa"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Taxa de entrega (R$)</label>
                                    <input type="text" name="entrega_taxa" value="<?= number_format($config['entrega_taxa'],2,',','.') ?>">
                                    <span class="campo-hint">Use 0 para entrega sempre grátis</span>
                                </div>
                                <div class="campo">
                                    <label>Grátis acima de (R$)</label>
                                    <input type="text" name="entrega_gratis" value="<?= number_format($config['entrega_gratis'],2,',','.') ?>">
                                    <span class="campo-hint">0 = nunca gratuita automaticamente</span>
                                </div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Tempo estimado (min)</label>
                                    <input type="number" name="entrega_tempo" min="1" value="<?= $config['entrega_tempo'] ?>">
                                </div>
                                <div class="campo">
                                    <label>Raio de entrega (km)</label>
                                    <input type="number" name="entrega_raio" min="1" value="<?= $config['entrega_raio'] ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                            <div class="sec-head-texto"><h2>Retirada no local</h2><p>Permite que o cliente retire o pedido na loja</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Retirada ativa</div>
                                    <div class="t-sub">Permitir que clientes retirem pedidos na loja</div>
                                </div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="retirada_ativa" name="retirada_ativa" <?= $config['retirada_ativa'] ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="retirada_ativa"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Tempo estimado para retirada (min)</label>
                                    <input type="number" name="retirada_tempo" min="1" value="<?= $config['retirada_tempo'] ?>">
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($secao === 'pagamentos'): ?>
                    <!-- ================================
                         PAGAMENTOS
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                            <div class="sec-head-texto"><h2>Formas de pagamento</h2><p>Selecione os métodos aceitos na entrega</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="pagto-grid">
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_dinheiro" <?= $config['pag_dinheiro'] ? 'checked' : '' ?>>
                                    <svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                    <div class="pagto-card-info"><div class="p-titulo">Dinheiro</div><div class="p-sub">Pagamento na entrega</div></div>
                                </label>
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_cartao" <?= $config['pag_cartao'] ? 'checked' : '' ?>>
                                    <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    <div class="pagto-card-info"><div class="p-titulo">Cartão</div><div class="p-sub">Débito ou crédito</div></div>
                                </label>
                                <label class="pagto-card">
                                    <input type="checkbox" name="pag_pix" <?= $config['pag_pix'] ? 'checked' : '' ?>>
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
                                        <option value="telefone" <?= $config['pix_tipo']==='telefone'?'selected':'' ?>>Telefone</option>
                                        <option value="cpf"      <?= $config['pix_tipo']==='cpf'     ?'selected':'' ?>>CPF</option>
                                        <option value="cnpj"     <?= $config['pix_tipo']==='cnpj'    ?'selected':'' ?>>CNPJ</option>
                                        <option value="email"    <?= $config['pix_tipo']==='email'   ?'selected':'' ?>>E-mail</option>
                                        <option value="aleatoria"<?= $config['pix_tipo']==='aleatoria'?'selected':'' ?>>Chave aleatória</option>
                                    </select>
                                </div>
                                <div class="campo">
                                    <label>Chave Pix</label>
                                    <input type="text" name="pix_chave" value="<?= htmlspecialchars($config['pix_chave']) ?>">
                                </div>
                            </div>
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>Nome do beneficiário</label>
                                    <input type="text" name="pix_nome" value="<?= htmlspecialchars($config['pix_nome']) ?>">
                                    <span class="campo-hint">Nome que aparecerá para o cliente no app de pagamento</span>
                                </div>
                                <div class="campo"></div>
                            </div>
                            <div class="info-box">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                O QR Code do Pix é gerado automaticamente no checkout com base nessas informações.
                            </div>
                        </div>
                    </div>

                    <?php elseif ($secao === 'notificacoes'): ?>
                    <!-- ================================
                         NOTIFICAÇÕES
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                            <div class="sec-head-texto"><h2>Alertas de pedidos</h2><p>Quando e como você quer ser notificado</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Notificar novos pedidos</div>
                                    <div class="t-sub">Receber alerta sempre que um novo pedido chegar</div>
                                </div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_pedido" name="notif_pedido" <?= $config['notif_pedido'] ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_pedido"></label>
                                </div>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Notificação por e-mail</div>
                                    <div class="t-sub">Enviar e-mail para cada novo pedido</div>
                                </div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_email" name="notif_email" <?= $config['notif_email'] ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_email"></label>
                                </div>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Resumo diário por WhatsApp</div>
                                    <div class="t-sub">Receber um resumo dos pedidos do dia às 23h</div>
                                </div>
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="notif_wpp" name="notif_wpp" <?= $config['notif_wpp'] ? 'checked' : '' ?>>
                                    <label class="toggle-label" for="notif_wpp"></label>
                                </div>
                            </div>
                            <hr class="campo-sep">
                            <div class="campos-grid-2">
                                <div class="campo">
                                    <label>E-mail para notificações</label>
                                    <input type="email" name="notif_email_dest" value="<?= htmlspecialchars($config['notif_email_dest']) ?>">
                                </div>
                                <div class="campo"></div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($secao === 'conta'): ?>
                    <!-- ================================
                         MINHA CONTA
                    ================================ -->
                    <div class="sec-card">
                        <div class="sec-head">
                            <div class="sec-head-icone"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                            <div class="sec-head-texto"><h2>Dados do administrador</h2><p>Informações da sua conta de acesso</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="campos-grid-2">
                                <div class="campo"><label>Nome</label><input type="text" name="admin_nome" value="<?= htmlspecialchars($config['admin_nome']) ?>"></div>
                                <div class="campo"><label>E-mail de acesso</label><input type="email" name="admin_email" value="<?= htmlspecialchars($config['admin_email']) ?>"></div>
                            </div>
                        </div>
                    </div>

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
                                Deixe em branco para manter a senha atual. Mínimo de 8 caracteres.
                            </div>
                        </div>
                    </div>

                    <div class="sec-card" style="border-color:#fca5a5">
                        <div class="sec-head" style="background:#fff5f5">
                            <div class="sec-head-icone" style="background:#fff;border:1px solid #fca5a5"><svg viewBox="0 0 24 24" style="stroke:#dc2626"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                            <div class="sec-head-texto"><h2 style="color:#dc2626">Zona de perigo</h2><p style="color:#dc2626;opacity:.7">Ações irreversíveis</p></div>
                        </div>
                        <div class="sec-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Encerrar todas as sessões</div>
                                    <div class="t-sub">Desconecta todos os dispositivos logados no painel</div>
                                </div>
                                <button type="button" class="btn btn-cinza" style="flex-shrink:0">Encerrar sessões</button>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <div class="t-titulo">Limpar cache do sistema</div>
                                    <div class="t-sub">Remove dados temporários e força recarregamento</div>
                                </div>
                                <button type="button" class="btn btn-cinza" style="flex-shrink:0">Limpar cache</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- BARRA SALVAR -->
                    <div class="save-bar">
                        <p>As alterações serão aplicadas imediatamente ao site.</p>
                        <div style="display:flex;gap:10px">
                            <a href="?secao=<?= $secao ?>"><button type="button" class="btn btn-cinza">Descartar</button></a>
                            <button type="submit" class="btn btn-rosa">
                                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                Salvar configurações
                            </button>
                        </div>
                    </div>

                </form>
            </div><!-- /config-layout -->
        </div><!-- /conteudo -->
    </div><!-- /main -->
</div><!-- /admin-wrap -->

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

    // Toggle dia (habilitar/desabilitar campos de horário)
    function toggleDia(sigla) {
        var cb     = document.getElementById('func_' + sigla);
        var horas  = document.getElementById('horas_' + sigla);
        var status = cb.closest('.dia-row').querySelector('div:last-child');
        if (cb.checked) {
            horas.classList.remove('fechado');
            status.textContent  = 'Aberto';
            status.style.color  = '#16a34a';
        } else {
            horas.classList.add('fechado');
            status.textContent  = 'Fechado';
            status.style.color  = 'var(--cinza)';
        }
    }

    // Mostrar/ocultar senha
    function toggleSenha(id) {
        var inp = document.getElementById(id);
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }
</script>
</body>
</html>