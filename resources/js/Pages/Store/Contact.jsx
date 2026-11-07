import { Head, usePage } from '@inertiajs/react';
import StoreLayout from '../../Layouts/StoreLayout';

function Contact() {
    const { settings, translations } = usePage().props;
    const t = (value) => translations?.[value] ?? value;

    return (
        <>
            <Head title="Contact us" />

            <section className="max-w-xl mx-auto px-4 py-12 text-center">
                <h1 className="font-serif text-4xl gtext">{t('Contact us')}</h1>
                <p className="text-gray-600 my-3">{t('Questions? Do not hesitate to reach us.')}</p>

                <div className="bg-white rounded-2xl p-6 text-left space-y-2 text-sm">
                    {settings.phone && <div><b>{t('Phone')}:</b> {settings.phone}</div>}
                    {settings.email && <div><b>{t('Email')}:</b> {settings.email}</div>}
                    {settings.address && <div><b>Address:</b> {settings.address}</div>}
                </div>

                <a className="btn mt-4" href={`https://wa.me/${settings.wa}`}>
                    {t('Chat on WhatsApp')}: +{settings.wa}
                </a>

                <div className="flex gap-3 justify-center mt-4 flex-wrap">
                    {settings.fb && <a href={settings.fb} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">Facebook</a>}
                    {settings.instagram && <a href={settings.instagram} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">Instagram</a>}
                    {settings.youtube && <a href={settings.youtube} target="_blank" rel="noreferrer" className="px-4 py-2 rounded-full border font-semibold">YouTube</a>}
                </div>
            </section>
        </>
    );
}

Contact.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default Contact;
