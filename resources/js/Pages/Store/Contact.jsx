import { Head, usePage } from '@inertiajs/react';
import StoreLayout from '../../Layouts/StoreLayout';

function Contact() {
    const { settings, translations } = usePage().props;
    const t = (value) => translations?.[value] ?? value;

    return (
        <>
            <Head title={t('Contact us')} />

            <section className="max-w-xl mx-auto px-4 py-12 text-center">
                <h2 className="font-serif text-4xl gtext">{t('Contact us')}</h2>
                <p className="text-gray-600 my-3">{t('Questions? Do not hesitate to reach us.')}</p>
                <a className="btn" href={`https://wa.me/${settings.wa}`}>
                    {t('Chat on WhatsApp')}: +{settings.wa}
                </a>
                {settings.fb && (
                    <a className="block mt-4 text-pur font-semibold" href={settings.fb}>
                        Facebook: {settings.name}
                    </a>
                )}
            </section>
        </>
    );
}

Contact.layout = (page) => <StoreLayout>{page}</StoreLayout>;

export default Contact;
