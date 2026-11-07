import { Head, Link, usePage } from '@inertiajs/react';
import StoreLayout from '../../Layouts/StoreLayout';
import ProductCard from '../../Components/ProductCard';

function Home({ products, reviews, heroImage, heroProduct }) {
    const { settings, translations } = usePage().props;
    const t = (value) => translations?.[value] ?? value;

    return (
        <>
            <Head title="Home" />

            <section className="max-w-6xl mx-auto px-4 pt-8">
                <div className="relative overflow-hidden rounded-3xl grad text-white shadow-xl">
                    <div className="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 -left-16 w-80 h-80 rounded-full bg-white/10" />
                    <div className="relative grid md:grid-cols-2 items-center gap-6 p-8 md:p-12">
                        <div>
                            <span className="inline-block bg-white/20 backdrop-blur rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                                {settings.top || 'New Collection'}
                            </span>
                            <h1 className="font-serif text-4xl md:text-6xl font-bold mt-4">{settings.title}</h1>
                            <p className="mt-4 text-white/85 max-w-md">{settings.sub}</p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link
                                    href="/shop"
                                    className="bg-white text-mag font-semibold rounded-full px-6 py-3 hover:bg-white/90 transition"
                                >
                                    {t('Shop now')}
                                </Link>
                                <Link
                                    href="/reviews"
                                    className="border border-white/60 rounded-full px-6 py-3 font-semibold hover:bg-white/10 transition"
                                >
                                    {t('Reviews')}
                                </Link>
                            </div>
                        </div>
                        {heroImage && (
                            <div className="relative hidden md:block">
                                <img
                                    src={heroImage}
                                    alt={settings.name}
                                    className="w-full max-w-sm mx-auto aspect-square object-cover rounded-2xl shadow-2xl rotate-2"
                                />
                                {!settings.hero_image && heroProduct && (
                                    <div className="absolute bottom-3 left-1/2 -translate-x-1/2 bg-white text-gray-900 rounded-xl px-4 py-2 text-sm font-semibold shadow whitespace-nowrap">
                                        {heroProduct.name} · ৳{Number(heroProduct.price).toLocaleString()}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </section>

            <section className="max-w-6xl mx-auto px-4 py-8">
                <h2 className="font-serif text-3xl mb-4">{t('All products')}</h2>
                {products.length > 0 ? (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {products.map((product) => (
                            <ProductCard key={product.id} product={product} t={t} />
                        ))}
                    </div>
                ) : (
                    <p className="text-gray-500">{t('No products yet.')}</p>
                )}
            </section>

            {reviews.length > 0 && (
                <section className="max-w-6xl mx-auto px-4 py-8">
                    <h2 className="font-serif text-3xl mb-4">
                        {t('Customers are saying')}{' '}
                        <Link href="/reviews" className="text-sm text-pur font-semibold">
                            {t('All reviews')}
                        </Link>
                    </h2>
                    <div className="grid md:grid-cols-3 gap-4">
                        {reviews.map((review) => (
                            <div key={review.id} className="bg-white rounded-2xl p-5 shadow-sm">
                                <div className="text-org">{'★'.repeat(review.stars)}</div>
                                <p className="my-2">{review.text}</p>
                                <b className="text-sm text-gray-500">{review.name}</b>
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}

Home.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default Home;
