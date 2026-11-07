import { Link, router, usePage } from '@inertiajs/react';
import Flash from '../Components/Flash';

const items = [
    { href: '/admin', label: 'Dashboard', icon: '▦', match: /^\/admin$/ },
    { href: '/admin/products', label: 'Products', icon: '▤', match: /^\/admin\/products/ },
    { href: '/admin/categories', label: 'Categories', icon: '◫', match: /^\/admin\/categories/ },
    { href: '/admin/orders', label: 'Orders', icon: '▥', match: /^\/admin\/orders/ },
    { href: '/admin/reviews', label: 'Reviews', icon: '★', match: /^\/admin\/reviews/ },
    { href: '/admin/settings', label: 'Settings', icon: '⚙', match: /^\/admin\/settings/ },
];

export default function AdminLayout({ children }) {
    const page = usePage();
    const { settings, flash, errors } = page.props;
    const url = page.url || '';

    const logout = (event) => {
        event.preventDefault();
        router.post('/admin/logout');
    };

    return (
        <div className="min-h-screen flex">
            <aside className="hidden md:flex md:flex-col w-64 shrink-0 bg-white border-r border-purple-100 sticky top-0 h-screen p-4">
                <Link href="/admin" className="flex items-center gap-2 mb-6 px-2">
                    <span className="w-8 h-8 rounded-lg grad flex items-center justify-center text-white font-serif font-bold">
                        A
                    </span>
                    <span className="font-serif text-lg font-semibold gtext">{settings.name}</span>
                </Link>

                <nav className="flex flex-col gap-1">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`px-3 py-2 rounded-lg text-sm font-semibold ${
                                item.match.test(url) ? 'grad text-white' : 'text-gray-600 hover:bg-purple-50'
                            }`}
                        >
                            <span className="mr-2">{item.icon}</span>
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <form onSubmit={logout} className="mt-auto">
                    <button className="w-full px-3 py-2 rounded-lg text-sm font-semibold bg-red-500 text-white">
                        Log out
                    </button>
                </form>
            </aside>

            <div className="flex-1 min-w-0 flex flex-col">
                <div className="md:hidden flex gap-2 p-3 overflow-x-auto bg-white border-b sticky top-0 z-20">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`whitespace-nowrap px-4 py-2 rounded-full text-sm font-semibold ${
                                item.match.test(url) ? 'grad text-white' : 'bg-white border'
                            }`}
                        >
                            {item.label}
                        </Link>
                    ))}
                    <form onSubmit={logout} className="ml-auto">
                        <button className="whitespace-nowrap px-4 py-2 rounded-full text-sm font-semibold bg-red-500 text-white">
                            Log out
                        </button>
                    </form>
                </div>

                <main className="p-4 w-full max-w-5xl mx-auto">
                    <Flash ok={flash?.ok} errors={errors} />
                    {children}
                </main>
            </div>
        </div>
    );
}
