import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import SettingsTabs from '../../../Components/SettingsTabs';
import StoreHero from '../../../Components/StoreHero';
import { optimizeImage } from '../../../lib/optimizeImage';

const ACCEPT = 'image/jpeg,image/png,image/webp';
const ACCEPT_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 8 * 1024 * 1024;

const defaultHeroSettings = {
    autoplay: true,
    duration: 6500,
    transition: 'fade',
    speed: 0.7,
    show_arrows: true,
    show_dots: true,
    pause_hover: true,
};

function buildHeroData(images, settings) {
    const source = settings || defaultHeroSettings;

    return {
        images: (images || []).map((image) => ({ id: image.id, path: image.path })),
        images_present: true,
        new_images: [],
        main_image: null,
        autoplay: source.autoplay,
        duration: source.duration,
        transition: source.transition,
        speed: source.speed,
        show_arrows: source.show_arrows,
        show_dots: source.show_dots,
        pause_hover: source.pause_hover,
    };
}

function Homepage({ section, heroImages = [], heroSettings }) {
    const { settings } = usePage().props;

    const [previewDevice, setPreviewDevice] = useState('desktop');
    const [uploadError, setUploadError] = useState('');
    const [optimizing, setOptimizing] = useState(false);
    const [dragging, setDragging] = useState(false);
    const [newPreviews, setNewPreviews] = useState([]);
    const [mainPreview, setMainPreview] = useState(null);
    const desktopInput = useRef(null);

    const pageForm = useForm({
        top: settings.top ?? '',
        title: settings.title ?? '',
        sub: settings.sub ?? '',
        promo_title: settings.promo_title ?? '',
        promo_text: settings.promo_text ?? '',
        why_choose_us: settings.why_choose_us ?? '',
        hero_image: null,
    });

    const heroForm = useForm(buildHeroData(heroImages, heroSettings));

    const imageMap = useMemo(() => {
        const map = {};
        heroImages.forEach((image) => {
            map[image.path] = image.url;
        });

        return map;
    }, [heroImages]);

    const existingItems = heroForm.data.images
        .map((image) => ({
            key: `existing-${image.id}`,
            kind: 'existing',
            id: image.id,
            url: imageMap[image.path],
        }))
        .filter((item) => item.url);

    const newItems = (heroForm.data.new_images || [])
        .map((file, index) => ({
            key: `new-${index}`,
            kind: 'new',
            index,
            file,
            url: newPreviews[index],
        }))
        .filter((item) => item.url);

    const mainItem = heroForm.data.main_image && mainPreview
        ? { key: 'main-new', kind: 'main-new', url: mainPreview }
        : null;

    const previewItems = [mainItem, ...existingItems, ...newItems].filter(Boolean);
    const previewImages = previewItems.map((item) => ({ id: item.key, url: item.url }));
    const pool = previewItems.map((item, index) => ({
        ...item,
        main: item.kind === 'main-new' || (!mainItem && index === 0),
    }));

    const previewSettings = { ...heroForm.data, autoplay: false };
    const copyPreview = {
        top: pageForm.data.top,
        title: pageForm.data.title,
        sub: pageForm.data.sub,
    };

    useEffect(() => {
        const urls = (heroForm.data.new_images || []).map((file) => URL.createObjectURL(file));
        setNewPreviews(urls);

        return () => urls.forEach((url) => URL.revokeObjectURL(url));
    }, [heroForm.data.new_images]);

    useEffect(() => {
        if (!heroForm.data.main_image) {
            setMainPreview(null);

            return undefined;
        }

        const url = URL.createObjectURL(heroForm.data.main_image);
        setMainPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [heroForm.data.main_image]);

    const addFiles = async (fileList) => {
        const files = Array.from(fileList || []);

        if (!files.length) {
            return;
        }

        setUploadError('');
        setOptimizing(true);
        const accepted = [];

        for (const file of files) {
            if (!ACCEPT_TYPES.includes(file.type)) {
                setUploadError('Use JPG, PNG or WebP images only.');
                continue;
            }

            if (file.size > MAX_BYTES) {
                setUploadError('Each image must be 8 MB or smaller.');
                continue;
            }

            accepted.push(await optimizeImage(file, { maxWidth: 1600, maxHeight: 2000 }));
        }

        if (accepted.length) {
            heroForm.setData('new_images', [...(heroForm.data.new_images || []), ...accepted]);
        }

        setOptimizing(false);
    };

    const chooseMain = (target) => {
        if (target.kind === 'existing') {
            const image = heroForm.data.images.find((item) => item.id === target.id);

            if (!image) {
                return;
            }

            const rest = heroForm.data.images.filter((item) => item.id !== target.id);
            const hadMain = heroForm.data.main_image;

            heroForm.setData({
                ...heroForm.data,
                images: [image, ...rest],
                main_image: null,
                new_images: hadMain ? [hadMain, ...(heroForm.data.new_images || [])] : heroForm.data.new_images,
            });

            return;
        }

        if (target.kind === 'new') {
            const file = heroForm.data.new_images[target.index];
            const files = (heroForm.data.new_images || []).filter((_, index) => index !== target.index);
            const previousMain = heroForm.data.main_image;

            heroForm.setData({
                ...heroForm.data,
                main_image: file,
                new_images: previousMain ? [previousMain, ...files] : files,
            });
        }
    };

    const removeImage = (target) => {
        if (target.kind === 'existing') {
            heroForm.setData('images', heroForm.data.images.filter((image) => image.id !== target.id));

            return;
        }

        if (target.kind === 'new') {
            heroForm.setData('new_images', (heroForm.data.new_images || []).filter((_, index) => index !== target.index));

            return;
        }

        if (target.kind === 'main-new') {
            heroForm.setData({ ...heroForm.data, main_image: null });
        }
    };

    const submitPage = (event) => {
        event.preventDefault();
        pageForm.post('/admin/settings/homepage', { forceFormData: true, preserveScroll: true });
    };

    const submitHero = (event) => {
        event.preventDefault();

        heroForm.post('/admin/settings/homepage/hero', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                heroForm.setData(buildHeroData(page.props.heroImages || [], page.props.heroSettings || defaultHeroSettings));
                setUploadError('');
            },
        });
    };

    const field = (key, label) => (
        <label key={key} className="block text-sm mb-3">
            {label}
            <input className="inp" value={pageForm.data[key]} onChange={(event) => pageForm.setData(key, event.target.value)} />
            {pageForm.errors[key] && <span className="text-xs text-red-600">{pageForm.errors[key]}</span>}
        </label>
    );

    return (
        <>
            <Head title="Settings · Homepage" />

            <div className="mb-5">
                <h1 className="font-serif text-3xl font-bold">Homepage</h1>
                <p className="text-sm text-gray-500 mt-1">Hero gallery, announcement and promo content</p>
            </div>

            <SettingsTabs section={section} />

            <form onSubmit={submitPage} className="space-y-4">
                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">Homepage content</h2>
                    <div className="grid md:grid-cols-2 gap-x-5">
                        {field('top', 'Announcement text')}
                        {field('title', 'Hero title')}
                        {field('sub', 'Hero subtitle')}
                        {field('promo_title', 'Promo title')}
                        {field('promo_text', 'Promo text')}
                        {field('why_choose_us', 'Why choose us (separate with |)')}
                    </div>
                    <label className="block text-sm">
                        Fallback hero image
                        <input type="file" accept="image/*" className="inp" onChange={(event) => pageForm.setData('hero_image', event.target.files[0])} />
                        {settings.hero_image && <img src={`/storage/${settings.hero_image}`} alt="Hero" className="h-20 mt-2 rounded-lg object-cover" />}
                        <span className="block text-[11px] text-gray-400 mt-1">Used only when the hero gallery is empty.</span>
                    </label>
                </div>

                <button className="btn" disabled={pageForm.processing}>
                    {pageForm.processing ? 'Saving…' : 'Save homepage'}
                </button>
            </form>

            <form onSubmit={submitHero} className="mt-6 space-y-4">
                <div className="bg-white rounded-2xl p-5">
                    <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <div>
                            <h2 className="font-semibold">Hero gallery</h2>
                            <p className="text-sm text-gray-500">
                                Upload multiple images. They change automatically in the 3D hero; clicking the hero opens the shop.
                            </p>
                        </div>
                        <span className="badge bg-purple-100 text-purple-700">
                            {heroForm.data.images.length + (heroForm.data.new_images?.length || 0) + (heroForm.data.main_image ? 1 : 0)} images
                        </span>
                    </div>

                    {Object.keys(heroForm.errors).length > 0 && (
                        <div className="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                            {Object.values(heroForm.errors)[0]}
                        </div>
                    )}

                    {uploadError && (
                        <div className="mb-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">{uploadError}</div>
                    )}

                    <div
                        onDragOver={(event) => {
                            event.preventDefault();
                            setDragging(true);
                        }}
                        onDragLeave={() => setDragging(false)}
                        onDrop={(event) => {
                            event.preventDefault();
                            setDragging(false);
                            addFiles(event.dataTransfer.files);
                        }}
                        className={`rounded-2xl border-2 border-dashed p-5 text-center transition ${
                            dragging ? 'border-pur bg-purple-50' : 'border-purple-200 bg-purple-50/30'
                        }`}
                    >
                        <div className="mx-auto mb-2 grid h-11 w-11 place-items-center rounded-full grad text-2xl font-light text-white">+</div>
                        <b className="block text-sm">Drop hero images here</b>
                        <div className="my-1 text-[11px] text-gray-400">or</div>
                        <button type="button" className="btn !px-4 !py-2 text-sm" onClick={() => desktopInput.current?.click()} disabled={optimizing}>
                            {optimizing ? 'Preparing…' : '+ Choose images'}
                        </button>
                        <div className="mt-2 text-[11px] text-gray-500">
                            JPG, JPEG, PNG or WebP · up to 8 MB each · select multiple
                        </div>
                        <input
                            ref={desktopInput}
                            type="file"
                            accept={ACCEPT}
                            multiple
                            className="hidden"
                            onChange={(event) => {
                                addFiles(event.target.files);
                                event.target.value = '';
                            }}
                        />
                    </div>

                    {heroForm.progress && (
                        <div className="mt-3">
                            <div className="h-2 overflow-hidden rounded-full bg-purple-100">
                                <div className="h-full bg-gradient-to-r from-pur to-org transition-all" style={{ width: `${heroForm.progress.percentage}%` }} />
                            </div>
                            <div className="mt-1 text-[10px] text-gray-400">Uploading {heroForm.progress.percentage}%</div>
                        </div>
                    )}

                    <div className="mt-3 grid grid-cols-4 gap-2 md:grid-cols-5">
                        {pool.map((item) => (
                            <div
                                key={item.key}
                                className={`relative aspect-[3/4] overflow-hidden rounded-xl border-2 bg-gradient-to-br from-pur to-org ${
                                    item.main ? 'border-pur ring-4 ring-purple-100' : 'border-transparent'
                                }`}
                            >
                                <img src={item.url} alt="" className="h-full w-full object-cover" />
                                <span className={`absolute left-1 top-1 rounded-full px-1.5 py-0.5 text-[8px] font-extrabold ${
                                    item.main ? 'grad text-white' : 'bg-white/90 text-gray-600'
                                }`}>
                                    {item.main ? 'MAIN' : item.kind === 'new' ? 'NEW' : 'IMAGE'}
                                </span>
                                <div className="absolute inset-x-1 bottom-1 flex justify-end gap-1">
                                    {!item.main && (
                                        <button
                                            type="button"
                                            onClick={() => chooseMain(item)}
                                            className="grid h-6 w-6 place-items-center rounded-full bg-black/55 text-[11px] text-white hover:bg-black/75"
                                            title="Set as main image"
                                        >
                                            ★
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => removeImage(item)}
                                        className="grid h-6 w-6 place-items-center rounded-full bg-black/55 text-[11px] text-white hover:bg-black/75"
                                        title="Remove image"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        ))}

                        <button
                            type="button"
                            onClick={() => desktopInput.current?.click()}
                            className="grid aspect-[3/4] place-items-center rounded-xl border-2 border-dashed border-purple-200 text-2xl font-light text-pur hover:bg-purple-50"
                            title="Add images"
                        >
                            +
                        </button>
                    </div>

                    <div className="mt-2 text-[10px] text-gray-400">
                        Recommended 1600 × 2000 (4:5). Tap the star to make an image the main card. Images are compressed before upload.
                    </div>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">Slideshow settings</h2>
                    <div className="grid gap-x-5 md:grid-cols-2">
                        <label className="flex items-center justify-between text-sm mb-3">
                            Autoplay
                            <input
                                type="checkbox"
                                className="h-4 w-4 accent-pur"
                                checked={heroForm.data.autoplay}
                                onChange={(event) => heroForm.setData('autoplay', event.target.checked)}
                            />
                        </label>

                        <label className="block text-sm mb-3">
                            <span className="flex items-center justify-between">
                                Slide duration
                                <span className="text-[11px] text-gray-400">{heroForm.data.duration / 1000}s</span>
                            </span>
                            <input
                                type="range"
                                min="2"
                                max="15"
                                step="0.5"
                                value={heroForm.data.duration / 1000}
                                onChange={(event) => heroForm.setData('duration', Math.round(Number(event.target.value) * 1000))}
                                className="mt-1 w-full accent-pur"
                            />
                        </label>

                        <label className="block text-sm mb-3">
                            Transition
                            <select className="inp" value={heroForm.data.transition} onChange={(event) => heroForm.setData('transition', event.target.value)}>
                                <option value="fade">Fade</option>
                                <option value="zoom">Zoom</option>
                                <option value="slide">Slide</option>
                            </select>
                        </label>

                        <label className="block text-sm mb-3">
                            Animation speed
                            <select className="inp" value={heroForm.data.speed} onChange={(event) => heroForm.setData('speed', Number(event.target.value))}>
                                <option value="0.4">0.4s — quick</option>
                                <option value="0.7">0.7s — balanced</option>
                                <option value="1">1.0s — cinematic</option>
                            </select>
                        </label>

                        <label className="flex items-center justify-between text-sm mb-3">
                            Navigation arrows
                            <input
                                type="checkbox"
                                className="h-4 w-4 accent-pur"
                                checked={heroForm.data.show_arrows}
                                onChange={(event) => heroForm.setData('show_arrows', event.target.checked)}
                            />
                        </label>

                        <label className="flex items-center justify-between text-sm mb-3">
                            Pagination dots
                            <input
                                type="checkbox"
                                className="h-4 w-4 accent-pur"
                                checked={heroForm.data.show_dots}
                                onChange={(event) => heroForm.setData('show_dots', event.target.checked)}
                            />
                        </label>

                        <label className="flex items-center justify-between text-sm mb-3">
                            Pause on hover
                            <input
                                type="checkbox"
                                className="h-4 w-4 accent-pur"
                                checked={heroForm.data.pause_hover}
                                onChange={(event) => heroForm.setData('pause_hover', event.target.checked)}
                            />
                        </label>
                    </div>
                </div>

                <button className="btn" disabled={heroForm.processing || optimizing}>
                    {heroForm.processing ? 'Saving…' : 'Save hero gallery'}
                </button>
            </form>

            <div className="mt-6 bg-white rounded-2xl p-4 shadow-sm">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 className="text-sm font-semibold">Live preview</h2>
                        <p className="text-[11px] text-gray-400">Unsaved changes are preview-only until you save.</p>
                    </div>
                    <div className="flex gap-1 rounded-full bg-purple-50 p-1">
                        <button
                            type="button"
                            onClick={() => setPreviewDevice('desktop')}
                            className={`rounded-full px-3 py-1 text-xs font-bold ${previewDevice === 'desktop' ? 'grad text-white' : 'text-gray-500'}`}
                        >
                            Desktop
                        </button>
                        <button
                            type="button"
                            onClick={() => setPreviewDevice('mobile')}
                            className={`rounded-full px-3 py-1 text-xs font-bold ${previewDevice === 'mobile' ? 'grad text-white' : 'text-gray-500'}`}
                        >
                            Mobile
                        </button>
                    </div>
                </div>

                {previewImages.length > 0 ? (
                    previewDevice === 'desktop' ? (
                        <div className="rounded-2xl bg-gradient-to-br from-[#1d1029] to-[#2b1639] p-3">
                            <StoreHero preview images={previewImages} settings={previewSettings} copy={copyPreview} />
                        </div>
                    ) : (
                        <div className="flex justify-center rounded-2xl bg-[#f4eef8] p-3">
                            <div className="w-[340px] max-w-full rounded-[34px] border-8 border-[#241a2e] bg-[#241a2e] p-1">
                                <div className="overflow-hidden rounded-[26px]">
                                    <StoreHero preview images={previewImages} settings={previewSettings} copy={copyPreview} device="mobile" />
                                </div>
                            </div>
                        </div>
                    )
                ) : (
                    <div className="rounded-2xl border border-dashed border-purple-200 p-8 text-center text-sm text-gray-400">
                        Add at least one image to preview the 3D hero.
                    </div>
                )}
            </div>
        </>
    );
}

Homepage.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default Homepage;
