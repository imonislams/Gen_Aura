import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Brand from './Brand';

const navItems = [
    { key: 'home', label: 'Home', href: '/' },
    { key: 'shop', label: 'Shop', href: '/shop' },
    { key: 'about', label: 'About', href: '/about' },
    { key: 'reviews', label: 'Reviews', href: '/reviews' },
    { key: 'contact', label: 'Contact', href: '/contact' },
];

function SearchIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" {...props}>
            <circle cx="11" cy="11" r="7" />
            <path d="M20 20l-3.2-3.2" />
        </svg>
    );
}

function CartIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h7.1a2 2 0 0 0 2-1.6L21 8H7" />
            <circle cx="10.5" cy="20" r="1.4" />
            <circle cx="18" cy="20" r="1.4" />
        </svg>
    );
}

function MenuIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" {...props}>
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    );
}

function CloseIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" {...props}>
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    );
}

function ArrowIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
    );
}

export default function StoreNav() {
    const page = usePage();
    const { settings, cartCount } = page.props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [query, setQuery] = useState('');
    const searchRef = useRef(null);
    const closeButtonRef = useRef(null);

    const currentUrl = page.url || '/';
    const path = currentUrl.split('?')[0];

    const activeKey = path === '/'
        ? 'home'
        : path.startsWith('/about')
            ? 'about'
            : path.startsWith('/reviews')
                ? 'reviews'
                : path.startsWith('/contact')
                    ? 'contact'
                    : path === '/shop' || path.startsWith('/product')
                        ? 'shop'
                        : null;

    const closeAll = () => {
        setMenuOpen(false);
        setSearchOpen(false);
    };

    const submitSearch = (event) => {
        event.preventDefault();
        const term = query.trim();
        closeAll();
        setQuery('');
        router.get('/shop', term ? { q: term } : {});
    };

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    useEffect(() => {
        if (searchOpen) {
            const timer = setTimeout(() => searchRef.current?.focus(), 80);

            return () => clearTimeout(timer);
        }
    }, [searchOpen]);

    useEffect(() => {
        if (!menuOpen) {
            return undefined;
        }

        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        closeButtonRef.current?.focus();

        return () => {
            document.body.style.overflow = previous;
        };
    }, [menuOpen]);

    return (
        <>
            <header className="sticky top-0 z-30 border-b border-purple-100/80 bg-white/85 shadow-[0_10px_30px_rgba(58,24,84,0.05)] backdrop-blur-xl">
                <div className="relative mx-auto grid min-h-[84px] max-w-6xl grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-4 px-4 py-2">
                    <div className="justify-self-start">
                        <Brand settings={settings} />
                    </div>

                    <nav aria-label="Main navigation" className="store-nav-pill hidden lg:flex">
                        {navItems.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                className="store-nav-link"
                                data-active={activeKey === item.key}
                                aria-current={activeKey === item.key ? 'page' : undefined}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center justify-self-end gap-2">
                        <button
                            type="button"
                            className="store-nav-icon"
                            aria-label="Search products"
                            aria-expanded={searchOpen}
                            aria-controls="store-search"
                            onClick={() => setSearchOpen((open) => !open)}
                        >
                            <SearchIcon className="h-[17px] w-[17px]" />
                        </button>

                        <Link
                            href="/cart"
                            className="store-nav-icon"
                            aria-label={cartCount > 0 ? `Shopping cart, ${cartCount} items` : 'Shopping cart'}
                        >
                            <CartIcon className="h-[17px] w-[17px]" />
                            {cartCount > 0 && (
                                <span className="absolute -right-1 -top-1 grid h-[18px] min-w-[18px] place-items-center rounded-full border-2 border-white bg-gradient-to-br from-mag to-org px-1 text-[9.5px] font-extrabold leading-none text-white shadow">
                                    {cartCount > 99 ? '99+' : cartCount}
                                </span>
                            )}
                        </Link>

                        <button
                            type="button"
                            className="store-nav-icon lg:hidden"
                            aria-label="Open menu"
                            aria-expanded={menuOpen}
                            aria-controls="store-menu"
                            onClick={() => setMenuOpen(true)}
                        >
                            <MenuIcon className="h-[18px] w-[18px]" />
                        </button>
                    </div>
                </div>

                <div
                    id="store-search"
                    aria-hidden={!searchOpen}
                    inert={!searchOpen}
                    className={`absolute inset-x-0 top-full z-40 overflow-hidden border-b border-purple-100 bg-white/95 backdrop-blur-xl transition-all duration-200 ${
                        searchOpen ? 'max-h-32 opacity-100' : 'pointer-events-none max-h-0 opacity-0'
                    }`}
                >
                    <form onSubmit={submitSearch} className="mx-auto flex max-w-6xl items-center gap-2 px-4 py-3">
                        <div className="flex min-w-0 flex-1 items-center gap-2 rounded-full border border-purple-100 bg-white px-4 py-1.5 shadow-sm focus-within:ring-2 focus-within:ring-pur/30">
                            <SearchIcon className="h-4 w-4 shrink-0 text-gray-400" />
                            <input
                                ref={searchRef}
                                type="search"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Search products — T-shirt, panjabi, chino…"
                                aria-label="Search products"
                                className="w-full min-w-0 bg-transparent py-1 text-sm outline-none"
                            />
                            <button
                                type="submit"
                                className="grid h-8 w-8 shrink-0 place-items-center rounded-full grad text-white"
                                aria-label="Search"
                            >
                                <ArrowIcon className="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            onClick={() => setSearchOpen(false)}
                            className="rounded-full px-3 py-2 text-sm font-semibold text-gray-500 hover:bg-purple-50"
                        >
                            Close
                        </button>
                    </form>
                </div>
            </header>

            <div className={`fixed inset-0 z-50 lg:hidden ${menuOpen ? '' : 'pointer-events-none'}`} aria-hidden={!menuOpen}>
                <button
                    type="button"
                    aria-label="Close menu"
                    className={`absolute inset-0 bg-[#1e082c]/40 backdrop-blur-[2px] transition-opacity duration-300 ${
                        menuOpen ? 'opacity-100' : 'opacity-0'
                    }`}
                    onClick={closeAll}
                />

                <aside
                    id="store-menu"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Mobile navigation"
                    inert={!menuOpen}
                    className={`absolute right-0 top-0 flex h-full w-[86%] max-w-sm flex-col bg-white/95 p-4 shadow-2xl backdrop-blur-xl transition-transform duration-300 ${
                        menuOpen ? 'translate-x-0' : 'translate-x-full'
                    }`}
                >
                    <div className="flex items-center justify-between border-b border-purple-100 pb-3">
                        <span className="font-serif text-xl font-semibold">Menu</span>
                        <button
                            ref={closeButtonRef}
                            type="button"
                            aria-label="Close menu"
                            className="grid h-9 w-9 place-items-center rounded-full border border-purple-100 bg-white text-gray-600 hover:bg-purple-50 hover:text-pur"
                            onClick={closeAll}
                        >
                            <CloseIcon className="h-4 w-4" />
                        </button>
                    </div>

                    <nav className="mt-2 flex flex-col gap-1" aria-label="Mobile main navigation">
                        {navItems.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                onClick={closeAll}
                                aria-current={activeKey === item.key ? 'page' : undefined}
                                className={`flex items-center justify-between rounded-xl px-3 py-3 text-sm font-bold transition ${
                                    activeKey === item.key
                                        ? 'grad text-white shadow'
                                        : 'text-gray-700 hover:bg-purple-50 hover:text-pur'
                                }`}
                            >
                                {item.label}
                                <span className="text-xs opacity-50">›</span>
                            </Link>
                        ))}
                    </nav>

                    <div className="mt-4 border-t border-purple-100 pt-2">
                        <Link
                            href="/cart"
                            onClick={closeAll}
                            className="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-purple-50 hover:text-pur"
                        >
                            Cart
                            <span className="text-xs font-extrabold text-mag">{cartCount}</span>
                        </Link>
                    </div>

                    <div className="mt-auto border-t border-purple-100 px-2 pt-3 text-xs leading-relaxed text-gray-400">
                        {settings.name} · same brand assets and routes.
                    </div>
                </aside>
            </div>
        </>
    );
}
