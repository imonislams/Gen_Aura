import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import StoreLayout from '../../Layouts/StoreLayout';
import ProductCard from '../../Components/ProductCard';
import StarRating from '../../Components/StarRating';

function Product({ product, related, reviews }) {
    const hasVariants = !!product.has_variants && (product.variants?.length ?? 0) > 0;
    const variants = product.variants ?? [];
    const colors = product.colors ?? [];
    const colorKey = (variant) => String(variant?.color_id ?? '');
    const firstAvailable = variants.find((variant) => variant.available) ?? variants[0] ?? null;
    const hasColors = colors.length > 0;
    const initialSize = hasColors ? '' : (firstAvailable?.size ?? product.sizes?.[0] ?? '');

    const [image, setImage] = useState(product.image_url);
    const [colorId, setColorId] = useState('');
    const [size, setSize] = useState(initialSize);
    const [qty, setQty] = useState(1);

    const activeColor = colors.find((color) => String(color.id) === colorId) || null;
    const colorGallery = activeColor?.images?.length
        ? activeColor.images
        : (activeColor?.image_url ? [activeColor.image_url] : null);
    const galleryImages = colorGallery ?? product.gallery;
    const imageIndex = Math.max(0, galleryImages.indexOf(image));
    const showImageArrows = galleryImages.length > 1;
    const previousImage = () => setImage(galleryImages[(imageIndex - 1 + galleryImages.length) % galleryImages.length]);
    const nextImage = () => setImage(galleryImages[(imageIndex + 1) % galleryImages.length]);
    const colorVariants = hasVariants ? variants.filter((variant) => colorKey(variant) === colorId) : [];
    const displaySizes = hasVariants
        ? colorVariants.map((variant) => ({
            name: variant.size ?? '',
            stock: variant.stock,
            available: variant.available,
            low: variant.low,
            sold_out: variant.sold_out,
            variantId: variant.id,
        }))
        : (product.size_options?.length
            ? product.size_options
            : product.sizes.map((name) => ({
                name,
                stock: null,
                available: product.stock > 0,
                low: false,
                sold_out: product.stock <= 0,
            })));

    const showSizeSelector = displaySizes.some((option) => String(option.name || '').trim() !== '');
    const selectedOption = displaySizes.find((option) => option.name === size) || null;
    const selectedVariant = selectedOption?.variantId
        ? variants.find((variant) => variant.id === selectedOption.variantId) || null
        : null;
    const readyToAdd = hasVariants ? !!selectedVariant : (displaySizes.length === 0 || !!selectedOption);
    const availableQty = selectedOption ? (selectedOption.stock ?? product.stock) : product.stock;
    const maxQty = Math.max(1, Math.min(50, availableQty));
    const out = hasVariants
        ? !variants.some((variant) => variant.available)
        : (product.stock <= 0 || (displaySizes.length > 0 && !displaySizes.some((option) => option.available)));
    const selectedSoldOut = hasVariants
        ? (!selectedVariant || selectedVariant.sold_out)
        : (selectedOption ? selectedOption.sold_out : out);
    const lowStock = selectedOption
        ? selectedOption.low
        : (product.stock > 0 && product.stock <= (product.low_stock_threshold ?? 5));

    const chooseColor = (id) => {
        const key = String(id ?? '');
        setColorId(key);

        const options = variants.filter((variant) => colorKey(variant) === key);
        const pick = options.find((variant) => variant.available) ?? options[0];

        if (pick) {
            setSize(pick.size ?? '');
        }

        setQty(1);

        const color = colors.find((item) => String(item.id) === key);
        setImage(color?.images?.[0] || color?.image_url || product.image_url);
    };

    const chooseDefault = () => {
        setColorId('');
        setQty(1);
        setImage(product.image_url);

        const options = variants.filter((variant) => colorKey(variant) === '');
        const pick = options.find((variant) => variant.available) ?? options[0] ?? null;

        setSize(pick?.size ?? '');
    };

    const chooseSize = (option) => {
        if (option.sold_out) {
            return;
        }

        setSize(option.name);
        setQty(1);
    };

    const add = (go) => {
        if (hasVariants && !selectedVariant) {
            return;
        }

        router.post(`/cart/add/${product.id}`, {
            variant_id: hasVariants ? selectedVariant.id : undefined,
            size: hasVariants ? selectedVariant.size : size,
            qty,
            go,
        }, {
            preserveScroll: go !== 'buy',
        });
    };

    const reviewForm = useForm({
        name: '',
        text: '',
        stars: 5,
        product_id: product.id,
    });

    const submitReview = (event) => {
        event.preventDefault();
        reviewForm.post('/reviews', {
            preserveScroll: true,
            onSuccess: () => reviewForm.reset('text'),
        });
    };

    return (
        <>
            <Head title={product.name}>
                <meta name="description" content={product.description || product.name} />
                <script type="application/ld+json">{JSON.stringify({
                    '@context': 'https://schema.org',
                    '@type': 'Product',
                    name: product.name,
                    description: product.description,
                    category: product.cat,
                    offers: {
                        '@type': 'Offer',
                        priceCurrency: 'BDT',
                        price: product.price,
                        availability: product.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    },
                })}</script>
            </Head>

            <section className="max-w-6xl mx-auto px-4 py-8">
                <Link href="/shop" className="text-sm text-pur font-semibold">
                    ← Back to shop
                </Link>

                <div className="grid md:grid-cols-2 gap-8 mt-4">
                    <div>
                        <div
                            className="relative rounded-2xl outline-none focus:ring-2 focus:ring-purple-200"
                            tabIndex={0}
                            onKeyDown={(event) => {
                                if (event.key === 'ArrowLeft') {
                                    event.preventDefault();
                                    previousImage();
                                }

                                if (event.key === 'ArrowRight') {
                                    event.preventDefault();
                                    nextImage();
                                }
                            }}
                        >
                            <img src={image} alt={product.name} className="w-full aspect-square object-cover rounded-2xl shadow" />

                            {showImageArrows && (
                                <>
                                    <button
                                        type="button"
                                        onClick={previousImage}
                                        aria-label="Previous image"
                                        className="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl font-bold text-gray-700 shadow-md ring-1 ring-gray-200 transition hover:bg-white"
                                    >
                                        ‹
                                    </button>
                                    <button
                                        type="button"
                                        onClick={nextImage}
                                        aria-label="Next image"
                                        className="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-xl font-bold text-gray-700 shadow-md ring-1 ring-gray-200 transition hover:bg-white"
                                    >
                                        ›
                                    </button>
                                    <span className="absolute bottom-3 right-3 rounded-full bg-black/50 px-2.5 py-1 text-[11px] font-semibold text-white">
                                        {imageIndex + 1} / {galleryImages.length}
                                    </span>
                                </>
                            )}
                        </div>

                        {galleryImages.length > 1 && (
                            <div className="flex gap-2 mt-3 flex-wrap">
                                {galleryImages.map((src) => (
                                    <button key={src} type="button" onClick={() => setImage(src)} className="w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent hover:border-pur">
                                        <img src={src} alt="" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <div>
                        <div className="flex gap-2 flex-wrap mb-2">
                            <span className="inline-block text-xs font-bold bg-white border border-gold text-gold rounded-full px-3 py-1">{product.cat}</span>
                            <span className="inline-block text-xs font-bold bg-white border rounded-full px-3 py-1">🚚 COD · All Bangladesh</span>
                        </div>
                        <h1 className="font-serif text-4xl md:text-5xl font-bold mt-3">{product.name}</h1>
                        {product.sub && <p className="font-serif italic text-3xl gtext">{product.sub}</p>}
                        <p className="text-gray-600 mt-3">{product.description}</p>

                        {product.features.length > 0 && (
                            <div className="flex gap-4 flex-wrap my-5">
                                {product.features.map((feature) => (
                                    <div key={feature} className="w-20 text-center text-xs font-semibold">
                                        <div className="w-14 h-14 mx-auto mb-1 rounded-full border-2 border-gold bg-amber-50 flex items-center justify-center text-gold text-xl">✦</div>
                                        {feature}
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="flex items-baseline gap-3 my-4">
                            <b className="text-2xl">৳{Number(product.price).toLocaleString()}</b>
                            {product.old_price > 0 && <s className="text-gray-400">৳{Number(product.old_price).toLocaleString()}</s>}
                            {product.off > 0 && <span className="badge badge-dark">-{product.off}%</span>}
                        </div>

                        {out ? (
                            <p className="w-full text-center rounded-xl py-3 font-semibold bg-gray-200 text-gray-500">Out of stock</p>
                        ) : (
                            <>
                                {hasVariants && colors.length > 0 && (
                                    <div className="mb-4">
                                        <div className="text-sm font-semibold mb-1">Color</div>
                                        <div className="flex gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                onClick={chooseDefault}
                                                title="Show all product images"
                                                className={`flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-semibold transition ${
                                                    !activeColor ? 'border-purple-600 text-purple-700 bg-purple-50' : 'border-gray-200 bg-white'
                                                }`}
                                            >
                                                <span className="h-4 w-4 rounded-full border border-dashed border-gray-400 bg-white" />
                                                Default
                                            </button>
                                            {colors.map((color) => {
                                                const key = String(color.id);
                                                const selected = colorId === key;

                                                return (
                                                    <button
                                                        key={color.id}
                                                        type="button"
                                                        disabled={!color.available}
                                                        onClick={() => chooseColor(key)}
                                                        className={`flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-semibold transition ${
                                                            selected ? 'border-purple-600 text-purple-700 bg-purple-50' : 'border-gray-200 bg-white'
                                                        } ${!color.available ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                    >
                                                        <span
                                                            className="w-4 h-4 rounded-full border border-gray-300"
                                                            style={{ backgroundColor: color.hex || '#d1d5db' }}
                                                        />
                                                        {color.name}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {hasVariants && colors.length > 0 && !activeColor && colorVariants.length === 0 && (
                                    <p className="mb-4 rounded-xl bg-purple-50 px-4 py-3 text-sm font-semibold text-purple-800">
                                        Select a color to see its images and sizes.
                                    </p>
                                )}

                                {displaySizes.length > 0 && showSizeSelector && (
                                    <div className="mb-4">
                                        <div className="text-sm font-semibold mb-1">
                                            Size{hasVariants && activeColor ? ` — ${activeColor.name}` : ''}
                                        </div>
                                        <div className="flex gap-2 flex-wrap">
                                            {displaySizes.map((option) => {
                                                const selected = size === option.name;
                                                const disabled = option.sold_out;

                                                return (
                                                    <button
                                                        key={option.name}
                                                        type="button"
                                                        disabled={disabled}
                                                        onClick={() => chooseSize(option)}
                                                        className={`px-3 py-2 rounded-lg border text-sm font-semibold text-left transition ${
                                                            disabled
                                                                ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                                                : selected
                                                                    ? 'bg-purple-600 text-white border-purple-600'
                                                                    : option.low
                                                                        ? 'bg-amber-50 text-amber-800 border-amber-300'
                                                                        : 'bg-white border-gray-200'
                                                        }`}
                                                    >
                                                        <span className={`block ${disabled ? 'line-through' : ''}`}>{option.name}</span>
                                                        <span className={`block text-[10px] font-medium ${
                                                            selected
                                                                ? 'text-white/80'
                                                                : disabled
                                                                    ? 'text-gray-400'
                                                                    : option.low
                                                                        ? 'text-amber-700'
                                                                        : 'text-gray-400'
                                                        }`}>
                                                            {disabled
                                                                ? 'Sold out'
                                                                : option.stock !== null
                                                                    ? (option.low ? `Only ${option.stock} left` : `${option.stock} in stock`)
                                                                    : 'In stock'}
                                                        </span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {readyToAdd ? (
                                    <>
                                        <div className="flex items-center gap-3 mb-4">
                                            <span className="text-sm font-semibold">Quantity</span>
                                            <div className="flex items-center gap-2">
                                                <button type="button" className="w-8 h-8 rounded-full border" disabled={qty <= 1 || selectedSoldOut} onClick={() => setQty(Math.max(1, qty - 1))}>−</button>
                                                <span className="w-8 text-center">{qty}</span>
                                                <button type="button" className="w-8 h-8 rounded-full border" disabled={qty >= maxQty || selectedSoldOut} onClick={() => setQty(Math.min(maxQty, qty + 1))}>+</button>
                                            </div>
                                            {lowStock && (
                                                <span className="text-sm text-mag font-semibold">
                                                    {selectedOption?.stock !== null && selectedOption?.stock !== undefined
                                                        ? `Only ${selectedOption.stock} left`
                                                        : `${product.stock} left`}
                                                </span>
                                            )}
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => add('bag')}
                                            disabled={out || selectedSoldOut}
                                            className="w-full border border-gray-400 rounded-xl py-3 font-semibold bg-white disabled:opacity-50"
                                        >
                                            👉 Add to bag
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => add('buy')}
                                            disabled={out || selectedSoldOut}
                                            className="w-full mt-3 rounded-xl py-3 font-semibold bg-black text-white disabled:opacity-50"
                                        >
                                            Buy it now
                                        </button>
                                    </>
                                ) : (
                                    <p className="rounded-xl bg-purple-50 px-4 py-3 text-sm font-semibold text-purple-800">
                                        {hasVariants && colors.length > 0 && !activeColor
                                            ? 'Select a color and size to continue.'
                                            : 'Select a size to continue.'}
                                    </p>
                                )}
                            </>
                        )}

                        <details className="border-t mt-6">
                            <summary className="py-4 font-semibold cursor-pointer">🚚 Shipping</summary>
                            <p className="pb-4 text-gray-600 text-sm">Delivery within 3 days all over Bangladesh. The charge depends on your delivery area and is shown at checkout.</p>
                        </details>
                        <details className="border-t border-b">
                            <summary className="py-4 font-semibold cursor-pointer">↩️ Return &amp; Refund Policy</summary>
                            <p className="pb-4 text-gray-600 text-sm">If you receive a damaged or wrong item, contact us within 3 days of delivery for a replacement or refund.</p>
                        </details>
                    </div>
                </div>

                {related.length > 0 && (
                    <div className="mt-10">
                        <h2 className="font-serif text-3xl mb-4">Related products</h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {related.map((item) => <ProductCard key={item.id} product={item} />)}
                        </div>
                    </div>
                )}

                <div className="mt-10 bg-white rounded-2xl p-5">
                    <h2 className="font-serif text-2xl mb-3">Customer reviews</h2>
                    {reviews.length > 0 ? (
                        <div className="grid md:grid-cols-2 gap-4 mb-5">
                            {reviews.map((review) => (
                                <div key={review.id} className="border border-gray-100 rounded-xl p-4">
                                    <div className="text-org">{'★'.repeat(review.stars)}</div>
                                    <p className="my-1 text-sm">{review.text}</p>
                                    <b className="text-xs text-gray-500">{review.name}</b>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500 mb-5">No approved reviews for this product yet.</p>
                    )}

                    <form onSubmit={submitReview} className="border-t border-gray-100 pt-4">
                        <h3 className="font-semibold mb-2">Write a review</h3>
                        <div className="grid md:grid-cols-2 gap-3">
                            <input className="inp" value={reviewForm.data.name} onChange={(event) => reviewForm.setData('name', event.target.value)} placeholder="Your name" />
                            <div className="inp flex items-center justify-between gap-2">
                                <span className="text-sm font-semibold">Rating</span>
                                <StarRating value={reviewForm.data.stars} onChange={(stars) => reviewForm.setData('stars', stars)} />
                            </div>
                        </div>
                        <textarea className="inp mt-3" rows="3" value={reviewForm.data.text} onChange={(event) => reviewForm.setData('text', event.target.value)} placeholder="Your review" required />
                        {reviewForm.errors.text && <div className="text-xs text-red-600">{reviewForm.errors.text}</div>}
                        <button className="btn mt-3" disabled={reviewForm.processing}>Submit review</button>
                        <div className="text-xs text-gray-400 mt-2">Reviews appear after approval.</div>
                    </form>
                </div>
            </section>
        </>
    );
}

Product.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default Product;
