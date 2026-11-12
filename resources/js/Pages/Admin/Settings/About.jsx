import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import SettingsTabs from '../../../Components/SettingsTabs';

const ACCEPT = 'image/jpeg,image/png,image/webp';
const ACCEPT_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 4 * 1024 * 1024;
const MAX_IMAGES = 20;

function savedItem(image, index) {
    return {
        key: `saved-${index}-${image.path}`,
        kind: 'saved',
        path: image.path,
        url: image.url,
        file: null,
        replacement: null,
    };
}

function About({ section, aboutImages = [] }) {
    const { settings } = usePage().props;

    const [items, setItems] = useState(() => aboutImages.map(savedItem));
    const [changeKey, setChangeKey] = useState(null);
    const [dragKey, setDragKey] = useState(null);
    const [dragging, setDragging] = useState(false);
    const [localError, setLocalError] = useState('');
    const addInput = useRef(null);
    const changeInput = useRef(null);
    const newKey = useRef(0);
    const objectUrls = useRef(new Set());

    const form = useForm({
        about_title: settings.about_title ?? '',
        about_body: settings.about_body ?? '',
        about_images: [],
        new_about_images: [],
        replace_about_images: {},
        about_images_present: true,
    });

    useEffect(() => () => {
        objectUrls.current.forEach((url) => URL.revokeObjectURL(url));
        objectUrls.current.clear();
    }, []);

    const makeUrl = (file) => {
        const url = URL.createObjectURL(file);
        objectUrls.current.add(url);

        return url;
    };

    const revokeUrl = (url) => {
        if (url && url.startsWith('blob:')) {
            URL.revokeObjectURL(url);
            objectUrls.current.delete(url);
        }
    };

    const addFiles = (fileList) => {
        const files = Array.from(fileList || []);

        if (!files.length) {
            return;
        }

        setLocalError('');
        const accepted = [];

        for (const file of files) {
            if (!ACCEPT_TYPES.includes(file.type)) {
                setLocalError('Use JPG, PNG or WebP images only.');
                continue;
            }

            if (file.size > MAX_BYTES) {
                setLocalError('Each image must be 4 MB or smaller.');
                continue;
            }

            accepted.push(file);
        }

        const remaining = MAX_IMAGES - items.length;

        if (remaining <= 0) {
            setLocalError('You can add up to 20 images.');

            return;
        }

        if (accepted.length > remaining) {
            setLocalError('Only 20 images are allowed; extra files were skipped.');
        }

        const chosen = accepted.slice(0, remaining);

        if (!chosen.length) {
            return;
        }

        setItems((prev) => [
            ...prev,
            ...chosen.map((file) => {
                newKey.current += 1;

                return {
                    key: `new-${newKey.current}`,
                    kind: 'new',
                    path: null,
                    file,
                    replacement: null,
                    url: makeUrl(file),
                };
            }),
        ]);
    };

    const startChange = (item) => {
        setChangeKey(item.key);
        changeInput.current?.click();
    };

    const applyChange = (file) => {
        if (!file || !changeKey) {
            return;
        }

        if (!ACCEPT_TYPES.includes(file.type)) {
            setLocalError('Use JPG, PNG or WebP images only.');

            return;
        }

        if (file.size > MAX_BYTES) {
            setLocalError('Each image must be 4 MB or smaller.');

            return;
        }

        setItems((prev) => prev.map((item) => {
            if (item.key !== changeKey) {
                return item;
            }

            revokeUrl(item.url);

            return {
                ...item,
                file: item.kind === 'new' ? file : item.file,
                replacement: item.kind === 'saved' ? file : item.replacement,
                url: makeUrl(file),
            };
        }));

        setChangeKey(null);
        setLocalError('');
    };

    const removeItem = (item) => {
        if (!window.confirm('Remove this image? This will take effect after you save.')) {
            return;
        }

        revokeUrl(item.url);
        setItems((prev) => prev.filter((entry) => entry.key !== item.key));
    };

    const dropItem = (targetKey) => {
        if (!dragKey || dragKey === targetKey) {
            setDragKey(null);

            return;
        }

        setItems((prev) => {
            const from = prev.findIndex((item) => item.key === dragKey);
            const to = prev.findIndex((item) => item.key === targetKey);

            if (from < 0 || to < 0) {
                return prev;
            }

            const next = [...prev];
            const [moved] = next.splice(from, 1);
            next.splice(to, 0, moved);

            return next;
        });

        setDragKey(null);
    };

    const submit = (event) => {
        event.preventDefault();

        const tokens = [];
        const newFiles = [];
        const replacements = {};
        let newIndex = 0;

        items.forEach((item, index) => {
            if (item.kind === 'saved') {
                tokens.push(item.path);

                if (item.replacement) {
                    replacements[index] = item.replacement;
                }
            } else {
                tokens.push(`__new_${newIndex}__`);
                newFiles[newIndex] = item.file;
                newIndex += 1;
            }
        });

        form.transform((data) => ({
            ...data,
            about_images: tokens,
            new_about_images: newFiles,
            replace_about_images: replacements,
            about_images_present: true,
        }));

        form.post('/admin/settings/about', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                objectUrls.current.forEach((url) => URL.revokeObjectURL(url));
                objectUrls.current.clear();
                setItems((page.props.aboutImages || []).map(savedItem));
                setLocalError('');
            },
        });
    };

    return (
        <>
            <Head title="Settings · About" />

            <div className="mb-5">
                <h1 className="font-serif text-3xl font-bold">About page</h1>
                <p className="text-sm text-gray-500 mt-1">About section content and images</p>
            </div>

            <SettingsTabs section={section} />

            <form onSubmit={submit} className="space-y-4">
                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">About content</h2>
                    <label className="block text-sm mb-3">
                        About title
                        <input className="inp" value={form.data.about_title} onChange={(event) => form.setData('about_title', event.target.value)} />
                        {form.errors.about_title && <span className="text-xs text-red-600">{form.errors.about_title}</span>}
                    </label>
                    <label className="block text-sm mb-3">
                        About body
                        <textarea className="inp" rows="4" value={form.data.about_body} onChange={(event) => form.setData('about_body', event.target.value)} />
                        {form.errors.about_body && <span className="text-xs text-red-600">{form.errors.about_body}</span>}
                    </label>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">About images</h2>
                            <p className="text-sm text-gray-500">
                                Upload multiple images. Drag to reorder; the About page displays them in this order.
                            </p>
                        </div>
                        <button
                            type="button"
                            className="btn"
                            onClick={() => addInput.current?.click()}
                            disabled={form.processing}
                        >
                            + Add Images
                        </button>
                    </div>

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
                        className={`mt-3 rounded-2xl border-2 border-dashed p-5 text-center transition ${
                            dragging ? 'border-pur bg-purple-50' : 'border-purple-200 bg-purple-50/30'
                        }`}
                    >
                        <div className="mx-auto mb-2 grid h-11 w-11 place-items-center rounded-full grad text-2xl font-light text-white">
                            +
                        </div>
                        <b className="block text-sm">Drop about images here</b>
                        <div className="my-1 text-[11px] text-gray-400">or</div>
                        <button
                            type="button"
                            className="btn !px-4 !py-2 text-sm"
                            onClick={() => addInput.current?.click()}
                            disabled={form.processing}
                        >
                            + Choose images
                        </button>
                        <div className="mt-2 text-[11px] text-gray-500">JPG, PNG or WebP · up to 4 MB each · multiple selection</div>
                        <input
                            ref={addInput}
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

                    {form.progress && (
                        <div className="mt-3">
                            <div className="h-2 overflow-hidden rounded-full bg-purple-100">
                                <div className="h-full bg-gradient-to-r from-pur to-org transition-all" style={{ width: `${form.progress.percentage}%` }} />
                            </div>
                            <div className="mt-1 text-[10px] text-gray-400">Uploading {form.progress.percentage}%</div>
                        </div>
                    )}

                    {(localError || Object.keys(form.errors).length > 0) && (
                        <div className="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                            {localError || Object.values(form.errors)[0]}
                        </div>
                    )}

                    <input
                        ref={changeInput}
                        type="file"
                        accept={ACCEPT}
                        className="hidden"
                        onChange={(event) => {
                            applyChange(event.target.files[0]);
                            event.target.value = '';
                        }}
                    />

                    <div className="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
                        {items.map((item, index) => {
                            const isNew = item.kind === 'new' || !!item.replacement;
                            const label = isNew ? 'NEW' : 'SAVED';

                            return (
                                <div
                                    key={item.key}
                                    draggable
                                    onDragStart={() => setDragKey(item.key)}
                                    onDragEnd={() => setDragKey(null)}
                                    onDragOver={(event) => event.preventDefault()}
                                    onDrop={() => dropItem(item.key)}
                                    className={`overflow-hidden rounded-xl border bg-white shadow-sm transition ${
                                        dragKey === item.key ? 'border-pur ring-4 ring-purple-100' : 'border-gray-100'
                                    }`}
                                >
                                    <div className="relative aspect-square bg-gray-50">
                                        <img src={item.url} alt="" className="h-full w-full object-cover" />
                                        <span className={`absolute left-2 top-2 rounded-full px-2 py-0.5 text-[9px] font-extrabold ${
                                            isNew ? 'grad text-white' : 'bg-white/90 text-gray-600'
                                        }`}>
                                            {label}
                                        </span>
                                        <span className="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-full bg-black/50 text-[10px] font-bold text-white">
                                            {index + 1}
                                        </span>
                                        <span className="absolute bottom-2 left-2 cursor-grab select-none rounded-full bg-black/45 px-2 py-0.5 text-[10px] text-white" title="Drag to reorder">
                                            ⠿
                                        </span>
                                    </div>
                                    <div className="flex gap-2 p-2">
                                        <button
                                            type="button"
                                            className="flex-1 rounded-full border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-600 hover:bg-gray-50"
                                            onClick={() => startChange(item)}
                                        >
                                            Change image
                                        </button>
                                        <button
                                            type="button"
                                            className="rounded-full border border-red-100 px-2 py-1 text-[11px] font-semibold text-red-500 hover:bg-red-50"
                                            onClick={() => removeItem(item)}
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            );
                        })}

                        {items.length === 0 && (
                            <div className="col-span-2 rounded-xl border border-dashed border-purple-200 p-8 text-center text-sm text-gray-400 md:col-span-4">
                                No about images yet. Add images above or drop them here.
                            </div>
                        )}
                    </div>

                    <div className="mt-2 text-[10px] text-gray-400">
                        Drag images to change their order. NEW images are saved only after you click “Save about page”.
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <button className="btn" disabled={form.processing}>
                        {form.processing ? 'Saving…' : 'Save about page'}
                    </button>
                    {form.recentlySuccessful && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1.5 text-sm font-semibold text-green-700">
                            ✓ Saved
                        </span>
                    )}
                </div>
            </form>
        </>
    );
}

About.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default About;
