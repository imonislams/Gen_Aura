import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StoreLayout from '../../Layouts/StoreLayout';
import { SocialLinks } from '../../Components/SocialIcons';

function About({ aboutImages = [] }) {
    const { settings } = usePage().props;
    const why = (settings.why_choose_us || '').split('|').map((item) => item.trim()).filter(Boolean);
    const [activeImage, setActiveImage] = useState(0);
    const imageCount = aboutImages.length;
    const currentImage = imageCount > 0 ? aboutImages[Math.min(activeImage, imageCount - 1)] : null;

    const goToImage = (index) => {
        if (imageCount < 2) {
            return;
        }

        setActiveImage(((index % imageCount) + imageCount) % imageCount);
    };

    return (
        <>
            <Head title="About" />

            <section className="max-w-5xl mx-auto px-4 py-10">
                <div className="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h1 className="font-serif text-4xl md:text-5xl font-bold">{settings.about_title}</h1>
                        <p className="mt-4 text-gray-600 whitespace-pre-line">{settings.about_body}</p>
                        <SocialLinks settings={settings} className="mt-5" />
                    </div>
                    <div>
                        {currentImage ? (
                            <div className="relative">
                                <img
                                    src={currentImage}
                                    alt={settings.name}
                                    className="w-full aspect-square object-cover rounded-3xl shadow-xl"
                                />

                                {imageCount > 1 && (
                                    <>
                                        <button
                                            type="button"
                                            aria-label="Previous about image"
                                            onClick={() => goToImage(activeImage - 1)}
                                            className="absolute left-3 top-1/2 -translate-y-1/2 grid h-9 w-9 place-items-center rounded-full bg-white/85 text-gray-700 shadow-lg backdrop-blur transition hover:bg-white"
                                        >
                                            ‹
                                        </button>
                                        <button
                                            type="button"
                                            aria-label="Next about image"
                                            onClick={() => goToImage(activeImage + 1)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 grid h-9 w-9 place-items-center rounded-full bg-white/85 text-gray-700 shadow-lg backdrop-blur transition hover:bg-white"
                                        >
                                            ›
                                        </button>
                                        <div className="absolute inset-x-0 bottom-4 flex justify-center gap-1.5">
                                            {aboutImages.map((image, index) => (
                                                <button
                                                    key={`${image}-${index}`}
                                                    type="button"
                                                    aria-label={`Go to about image ${index + 1}`}
                                                    onClick={() => setActiveImage(index)}
                                                    className={`h-1.5 rounded-full transition-all ${
                                                        index === activeImage ? 'w-5 bg-white' : 'w-1.5 bg-white/60 hover:bg-white/90'
                                                    }`}
                                                />
                                            ))}
                                        </div>
                                    </>
                                )}
                            </div>
                        ) : (
                            <div className="w-full aspect-square rounded-3xl grad grid place-items-center text-white font-serif text-7xl shadow-xl">A</div>
                        )}
                    </div>
                </div>

                {why.length > 0 && (
                    <div className="grid md:grid-cols-3 gap-4 mt-10">
                        {why.map((item, index) => (
                            <div key={index} className="bg-white rounded-2xl p-5 text-center shadow-sm">
                                <div className="text-2xl">✦</div>
                                <b>{item}</b>
                            </div>
                        ))}
                    </div>
                )}

                <div className="bg-white rounded-2xl p-6 grid md:grid-cols-3 gap-4 text-sm mt-8">
                    <div><div className="text-xs text-gray-400 uppercase">Phone</div><div>{settings.phone || '—'}</div></div>
                    <div><div className="text-xs text-gray-400 uppercase">Email</div><div>{settings.email || '—'}</div></div>
                    <div><div className="text-xs text-gray-400 uppercase">Address</div><div>{settings.address || '—'}</div></div>
                </div>
            </section>
        </>
    );
}

About.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default About;
