<?php
// admin/logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guardar nome antes de destruir
$nome = $_SESSION['admin_nome'] ?? 'Administrador';

// Destruir sessão completamente
session_unset();
session_destroy();

// Limpar cookie de sessão
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $p['path'], $p['domain'],
        $p['secure'], $p['httponly']
    );
}

// Redirecionar imediatamente? Descomente:
// header('Location: login.php'); exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="3;url=login.php">
    <title>Saindo — Sabor&Cia Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rosa:       #f43f7a;
            --rosa-claro: #fce7f0;
            --escuro:     #1a1014;
            --cinza:      #9ca3af;
            --branco:     #ffffff;
            --bg:         #fafafa;
            --borda:      #e5e7eb;
            --r:          12px;
            --f-titulo:   Georgia, 'Times New Roman', serif;
            --f-corpo:    'DM Sans', system-ui, sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--f-corpo);
            background: var(--bg);
            color: var(--escuro);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: var(--branco);
            border: 1px solid var(--borda);
            border-radius: var(--r);
            padding: 48px 44px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }

        .logo {
            font-family: var(--f-titulo);
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--escuro);
            margin-bottom: 28px;
        }
        .logo span { color: var(--rosa); }

        .icone {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--rosa-claro);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .icone svg { width: 28px; height: 28px; stroke: var(--rosa); fill: none; stroke-width: 2; }

        h1 {
            font-family: var(--f-titulo);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--escuro);
            margin-bottom: 8px;
        }
        p {
            font-size: .88rem;
            color: var(--cinza);
            line-height: 1.6;
            margin-bottom: 28px;
        }
        p strong { color: var(--escuro); }

        /* Barra de progresso */
        .progresso-wrap {
            background: var(--bg);
            border: 1px solid var(--borda);
            border-radius: 50px;
            height: 5px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .progresso-bar {
            height: 100%;
            background: var(--rosa);
            border-radius: 50px;
            animation: progresso 3s linear forwards;
        }
        @keyframes progresso { from { width: 0% } to { width: 100% } }

        /* Botões */
        .btns { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            border-radius: 8px;
            font-family: var(--f-corpo);
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn:hover { opacity: .88; }
        .btn-rosa  { background: var(--rosa); color: #fff; }
        .btn-cinza { background: var(--bg); border: 1px solid var(--borda); color: var(--cinza); }
        .btn svg   { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }
    </style>
</head>
<body>

    <div class="card">

        <div class="logo">Sabor<span>&</span>Cia</div>

        <div class="icone">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>

        <h1>Até logo, <?= htmlspecialchars(explode(' ', $nome)[0]) ?>!</h1>
        <p>Sua sessão foi encerrada com segurança.<br>Redirecionando para o <strong>login</strong> em 3 segundos...</p>

        <div class="progresso-wrap">
            <div class="progresso-bar"></div>
        </div>

        <div class="btns">
            <a href="login.php" class="btn btn-rosa">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Entrar novamente
            </a>
            <a href="../index.php" class="btn btn-cinza">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Ir ao site
            </a>
        </div>

    </div>

</body>
</html>