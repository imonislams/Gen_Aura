import { Head, usePage } from '@inertiajs/react';
import StoreLayout from '../../Layouts/StoreLayout';

function About() {
    const { settings, translations } = usePage().props;
    const t = (value) => translations?.[value] ?? value;
    const why = (settings.why_choose_us || '').split('|').map((item) => item.trim()).filter(Boolean);

    return (
        <>
            <Head title="About" />

            <section className="max-w-5xl mx-auto px-4 py-10">
                <div className="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h1 className="font-serif text-4xl md:text-5xl font-bold">{settings.about_title}</h1>
                        <p className="mt-4 text-gray-600 whitespace-pre-line">{settings.about_body}</p>
                        <div className="flex gap-3 flex-wrap mt-5">
                            {settings.fb && <a href={settings.fb} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">Facebook</a>}
                            {settings.instagram && <a href={settings.instagram} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">Instagram</a>}
                            {settings.youtube && <a href={settings.youtube} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">YouTube</a>}
                        </div>
                    </div>
                    <div>
                        {settings.about_image ? (
                            <img src={`/storage/${settings.about_image}`} alt={settings.name} className="w-full aspect-square object-cover rounded-3xl shadow-xl" />
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
                    <div><div className="text-xs text-gray-400 uppercase">{t('Phone')}</div><div>{settings.phone || '—'}</div></div>
                    <div><div className="text-xs text-gray-400 uppercase">{t('Email')}</div><div>{settings.email || '—'}</div></div>
                    <div><div className="text-xs text-gray-400 uppercase">Address</div><div>{settings.address || '—'}</div></div>
                </div>
            </section>
        </>
    );
}

About.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default About;
