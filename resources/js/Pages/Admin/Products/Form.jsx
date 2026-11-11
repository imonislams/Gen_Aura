import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function ProductForm({ product, categories }) {
    const editing = !!product;
    const { settings } = usePage().props;
    const threshold = Number(settings.low_stock_threshold ?? 5);

    const form = useForm({
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        sku: product?.sku ?? '',
        category_id: product?.category_id ?? '',
        cat: product?.cat ?? '',
        sub: product?.sub ?? '',
        sizes: product?.sizes ?? '',
        feat: product?.feat ?? '',
        description: product?.description ?? '',
        price: product?.price ?? '',
        old_price: product?.old_price ?? 0,
        stock: product?.stock ?? 10,
        size_stock: product?.size_stock ?? {},
        status: product?.status ?? 'active',
        featured: product?.featured ? 1 : 0,
        image: null,
        images: [],
        remove_images: [],
        _method: editing ? 'put' : undefined,
    });

    const sizeList = Array.from(new Set((form.data.sizes || '').split(',').map((size) => size.trim()).filter(Boolean)));
    const sizeStock = form.data.size_stock || {};
    const sizeConfigured = Object.keys(sizeStock).length > 0;
    const totalFromSizes = sizeList.reduce((sum, size) => sum + (Number(sizeStock[size]) || 0), 0);
    const totalDisplay = sizeList.length > 0
        ? (sizeConfigured ? totalFromSizes : Number(form.data.stock || 0))
        : Number(form.data.stock || 0);

    const updateSizes = (value) => {
        const nextList = Array.from(new Set(value.split(',').map((size) => size.trim()).filter(Boolean)));

        if (nextList.join('|') === sizeList.join('|') && !sizeConfigured) {
            form.setData({ ...form.data, sizes: value, size_stock: {} });

            return;
        }

        const nextStock = {};
        nextList.forEach((size) => {
            nextStock[size] = Number(sizeStock[size]) || 0;
        });

        form.setData({
            ...form.data,
            sizes: value,
            size_stock: nextStock,
            stock: nextList.length ? Object.values(nextStock).reduce((sum, qty) => sum + Number(qty || 0), 0) : form.data.stock,
        });
    };

    const updateSizeStock = (size, value) => {
        const next = { ...sizeStock, [size]: Math.max(0, Number(value) || 0) };

        form.setData({
            ...form.data,
            size_stock: next,
            stock: sizeList.reduce((sum, item) => sum + Number(next[item] || 0), 0),
        });
    };

    const distributeTotal = () => {
        if (!sizeList.length) {
            return;
        }

        const total = Math.max(0, Number(form.data.stock) || 0);
        const base = Math.floor(total / sizeList.length);
        const remainder = total % sizeList.length;
        const next = {};

        sizeList.forEach((size, index) => {
            next[size] = base + (index < remainder ? 1 : 0);
        });

        form.setData({ ...form.data, size_stock: next, stock: total });
    };

    const toggleRemove = (path) => {
        const current = form.data.remove_images;
        form.setData('remove_images', current.includes(path) ? current.filter((item) => item !== path) : [...current, path]);
    };

    const submit = (event) => {
        event.preventDefault();
        form.post(editing ? `/admin/products/${product.id}` : '/admin/products', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={editing ? `Edit ${product.name}` : 'Add product'} />

            <div className="mb-5">
                <Link href="/admin/products" className="text-sm text-pur font-semibold">← Products</Link>
                <h1 className="font-serif text-3xl font-bold mt-1">{editing ? `Edit ${product.name}` : 'Add product'}</h1>
            </div>

            <form onSubmit={submit} className="bg-white rounded-2xl p-5 grid md:grid-cols-2 gap-x-5">
                <label className="block text-sm mb-3">
                    Name
                    <input className="inp" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required />
                    {form.errors.name && <span className="text-xs text-red-600">{form.errors.name}</span>}
                </label>
                <label className="block text-sm mb-3">
                    Slug (optional)
                    <input className="inp" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} placeholder="auto from name" />
                </label>
                <label className="block text-sm mb-3">
                    SKU (optional)
                    <input className="inp" value={form.data.sku} onChange={(event) => form.setData('sku', event.target.value)} placeholder="e.g. GA-TS-001" />
                </label>
                <label className="block text-sm mb-3">
                    Category
                    <select className="inp" value={form.data.category_id ?? ''} onChange={(event) => form.setData('category_id', event.target.value)}>
                        <option value="">Uncategorised</option>
                        {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                    </select>
                </label>
                <label className="block text-sm mb-3">
                    Price (৳)
                    <input type="number" min="0" className="inp" value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} required />
                </label>
                <label className="block text-sm mb-3">
                    Old price (৳)
                    <input type="number" min="0" className="inp" value={form.data.old_price} onChange={(event) => form.setData('old_price', event.target.value)} />
                </label>
                {sizeList.length === 0 ? (
                    <label className="block text-sm mb-3">
                        Stock
                        <input type="number" min="0" className="inp" value={form.data.stock} onChange={(event) => form.setData('stock', event.target.value)} required />
                    </label>
                ) : (
                    <div className="md:col-span-2 mb-3 rounded-xl border border-gray-100 p-3">
                        <div className="flex items-center justify-between gap-2">
                            <div className="text-sm font-semibold">Size stock</div>
                            <div className="text-xs text-gray-500">
                                Total: <b>{totalDisplay}</b>{sizeConfigured ? ' (auto)' : ''}
                            </div>
                        </div>

                        {!sizeConfigured && (
                            <div className="mt-2 rounded-lg bg-amber-50 p-2 text-[11px] text-amber-800">
                                Size stock not configured. Current total stock: {form.data.stock}. Distribute it or enter quantities.
                            </div>
                        )}

                        <div className="mt-2 grid gap-2 md:grid-cols-2">
                            {sizeList.map((size) => {
                                const qty = Number(sizeStock[size] || 0);
                                const status = !sizeConfigured ? 'Not set' : qty <= 0 ? 'Sold out' : qty <= threshold ? 'Low stock' : 'In stock';
                                const color = !sizeConfigured
                                    ? 'text-gray-400'
                                    : qty <= 0
                                        ? 'text-red-600'
                                        : qty <= threshold
                                            ? 'text-amber-700'
                                            : 'text-green-700';

                                return (
                                    <div key={size} className="flex items-center gap-2">
                                        <span className="w-12 text-sm font-semibold">{size}</span>
                                        <input
                                            type="number"
                                            min="0"
                                            className="inp !mt-0"
                                            value={sizeConfigured ? qty : ''}
                                            onChange={(event) => updateSizeStock(size, event.target.value)}
                                            placeholder="0"
                                        />
                                        <span className={`w-20 text-[11px] font-semibold ${color}`}>{status}</span>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="mt-2">
                            <button type="button" className="text-xs font-semibold text-pur" onClick={distributeTotal}>
                                Distribute existing total
                            </button>
                        </div>
                    </div>
                )}
                <label className="block text-sm mb-3">
                    Status
                    <select className="inp" value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label className="block text-sm mb-3">
                    Sizes (comma separated)
                    <input className="inp" value={form.data.sizes} onChange={(event) => updateSizes(event.target.value)} placeholder="M,L,XL" />
                    <span className="text-[10px] text-gray-400">Adding or removing sizes updates the size stock rows.</span>
                </label>
                <label className="block text-sm mb-3">
                    Subtitle
                    <input className="inp" value={form.data.sub} onChange={(event) => form.setData('sub', event.target.value)} />
                </label>
                <label className="block text-sm mb-3 md:col-span-2">
                    Highlights (comma separated)
                    <input className="inp" value={form.data.feat} onChange={(event) => form.setData('feat', event.target.value)} />
                </label>
                <label className="block text-sm mb-3 md:col-span-2">
                    Description
                    <textarea className="inp" rows="3" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                </label>
                <label className="text-sm flex items-center gap-2 mb-3 md:col-span-2">
                    <input type="checkbox" checked={!!form.data.featured} onChange={(event) => form.setData('featured', event.target.checked ? 1 : 0)} />
                    Featured product
                </label>

                <label className="block text-sm mb-3">
                    Main image
                    <input type="file" accept="image/*" className="mt-1 text-sm" onChange={(event) => form.setData('image', event.target.files[0])} />
                </label>
                <label className="block text-sm mb-3">
                    More images (select many)
                    <input type="file" accept="image/*" multiple className="mt-1 text-sm" onChange={(event) => form.setData('images', Array.from(event.target.files))} />
                </label>

                {editing && (
                    <div className="md:col-span-2 flex gap-3 flex-wrap mb-3">
                        <div className="text-center">
                            <img src={product.image_url} alt="" className="w-16 h-16 rounded object-cover ring-2 ring-purple-300" />
                            <div className="text-xs mt-1">Main</div>
                        </div>
                        {product.images.map((path) => (
                            <label key={path} className="text-xs text-center">
                                <img src={`/storage/${path}`} alt="" className="w-16 h-16 rounded object-cover" />
                                <div>
                                    <input type="checkbox" checked={form.data.remove_images.includes(path)} onChange={() => toggleRemove(path)} /> remove
                                </div>
                            </label>
                        ))}
                    </div>
                )}

                <div className="flex gap-2 md:col-span-2">
                    <button className="btn" disabled={form.processing}>{form.processing ? 'Saving…' : 'Save'}</button>
                    <Link href="/admin/products" className="px-4 py-2 border rounded-full">Cancel</Link>
                </div>
            </form>
        </>
    );
}

ProductForm.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default ProductForm;
