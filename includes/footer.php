<?php
// includes/footer.php
// Fecha o layout do painel admin e adiciona scripts globais
// Uso: require_once __DIR__ . '/../../includes/footer.php';
?>
    </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- Overlay mobile (fecha sidebar ao clicar fora) -->
<div id="overlayMobile"
     onclick="fecharMenu()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199;"></div>

<script>
(function() {
    // Abre/fecha sidebar no mobile
    var btnMenu = document.getElementById('btnMenu');
    if (btnMenu) {
        btnMenu.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('aberta');
            document.getElementById('overlayMobile').style.display =
                document.getElementById('sidebar').classList.contains('aberta') ? 'block' : 'none';
        });
    }

    function fecharMenu() {
        var sb = document.getElementById('sidebar');
        if (sb) sb.classList.remove('aberta');
        document.getElementById('overlayMobile').style.display = 'none';
    }

    // Fechar modal com ESC (se existir na página)
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        // Modal genérico
        var overlay = document.querySelector('.modal-overlay');
        if (overlay) {
            var url = new URL(window.location.href);
            ['ver','editar','novo','mov','tipo'].forEach(function(p){ url.searchParams.delete(p); });
            // Só redireciona se havia parâmetros de modal
            if (window.location.search !== url.search) {
                window.location.href = url.toString();
            }
        }
        // Confirm overlay
        var confirm = document.getElementById('confirmOverlay');
        if (confirm) confirm.style.display = 'none';

        fecharMenu();
    });

    // Expõe fecharMenu globalmente para o onclick do overlay
    window.fecharMenu = fecharMenu;
})();
</script>
</body>
</html>