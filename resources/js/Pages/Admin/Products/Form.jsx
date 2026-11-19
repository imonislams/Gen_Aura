import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';

const inputCls = 'inp';
const tableInput = 'border border-purple-100 rounded px-2 py-1.5 text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-purple-200';
const primaryBtn = 'btn disabled:opacity-50';
const secondaryBtn = 'px-3 py-2 rounded-full border border-purple-200 bg-white text-sm font-semibold text-gray-600 hover:bg-purple-50 disabled:opacity-50';
const dangerBtn = 'text-xs font-semibold text-red-500 hover:text-red-600';
const labelCls = 'mb-1 block text-sm text-gray-700';

const parseSizes = (value) => Array.from(new Set(String(value || '').split(',').map((size) => size.trim()).filter(Boolean)));
const uniqueSizes = (list) => Array.from(new Set(list.map((size) => String(size || '').trim()).filter(Boolean)));

const COLOR_CHOICES = [
    { name: 'Black', hex: '#000000' },
    { name: 'White', hex: '#FFFFFF' },
    { name: 'Charcoal', hex: '#374151' },
    { name: 'Grey', hex: '#6B7280' },
    { name: 'Silver', hex: '#C0C0C0' },
    { name: 'Ash', hex: '#B2BEB5' },
    { name: 'Red', hex: '#DC2626' },
    { name: 'Maroon', hex: '#7F1D1D' },
    { name: 'Wine', hex: '#722F37' },
    { name: 'Pink', hex: '#EC4899' },
    { name: 'Rose', hex: '#F43F5E' },
    { name: 'Orange', hex: '#EA580C' },
    { name: 'Coral', hex: '#FF7F50' },
    { name: 'Peach', hex: '#FFCBA4' },
    { name: 'Gold', hex: '#D4AF37' },
    { name: 'Yellow', hex: '#EAB308' },
    { name: 'Mustard', hex: '#D97706' },
    { name: 'Olive', hex: '#556B2F' },
    { name: 'Lime', hex: '#84CC16' },
    { name: 'Green', hex: '#16A34A' },
    { name: 'Mint', hex: '#98FF98' },
    { name: 'Teal', hex: '#0F766E' },
    { name: 'Turquoise', hex: '#14B8A6' },
    { name: 'Cyan', hex: '#06B6D4' },
    { name: 'Sky Blue', hex: '#0EA5E9' },
    { name: 'Blue', hex: '#2563EB' },
    { name: 'Denim', hex: '#1560BD' },
    { name: 'Navy Blue', hex: '#1E3A8A' },
    { name: 'Indigo', hex: '#4F46E5' },
    { name: 'Purple', hex: '#7C3AED' },
    { name: 'Violet', hex: '#8B5CF6' },
    { name: 'Lavender', hex: '#E6E6FA' },
    { name: 'Magenta', hex: '#D946EF' },
    { name: 'Brown', hex: '#78350F' },
    { name: 'Coffee', hex: '#6F4E37' },
    { name: 'Chocolate', hex: '#7B3F00' },
    { name: 'Tan', hex: '#D2B48C' },
    { name: 'Khaki', hex: '#C3B091' },
    { name: 'Beige', hex: '#F5F5DC' },
    { name: 'Cream', hex: '#FFFDD0' },
    { name: 'Ivory', hex: '#FFFFF0' },
];

const normalizeColorName = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]/g, '');

const hexToRgb = (hex) => {
    const value = String(hex || '').replace('#', '');

    if (value.length !== 6) {
        return null;
    }

    return {
        r: parseInt(value.slice(0, 2), 16),
        g: parseInt(value.slice(2, 4), 16),
        b: parseInt(value.slice(4, 6), 16),
    };
};

const hslToHex = (hue, saturation, lightness) => {
    const s = saturation / 100;
    const l = lightness / 100;
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs(((hue / 60) % 2) - 1));
    const m = l - c / 2;
    let r = 0;
    let g = 0;
    let b = 0;

    if (hue < 60) {
        r = c;
        g = x;
    } else if (hue < 120) {
        r = x;
        g = c;
    } else if (hue < 180) {
        g = c;
        b = x;
    } else if (hue < 240) {
        g = x;
        b = c;
    } else if (hue < 300) {
        r = x;
        b = c;
    } else {
        r = c;
        b = x;
    }

    const part = (value) => Math.round((value + m) * 255).toString(16).padStart(2, '0');

    return `#${part(r)}${part(g)}${part(b)}`.toUpperCase();
};

const hexForColorName = (name) => {
    const key = normalizeColorName(name);

    if (!key) {
        return null;
    }

    const exact = COLOR_CHOICES.find((choice) => normalizeColorName(choice.name) === key);

    if (exact) {
        return exact.hex;
    }

    const partial = COLOR_CHOICES.find((choice) => {
        const choiceKey = normalizeColorName(choice.name);

        return key.includes(choiceKey) || choiceKey.includes(key);
    });

    if (partial) {
        return partial.hex;
    }

    let hash = 0;

    for (let index = 0; index < key.length; index += 1) {
        hash = (hash * 31 + key.charCodeAt(index)) % 360;
    }

    return hslToHex(hash, 62, 42);
};

const nameForHex = (hex) => {
    const target = hexToRgb(hex);

    if (!target) {
        return null;
    }

    let best = null;
    let bestDistance = Infinity;

    COLOR_CHOICES.forEach((choice) => {
        const rgb = hexToRgb(choice.hex);

        if (!rgb) {
            return;
        }

        const distance = ((rgb.r - target.r) ** 2) + ((rgb.g - target.g) ** 2) + ((rgb.b - target.b) ** 2);

        if (distance < bestDistance) {
            bestDistance = distance;
            best = choice.name;
        }
    });

    return best;
};

function Section({ number, title, description, actions, children }) {
    return (
        <section className="rounded-2xl border border-purple-100 bg-white shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div className="flex items-start gap-3">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg grad text-xs font-bold text-white">
                        {number}
                    </span>
                    <div>
                        <h2 className="text-sm font-bold text-gray-900">{title}</h2>
                        {description && <p className="mt-0.5 text-xs text-gray-500">{description}</p>}
                    </div>
                </div>
                {actions}
            </div>
            <div className="p-5">{children}</div>
        </section>
    );
}

function Field({ label, hint, error, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className={labelCls}>{label}</span>
            {children}
            {hint && <span className="mt-1 block text-[11px] text-gray-400">{hint}</span>}
            {error && <span className="mt-1 block text-[11px] font-semibold text-red-600">{error}</span>}
        </label>
    );
}

function FilePreview({ file, className = 'h-14 w-14 rounded-lg object-cover ring-1 ring-purple-100' }) {
    const [url, setUrl] = useState(null);

    useEffect(() => {
        const objectUrl = URL.createObjectURL(file);
        setUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);

    return url ? <img src={url} alt="" className={className} /> : null;
}

function Stat({ label, value, hint }) {
    return (
        <div className="rounded-xl border border-purple-100 bg-white p-4">
            <div className="text-[11px] font-bold uppercase tracking-wide text-gray-500">{label}</div>
            <div className="mt-1 text-2xl font-bold tracking-tight gtext">{value}</div>
            {hint && <div className="mt-0.5 text-[11px] text-gray-400">{hint}</div>}
        </div>
    );
}

function ProductForm({ product, categories }) {
    const editing = !!product;
    const { settings } = usePage().props;
    const threshold = Number(settings.low_stock_threshold ?? 5);
    const keySeq = useRef(0);

    const initialVariants = (product?.variants ?? []).map((variant) => ({
        id: variant.id,
        color_key: variant.color_key ?? '',
        size: variant.size ?? '',
        stock: variant.stock ?? 0,
        sku: variant.sku ?? '',
        barcode: variant.barcode ?? '',
        status: variant.status ?? 'active',
        has_sales: !!variant.has_sales,
    }));

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
        stock: product?.stock ?? 0,
        size_stock: product?.size_stock ?? {},
        has_variants: product ? (product.has_variants ? 1 : 0) : 1,
        colors: (product?.colors ?? []).map((color) => ({
            key: String(color.id),
            id: color.id,
            name: color.name,
            hex: color.hex ?? '',
            images: color.images ?? [],
            new_images: [],
            status: color.status ?? 'active',
        })),
        variants: initialVariants,
        removed_variant_ids: [],
        status: product?.status ?? 'active',
        featured: product?.featured ? 1 : 0,
        image: null,
        images: [],
        remove_images: [],
        _method: editing ? 'put' : undefined,
    });

    const [showCodes, setShowCodes] = useState(false);
    const [commonSizesInput, setCommonSizesInput] = useState(
        product?.sizes || uniqueSizes(initialVariants.map((variant) => variant.size)).join(',') || 'S,M,L,XL,XXL'
    );
    const [sizeDrafts, setSizeDrafts] = useState({});
    const [copySources, setCopySources] = useState({});
    const [addColorSource, setAddColorSource] = useState('');

    const hasVariants = !!form.data.has_variants;
    const colors = form.data.colors || [];
    const variants = form.data.variants || [];
    const commonSizes = parseSizes(commonSizesInput);

    const sizeList = parseSizes(form.data.sizes || '');
    const sizeStock = form.data.size_stock || {};
    const sizeConfigured = Object.keys(sizeStock).length > 0;
    const legacyTotal = sizeList.reduce((sum, size) => sum + (Number(sizeStock[size]) || 0), 0);

    const activeColors = colors.filter((color) => color.status === 'active');
    const inactiveColors = colors.filter((color) => color.status !== 'active');
    const hasColorless = variants.some((variant) => (variant.color_key || '') === '');

    const cards = [
        ...(hasColorless
            ? [{ key: '__default__', variantKey: '', name: 'Default sizes (no color)', hex: '', image_url: null, status: 'active', isDefault: true }]
            : []),
        ...activeColors.map((color) => ({ ...color, variantKey: color.key })),
    ];

    const variantRowsFor = (variantKey) => variants
        .map((variant, index) => ({ variant, index }))
        .filter(({ variant }) => (variant.color_key || '') === variantKey);

    const isVariantActive = (variant) => {
        if (variant.status !== 'active') {
            return false;
        }

        const key = variant.color_key || '';

        if (key === '') {
            return true;
        }

        const color = colors.find((item) => item.key === key);

        return !color || color.status === 'active';
    };

    const colorTotal = (variantKey) => variants
        .filter((variant) => (variant.color_key || '') === variantKey && isVariantActive(variant))
        .reduce((sum, variant) => sum + (Number(variant.stock) || 0), 0);

    const rawColorTotal = (variantKey) => variants
        .filter((variant) => (variant.color_key || '') === variantKey)
        .reduce((sum, variant) => sum + (Number(variant.stock) || 0), 0);

    const submittedColors = colors.filter((color) => (
        color.id
        || String(color.name || '').trim() !== ''
        || variants.some((variant) => (variant.color_key || '') === color.key)
    ));

    const colorNameError = (key) => {
        const index = submittedColors.findIndex((color) => color.key === key);

        return index >= 0 ? form.errors[`colors.${index}.name`] : null;
    };

    const activeVariants = variants.filter(isVariantActive);
    const totalStock = activeVariants.reduce((sum, variant) => sum + (Number(variant.stock) || 0), 0);
    const totalVariants = activeVariants.length;
    const sizeTotals = activeVariants.reduce((totals, variant) => {
        const size = String(variant.size || '').trim();

        if (size) {
            totals[size] = (totals[size] || 0) + (Number(variant.stock) || 0);
        }

        return totals;
    }, {});

    const newVariant = (colorKey, size) => ({
        id: null,
        color_key: colorKey || '',
        size: size || '',
        stock: 0,
        sku: '',
        barcode: '',
        status: 'active',
        has_sales: false,
    });

    const updateVariant = (index, field, value) => {
        form.setData('variants', variants.map((variant, position) => (
            position === index ? { ...variant, [field]: value } : variant
        )));
    };

    const updateColorFields = (key, fields) => {
        form.setData('colors', colors.map((color) => (
            color.key === key ? { ...color, ...fields } : color
        )));
    };

    const updateColor = (key, field, value) => updateColorFields(key, { [field]: value });

    const updateColorName = (key, name) => {
        const hex = hexForColorName(name);
        updateColorFields(key, hex ? { name, hex } : { name });
    };

    const updateColorSwatch = (key, hex) => {
        const color = colors.find((item) => item.key === key);
        const autoName = String(color?.name || '').trim() === '' ? nameForHex(hex) : null;

        updateColorFields(key, autoName ? { hex, name: autoName } : { hex });
    };

    const addColorImages = (key, fileList) => {
        const color = colors.find((item) => item.key === key);
        const files = Array.from(fileList || []);

        if (!color || !files.length) {
            return;
        }

        updateColor(key, 'new_images', [...(color.new_images || []), ...files]);
    };

    const removeColorImage = (key, path) => {
        const color = colors.find((item) => item.key === key);

        updateColor(key, 'images', (color?.images || []).filter((item) => item !== path));
    };

    const removeNewColorImage = (key, index) => {
        const color = colors.find((item) => item.key === key);

        updateColor(key, 'new_images', (color?.new_images || []).filter((_, position) => position !== index));
    };

    const addColor = () => {
        const key = `new-${++keySeq.current}`;
        const color = {
            key,
            id: null,
            name: '',
            hex: '',
            images: [],
            new_images: [],
            status: 'active',
        };

        let additions = [];

        if (addColorSource !== '') {
            const sourceKey = addColorSource === '__default__' ? '' : addColorSource;
            additions = uniqueSizes(
                variants.filter((variant) => (variant.color_key || '') === sourceKey).map((variant) => variant.size)
            ).map((size) => newVariant(key, size));
        }

        form.setData({
            ...form.data,
            colors: [...colors, color],
            variants: [...variants, ...additions],
        });
        setAddColorSource('');
    };

    const removeColor = (key) => {
        const color = colors.find((item) => item.key === key);

        if (!window.confirm(`Remove ${color?.name || 'this color'}? The color will be hidden from the storefront. Stock and order history are kept.`)) {
            return;
        }

        updateColor(key, 'status', 'disabled');
    };

    const restoreColor = (key) => {
        updateColor(key, 'status', 'active');
    };

    const addSizeToColor = (colorKey, size) => {
        const value = String(size || '').trim();

        if (!value) {
            return;
        }

        const exists = variants.some((variant) => (
            (variant.color_key || '') === colorKey
            && String(variant.size || '').trim().toLowerCase() === value.toLowerCase()
        ));

        if (exists) {
            return;
        }

        form.setData('variants', [...variants, newVariant(colorKey, value)]);
    };

    const copySizesTo = (targetKey, sourceKey) => {
        const sourceSizes = uniqueSizes(
            variants.filter((variant) => (variant.color_key || '') === sourceKey).map((variant) => variant.size)
        );
        const existing = new Set(
            variants
                .filter((variant) => (variant.color_key || '') === targetKey)
                .map((variant) => String(variant.size || '').trim().toLowerCase())
        );
        const additions = sourceSizes
            .filter((size) => !existing.has(size.toLowerCase()))
            .map((size) => newVariant(targetKey, size));

        if (additions.length) {
            form.setData('variants', [...variants, ...additions]);
        }
    };

    const removeSize = (index) => {
        const variant = variants[index];

        if (!variant) {
            return;
        }

        const label = [variant.size || 'size', 'variant'].join(' ');
        const stock = Number(variant.stock) || 0;

        if (variant.id || stock > 0) {
            const message = variant.has_sales
                ? `${label} has sales history. It will be disabled instead of deleted. Continue?`
                : stock > 0
                    ? `${label} has ${stock} pcs in stock. Remove it anyway?`
                    : `Remove ${label}?`;

            if (!window.confirm(message)) {
                return;
            }
        }

        form.setData({
            ...form.data,
            variants: variants.filter((_, position) => position !== index),
            removed_variant_ids: variant.id
                ? [...form.data.removed_variant_ids, variant.id]
                : form.data.removed_variant_ids,
        });
    };

    const applyCommonSizes = () => {
        if (!commonSizes.length || !activeColors.length) {
            return;
        }

        const additions = [];

        activeColors.forEach((color) => {
            commonSizes.forEach((size) => {
                const exists = variants.some((variant) => (
                    (variant.color_key || '') === color.key
                    && String(variant.size || '').trim().toLowerCase() === size.toLowerCase()
                ));

                if (!exists) {
                    additions.push(newVariant(color.key, size));
                }
            });
        });

        if (additions.length) {
            form.setData('variants', [...variants, ...additions]);
        }
    };

    const toggleVariants = (checked) => {
        if (!checked) {
            form.setData('has_variants', 0);

            return;
        }

        let nextVariants = variants;

        if (nextVariants.length === 0 && sizeList.length > 0) {
            nextVariants = sizeList.map((size) => ({
                ...newVariant('', size),
                stock: Number(sizeStock[size]) || 0,
            }));
        }

        form.setData({ ...form.data, has_variants: 1, variants: nextVariants });
    };

    const updateSizes = (value) => {
        const nextList = parseSizes(value);

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

    const variantError = (index, field) => form.errors[`variants.${index}.${field}`];

    const submit = (event) => {
        event.preventDefault();

        const variantSizeUnion = uniqueSizes(variants.map((variant) => variant.size)).join(',');

        form.transform((data) => (
            data.has_variants
                ? { ...data, sizes: variantSizeUnion, size_stock: {}, stock: totalStock }
                : { ...data, colors: [], variants: [], removed_variant_ids: [] }
        ));

        form.post(editing ? `/admin/products/${product.id}` : '/admin/products', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={editing ? `Edit ${product.name}` : 'Add product'} />

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-5">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <Link href="/admin/products" className="text-sm font-semibold text-pur">
                            ← Products
                        </Link>
                        <h1 className="mt-1 font-serif text-3xl font-bold">
                            {editing ? `Edit ${product.name}` : 'Add product'}
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {editing ? 'Update product details, colors, sizes and stock.' : 'Create a product with color and size wise stock.'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/admin/products" className={secondaryBtn}>Cancel</Link>
                        <button type="submit" className={primaryBtn} disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save product'}
                        </button>
                    </div>
                </div>

                <Section number="01" title="Basic Information" description="The product identity and pricing shown across the website.">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Product name" error={form.errors.name}>
                            <input className={inputCls} value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required />
                        </Field>
                        <Field label="Slug (optional)" hint="Leave blank to generate automatically from the product name.">
                            <input className={inputCls} value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value)} placeholder="auto from name" />
                        </Field>
                        <Field label="SKU (optional)">
                            <input className={inputCls} value={form.data.sku} onChange={(event) => form.setData('sku', event.target.value)} placeholder="e.g. GA-TS-001" />
                        </Field>
                        <Field label="Category" error={form.errors.category_id}>
                            <select className={inputCls} value={form.data.category_id ?? ''} onChange={(event) => form.setData('category_id', event.target.value)} required={!editing}>
                                <option value="">Uncategorised</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>{category.name}</option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Selling price (৳)" error={form.errors.price}>
                            <input type="number" min="0" className={inputCls} value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} required />
                        </Field>
                        <Field label="Old price (৳)" hint="Shown as the struck-through compare-at price.">
                            <input type="number" min="0" className={inputCls} value={form.data.old_price} onChange={(event) => form.setData('old_price', event.target.value)} />
                        </Field>
                        <Field label="Status">
                            <select className={inputCls} value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </Field>
                    </div>
                </Section>

                <Section
                    number="02"
                    title="Product Inventory — Color & Size"
                    description="Add each color, then enter the available quantity for its sizes. Zero means unavailable."
                >
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3">
                        <label className="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                className="mt-0.5 h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                checked={hasVariants}
                                onChange={(event) => toggleVariants(event.target.checked)}
                            />
                            <span>
                                <span className="block text-sm font-semibold text-gray-900">Track inventory by color and size</span>
                                <span className="block text-xs text-gray-500">Each color gets its own size-wise stock cards. Turn off for a single-stock product.</span>
                            </span>
                        </label>
                    </div>

                    {!hasVariants && (
                        <div className="mt-4 rounded-xl border border-purple-100 bg-white p-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label="Stock quantity">
                                    <input
                                        type="number"
                                        min="0"
                                        className={inputCls}
                                        value={form.data.stock}
                                        onFocus={(event) => {
                                            if (Number(event.target.value) === 0) {
                                                form.setData('stock', '');
                                            } else {
                                                event.target.select();
                                            }
                                        }}
                                        onBlur={() => {
                                            if (String(form.data.stock ?? '').trim() === '') {
                                                form.setData('stock', 0);
                                            }
                                        }}
                                        onChange={(event) => form.setData('stock', event.target.value)}
                                    />
                                </Field>
                                <Field label="Sizes (comma separated)" hint="Optional for simple products.">
                                    <input className={inputCls} value={form.data.sizes} onChange={(event) => updateSizes(event.target.value)} placeholder="M,L,XL" />
                                </Field>
                            </div>

                            {sizeList.length > 0 && (
                                <div className="mt-4 rounded-lg border border-purple-100 p-3">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div className="text-sm font-semibold text-gray-900">Size stock</div>
                                        <div className="text-xs text-gray-500">Total: <b>{legacyTotal}</b>{sizeConfigured ? ' (auto)' : ''}</div>
                                    </div>

                                    {!sizeConfigured && (
                                        <p className="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-[11px] text-amber-800">
                                            Size stock not configured. The total stock is used for every size.
                                        </p>
                                    )}

                                    <div className="mt-3 grid gap-2 md:grid-cols-2">
                                        {sizeList.map((size) => {
                                            const qty = Number(sizeStock[size] || 0);
                                            const status = !sizeConfigured ? 'Not set' : qty <= 0 ? 'Sold out' : qty <= threshold ? 'Low stock' : 'In stock';
                                            const tone = !sizeConfigured
                                                ? 'text-gray-400'
                                                : qty <= 0
                                                    ? 'text-red-600'
                                                    : qty <= threshold
                                                        ? 'text-amber-700'
                                                        : 'text-green-700';

                                            return (
                                                <div key={size} className="flex items-center gap-2">
                                                    <span className="w-12 text-sm font-semibold text-gray-700">{size}</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        className={`${tableInput} flex-1`}
                                                        value={sizeConfigured ? qty : ''}
                                                        onChange={(event) => updateSizeStock(size, event.target.value)}
                                                        placeholder="0"
                                                    />
                                                    <span className={`w-20 text-[11px] font-semibold ${tone}`}>{status}</span>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    <button type="button" className="mt-2 text-xs font-semibold text-purple-700" onClick={distributeTotal}>
                                        Distribute existing total
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {hasVariants && (
                        <>
                            <div className="mt-4 rounded-xl border border-purple-100 bg-white p-4">
                                <div className="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                                    <div>
                                        <span className={labelCls}>Common sizes</span>
                                        <div className="flex flex-wrap gap-2">
                                            <input
                                                className={`${inputCls} !mt-0 sm:w-72`}
                                                value={commonSizesInput}
                                                onChange={(event) => setCommonSizesInput(event.target.value)}
                                                placeholder="S, M, L, XL, XXL"
                                            />
                                            <button type="button" className={secondaryBtn} onClick={applyCommonSizes}>
                                                Apply to all colors
                                            </button>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-end gap-2">
                                        <Field label="New color" className="min-w-[180px]">
                                            <select className={inputCls} value={addColorSource} onChange={(event) => setAddColorSource(event.target.value)}>
                                                <option value="">Start with empty sizes</option>
                                                {hasColorless && <option value="__default__">Copy sizes from default</option>}
                                                {activeColors.map((color) => (
                                                    <option key={color.key} value={color.key}>Copy sizes from {color.name || 'Untitled'}</option>
                                                ))}
                                            </select>
                                        </Field>
                                        <button type="button" className={primaryBtn} onClick={addColor}>+ Add color</button>
                                    </div>
                                </div>

                                <div className="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                                    <label className="flex items-center gap-2 text-xs font-semibold text-gray-600">
                                        <input
                                            type="checkbox"
                                            className="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                            checked={showCodes}
                                            onChange={(event) => setShowCodes(event.target.checked)}
                                        />
                                        Show SKU and barcode fields
                                    </label>
                                    <span className="text-[11px] text-gray-400">Blank SKUs are generated automatically on save.</span>
                                </div>

                                {form.errors.colors && <div className="mt-2 text-xs font-semibold text-red-600">{form.errors.colors}</div>}
                            </div>

                            {cards.length === 0 && (
                                <div className="mt-4 rounded-xl border border-dashed border-purple-200 bg-purple-50/30 p-8 text-center">
                                    <p className="text-sm font-semibold text-gray-700">No colors or sizes yet</p>
                                    <p className="mt-1 text-xs text-gray-500">Add a color, then enter the available quantity for each size.</p>
                                    <button type="button" className={`${primaryBtn} mt-3`} onClick={addColor}>+ Add your first color</button>
                                </div>
                            )}

                            <div className="mt-4 space-y-4">
                                {cards.map((card) => {
                                    const rows = variantRowsFor(card.variantKey);
                                    const colorNumber = card.isDefault ? null : activeColors.findIndex((color) => color.key === card.key) + 1;

                                    return (
                                        <div key={card.key} className="overflow-hidden rounded-2xl border border-purple-100 bg-white">
                                            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-purple-50/40 px-4 py-3">
                                                <div className="flex min-w-0 flex-1 items-center gap-3">
                                                    <span
                                                        className={`h-9 w-9 shrink-0 rounded-full border ${
                                                            card.hex ? 'border-purple-100' : 'border-dashed border-gray-300 bg-white'
                                                        }`}
                                                        style={card.hex ? { backgroundColor: card.hex } : undefined}
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <div className="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                                            {card.isDefault ? 'Default' : `Color ${String(colorNumber).padStart(2, '0')}`}
                                                        </div>
                                                        {card.isDefault ? (
                                                            <div className="truncate text-sm font-semibold text-gray-900">Default sizes (no color)</div>
                                                        ) : (
                                                            <>
                                                                <label className="mt-0.5 block text-[10px] font-bold uppercase tracking-wide text-purple-700">
                                                                    Color name <span className="text-red-500">*</span>
                                                                </label>
                                                                <input
                                                                    className={`inp !mt-0 w-full max-w-md ${
                                                                        String(card.name || '').trim() === ''
                                                                            ? 'border-amber-400 bg-amber-50/50 ring-2 ring-amber-100'
                                                                            : ''
                                                                    }`}
                                                                    value={card.name}
                                                                    onChange={(event) => updateColorName(card.key, event.target.value)}
                                                                    placeholder="Type the color name here (e.g. Black)"
                                                                />
                                                                {String(card.name || '').trim() === '' && (
                                                                    <span className="mt-1 block text-[10px] font-semibold text-amber-700">
                                                                        Click here and type the color name.
                                                                    </span>
                                                                )}
                                                                {colorNameError(card.key) && (
                                                                    <div className="mt-1 text-[10px] text-red-600">
                                                                        Color name is required.
                                                                    </div>
                                                                )}
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-gray-700 ring-1 ring-purple-100">
                                                        {colorTotal(card.variantKey)} pcs
                                                    </span>
                                                    {!card.isDefault && (
                                                        <button type="button" className={dangerBtn} onClick={() => removeColor(card.key)}>
                                                            Remove
                                                        </button>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="grid gap-4 p-4 lg:grid-cols-[210px_1fr]">
                                                <div className="space-y-3">
                                                    {card.isDefault ? (
                                                        <p className="rounded-lg bg-purple-50/40 p-3 text-xs leading-relaxed text-gray-500">
                                                            These sizes have no color. Existing size-based stock stays here until you assign colors.
                                                        </p>
                                                    ) : (
                                                        <>
                                                            <Field label="Swatch" hint="Follows the color name automatically. Click to pick a swatch manually.">
                                                                <label className="flex cursor-pointer items-center gap-2">
                                                                    <span
                                                                        className={`h-9 w-9 shrink-0 rounded-full border ${
                                                                            card.hex ? 'border-purple-100' : 'border-dashed border-gray-300 bg-white'
                                                                        }`}
                                                                        style={card.hex ? { backgroundColor: card.hex } : undefined}
                                                                    />
                                                                    <span className="text-xs font-semibold text-gray-500">
                                                                        {card.hex || 'No color selected — click to pick'}
                                                                    </span>
                                                                    <input
                                                                        type="color"
                                                                        className="sr-only"
                                                                        value={card.hex || '#FFFFFF'}
                                                                        onChange={(event) => updateColorSwatch(card.key, event.target.value)}
                                                                    />
                                                                </label>
                                                            </Field>
                                                            <Field label="Color images" hint="Add multiple images. The first image is used as the color cover.">
                                                                <input
                                                                    type="file"
                                                                    accept="image/*"
                                                                    multiple
                                                                    className="block w-full text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-purple-700"
                                                                    onChange={(event) => {
                                                                        addColorImages(card.key, event.target.files);
                                                                        event.target.value = '';
                                                                    }}
                                                                />

                                                                {((card.images || []).length > 0 || (card.new_images || []).length > 0) && (
                                                                    <div className="mt-2 flex flex-wrap gap-2">
                                                                        {(card.images || []).map((path, index) => (
                                                                            <div key={path} className="relative">
                                                                                <img src={`/storage/${path}`} alt="" className="h-14 w-14 rounded-lg object-cover ring-1 ring-purple-100" />
                                                                                {index === 0 && (
                                                                                    <span className="absolute bottom-0 left-0 rounded bg-black/60 px-1 text-[8px] font-bold text-white">COVER</span>
                                                                                )}
                                                                                <button
                                                                                    type="button"
                                                                                    className="absolute -right-1 -top-1 grid h-4 w-4 place-items-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white"
                                                                                    onClick={() => removeColorImage(card.key, path)}
                                                                                    aria-label="Remove image"
                                                                                >
                                                                                    ×
                                                                                </button>
                                                                            </div>
                                                                        ))}
                                                                        {(card.new_images || []).map((file, index) => (
                                                                            <div key={`${file.name}-${index}`} className="relative">
                                                                                <FilePreview file={file} />
                                                                                {(card.images || []).length === 0 && index === 0 && (
                                                                                    <span className="absolute bottom-0 left-0 rounded bg-black/60 px-1 text-[8px] font-bold text-white">COVER</span>
                                                                                )}
                                                                                <button
                                                                                    type="button"
                                                                                    className="absolute -right-1 -top-1 grid h-4 w-4 place-items-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white"
                                                                                    onClick={() => removeNewColorImage(card.key, index)}
                                                                                    aria-label="Remove image"
                                                                                >
                                                                                    ×
                                                                                </button>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                )}
                                                            </Field>
                                                        </>
                                                    )}
                                                </div>

                                                <div className="min-w-0">
                                                    <div className="overflow-x-auto rounded-xl border border-purple-100">
                                                        <table className="w-full min-w-[460px] text-sm">
                                                            <thead>
                                                                <tr className="bg-purple-50/40 text-left text-[10px] uppercase tracking-wide text-gray-500">
                                                                    <th className="px-3 py-2">Size</th>
                                                                    <th className="px-3 py-2">Available quantity</th>
                                                                    <th className="px-3 py-2">Status</th>
                                                                    {showCodes && (
                                                                        <>
                                                                            <th className="px-3 py-2">SKU</th>
                                                                            <th className="px-3 py-2">Barcode</th>
                                                                        </>
                                                                    )}
                                                                    <th className="px-3 py-2" />
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {rows.map(({ variant, index }) => (
                                                                    <tr key={variant.id ?? `new-${index}`} className="border-t border-gray-100">
                                                                        <td className="px-3 py-2">
                                                                            <input
                                                                                className={`${tableInput} w-20`}
                                                                                value={variant.size ?? ''}
                                                                                onChange={(event) => updateVariant(index, 'size', event.target.value)}
                                                                                placeholder="M"
                                                                            />
                                                                            {variantError(index, 'size') && <div className="mt-1 text-[10px] text-red-600">{variantError(index, 'size')}</div>}
                                                                        </td>
                                                                        <td className="px-3 py-2">
                                                                            <input
                                                                                type="number"
                                                                                min="0"
                                                                                inputMode="numeric"
                                                                                className={`${tableInput} w-24`}
                                                                                value={variant.stock}
                                                                                onFocus={(event) => {
                                                                                    if (Number(event.target.value) === 0) {
                                                                                        updateVariant(index, 'stock', '');
                                                                                    } else {
                                                                                        event.target.select();
                                                                                    }
                                                                                }}
                                                                                onBlur={() => {
                                                                                    if (String(variants[index]?.stock ?? '').trim() === '') {
                                                                                        updateVariant(index, 'stock', 0);
                                                                                    }
                                                                                }}
                                                                                onChange={(event) => updateVariant(index, 'stock', event.target.value)}
                                                                            />
                                                                            {variantError(index, 'stock') && <div className="mt-1 text-[10px] text-red-600">{variantError(index, 'stock')}</div>}
                                                                        </td>
                                                                        <td className="px-3 py-2">
                                                                            <select
                                                                                className={`${tableInput} w-28`}
                                                                                value={variant.status}
                                                                                onChange={(event) => updateVariant(index, 'status', event.target.value)}
                                                                            >
                                                                                <option value="active">Active</option>
                                                                                <option value="disabled">Disabled</option>
                                                                            </select>
                                                                            {variant.has_sales && (
                                                                                <span className="mt-1 block text-[10px] font-semibold text-amber-600">Has sales</span>
                                                                            )}
                                                                        </td>
                                                                        {showCodes && (
                                                                            <>
                                                                                <td className="px-3 py-2">
                                                                                    <input
                                                                                        className={`${tableInput} w-36`}
                                                                                        value={variant.sku ?? ''}
                                                                                        onChange={(event) => updateVariant(index, 'sku', event.target.value)}
                                                                                        placeholder="Auto"
                                                                                    />
                                                                                    {variantError(index, 'sku') && <div className="mt-1 text-[10px] text-red-600">{variantError(index, 'sku')}</div>}
                                                                                </td>
                                                                                <td className="px-3 py-2">
                                                                                    <input
                                                                                        className={`${tableInput} w-36`}
                                                                                        value={variant.barcode ?? ''}
                                                                                        onChange={(event) => updateVariant(index, 'barcode', event.target.value)}
                                                                                        placeholder="Optional"
                                                                                    />
                                                                                    {variantError(index, 'barcode') && <div className="mt-1 text-[10px] text-red-600">{variantError(index, 'barcode')}</div>}
                                                                                </td>
                                                                            </>
                                                                        )}
                                                                        <td className="px-3 py-2 text-right">
                                                                            <button
                                                                                type="button"
                                                                                className={dangerBtn}
                                                                                onClick={() => removeSize(index)}
                                                                                aria-label="Remove size"
                                                                            >
                                                                                Remove
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                                {rows.length === 0 && (
                                                                    <tr>
                                                                        <td colSpan={showCodes ? 6 : 4} className="px-3 py-6 text-center text-xs text-gray-400">
                                                                            No sizes yet. Add a size below.
                                                                        </td>
                                                                    </tr>
                                                                )}
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                                        {commonSizes.map((size) => {
                                                            const exists = variants.some((variant) => (
                                                                (variant.color_key || '') === card.variantKey
                                                                && String(variant.size || '').trim().toLowerCase() === size.toLowerCase()
                                                            ));

                                                            return (
                                                                <button
                                                                    key={size}
                                                                    type="button"
                                                                    disabled={exists}
                                                                    className={`rounded-full border px-2.5 py-1 text-xs font-semibold transition ${
                                                                        exists
                                                                            ? 'border-purple-100 bg-purple-50 text-gray-400'
                                                                            : 'border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100'
                                                                    }`}
                                                                    onClick={() => addSizeToColor(card.variantKey, size)}
                                                                >
                                                                    {exists ? '✓ ' : '+ '}{size}
                                                                </button>
                                                            );
                                                        })}
                                                    </div>

                                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                                        <input
                                                            className={`${inputCls} !mt-0 w-40`}
                                                            value={sizeDrafts[card.key] || ''}
                                                            onChange={(event) => setSizeDrafts({ ...sizeDrafts, [card.key]: event.target.value })}
                                                            onKeyDown={(event) => {
                                                                if (event.key === 'Enter') {
                                                                    event.preventDefault();
                                                                    addSizeToColor(card.variantKey, sizeDrafts[card.key] || '');
                                                                    setSizeDrafts({ ...sizeDrafts, [card.key]: '' });
                                                                }
                                                            }}
                                                            placeholder="Add another size"
                                                        />
                                                        <button
                                                            type="button"
                                                            className={secondaryBtn}
                                                            onClick={() => {
                                                                addSizeToColor(card.variantKey, sizeDrafts[card.key] || '');
                                                                setSizeDrafts({ ...sizeDrafts, [card.key]: '' });
                                                            }}
                                                        >
                                                            Add size
                                                        </button>

                                                        <select
                                                            className={`${inputCls} !mt-0 w-auto`}
                                                            value={copySources[card.key] || ''}
                                                            onChange={(event) => setCopySources({ ...copySources, [card.key]: event.target.value })}
                                                        >
                                                            <option value="">Copy sizes from…</option>
                                                            {cards
                                                                .filter((other) => other.key !== card.key)
                                                                .map((other) => (
                                                                    <option key={other.key} value={other.key}>{other.name}</option>
                                                                ))}
                                                        </select>
                                                        <button
                                                            type="button"
                                                            className={secondaryBtn}
                                                            disabled={!copySources[card.key]}
                                                            onClick={() => copySizesTo(
                                                                card.variantKey,
                                                                copySources[card.key] === '__default__' ? '' : copySources[card.key]
                                                            )}
                                                        >
                                                            Copy
                                                        </button>
                                                    </div>

                                                    <div className="mt-2 text-[11px] text-gray-400">
                                                        Color total: <b className="text-gray-600">{colorTotal(card.variantKey)} pcs</b>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {inactiveColors.length > 0 && (
                                <div className="mt-4 rounded-xl border border-purple-100 bg-purple-50/40 p-4">
                                    <div className="text-[11px] font-bold uppercase tracking-wide text-gray-500">Inactive colors</div>
                                    <p className="mt-0.5 text-[11px] text-gray-500">
                                        Hidden from the storefront. Stock and order history are preserved.
                                    </p>
                                    <div className="mt-2 divide-y divide-purple-100">
                                        {inactiveColors.map((color) => (
                                            <div key={color.key} className="flex flex-wrap items-center justify-between gap-2 py-2">
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={`h-5 w-5 rounded-full border ${
                                                            color.hex ? 'border-gray-300' : 'border-dashed border-gray-300 bg-white'
                                                        }`}
                                                        style={color.hex ? { backgroundColor: color.hex } : undefined}
                                                    />
                                                    <span className="text-sm font-semibold text-gray-700">{color.name || 'Untitled color'}</span>
                                                    <span className="text-xs text-gray-500">{rawColorTotal(color.key)} pcs kept</span>
                                                </div>
                                                <button type="button" className={secondaryBtn} onClick={() => restoreColor(color.key)}>
                                                    Restore color
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                <Stat label="Total colors" value={activeColors.length} hint="Colors currently visible on the website" />
                                <Stat label="Total variants" value={totalVariants} hint="Active color and size combinations" />
                                <Stat label="Total product stock" value={`${totalStock} pcs`} hint="Sum of every active variant" />
                            </div>

                            <div className="mt-4 overflow-hidden rounded-xl border border-purple-100">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-purple-50/40 text-left text-[10px] uppercase tracking-wide text-gray-500">
                                            <th className="px-3 py-2">Color</th>
                                            <th className="px-3 py-2">Sizes</th>
                                            <th className="px-3 py-2 text-right">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {cards.map((card) => {
                                            const sizes = uniqueSizes(
                                                variants
                                                    .filter((variant) => (variant.color_key || '') === card.variantKey && isVariantActive(variant))
                                                    .map((variant) => variant.size)
                                            );

                                            return (
                                                <tr key={card.key} className="border-t border-gray-100">
                                                    <td className="px-3 py-2">
                                                        <div className="flex items-center gap-2">
                                                            <span
                                                                className={`h-4 w-4 rounded-full border ${
                                                                    card.hex ? 'border-purple-100' : 'border-dashed border-gray-300 bg-white'
                                                                }`}
                                                                style={card.hex ? { backgroundColor: card.hex } : undefined}
                                                            />
                                                            <span className="font-semibold text-gray-700">{card.name || 'Untitled'}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-3 py-2 text-gray-500">{sizes.join(', ') || '—'}</td>
                                                    <td className="px-3 py-2 text-right font-semibold text-gray-800">{colorTotal(card.variantKey)} pcs</td>
                                                </tr>
                                            );
                                        })}
                                        {cards.length === 0 && (
                                            <tr>
                                                <td colSpan="3" className="px-3 py-6 text-center text-xs text-gray-400">No colors added yet.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t border-purple-100 bg-purple-50 font-bold text-gray-900">
                                            <td className="px-3 py-2" colSpan="2">Total</td>
                                            <td className="px-3 py-2 text-right">{totalStock} pcs</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {Object.keys(sizeTotals).length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {Object.entries(sizeTotals).map(([size, qty]) => (
                                        <span key={size} className="rounded-full border border-purple-100 bg-white px-2.5 py-1 text-xs font-semibold text-gray-600">
                                            {size}: {qty} pcs
                                        </span>
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </Section>

                <Section number="03" title="Product Images" description="The main image, gallery and per-color images used on the storefront.">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Main product image" hint="One cover image for the product.">
                            <input
                                type="file"
                                accept="image/*"
                                className="block w-full text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-purple-700"
                                onChange={(event) => form.setData('image', event.target.files[0] || null)}
                            />
                            {form.data.image && (
                                <FilePreview file={form.data.image} className="mt-2 h-20 w-20 rounded-lg object-cover ring-2 ring-purple-200" />
                            )}
                            {editing && product.image_url && (
                                <img src={product.image_url} alt="" className="mt-2 h-20 w-20 rounded-lg object-cover ring-1 ring-purple-100" />
                            )}
                        </Field>
                        <Field label="Additional images" hint="Select multiple files, then add more any time. These appear in the product gallery.">
                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                className="block w-full text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-purple-700"
                                onChange={(event) => {
                                    form.setData('images', [...form.data.images, ...Array.from(event.target.files)]);
                                    event.target.value = '';
                                }}
                            />
                            {form.data.images.length > 0 && (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {form.data.images.map((file, index) => (
                                        <div key={`${file.name}-${index}`} className="relative">
                                            <FilePreview file={file} />
                                            <button
                                                type="button"
                                                className="absolute -right-1 -top-1 grid h-4 w-4 place-items-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white"
                                                onClick={() => form.setData('images', form.data.images.filter((_, position) => position !== index))}
                                                aria-label="Remove image"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Field>
                    </div>

                    {editing && product.images.length > 0 && (
                        <div className="mt-4">
                            <div className="text-[11px] font-bold uppercase tracking-wide text-gray-500">Existing gallery</div>
                            <div className="mt-2 flex flex-wrap gap-3">
                                {product.images.map((path) => (
                                    <label key={path} className="text-center">
                                        <img src={`/storage/${path}`} alt="" className="h-16 w-16 rounded-lg object-cover ring-1 ring-purple-100" />
                                        <span className="mt-1 flex items-center gap-1 text-[11px] text-gray-500">
                                            <input
                                                type="checkbox"
                                                checked={form.data.remove_images.includes(path)}
                                                onChange={() => toggleRemove(path)}
                                            />
                                            Remove
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    )}
                </Section>

                <Section number="04" title="Product Details" description="Supporting content shown on the product details page.">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Subtitle">
                            <input className={inputCls} value={form.data.sub} onChange={(event) => form.setData('sub', event.target.value)} />
                        </Field>
                        <Field label="Highlights (comma separated)">
                            <input className={inputCls} value={form.data.feat} onChange={(event) => form.setData('feat', event.target.value)} />
                        </Field>
                    </div>
                    <Field label="Description" className="mt-4">
                        <textarea className={inputCls} rows="4" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                    </Field>
                    <label className="mt-4 flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input
                            type="checkbox"
                            className="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                            checked={!!form.data.featured}
                            onChange={(event) => form.setData('featured', event.target.checked ? 1 : 0)}
                        />
                        Featured product
                    </label>
                </Section>

                <div className="flex flex-wrap justify-end gap-2 rounded-2xl border border-purple-100 bg-white px-5 py-4 shadow-sm">
                    <Link href="/admin/products" className={secondaryBtn}>Cancel</Link>
                    <button type="submit" className={primaryBtn} disabled={form.processing}>
                        {form.processing ? 'Saving…' : editing ? 'Update product' : 'Save product'}
                    </button>
                </div>
                </div>
            </form>
        </>
    );
}

ProductForm.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default ProductForm;
