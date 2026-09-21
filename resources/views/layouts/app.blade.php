<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Servicios')</title>

    <link rel="icon" type="image/png" href="{{ asset('logo_sapp_xs.png') }}?v={{ filemtime(public_path('logo_sapp_xs.png')) }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/tailwindcss">
        @theme {
            --color-clifford: #da373d;
        }
    </script>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE"></script>

    @stack('styles')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        [x-cloak] {
            display: none !important;
        }

        .nav-item-active {
            background-color: transparent;
            box-shadow: none;
        }

        .primary-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.9375rem;
            border-radius: 0.5rem;
            padding: 0.55rem 0.9rem;
            white-space: nowrap;
            min-height: 2.25rem;
            transition: background-color 0.2s ease;
        }

        .primary-nav-link i,
        .dropdown-menu a i,
        .mobile-nav-link i {
            width: 1.125rem;
            text-align: center;
            font-size: 0.875rem;
        }

        .primary-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }

        .primary-nav-link.compact {
            padding-left: 0.6rem;
            padding-right: 0.6rem;
        }

        .mobile-menu {
            transition: opacity 0.3s ease, transform 0.3s ease;
            max-height: calc(100vh - 80px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-section-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
            color: #fee2e2;
            background-color: rgba(0, 0, 0, 0.12);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .mobile-section-trigger:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }

        .mobile-section-trigger[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1rem;
            border-radius: 0.75rem;
            color: white;
            background-color: rgba(255, 255, 255, 0.02);
            transition: background-color 0.2s ease;
        }

        .mobile-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .mobile-nav-link-active {
            background-color: rgba(0, 0, 0, 0.35);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #dc2626;
            min-width: 220px;
            max-width: 320px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.18);
            z-index: 1000;
            border-radius: 0 0 0.75rem 0.75rem;
            top: 100%;
            left: 0;
            padding: 0.35rem 0;
            box-sizing: border-box;
        }

        .dropdown-menu.show {
            display: block;
        }

        /* Prevent dropdown from overflowing viewport on right side */
        [data-dropdown]:last-child .dropdown-menu,
        [data-dropdown]:nth-last-child(2) .dropdown-menu {
            left: auto;
            right: 0;
        }

        .dropdown-menu a,
        .dropdown-menu form > button {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            font-size: 0.9375rem;
            font-weight: 500;
            min-height: 2.25rem;
            transition: background-color 0.2s ease;
            white-space: normal;
            line-height: 1.3;
            width: 100%;
            box-sizing: border-box;
            word-break: break-word;
        }

        .dropdown-menu a:hover,
        .dropdown-menu a.bg-red-700 {
            background-color: #b91c1c;
        }

        /* El selector de workspace usa <button> dentro de <form>; su color lo
           manejan clases Tailwind. Solo se refuerza el hover neutro para que no
           tome el rojo de los enlaces de navegación. */
        .dropdown-menu form > button:hover {
            background-color: #f3f4f6;
        }

        /* Estilos mejorados para los logos */
        .logo-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .logo-large,
        .logo-small {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .logo-large {
            height: 3rem;
            width: auto;
        }

        .logo-small {
            height: 1.5rem;
            width: auto;
        }

        .logo-large:hover,
        .logo-small:hover {
            transform: scale(1.03) rotate(1deg);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2)) brightness(1.1);
        }

        .logo-large:active,
        .logo-small:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }

        /* Efecto de pulso sutil al cargar */
        @keyframes gentlePulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        .logo-pulse {
            animation: gentlePulse 2s ease-in-out;
        }

        /* Efecto de brillo al pasar el cursor */
        .logo-glow {
            position: relative;
            overflow: hidden;
        }

        .logo-glow::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
            transform: rotate(45deg);
        }

        .logo-glow:hover::after {
            opacity: 1;
        }

        /* Efecto de borde animado */
        .logo-border-animation {
            position: relative;
        }

        .logo-border-animation::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            /* background: linear-gradient(45deg, #ff6b6b, #ffd93d, #6bcf7f, #4d96ff); */
            border-radius: 8px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        .logo-border-animation:hover::before {
            opacity: 1;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Responsive */
        @media (max-width: 1023px) {
            .dropdown-menu {
                position: static;
                box-shadow: none;
            }

            .logo-large {
                display: none;
            }

            .logo-small {
                display: block;
            }

            .logo-border-animation::before {
                border-radius: 6px;
            }
        }

        @media (min-width: 1024px) {
            .logo-large {
                display: block;
            }

            .logo-small {
                display: none;
            }
        }

        /* Efecto de partículas para el logo (opcional) */
        .logo-particles {
            position: relative;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
        }

        @keyframes floatParticle {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }

            100% {
                transform: translate(var(--tx), var(--ty)) scale(0);
                opacity: 0;
            }
        }

        /* ===================================================================
           APP SHELL + SIDEBAR (navegación lateral)
           =================================================================== */
        :root {
            --sidebar-w: 240px;
            --sidebar-w-collapsed: 64px;
            /* Base sobria en gris oscuro NEUTRO (sin tinte azulado) */
            --sidebar-bg: #262626;          /* neutral-800 */
            --sidebar-bg-elev: #171717;     /* neutral-900 para brand/footer */
            /* Rojo original de la marca: efectos, hover y estado activo */
            --sidebar-brand: #DC2626;       /* red-600 */
            --sidebar-brand-soft: rgba(220,38,38,0.16);
            /* Detalles finos según la entidad activa */
            --sidebar-accent: {{ $workspaceAccent ?? '#DC2626' }};
        }

        /* Ocultar el navbar superior legacy (se retira en la fase 8). */
        .app-legacy-nav { display: none !important; }

        /* Sidebar fijo a la izquierda. */
        .app-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg); /* gris oscuro sobrio */
            color: #fff;
            display: flex;
            flex-direction: column;
            z-index: 50;
            transition: width 0.2s ease, transform 0.2s ease;
            border-right: 3px solid var(--sidebar-accent); /* detalle: color de la entidad */
        }
        .app-shell--collapsed .app-sidebar { width: var(--sidebar-w-collapsed); }

        /* Columna de contenido: deja espacio para el sidebar. */
        .app-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            transition: margin-left 0.2s ease;
        }
        .app-shell--collapsed .app-main { margin-left: var(--sidebar-w-collapsed); }

        /* Marca / logo */
        .app-sidebar__brand {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.5rem; padding: 0.85rem 0.9rem;
            background: var(--sidebar-bg-elev);
            border-bottom: 2px solid var(--sidebar-accent); /* detalle de entidad */
            min-height: 60px;
        }
        .app-sidebar__brand-link { position: relative; display: flex; align-items: center; justify-content: center; color: #fff; min-width: 0; height: 2.2rem; width: 100%; }
        /* Los dos logos se superponen en el mismo punto (posición absoluta) para que
           el cruce de opacidad no altere el ancho/alto y NO se produzca salto. */
        .app-sidebar__brand-logo-full,
        .app-sidebar__brand-logo-mark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        }
        /* Transición (fade) entre logo alargado y cuadrado. */
        .app-logo-fade-enter, .app-logo-fade-leave { transition: opacity 0.25s ease; }
        .app-logo-fade-start { opacity: 0; }
        .app-logo-fade-end { opacity: 1; }
        /* Logo alargado (expandido): proporcional (sin deformar) y redondeado. */
        .app-sidebar__brand-logo-full {
            height: 2.2rem; width: auto; max-width: 100%;
            object-fit: contain; border-radius: 0.5rem;
        }
        /* Logo cuadrado (colapsado): proporcional, tamaño fijo y redondeado. */
        .app-sidebar__brand-logo-mark {
            width: 2.2rem; height: 2.2rem; border-radius: 0.5rem;
            object-fit: contain; flex-shrink: 0;
        }
        .app-shell--collapsed .app-sidebar__brand { justify-content: center; padding: 0.7rem 0; }
        .app-sidebar__collapse-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.75rem; height: 1.75rem; border-radius: 0.375rem;
            color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.08);
            transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
        }
        /* Efecto en rojo de marca */
        .app-sidebar__collapse-btn:hover { background: var(--sidebar-brand); color: #fff; transform: scale(1.08); }
        .app-shell--collapsed .app-sidebar__collapse-btn { margin: 0 auto; }

        /* Navegación */
        .app-sidebar__nav {
            flex: 1; overflow-y: auto; padding: 0.6rem 0.5rem;
            display: flex; flex-direction: column; gap: 0.35rem; /* separación entre ítems */
        }
        .app-sidebar__nav::-webkit-scrollbar { width: 6px; }
        .app-sidebar__nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        /* Divisor sutil entre secciones de nivel superior. */
        .app-sidebar__nav > .app-sidebar__group,
        .app-sidebar__nav > .app-sidebar__item { position: relative; }
        .app-sidebar__nav > .app-sidebar__group:not(:last-child)::after,
        .app-sidebar__nav > .app-sidebar__item:not(:last-child)::after {
            content: ""; position: absolute; left: 0.6rem; right: 0.6rem; bottom: -0.2rem;
            height: 1px; background: rgba(255,255,255,0.07);
        }

        .app-sidebar__item {
            display: flex; align-items: center; gap: 0.75rem;
            width: 100%; padding: 0.6rem 0.7rem; border-radius: 0.5rem;
            color: rgba(255,255,255,0.82); font-size: 0.9rem; font-weight: 500;
            text-align: left; position: relative;
            transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        }
        /* Efecto hover en rojo de marca */
        .app-sidebar__item:hover { background: var(--sidebar-brand-soft); color: #fff; }
        /* Estado activo: rojo de marca + indicador lateral con el color de la entidad */
        .app-sidebar__item--active {
            background: var(--sidebar-brand); color: #fff; font-weight: 600;
            box-shadow: inset 3px 0 0 var(--sidebar-accent);
        }
        .app-sidebar__item--active:hover { background: var(--sidebar-brand); }
        /* El ícono del ítem activo/hover toma el color de la entidad como detalle
           (salvo cuando el fondo ya es rojo pleno del activo, donde va en blanco). */
        .app-sidebar__item:hover .app-sidebar__icon { color: var(--sidebar-accent); }
        .app-sidebar__item--active .app-sidebar__icon { color: #fff; }
        .app-sidebar__icon { width: 1.25rem; text-align: center; flex-shrink: 0; }
        .app-sidebar__label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .app-sidebar__chevron { font-size: 0.7rem; transition: transform 0.2s ease; }
        .app-sidebar__chevron--open { transform: rotate(180deg); }
        .app-sidebar__group-toggle { cursor: pointer; }

        /* Submenú (modo expandido) */
        .app-sidebar__submenu { padding: 0.15rem 0 0.25rem 0.5rem; }
        .app-sidebar__subitem {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.45rem 0.7rem; border-radius: 0.45rem;
            color: rgba(255,255,255,0.75); font-size: 0.85rem;
        }
        .app-sidebar__subitem { transition: background 0.15s ease, color 0.15s ease; }
        .app-sidebar__subitem:hover { background: var(--sidebar-brand-soft); color: #fff; }
        .app-sidebar__subitem--active {
            color: #fff; font-weight: 600; background: var(--sidebar-brand-soft);
            box-shadow: inset 2px 0 0 var(--sidebar-accent); /* detalle de entidad */
        }
        .app-sidebar__subicon { width: 1rem; text-align: center; flex-shrink: 0; opacity: 0.85; }
        .app-sidebar__subitem--active .app-sidebar__subicon { color: var(--sidebar-accent); opacity: 1; }

        /* En modo colapsado, centrar íconos y ocultar textos/chevron. */
        .app-shell--collapsed .app-sidebar__item { justify-content: center; padding: 0.6rem 0; }
        .app-shell--collapsed .app-sidebar__nav { padding: 0.5rem 0.35rem; }

        /* Flyout (modo rail): panel lateral con los sub-ítems. */
        .app-sidebar__group { position: relative; }
        .app-sidebar__flyout {
            position: absolute; left: 100%; top: 0; margin-left: 0.4rem;
            min-width: 200px; background: #fff; color: #374151;
            border-radius: 0.6rem; box-shadow: 0 10px 30px rgba(0,0,0,0.18);
            padding: 0.4rem; z-index: 60;
        }
        .app-sidebar__flyout-title {
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
            color: #9ca3af; padding: 0.3rem 0.6rem; font-weight: 700;
        }
        .app-sidebar__flyout .app-sidebar__subitem { color: #374151; }
        .app-sidebar__flyout .app-sidebar__subitem:hover { background: #f3f4f6; color: #b91c1c; }
        .app-sidebar__flyout .app-sidebar__subitem--active { background: #fef2f2; color: #b91c1c; }

        /* Footer del sidebar (context: workspace/alertas/usuario). */
        .app-sidebar__footer {
            border-top: 1px solid rgba(255,255,255,0.12);
            background: var(--sidebar-bg-elev); padding: 0.5rem;
        }
        .app-footer { display: flex; flex-direction: column; gap: 0.25rem; }
        .app-footer__block { position: relative; }
        .app-footer__btn {
            display: flex; align-items: center; gap: 0.6rem; width: 100%;
            padding: 0.5rem 0.6rem; border-radius: 0.5rem;
            color: rgba(255,255,255,0.85); font-size: 0.85rem; text-align: left;
        }
        .app-footer__btn { transition: background 0.15s ease, color 0.15s ease; }
        .app-footer__btn:hover { background: var(--sidebar-brand-soft); color: #fff; }
        .app-shell--collapsed .app-footer__btn { justify-content: center; padding: 0.5rem 0; }
        .app-footer__icon { width: 1.25rem; text-align: center; }
        .app-footer__icon-wrap { position: relative; display: inline-flex; }
        .app-footer__badge {
            position: absolute; top: -4px; right: -6px; min-width: 16px; height: 16px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 9999px; background: var(--sidebar-brand); color: #fff;
            font-size: 9px; font-weight: 700; padding: 0 3px;
            box-shadow: 0 0 0 2px var(--sidebar-bg-elev);
        }
        .app-footer__ws-logo {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.9rem; height: 1.9rem; border-radius: 0.4rem; background: #fff; flex-shrink: 0;
        }
        .app-footer__ws-logo img { max-width: 1.4rem; max-height: 1.4rem; object-fit: contain; }
        .app-footer__ws-text { display: flex; flex-direction: column; min-width: 0; flex: 1; }
        .app-footer__ws-name { font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .app-footer__ws-contract { font-size: 0.7rem; color: rgba(255,255,255,0.7); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .app-footer__chevron { font-size: 0.65rem; color: rgba(255,255,255,0.6); }
        .app-footer__label { flex: 1; }

        /* Menús emergentes del footer (workspace/alertas): salen hacia arriba. */
        .app-footer__menu {
            position: absolute; bottom: 100%; left: 0; right: 0; margin-bottom: 0.4rem;
            background: #fff; color: #374151; border-radius: 0.6rem;
            box-shadow: 0 -8px 30px rgba(0,0,0,0.2); padding: 0.35rem; z-index: 60;
            max-height: 60vh; overflow-y: auto;
        }
        .app-footer__menu--alerts { width: 20rem; left: 0; }
        .app-shell--collapsed .app-footer__menu { left: 100%; right: auto; bottom: 0; margin-bottom: 0; margin-left: 0.4rem; width: 20rem; }
        .app-footer__menu-title {
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
            color: #9ca3af; padding: 0.35rem 0.6rem; font-weight: 700;
        }
        .app-footer__menu-item {
            display: flex; align-items: center; gap: 0.5rem; width: 100%;
            padding: 0.5rem 0.6rem; border-radius: 0.45rem; text-align: left; font-size: 0.85rem; color: #374151;
        }
        .app-footer__menu-item:hover { background: #f3f4f6; }
        .app-footer__menu-item--current { background: #fef2f2; box-shadow: inset 2px 0 0 var(--sidebar-accent); }
        .app-footer__menu-empty { padding: 0.5rem 0.6rem; color: #9ca3af; font-size: 0.85rem; }
        .app-footer__menu-link {
            display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.6rem;
            border-top: 1px solid #f3f4f6; color: #6b7280; font-size: 0.8rem;
        }
        .app-footer__menu-link:hover { color: #b91c1c; }
        .app-footer__user {
            display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.6rem;
            color: rgba(255,255,255,0.85); font-size: 0.85rem;
        }
        .app-shell--collapsed .app-footer__user { justify-content: center; padding: 0.5rem 0; }
        .app-footer__user-name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .app-footer__logout button {
            width: 1.9rem; height: 1.9rem; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 0.4rem; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.85);
        }
        .app-footer__logout button { transition: background 0.15s ease, color 0.15s ease; }
        .app-footer__logout button:hover { background: var(--sidebar-brand); color: #fff; }

        /* Topbar mínima (hamburguesa en móvil). */
        .app-topbar { display: none; }
        .app-topbar__hamburger {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.5rem; height: 2.5rem; border-radius: 0.5rem;
            color: var(--sidebar-brand); background: #fff; border: 1px solid #e5e7eb;
        }
        .app-shell__backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 45;
        }

        /* ---------- Responsive: drawer en < lg ---------- */
        @media (max-width: 1023px) {
            .app-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-w);
            }
            .app-sidebar--mobile-open { transform: translateX(0); }
            .app-shell--collapsed .app-sidebar { width: var(--sidebar-w); } /* en móvil siempre expandido */
            .app-main, .app-shell--collapsed .app-main { margin-left: 0; }
            .app-topbar {
                display: flex; align-items: center; gap: 0.75rem;
                padding: 0.6rem 0.9rem; background: #fff;
                border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 40;
            }
        }
    </style>
</head>

<body class="bg-gray-100 overflow-x-hidden" x-data="appShell()" x-init="init()" :class="{ 'app-shell--collapsed': collapsed }">
    @php
        // $navSections y $isSectionActive los provee App\View\Composers\NavigationComposer
        // (registrado en AppServiceProvider). Aquí solo se derivan las variables de
        // presentación del workspace activo.
        $workspaceName = $currentWorkspace?->name ?? '';
        $workspaceDisplayName = $workspaceName;
        $workspaceKey = Str::lower($workspaceName);
        $workspaceAccent = $currentWorkspace?->primary_color ?? '#DC2626';
        $workspaceAccentBg = $workspaceAccent . '1A';
        $workspaceLogo = !empty($currentWorkspace?->logo_path) ? asset('storage/' . $currentWorkspace->logo_path) : null;
        $activeContract = $currentWorkspace?->activeContract;
        $activeContractLabel = $activeContract ? ($activeContract->number ?: $activeContract->name) : 'Sin contrato activo';
        if (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'movilidad')) {
            $workspaceAccent = '#BED000';
            $workspaceAccentBg = '#BED0002E';
        } elseif (!$currentWorkspace?->primary_color && Str::contains($workspaceKey, 'cultura')) {
            $workspaceAccent = '#493D86';
            $workspaceAccentBg = '#493D861F';
        }

        if (!$workspaceLogo && Str::contains($workspaceKey, 'movilidad')) {
            $workspaceLogo = asset('movilidad.jpg');
        } elseif (!$workspaceLogo && Str::contains($workspaceKey, 'cultura')) {
            $workspaceLogo = asset('cultura.png');
        }
    @endphp
    {{-- ===== SIDEBAR (nueva navegación lateral) ===== --}}
    @auth
        @include('layouts.partials.sidebar')
        {{-- Backdrop del drawer móvil --}}
        <div class="app-shell__backdrop" x-show="mobileOpen" x-cloak @click="mobileOpen = false"></div>
    @endauth

    {{-- Navegación superior LEGACY: oculta por CSS (.app-legacy-nav). Se retira en la fase 8. --}}
    <nav class="bg-red-600 text-white shadow-lg border-b-4 app-legacy-nav" id="mainNavigation"
        style="border-bottom-color: {{ $workspaceAccent }};">
        <div class="w-full px-2 sm:px-4 lg:px-6">
            <div class="flex justify-between items-center py-2 sm:py-3 md:py-4">
                <!-- Logo y menú principal -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="{{ route('my-space.index') }}"
                        class="text-xl font-bold flex items-center logo-container logo-particles" id="logoLink">
                        <!-- Icono grande para escritorio con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/sapp_logo_lg.png" alt="Sistema Sapp" class="logo-large logo-glow logo-pulse rounded-md"
                                id="logoLarge">
                        </div>
                        <!-- Icono pequeño para móvil con efectos -->
                        <div class="logo-border-animation mr-2">
                            <img src="/logo_sapp_xs.png" alt="Sistema Sapp" class="logo-small logo-glow logo-pulse rounded-md"
                                id="logoSmall">
                        </div>
                        {{-- <span class="hidden sm:inline transition-colors duration-300 hover:text-red-200">Sistema Sapp</span> --}}
                    </a>

                    @auth
                        <!-- Menú para desktop -->
                        <div class="hidden lg:flex items-center space-x-1">
                            @foreach ($navSections as $section)
                                @php
                                    $sectionActive = $isSectionActive($section['match'] ?? []);
                                @endphp
                                @if (($section['type'] ?? 'link') === 'link')
                                    <a href="{{ route($section['route']) }}"
                                        class="primary-nav-link {{ $sectionActive ? 'nav-item-active' : '' }}">
                                        <i class="{{ $section['icon'] }}"></i>
                                        {{ $section['label'] }}
                                    </a>
                                @else
                                    <div class="relative" data-dropdown="{{ $section['key'] }}">
                                        <button type="button"
                                            class="primary-nav-link {{ $sectionActive ? 'nav-item-active' : '' }}"
                                            data-dropdown-toggle="{{ $section['key'] }}" aria-expanded="false"
                                            aria-haspopup="true">
                                            <i class="{{ $section['icon'] }}"></i>
                                            {{ $section['label'] }}
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </button>
                                        <div class="dropdown-menu" data-dropdown-menu="{{ $section['key'] }}">
                                            @foreach ($section['links'] as $link)
                                                <a href="{{ route($link['route']) }}"
                                                    class="{{ $isSectionActive($link['match'] ?? []) ? 'bg-red-700' : '' }}">
                                                    <i class="{{ $link['icon'] }}"></i>
                                                    {{ $link['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endauth
                </div>

        <!-- Menú de usuario -->
                <div class="flex items-center space-x-1 sm:space-x-2 lg:space-x-4">
                    @auth
                        @if(isset($currentWorkspace))
                            @php
                                $switchableContracts = isset($userContracts) ? $userContracts : collect();
                                $currentContractId = $currentContract->id ?? null;
                                $currentRedirect = request()->fullUrl();
                            @endphp
                            {{-- Selector de workspace: dropdown para cambiar de entidad/contrato --}}
                            {{-- SIN salir de la pantalla actual (preserva redirect_to = URL actual). --}}
                            <div class="relative hidden md:block" data-dropdown="workspace">
                                <button type="button"
                                        class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition"
                                        data-dropdown-toggle="workspace" aria-expanded="false" aria-haspopup="true"
                                        title="{{ $workspaceDisplayName }} - {{ $activeContractLabel }}">
                                    @if ($workspaceLogo)
                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-white shrink-0">
                                            <img src="{{ $workspaceLogo }}" alt="{{ $workspaceDisplayName }}" class="max-w-[1.75rem] max-h-[1.75rem] object-contain">
                                        </div>
                                    @else
                                        <i class="fas fa-building text-base text-white/80"></i>
                                    @endif
                                    <div class="min-w-0 hidden lg:block text-left">
                                        <p class="text-sm font-semibold text-white leading-tight truncate">{{ $workspaceDisplayName }}</p>
                                        <p class="text-xs text-white/70 leading-none truncate">{{ $activeContractLabel }}</p>
                                    </div>
                                    <i class="fas fa-chevron-down text-xs text-white/70 ml-0.5"></i>
                                </button>

                                <div class="dropdown-menu" data-dropdown-menu="workspace">
                                    <div class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-100">
                                        Cambiar de entidad
                                    </div>
                                    @forelse ($switchableContracts as $contract)
                                        @php
                                            $isCurrent = (int) $contract->id === (int) $currentContractId;
                                            $entityName = $contract->company->name ?? 'Entidad';
                                            $contractLabel = $contract->number ?: $contract->name;
                                        @endphp
                                        <form method="POST" action="{{ route('workspaces.switch') }}" class="block">
                                            @csrf
                                            <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                                            <input type="hidden" name="redirect_to" value="{{ $currentRedirect }}">
                                            <button type="submit"
                                                    class="w-full flex items-center gap-2 text-left {{ $isCurrent ? 'bg-red-50' : '' }}"
                                                    @if($isCurrent) aria-current="true" @endif>
                                                <i class="fas fa-building text-gray-400 w-4 text-center"></i>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm text-gray-800 truncate">{{ $entityName }}</span>
                                                    <span class="block text-xs text-gray-400 truncate">{{ $contractLabel }}</span>
                                                </span>
                                                @if($isCurrent)
                                                    <i class="fas fa-check text-red-600 text-xs"></i>
                                                @endif
                                            </button>
                                        </form>
                                    @empty
                                        <div class="px-3 py-2 text-sm text-gray-500">Sin entidades disponibles</div>
                                    @endforelse
                                    <a href="{{ route('workspaces.select') }}" class="border-t border-gray-100 text-gray-500">
                                        <i class="fas fa-sliders-h"></i>
                                        Ver todas / pantalla completa
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- Campana de alertas con dropdown (LEGACY: ids renombrados para no
                             colisionar con la campana del sidebar-footer, que es la activa.
                             Este bloque se elimina en la fase 8.) --}}
                        <div class="relative" id="alertBellWrapper-legacy">
                            <button type="button"
                               class="relative flex items-center justify-center w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 transition"
                               title="Alertas operativas"
                               id="navAlertBell-legacy">
                                <i class="fas fa-bell text-base text-white/90"></i>
                                <span id="navAlertBadge-legacy" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 flex items-center justify-center rounded-full bg-white text-red-600 text-[9px] font-bold leading-none px-0.5">
                                </span>
                            </button>

                            {{-- Dropdown de alertas recientes --}}
                            <div id="alertDropdown-legacy" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-800">Alertas recientes</span>
                                    <a href="{{ route('operational-alerts.index') }}" class="text-xs text-red-600 hover:text-red-700 font-medium">Ver todas</a>
                                </div>
                                <div id="alertDropdownList-legacy" class="max-h-[300px] overflow-y-auto">
                                    <div class="px-4 py-6 text-center text-xs text-gray-400">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Cargando...
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Usuario --}}
                        <div class="flex items-center gap-2 bg-red-700/80 pl-3 pr-1.5 py-1.5 rounded-lg">
                            <span class="text-sm font-medium text-white truncate max-w-[130px] hidden sm:inline">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="w-7 h-7 flex items-center justify-center rounded bg-white/10 hover:bg-white/20 transition text-white/80 hover:text-white"
                                    title="Cerrar sesión">
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Botón menú móvil -->
                        <button type="button"
                            class="lg:hidden text-white focus:outline-none transition-transform duration-300 hover:scale-110 p-2"
                            data-mobile-menu-toggle aria-expanded="false" aria-controls="mobileMenuPanel">
                            <i class="fas fa-bars text-lg sm:text-xl"></i>
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                            class="hover:bg-red-700 px-3 py-2 rounded transition-all duration-300 hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-1"></i>
                            <span class="hidden sm:inline">Iniciar Sesión</span>
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Menú móvil -->
            @auth
                <div id="mobileMenuPanel"
                    class="mobile-menu lg:hidden bg-red-700 mt-2 rounded-2xl shadow-xl overflow-hidden hidden">
                    <div class="py-4 px-4 space-y-3">
                        @if(isset($currentWorkspace))
                            @php
                                $mobileContracts = isset($userContracts) ? $userContracts : collect();
                                $mobileCurrentContractId = $currentContract->id ?? null;
                                $mobileRedirect = request()->fullUrl();
                            @endphp
                            <div class="bg-white/10 rounded-2xl p-3">
                                <div class="flex items-center justify-between gap-3 mb-1">
                                    <div class="min-w-0">
                                        <p class="text-base text-white font-semibold truncate">{{ $workspaceDisplayName }}</p>
                                        <p class="text-sm text-white/80 truncate">{{ $activeContractLabel }}</p>
                                    </div>
                                    <i class="fas fa-building text-white/70"></i>
                                </div>
                                @if($mobileContracts->count() > 1)
                                    <p class="text-[11px] uppercase tracking-wide text-white/60 mt-2 mb-1">Cambiar de entidad</p>
                                    <div class="space-y-1">
                                        @foreach($mobileContracts as $contract)
                                            @php
                                                $isCurrent = (int) $contract->id === (int) $mobileCurrentContractId;
                                                $entityName = $contract->company->name ?? 'Entidad';
                                            @endphp
                                            @if(!$isCurrent)
                                                <form method="POST" action="{{ route('workspaces.switch') }}">
                                                    @csrf
                                                    <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                                                    <input type="hidden" name="redirect_to" value="{{ $mobileRedirect }}">
                                                    <button type="submit" class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-left text-sm text-white/90 hover:bg-white/15 transition">
                                                        <i class="fas fa-arrow-right-arrow-left text-xs text-white/60"></i>
                                                        <span class="truncate">{{ $entityName }} · {{ $contract->number ?: $contract->name }}</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                <a href="{{ route('workspaces.select') }}" class="mt-2 flex items-center gap-2 text-xs text-white/70 hover:text-white transition">
                                    <i class="fas fa-sliders-h"></i> Ver todas / pantalla completa
                                </a>
                            </div>
                        @endif

                        @foreach ($navSections as $section)
                            @php
                                $sectionActive = $isSectionActive($section['match'] ?? []);
                            @endphp
                            @if (($section['type'] ?? 'link') === 'link')
                                <a href="{{ route($section['route']) }}"
                                    class="mobile-nav-link {{ $sectionActive ? 'mobile-nav-link-active' : '' }}">
                                    <i class="{{ $section['icon'] }}"></i>
                                    <span>{{ $section['label'] }}</span>
                                </a>
                            @else
                                <div class="bg-white/5 rounded-2xl p-2">
                                    <button type="button" class="mobile-section-trigger"
                                        data-mobile-section-trigger="{{ $section['key'] }}"
                                        data-default-open="{{ $sectionActive ? 'true' : 'false' }}"
                                        aria-expanded="{{ $sectionActive ? 'true' : 'false' }}">
                                        <span class="flex items-center gap-3">
                                            <i class="{{ $section['icon'] }}"></i>
                                            {{ $section['label'] }}
                                        </span>
                                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                                    </button>
                                    <div class="mt-2 space-y-1 {{ $sectionActive ? '' : 'hidden' }}"
                                        data-mobile-section-panel="{{ $section['key'] }}">
                                        @foreach ($section['links'] as $link)
                                            <a href="{{ route($link['route']) }}"
                                                class="mobile-nav-link {{ $isSectionActive($link['match'] ?? []) ? 'mobile-nav-link-active' : '' }}">
                                                <i class="{{ $link['icon'] }}"></i>
                                                {{ $link['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <!-- Main Content -->
    <div class="app-main" @auth :class="{ 'app-main--has-sidebar': true }" @endauth>
        {{-- Topbar mínima: hamburguesa (móvil) + accesos de contexto. --}}
        @auth
            <header class="app-topbar">
                <button type="button" class="app-topbar__hamburger"
                        @click="mobileOpen = !mobileOpen; if (mobileOpen) collapsed = false"
                        aria-label="Abrir menú" :aria-expanded="mobileOpen.toString()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="{{ route('my-space.index') }}" class="app-topbar__brand" title="Inicio">
                    <img src="/logo_sapp_xs.png" alt="SAPP" class="h-7 w-7 rounded">
                </a>
            </header>
        @endauth

        <div class="app-main__inner max-w-7xl mx-auto py-3 sm:py-4 md:py-6 px-3 sm:px-4 md:px-6 lg:px-8">
        <!-- Flash Messages (toast flotante para evitar salto de layout) -->
        @if (!($__env->hasSection('disableGlobalFlash')) && (session('success') || session('error') || session('info')))
            <div class="fixed top-20 right-4 z-50 w-[calc(100%-2rem)] sm:w-auto sm:max-w-md space-y-2">
                @if (session('info'))
                    <div class="alert-flash flex items-start gap-3 bg-blue-100 border border-blue-400 text-blue-800 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base"
                        role="status" aria-live="polite">
                        <div class="flex-1">
                            <i class="fas fa-exchange-alt mr-1"></i> {{ session('info') }}
                        </div>
                        <button type="button" class="text-blue-800/70 hover:text-blue-900" aria-label="Cerrar"
                            data-flash-close>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert-flash flex items-start gap-3 bg-green-100 border border-green-400 text-green-800 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base"
                        role="status" aria-live="polite">
                        <div class="flex-1">
                            {{ session('success') }}
                        </div>
                        <button type="button" class="text-green-800/70 hover:text-green-900" aria-label="Cerrar"
                            data-flash-close>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-flash flex items-start gap-3 bg-red-100 border border-red-400 text-red-800 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base"
                        role="alert" aria-live="assertive">
                        <div class="flex-1">
                            {{ session('error') }}
                        </div>
                        <button type="button" class="text-red-800/70 hover:text-red-900" aria-label="Cerrar"
                            data-flash-close>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Page Header -->
        @php
            $hidePageHeader = $__env->hasSection('hidePageHeader');
            $pageTitle = trim($__env->yieldContent('title'));
        @endphp
        @if(!$hidePageHeader && $pageTitle !== '')
            <div class="mb-4 sm:mb-6">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ $pageTitle }}</h1>
                    @if(isset($serviceRequest) && ($serviceRequest->status ?? null) === 'CERRADA')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-red-500/70 bg-red-600 text-[11px] font-semibold uppercase tracking-wide text-white">
                            Cerrada
                        </span>
                    @endif
                </div>
                @yield('breadcrumb')
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
        </div>{{-- /app-main__inner --}}
    </div>{{-- /app-main --}}

    <!-- Scripts -->
    <script>
        // Función para confirmar eliminaciones
        function confirmDelete(message = '¿Está seguro de que desea eliminar este registro?') {
            return confirm(message);
        }

        // Auto-ocultar mensajes flash después de 5 segundos
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('.alert-flash, .flash-message');
            flashMessages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        document.addEventListener('click', function(event) {
            const closeBtn = event.target.closest('[data-flash-close]');
            if (!closeBtn) return;
            const flash = closeBtn.closest('.alert-flash, .flash-message');
            if (!flash) return;
            flash.style.transition = 'opacity 0.2s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 200);
        });

        document.addEventListener('DOMContentLoaded', function() {
            setupNavigationMenus();
            setupLogoEffects();
        });

        function setupNavigationMenus() {
            const dropdownWrappers = document.querySelectorAll('[data-dropdown]');
            const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');
            const mobileSectionButtons = document.querySelectorAll('[data-mobile-section-trigger]');
            const mobileSectionPanels = document.querySelectorAll('[data-mobile-section-panel]');
            let desktopOpenKey = null;
            let mobileMenuOpen = false;
            let currentMobileSection = null;

            function closeAllDesktopDropdowns() {
                dropdownWrappers.forEach(wrapper => {
                    const button = wrapper.querySelector('[data-dropdown-toggle]');
                    const menu = wrapper.querySelector('[data-dropdown-menu]');
                    if (button && menu) {
                        menu.classList.remove('show');
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
                desktopOpenKey = null;
            }

            function closeAllMobileSections() {
                mobileSectionPanels.forEach(panel => panel.classList.add('hidden'));
                mobileSectionButtons.forEach(button => button.setAttribute('aria-expanded', 'false'));
                currentMobileSection = null;
            }

            function resetMobileSectionsToDefault() {
                closeAllMobileSections();
                mobileSectionButtons.forEach(button => {
                    if (button.dataset.defaultOpen === 'true') {
                        const key = button.dataset.mobileSectionTrigger;
                        const panel = document.querySelector(
                            `[data-mobile-section-panel=\"${key}\"]`
                        );
                        if (panel) {
                            panel.classList.remove('hidden');
                            button.setAttribute('aria-expanded', 'true');
                            currentMobileSection = key;
                        }
                    }
                });
            }

            function closeMobileMenu() {
                if (mobileMenuPanel) {
                    mobileMenuPanel.classList.add('hidden');
                }
                if (mobileMenuToggle) {
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
                mobileMenuOpen = false;
                resetMobileSectionsToDefault();
            }

            dropdownWrappers.forEach(wrapper => {
                const button = wrapper.querySelector('[data-dropdown-toggle]');
                const menu = wrapper.querySelector('[data-dropdown-menu]');
                if (!button || !menu) {
                    return;
                }
                const key = wrapper.dataset.dropdown;

                const openMenu = () => {
                    closeAllDesktopDropdowns();
                    menu.classList.add('show');
                    button.setAttribute('aria-expanded', 'true');
                    desktopOpenKey = key;
                };

                const closeMenu = () => {
                    menu.classList.remove('show');
                    button.setAttribute('aria-expanded', 'false');
                    if (desktopOpenKey === key) {
                        desktopOpenKey = null;
                    }
                };

                let hoverTimeout;

                button.addEventListener('click', event => {
                    event.preventDefault();
                    if (desktopOpenKey === key) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });

                button.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                    openMenu();
                });

                button.addEventListener('mouseleave', () => {
                    hoverTimeout = setTimeout(closeMenu, 200);
                });

                menu.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                });

                menu.addEventListener('mouseleave', () => {
                    hoverTimeout = setTimeout(closeMenu, 200);
                });
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('[data-dropdown]')) {
                    closeAllDesktopDropdowns();
                }
            });

            mobileSectionButtons.forEach(button => {
                const key = button.dataset.mobileSectionTrigger;
                const panel = document.querySelector(`[data-mobile-section-panel=\"${key}\"]`);
                if (!panel) {
                    return;
                }

                button.addEventListener('click', () => {
                    const isOpen = currentMobileSection === key;
                    if (isOpen) {
                        panel.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                        currentMobileSection = null;
                    } else {
                        closeAllMobileSections();
                        panel.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        currentMobileSection = key;
                    }
                });
            });

            resetMobileSectionsToDefault();

            if (mobileMenuToggle && mobileMenuPanel) {
                mobileMenuToggle.addEventListener('click', () => {
                    mobileMenuOpen = !mobileMenuOpen;
                    if (mobileMenuOpen) {
                        mobileMenuPanel.classList.remove('hidden');
                        mobileMenuToggle.setAttribute('aria-expanded', 'true');
                        resetMobileSectionsToDefault();
                    } else {
                        closeMobileMenu();
                    }
                });

                document.addEventListener('click', event => {
                    if (
                        mobileMenuOpen &&
                        !mobileMenuPanel.contains(event.target) &&
                        !mobileMenuToggle.contains(event.target)
                    ) {
                        closeMobileMenu();
                    }
                });
            }

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeAllDesktopDropdowns();
                    if (mobileMenuOpen) {
                        closeMobileMenu();
                    }
                }
            });
        }

        function setupLogoEffects() {
            const logoLink = document.getElementById('logoLink');
            const logoLarge = document.getElementById('logoLarge');
            const logoSmall = document.getElementById('logoSmall');

            if (!logoLink) {
                return;
            }

            logoLink.addEventListener('click', function(e) {
                createParticles(e, logoLink);
            });

            logoLink.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            logoLink.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });

            setTimeout(() => {
                if (logoLarge) logoLarge.style.animation = 'none';
                if (logoSmall) logoSmall.style.animation = 'none';
            }, 3000);
        }

        // Función para crear partículas (efecto opcional)
        function createParticles(event, element) {
            const rect = element.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            for (let i = 0; i < 8; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                const angle = Math.random() * Math.PI * 2;
                const distance = 20 + Math.random() * 30;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance;

                particle.style.setProperty('--tx', `${tx}px`);
                particle.style.setProperty('--ty', `${ty}px`);
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;

                particle.style.animation = `floatParticle 0.6s ease-out forwards`;
                element.appendChild(particle);

                setTimeout(() => {
                    particle.remove();
                }, 600);
            }
        }
    </script>
    <script>
        function onClick(e) {
            e.preventDefault();
            grecaptcha.enterprise.ready(async () => {
                const token = await grecaptcha.enterprise.execute('6LfUdsYZAAAAAFnFtC01B3KQkS3qp6SSxhSoIiGE', {
                    action: 'LOGIN'
                });
            });
        }
    </script>

    @yield('scripts')
    @stack('scripts')

    {{-- Alert badge updater --}}
    <script>
    (function() {
        var badge = document.getElementById('navAlertBadge');
        var bell = document.getElementById('navAlertBell');
        var dropdown = document.getElementById('alertDropdown');
        var dropdownList = document.getElementById('alertDropdownList');
        var wrapper = document.getElementById('alertBellWrapper');
        if (!badge || !bell) return;

        var isOpen = false;

        function updateAlertBadge() {
            fetch('{{ route("operational-alerts.api.unread-count") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.unread > 0) {
                    badge.textContent = data.unread > 99 ? '99+' : data.unread;
                    badge.classList.remove('hidden');
                    badge.classList.add('flex');
                } else {
                    badge.classList.add('hidden');
                    badge.classList.remove('flex');
                }
            })
            .catch(function() {});
        }

        function loadRecentAlerts() {
            fetch('{{ route("operational-alerts.api.recent") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.alerts || data.alerts.length === 0) {
                    dropdownList.innerHTML = '<div class="px-4 py-6 text-center text-xs text-gray-400">Sin alertas pendientes</div>';
                    return;
                }
                var html = '';
                data.alerts.forEach(function(alert) {
                    html += '<a href="' + (alert.url || '#') + '" class="block px-4 py-3 border-l-4 ' + alert.border_class + ' hover:bg-gray-50 transition border-b border-gray-50">';
                    html += '<p class="text-sm font-medium text-gray-900 leading-tight">' + alert.title + '</p>';
                    html += '<p class="text-xs text-gray-500 mt-0.5 line-clamp-1">' + alert.message + '</p>';
                    html += '<p class="text-[10px] text-gray-400 mt-1">' + alert.time + '</p>';
                    html += '</a>';
                });
                dropdownList.innerHTML = html;
            })
            .catch(function() {
                dropdownList.innerHTML = '<div class="px-4 py-6 text-center text-xs text-red-400">Error al cargar</div>';
            });
        }

        function toggleDropdown() {
            isOpen = !isOpen;
            if (isOpen) {
                dropdown.classList.remove('hidden');
                loadRecentAlerts();
            } else {
                dropdown.classList.add('hidden');
            }
        }

        bell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown();
        });

        document.addEventListener('click', function(e) {
            if (isOpen && wrapper && !wrapper.contains(e.target)) {
                isOpen = false;
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                isOpen = false;
                dropdown.classList.add('hidden');
            }
        });

        updateAlertBadge();
        setInterval(updateAlertBadge, 60000);
    })();
    </script>

    {{-- Estado del app-shell (sidebar): colapso, grupos, flyouts y drawer móvil.
         Se registra con alpine:init (forma canónica, a prueba del orden de carga)
         y además se expone como función global por compatibilidad. --}}
    <script>
        function appShell() {
            return {
                // En reposo el sidebar está colapsado (rail de íconos). Se expande
                // al pasar el mouse (desktop) y se vuelve a colapsar al salir.
                collapsed: true,
                mobileOpen: false,
                openGroup: null,   // grupo expandido (cuando está expandido)
                flyout: null,      // grupo con flyout abierto en modo rail

                init() {
                    // Abrir por defecto el grupo de la sección activa (dato inyectado abajo).
                    if (window.__sappActiveGroup) {
                        this.openGroup = window.__sappActiveGroup;
                    }
                    // Cerrar el drawer móvil al cambiar de tamaño a desktop.
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.mobileOpen = false;
                    });
                },

                // Expandir al pasar el mouse (solo desktop; en móvil manda el drawer).
                expandOnHover() {
                    if (window.innerWidth >= 1024) {
                        this.collapsed = false;
                    }
                },

                // Colapsar al salir el mouse (solo desktop). Cierra flyouts abiertos.
                collapseOnLeave() {
                    if (window.innerWidth >= 1024) {
                        this.collapsed = true;
                        this.flyout = null;
                    }
                },

                toggleGroup(key) {
                    this.openGroup = this.openGroup === key ? null : key;
                },

                isGroupOpen(key) {
                    return this.openGroup === key;
                },
            };
        }

        // Registro canónico: garantiza que el componente exista antes de que
        // Alpine procese el x-data del <body>, sin depender del orden de <script>.
        document.addEventListener('alpine:init', () => {
            window.Alpine && window.Alpine.data('appShell', appShell);
        });
    </script>
    {{-- Sección activa para abrir su grupo por defecto en el sidebar. --}}
    <script>
        @php
            $activeGroupKey = null;
            foreach ($navSections as $s) {
                if ($isSectionActive($s['match'] ?? [])) { $activeGroupKey = $s['key']; break; }
            }
        @endphp
        window.__sappActiveGroup = @json($activeGroupKey);
    </script>

    <script src="//unpkg.com/alpinejs" defer></script>
</body>

</html>
