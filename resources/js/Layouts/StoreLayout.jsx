import { Link, usePage } from '@inertiajs/react';
import Brand from '../Components/Brand';
import Flash from '../Components/Flash';

export default function StoreLayout({ children }) {
    const { settings, cartCount, locale, translations, flash, errors } = usePage().props;
    const t = (value) => translations?.[value] ?? value;

    return (
        <div className="min-h-screen flex flex-col">
            <div className="grad text-white text-center text-sm py-1.5 font-semibold">
                {settings.top}
            </div>

            <header className="bg-white/90 backdrop-blur border-b sticky top-0 z-10">
                <div className="max-w-6xl mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-x-3">
                    <nav className="flex gap-4 md:gap-5 text-sm font-semibold order-last w-full justify-center pb-1 md:order-none md:w-auto md:pb-0">
                        <Link href="/">{t('Home')}</Link>
                        <Link href="/shop">{t('Shop')}</Link>
                        <Link href="/about">{t('About')}</Link>
                        <Link href="/reviews">{t('Reviews')}</Link>
                        <Link href="/contact">{t('Contact')}</Link>
                    </nav>

                    <Brand settings={settings} />

                    <div className="flex items-center gap-3 text-sm font-semibold">
                        <Link href={`/lang/${locale === 'en' ? 'bn' : 'en'}`} className="border rounded-full px-3 py-1">
                            {locale === 'en' ? 'বাংলা' : 'EN'}
                        </Link>
                        <Link href="/cart">
                            {t('Cart')}
                            {cartCount > 0 && (
                                <span className="ml-1 grad text-white rounded-full px-2 py-0.5 text-xs">{cartCount}</span>
                            )}
                        </Link>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                <Flash ok={flash?.ok} errors={errors} />
                {children}
            </main>

            <footer className="bg-white border-t mt-10 py-8 px-4 text-center text-sm">
                <Brand settings={settings} />
                <details className="max-w-md mx-auto mt-4 text-gray-600">
                    <summary className="cursor-pointer font-semibold">{t('Return & Refund Policy')}</summary>
                    <p className="mt-2">
                        {t('If you receive a damaged or wrong item, contact us within 3 days of delivery for a replacement or refund.')}
                    </p>
                </details>

                <div className="mt-4 text-gray-500 space-y-1">
                    {settings.address && <div>{settings.address}</div>}
                    {settings.phone && <div>{t('Phone')}: {settings.phone}</div>}
                    {settings.email && <div>{t('Email')}: {settings.email}</div>}
                </div>

                <div className="flex gap-4 justify-center mt-3 font-semibold text-pur">
                    {settings.fb && <a href={settings.fb} target="_blank" rel="noreferrer">Facebook</a>}
                    {settings.instagram && <a href={settings.instagram} target="_blank" rel="noreferrer">Instagram</a>}
                    {settings.youtube && <a href={settings.youtube} target="_blank" rel="noreferrer">YouTube</a>}
                </div>

                <a className="block mt-3 text-pur font-semibold" href={`https://wa.me/${settings.wa}`}>
                    WhatsApp: +{settings.wa}
                </a>
                <p className="mt-3 text-gray-400">© {new Date().getFullYear()} {settings.name}</p>
            </footer>
        </div>
    );
}
