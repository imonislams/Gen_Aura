import { Link } from '@inertiajs/react';

export default function ProductCard({ product, t = (value) => value }) {
    const out = product.stock <= 0;

    return (
        <Link
            href={product.url}
            className="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col hover:shadow-md transition"
        >
            <div className="relative">
                <img
                    src={product.image_url}
                    alt={product.name}
                    loading="lazy"
                    className={`w-full aspect-square object-cover ${out ? 'opacity-50' : ''}`}
                />
                {product.off > 0 && !out && (
                    <span className="absolute top-2 left-2 grad text-white text-xs font-bold rounded-full px-2 py-1">
                        -{product.off}%
                    </span>
                )}
                {out ? (
                    <span className="absolute top-2 right-2 bg-black text-white text-xs font-bold rounded-full px-2 py-1">
                        {t('Out of stock')}
                    </span>
                ) : (
                    product.stock <= 5 && (
                        <span className="absolute bottom-2 left-2 bg-white/90 text-mag text-xs font-bold rounded-full px-2 py-1">
                            {product.stock} {t('left')}
                        </span>
                    )
                )}
            </div>
            <div className="p-4 flex flex-col flex-1">
                <span className="text-xs text-mag font-semibold">{product.cat}</span>
                <h3 className="font-semibold">{product.name}</h3>
                <p className="text-sm text-gray-500 line-clamp-2 flex-1">{product.description}</p>
                <div className="mt-2 flex items-baseline gap-2">
                    <b className="text-lg">৳{Number(product.price).toLocaleString()}</b>
                    {product.old_price > 0 && (
                        <s className="text-gray-400 text-sm">৳{Number(product.old_price).toLocaleString()}</s>
                    )}
                </div>
            </div>
        </Link>
    );
}
