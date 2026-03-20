/**
 * main.js — Canto do Sabor
 * Módulos: Header · Cart · Filter · Wishlist · Reveal · Misc
 * @version 2.0
 */

'use strict';

/* ═══════════════════════════════════════════════════════════════════
   UTILS
   ═══════════════════════════════════════════════════════════════════ */
const qs  = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const fmt = (val) =>
    val.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Header — scroll + active link
   ═══════════════════════════════════════════════════════════════════ */
const HeaderModule = (() => {
    const header = qs('#cs-header');
    if (!header) return;

    // Classe de scroll
    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 16);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Active link por scroll
    const sections = qsa('section[id]');
    const navLinks = qsa('.navbar-nav .nav-link');

    const updateActive = () => {
        let current = sections[0]?.id || '';
        sections.forEach(sec => {
            if (window.scrollY + 90 >= sec.offsetTop) current = sec.id;
        });
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
        });
    };
    window.addEventListener('scroll', updateActive, { passive: true });

    // Fechar menu mobile ao clicar em link
    const collapse = qs('#csNav');
    const bsCollapse = collapse ? new bootstrap.Collapse(collapse, { toggle: false }) : null;
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992 && bsCollapse) bsCollapse.hide();
        });
    });
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Carrinho
   ═══════════════════════════════════════════════════════════════════ */
const CartModule = (() => {
    /* ── estado ── */
    let items = [];

    /* ── elementos ── */
    const badgeDesk = qs('#badge-desk');
    const badgeMob  = qs('#badge-mob');
    const listEl    = qs('#cart-items');
    const emptyEl   = qs('#cart-empty');
    const footEl    = qs('#cart-foot');
    const totalEl   = qs('#cart-total');
    const waLink    = qs('#cart-wa-link');
    const toastEl   = qs('#cs-toast');
    const toastMsg  = qs('#cs-toast-msg');
    const bsToast   = toastEl ? new bootstrap.Toast(toastEl, { delay: 2400 }) : null;

    /* ── badges ── */
    const refreshBadges = () => {
        const count = items.reduce((a, i) => a + i.qty, 0);
        [badgeDesk, badgeMob].forEach(el => {
            if (!el) return;
            el.textContent = count;
            el.classList.add('pop');
            setTimeout(() => el.classList.remove('pop'), 280);
        });
    };

    /* ── renderização ── */
    const render = () => {
        if (!listEl) return;

        if (items.length === 0) {
            listEl.innerHTML = '';
            emptyEl?.removeAttribute('hidden');
            footEl?.setAttribute('hidden', '');
            return;
        }

        emptyEl?.setAttribute('hidden', '');
        footEl?.removeAttribute('hidden');

        listEl.innerHTML = items.map(item => `
            <div class="cs-cart-item" role="listitem">
                <img class="cs-cart-item__img"
                     src="${item.img}"
                     alt="${item.nome}"
                     loading="lazy"
                     onerror="this.src='https://placehold.co/52x52/f6f1ec/b0a49a?text=?'">
                <div class="cs-cart-item__info">
                    <p class="cs-cart-item__name">${item.nome}</p>
                    <p class="cs-cart-item__price">${fmt(item.preco)} × ${item.qty}</p>
                </div>
                <button class="cs-cart-item__del"
                        aria-label="Remover ${item.nome}"
                        data-remove="${item.id}">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
            </div>
        `).join('');

        // Bind remover
        qsa('[data-remove]', listEl).forEach(btn => {
            btn.addEventListener('click', () => remove(Number(btn.dataset.remove)));
        });

        // Total
        const total = items.reduce((a, i) => a + i.preco * i.qty, 0);
        if (totalEl) totalEl.textContent = fmt(total);

        // Atualizar link WA com texto do pedido
        if (waLink) {
            const pedidoText = items
                .map(i => `${i.qty}x ${i.nome} (${fmt(i.preco)})`)
                .join(', ');
            waLink.href = `https://wa.me/5511999999999?text=${encodeURIComponent('Olá! Quero pedir: ' + pedidoText + '. Total: ' + fmt(total))}`;
        }
    };

    /* ── adicionar ── */
    const add = ({ id, nome, preco, img = '' }) => {
        const exists = items.find(i => i.id === id);
        exists
            ? exists.qty++
            : items.push({ id, nome, preco: Number(preco), img, qty: 1 });

        refreshBadges();
        render();
        save();
        notify(`🛒 ${nome} adicionado!`);
    };

    /* ── remover ── */
    const remove = (id) => {
        items = items.filter(i => i.id !== id);
        refreshBadges();
        render();
        save();
    };

    /* ── toast ── */
    const notify = (msg) => {
        if (!toastMsg || !bsToast) return;
        toastMsg.textContent = msg;
        bsToast.show();
    };

    /* ── persistência ── */
    const save    = () => sessionStorage.setItem('cs_cart', JSON.stringify(items));
    const restore = () => {
        try {
            const saved = sessionStorage.getItem('cs_cart');
            if (saved) { items = JSON.parse(saved); refreshBadges(); render(); }
        } catch (_) {}
    };

    /* ── delegação de eventos (botões "+" nos cards) ── */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-id][data-nome][data-preco]');
        if (!btn) return;

        add({
            id:    Number(btn.dataset.id),
            nome:  btn.dataset.nome,
            preco: Number(btn.dataset.preco),
            img:   btn.dataset.img || '',
        });

        // Feedback visual no botão
        btn.classList.add('added');
        setTimeout(() => btn.classList.remove('added'), 400);
    });

    restore();
    return { add, remove, items: () => [...items] };
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Filtro de produtos
   ═══════════════════════════════════════════════════════════════════ */
const FilterModule = (() => {
    const btns  = qsa('.cs-filter');
    const cards = qsa('.cs-product-card');
    if (!btns.length) return;

    const applyFilter = (target) => {
        cards.forEach(card => {
            const match = target === 'all' || card.dataset.cat === target;
            if (match) {
                card.classList.remove('is-hiding');
                card.style.display = '';
            } else {
                card.classList.add('is-hiding');
                setTimeout(() => {
                    if (card.classList.contains('is-hiding')) card.style.display = 'none';
                }, 220);
            }
        });
    };

    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilter(btn.dataset.filter);
        });
    });

    // Integrar com clique nas categorias
    qsa('.cs-cat-card').forEach(card => {
        card.addEventListener('click', () => {
            const cat = card.dataset.cat;
            qsa('.cs-cat-card').forEach(c => c.classList.remove('is-active'));
            card.classList.add('is-active');

            const matchBtn = qs(`.cs-filter[data-filter="${cat}"]`);
            if (matchBtn) {
                matchBtn.click();
                // Scroll suave para produtos
                const prodSec = qs('#cardapio');
                if (prodSec) {
                    const offset = (qs('#cs-header')?.offsetHeight || 72) + 16;
                    window.scrollTo({ top: prodSec.offsetTop - offset, behavior: 'smooth' });
                }
            }
        });
    });
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Wishlist (coração nos cards)
   ═══════════════════════════════════════════════════════════════════ */
const WishlistModule = (() => {
    let faved = new Set(JSON.parse(localStorage.getItem('cs_fav') || '[]'));

    const sync = () => {
        qsa('.cs-product-card__wish').forEach(btn => {
            const id = btn.closest('[data-id]')?.dataset?.id
                    || btn.closest('.cs-product-card')?.querySelector('[data-id]')?.dataset?.id;
            if (!id) return;
            btn.classList.toggle('is-faved', faved.has(id));
        });
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.cs-product-card__wish');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const card = btn.closest('.cs-product-card');
        const addBtn = card?.querySelector('[data-id]');
        const id = addBtn?.dataset?.id;
        if (!id) return;

        faved.has(id) ? faved.delete(id) : faved.add(id);
        localStorage.setItem('cs_fav', JSON.stringify([...faved]));
        sync();
        btn.style.transform = 'scale(1.4)';
        setTimeout(() => btn.style.transform = '', 200);
    });

    sync();
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Scroll Reveal
   ═══════════════════════════════════════════════════════════════════ */
const RevealModule = (() => {
    const targets = qsa('[data-reveal]');
    if (!targets.length) return;

    if (!('IntersectionObserver' in window)) {
        targets.forEach(el => el.classList.add('is-visible'));
        return;
    }

    const obs = new IntersectionObserver((entries) => {
        entries.forEach(({ isIntersecting, target }) => {
            if (!isIntersecting) return;
            const delay = parseInt(target.dataset.revealDelay || target.style.getPropertyValue('--delay') || '0');
            setTimeout(() => target.classList.add('is-visible'), delay);
            obs.unobserve(target);
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(el => obs.observe(el));
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Hero — animação de entrada e parallax sutil
   ═══════════════════════════════════════════════════════════════════ */
const HeroModule = (() => {
    const hero = qs('.cs-hero');
    const bg   = qs('#heroBg');
    if (!hero) return;

    // Marca hero como loaded para transição da imagem
    window.addEventListener('load', () => hero.classList.add('loaded'), { once: true });

    // Parallax muito sutil no scroll (performance: só GPU transform)
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && bg) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > window.innerHeight) return; // para fora da viewport
            bg.style.transform = `translateY(${window.scrollY * 0.25}px)`;
        }, { passive: true });
    }
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Smooth Scroll para âncoras
   ═══════════════════════════════════════════════════════════════════ */
const SmoothScrollModule = (() => {
    document.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href^="#"]');
        if (!anchor) return;
        const id = anchor.getAttribute('href').slice(1);
        if (!id) return;
        const target = document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        const offset = (qs('#cs-header')?.offsetHeight || 72) + 8;
        window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
    });
})();


/* ═══════════════════════════════════════════════════════════════════
   MÓDULO: Fallback de imagens
   ═══════════════════════════════════════════════════════════════════ */
(() => {
    document.addEventListener('error', (e) => {
        if (e.target.tagName !== 'IMG') return;
        const alt = encodeURIComponent(e.target.alt || 'Produto');
        e.target.src = `https://placehold.co/400x300/f6f1ec/b0a49a?text=${alt}`;
        e.target.removeAttribute('onerror');
    }, true);
})();