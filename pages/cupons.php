<?php
// admin/pages/cupons.php
require_once '../includes/auth.php';

$mensagem = '';
$erro     = '';

// ============================================
// DADOS SIMULADOS — substituir por banco depois
// ============================================
$cupons = [
    ['id'=>1, 'codigo'=>'BEMVINDO10', 'tipo'=>'percentual', 'valor'=>10,  'minimo'=>0,   'usos'=>34,  'limite'=>100, 'validade'=>'30/06/2026', 'ativo'=>true,  'descricao'=>'Desconto de boas-vindas'],
    ['id'=>2, 'codigo'=>'ACAI5OFF',   'tipo'=>'fixo',       'valor'=>5,   'minimo'=>20,  'usos'=>12,  'limite'=>50,  'validade'=>'31/05/2026', 'ativo'=>true,  'descricao'=>'R$5 off em pedidos de açaí'],
    ['id'=>3, 'codigo'=>'FIDELIDADE', 'tipo'=>'percentual', 'valor'=>15,  'minimo'=>40,  'usos'=>8,   'limite'=>30,  'validade'=>'15/04/2026', 'ativo'=>true,  'descricao'=>'Cupom para clientes fiéis'],
    ['id'=>4, 'codigo'=>'BURGER20',   'tipo'=>'percentual', 'valor'=>20,  'minimo'=>0,   'usos'=>50,  'limite'=>50,  'validade'=>'28/03/2026', 'ativo'=>false, 'descricao'=>'Promoção de hambúrguer — expirado'],
    ['id'=>5, 'codigo'=>'FRETEFREE',  'tipo'=>'fixo',       'valor'=>8,   'minimo'=>50,  'usos'=>3,   'limite'=>20,  'validade'=>'31/12/2026', 'ativo'=>true,  'descricao'=>'Desconto equivalente à taxa de entrega'],
    ['id'=>6, 'codigo'=>'ANIVER30',   'tipo'=>'percentual', 'valor'=>30,  'minimo'=>0,   'usos'=>1,   'limite'=>1,   'validade'=>'21/03/2026', 'ativo'=>true,  'descricao'=>'Cupom de aniversário exclusivo'],
];

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $cid  = (int)($_POST['cupom_id'] ?? 0);

    if ($acao === 'novo' || $acao === 'editar') {
        $codigo  = strtoupper(trim($_POST['codigo']    ?? ''));
        $tipo    = $_POST['tipo']    ?? 'percentual';
        $valor   = (float)str_replace(',','.',$_POST['valor']   ?? 0);
        $minimo  = (float)str_replace(',','.',$_POST['minimo']  ?? 0);
        $limite  = (int)($_POST['limite']  ?? 0);
        $valid   = trim($_POST['validade'] ?? '');
        $desc    = trim($_POST['descricao']?? '');
        $ativo   = isset($_POST['ativo']);

        if (empty($codigo) || $valor <= 0) {
            $erro = 'Código e valor são obrigatórios.';
        } elseif ($tipo === 'percentual' && $valor > 100) {
            $erro = 'Percentual não pode ser maior que 100%.';
        } else {
            if ($acao === 'novo') {
                // TODO: INSERT INTO cupons ...
                $mensagem = 'Cupom "' . $codigo . '" criado com sucesso!';
            } else {
                // TODO: UPDATE cupons SET ... WHERE id = ?
                $mensagem = 'Cupom "' . $codigo . '" atualizado.';
                foreach ($cupons as &$c) {
                    if ($c['id'] === $cid) {
                        $c['codigo']=$codigo; $c['tipo']=$tipo; $c['valor']=$valor;
                        $c['minimo']=$minimo; $c['limite']=$limite;
                        $c['validade']=$valid; $c['descricao']=$desc; $c['ativo']=$ativo;
                        break;
                    }
                }
                unset($c);
            }
        }
    }

    if ($acao === 'toggle') {
        foreach ($cupons as &$c) {
            if ($c['id'] === $cid) {
                $c['ativo'] = !$c['ativo'];
                $mensagem   = 'Cupom ' . ($c['ativo'] ? 'ativado' : 'desativado') . '.';
                break;
            }
        }
        unset($c);
    }

    if ($acao === 'excluir') {
        $cupons   = array_values(array_filter($cupons, fn($c) => $c['id'] !== $cid));
        $mensagem = 'Cupom removido.';
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_busca  = trim($_GET['busca'] ?? '');

$lista = array_filter($cupons, function($c) use ($filtro_status, $filtro_busca) {
    $ok_s = $filtro_status === 'todos' ||
            ($filtro_status === 'ativo'   &&  $c['ativo']) ||
            ($filtro_status === 'inativo' && !$c['ativo']);
    $ok_b = empty($filtro_busca) || stripos($c['codigo'], $filtro_busca) !== false || stripos($c['descricao'], $filtro_busca) !== false;
    return $ok_s && $ok_b;
});

// Edição
$editar_id = (int)($_GET['editar'] ?? 0);
$editar    = null;
foreach ($cupons as $c) { if ($c['id'] === $editar_id) { $editar = $c; break; } }
$abrir_modal = !empty($_GET['novo']) || $editar !== null;

// Stats
$total_usos   = array_sum(array_column($cupons, 'usos'));
$total_ativos = count(array_filter($cupons, fn($c) => $c['ativo']));
$esgotados    = count(array_filter($cupons, fn($c) => $c['limite'] > 0 && $c['usos'] >= $c['limite']));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupons — Sabor&Cia Admin</title>
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
            <a href="dashboard.php" class="nav-item"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
            <a href="pedidos.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="11" y2="16"/></svg>Pedidos</a>
            <a href="vendas.php"    class="nav-item"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Vendas</a>
            <div class="nav-label">Catálogo</div>
            <a href="produtos.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>Produtos</a>
            <a href="categorias.php"class="nav-item"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"/></svg>Categorias</a>
            <a href="estoque.php"   class="nav-item"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Estoque</a>
            <div class="nav-label">Clientes</div>
            <a href="clientes.php"  class="nav-item"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Clientes</a>
            <a href="cupons.php"    class="nav-item ativo"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Cupons</a>
            <div class="nav-label">Sistema</div>
            <a href="configuracoes.php" class="nav-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M12 2v2M4.93 4.93l1.41 1.41M2 12h2M4.93 19.07l1.41-1.41M12 20v2M19.07 19.07l-1.41-1.41M22 12h-2"/></svg>Configurações</a>
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
                    <div class="topbar-titulo">Cupons</div>
                    <div class="topbar-breadcrumb"><?= count($cupons) ?> cupons cadastrados</div>
                </div>
            </div>
            <div class="topbar-dir">
                <a href="../../index.php" target="_blank" style="font-size:.82rem;color:var(--cinza);display:flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Ver site
                </a>
                <a href="cupons.php?novo=1" class="btn-novo">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo cupom
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

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></div>
                    <div class="stat-val"><?= count($cupons) ?></div>
                    <div class="stat-lbl">Total de cupons</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div class="stat-val"><?= $total_ativos ?></div>
                    <div class="stat-lbl">Cupons ativos</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                    <div class="stat-val"><?= $total_usos ?></div>
                    <div class="stat-lbl">Usos totais</div>
                </div>
                <div class="stat">
                    <div class="stat-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                    <div class="stat-val"><?= $esgotados ?></div>
                    <div class="stat-lbl">Esgotados</div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="toolbar-esq">
                    <?php foreach (['todos'=>'Todos', 'ativo'=>'Ativos', 'inativo'=>'Desativados'] as $s => $l): ?>
                    <a href="cupons.php?status=<?= $s ?><?= $filtro_busca ? '&busca='.urlencode($filtro_busca) : '' ?>"
                       class="tab <?= $filtro_status === $s ? 'ativo' : '' ?>"><?= $l ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="toolbar-dir">
                    <form method="GET" action="cupons.php" class="busca-wrap">
                        <input type="hidden" name="status" value="<?= $filtro_status ?>">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="busca" class="busca-input" placeholder="Buscar cupom..."
                            value="<?= htmlspecialchars($filtro_busca) ?>" autocomplete="off">
                    </form>
                </div>
            </div>

            <!-- GRID DE CUPONS -->
            <?php if (empty($lista)): ?>
            <div class="lista-vazia">
                <svg width="36" height="36" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Nenhum cupom encontrado.
            </div>
            <?php else: ?>
            <div class="cupons-grid">
                <?php foreach ($lista as $c):
                    $esgotado = $c['limite'] > 0 && $c['usos'] >= $c['limite'];
                    $perc_uso = $c['limite'] > 0 ? min(100, ($c['usos'] / $c['limite']) * 100) : 0;
                ?>
                <div class="cupom-card <?= !$c['ativo'] ? 'inativo' : '' ?>">
                    <div class="cupom-topo">
                        <div class="cupom-codigo">
                            <?= htmlspecialchars($c['codigo']) ?>
                        </div>
                        <div class="cupom-valor">
                            <?= $c['tipo'] === 'percentual' ? $c['valor'] . '% OFF' : 'R$ ' . number_format($c['valor'], 2, ',', '.') . ' OFF' ?>
                        </div>
                        <button class="cupom-btn-copy" onclick="copiarCodigo('<?= htmlspecialchars($c['codigo']) ?>')" title="Copiar código">
                            <svg viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>

                    <div class="cupom-body">
                        <div class="cupom-desc"><?= htmlspecialchars($c['descricao']) ?></div>

                        <div class="cupom-meta">
                            <div class="cupom-meta-item">
                                <div class="cupom-meta-lbl">Tipo</div>
                                <span class="badge <?= $c['tipo']==='percentual' ? 'badge-percent' : 'badge-fixo' ?>">
                                    <?= $c['tipo'] === 'percentual' ? 'Percentual' : 'Fixo (R$)' ?>
                                </span>
                            </div>
                            <div class="cupom-meta-item">
                                <div class="cupom-meta-lbl">Status</div>
                                <?php if ($esgotado): ?>
                                <span class="badge badge-esgotado">Esgotado</span>
                                <?php elseif ($c['ativo']): ?>
                                <span class="badge badge-ativo">Ativo</span>
                                <?php else: ?>
                                <span class="badge badge-inativo">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <div class="cupom-meta-item">
                                <div class="cupom-meta-lbl">Mín. pedido</div>
                                <div class="cupom-meta-val"><?= $c['minimo'] > 0 ? 'R$ '.number_format($c['minimo'],2,',','.') : 'Sem mínimo' ?></div>
                            </div>
                            <div class="cupom-meta-item">
                                <div class="cupom-meta-lbl">Validade</div>
                                <div class="cupom-meta-val"><?= htmlspecialchars($c['validade']) ?></div>
                            </div>
                        </div>

                        <?php if ($c['limite'] > 0): ?>
                        <div class="uso-wrap">
                            <div class="uso-header">
                                <span>Usos: <?= $c['usos'] ?> / <?= $c['limite'] ?></span>
                                <span><?= round($perc_uso) ?>%</span>
                            </div>
                            <div class="uso-barra">
                                <div class="uso-fill <?= $esgotado ? 'esgotado' : '' ?>" style="width:<?= $perc_uso ?>%"></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="font-size:.75rem;color:var(--cinza);margin-bottom:12px;"><?= $c['usos'] ?> uso<?= $c['usos']!==1?'s':'' ?> — sem limite</div>
                        <?php endif; ?>

                        <!-- Toggle ativo -->
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:.78rem;color:var(--cinza)">Ativo no site</span>
                            <form method="POST">
                                <input type="hidden" name="acao"     value="toggle">
                                <input type="hidden" name="cupom_id" value="<?= $c['id'] ?>">
                                <div class="toggle-wrap">
                                    <input type="checkbox" class="toggle-inp" id="tog<?= $c['id'] ?>"
                                        <?= $c['ativo'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <label class="toggle-label" for="tog<?= $c['id'] ?>"></label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="cupom-acoes">
                        <a href="cupons.php?editar=<?= $c['id'] ?>&status=<?= $filtro_status ?>" class="btn-ac">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar
                        </a>
                        <button class="btn-ac danger" onclick="confirmarExclusao(<?= $c['id'] ?>,'<?= addslashes(htmlspecialchars($c['codigo'])) ?>')">
                            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                            Excluir
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- MODAL NOVO / EDITAR -->
<?php if ($abrir_modal): ?>
<div class="modal-overlay">
    <div class="modal">
        <div class="modal-head">
            <h2><?= $editar ? 'Editar cupom' : 'Novo cupom' ?></h2>
            <button class="modal-close" onclick="fecharModal()">&#x2715;</button>
        </div>
        <form method="POST" action="cupons.php?status=<?= $filtro_status ?>">
            <input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'novo' ?>">
            <?php if ($editar): ?>
            <input type="hidden" name="cupom_id" value="<?= $editar['id'] ?>">
            <?php endif; ?>
            <div class="modal-body">

                <div class="campo-full">
                    <div class="campo">
                        <label>Código do cupom *</label>
                        <input type="text" name="codigo" placeholder="Ex: BEMVINDO10"
                            value="<?= htmlspecialchars($editar['codigo'] ?? '') ?>"
                            oninput="this.value=this.value.toUpperCase()" required>
                        <span class="campo-hint">Somente letras maiúsculas e números, sem espaços.</span>
                    </div>
                </div>

                <div class="campo-grid-2">
                    <div class="campo">
                        <label>Tipo de desconto *</label>
                        <select name="tipo" id="f-tipo" onchange="atualizarLabel()">
                            <option value="percentual" <?= ($editar['tipo']??'')!=='fixo'?'selected':'' ?>>Percentual (%)</option>
                            <option value="fixo"       <?= ($editar['tipo']??'')==='fixo' ?'selected':'' ?>>Valor fixo (R$)</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label id="valorLabel">Valor do desconto *</label>
                        <input type="text" name="valor" id="f-valor" placeholder="0"
                            value="<?= $editar ? number_format($editar['valor'],2,',','.') : '' ?>" required>
                    </div>
                </div>

                <div class="campo-grid-2">
                    <div class="campo">
                        <label>Pedido mínimo (R$)</label>
                        <input type="text" name="minimo" placeholder="0,00"
                            value="<?= $editar ? number_format($editar['minimo'],2,',','.') : '0,00' ?>">
                        <span class="campo-hint">0 = sem valor mínimo</span>
                    </div>
                    <div class="campo">
                        <label>Limite de usos</label>
                        <input type="number" name="limite" min="0" placeholder="0"
                            value="<?= $editar['limite'] ?? 0 ?>">
                        <span class="campo-hint">0 = sem limite</span>
                    </div>
                </div>

                <div class="campo-full">
                    <div class="campo">
                        <label>Validade</label>
                        <input type="text" name="validade" placeholder="DD/MM/AAAA"
                            value="<?= htmlspecialchars($editar['validade'] ?? '') ?>">
                    </div>
                </div>

                <div class="campo-full">
                    <div class="campo">
                        <label>Descrição interna</label>
                        <input type="text" name="descricao" placeholder="Para que serve este cupom?"
                            value="<?= htmlspecialchars($editar['descricao'] ?? '') ?>">
                    </div>
                </div>

                <div class="campo-check">
                    <input type="checkbox" id="f-ativo" name="ativo" <?= ($editar['ativo'] ?? true) ? 'checked' : '' ?>>
                    <label for="f-ativo">Cupom ativo (disponível para uso no site)</label>
                </div>

            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-cinza" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn btn-rosa">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $editar ? 'Salvar alterações' : 'Criar cupom' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- CONFIRM EXCLUIR -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none">
    <div class="confirm-box">
        <div class="confirm-icon"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg></div>
        <h3>Excluir cupom?</h3>
        <p id="confirmTxt">Esta ação não pode ser desfeita.</p>
        <div class="confirm-btns">
            <form method="POST" id="formExcluir" action="cupons.php?status=<?= $filtro_status ?>">
                <input type="hidden" name="acao"     value="excluir">
                <input type="hidden" name="cupom_id" id="excluirId" value="">
                <button type="submit" class="btn btn-danger">Excluir</button>
            </form>
            <button class="btn btn-cinza" onclick="document.getElementById('confirmOverlay').style.display='none'">Cancelar</button>
        </div>
    </div>
</div>

<div id="toastCopy" style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(60px);background:var(--escuro);color:#fff;padding:10px 20px;border-radius:50px;font-size:.85rem;font-weight:500;z-index:2000;opacity:0;transition:all .3s;white-space:nowrap;pointer-events:none;">
    Código copiado!
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
    function fecharModal() {
        var url = new URL(window.location.href);
        url.searchParams.delete('novo');
        url.searchParams.delete('editar');
        window.location.href = url.toString();
    }
    function confirmarExclusao(id, codigo) {
        document.getElementById('excluirId').value = id;
        document.getElementById('confirmTxt').textContent = 'O cupom "' + codigo + '" será removido permanentemente.';
        document.getElementById('confirmOverlay').style.display = 'flex';
    }
    function atualizarLabel() {
        var tipo = document.getElementById('f-tipo').value;
        document.getElementById('valorLabel').textContent = tipo === 'percentual' ? 'Percentual (%) *' : 'Valor (R$) *';
    }
    function copiarCodigo(codigo) {
        navigator.clipboard.writeText(codigo).then(function() {
            var toast = document.getElementById('toastCopy');
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(60px)';
            }, 2000);
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
            document.getElementById('confirmOverlay').style.display = 'none';
        }
    });
</script>
</body>
</html>