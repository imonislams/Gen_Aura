import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function Settings({ adminUsername }) {
    const { settings } = usePage().props;

    const settingsForm = useForm({
        name: settings.name ?? '',
        ar: settings.ar ?? '',
        top: settings.top ?? '',
        title: settings.title ?? '',
        sub: settings.sub ?? '',
        wa: settings.wa ?? '',
        ship: settings.ship ?? 60,
        cats: settings.cats ?? '',
        fb: settings.fb ?? '',
        hero_image: null,
    });

    const passwordForm = useForm({
        username: adminUsername ?? '',
        current: '',
        new_password: '',
    });

    const saveSettings = (event) => {
        event.preventDefault();
        settingsForm.post('/admin/settings', { forceFormData: true, preserveScroll: true });
    };

    const savePassword = (event) => {
        event.preventDefault();
        passwordForm.post('/admin/password', { preserveScroll: true });
    };

    return (
        <>
            <Head title="Settings" />

            <div className="mb-5">
                <h1 className="font-serif text-3xl font-bold">Settings</h1>
                <p className="text-sm text-gray-500 mt-1">Store content, delivery and admin login</p>
            </div>

            <form onSubmit={saveSettings} className="bg-white rounded-2xl p-5 grid md:grid-cols-2 gap-x-5 mb-6">
                {[
                    ['name', 'Store name'],
                    ['ar', 'Arabic name'],
                    ['top', 'Top bar text'],
                    ['title', 'Hero title'],
                    ['sub', 'Hero subtitle'],
                    ['wa', 'WhatsApp number (880…)'],
                    ['ship', 'Delivery charge (৳)'],
                    ['cats', 'Legacy category list (manage in Categories)'],
                    ['fb', 'Facebook page link'],
                ].map(([key, label]) => (
                    <label key={key} className="block text-sm mb-3">
                        {label}
                        <input className="inp" value={settingsForm.data[key]} onChange={(event) => settingsForm.setData(key, event.target.value)} />
                        {settingsForm.errors[key] && <span className="text-xs text-red-600">{settingsForm.errors[key]}</span>}
                    </label>
                ))}

                <div className="md:col-span-2">
                    <label className="block text-sm mb-2">
                        Hero card image (jpg/png/webp, max 4MB)
                        <input type="file" accept="image/jpeg,image/png,image/webp" className="inp" onChange={(event) => settingsForm.setData('hero_image', event.target.files[0])} />
                    </label>
                    {settings.hero_image && (
                        <div className="flex items-center gap-3 mb-3">
                            <img src={`/storage/${settings.hero_image}`} alt="Hero" className="w-24 h-24 rounded-xl object-cover border" />
                            <span className="text-xs text-gray-500">Current image (upload a new one to replace)</span>
                        </div>
                    )}
                </div>

                <div className="md:col-span-2">
                    <button className="btn" disabled={settingsForm.processing}>{settingsForm.processing ? 'Saving…' : 'Save settings'}</button>
                </div>
            </form>

            <form onSubmit={savePassword} className="bg-white rounded-2xl p-5 grid md:grid-cols-3 gap-x-5">
                <h2 className="font-semibold md:col-span-3 mb-2">Change admin login</h2>
                <label className="block text-sm mb-3">
                    Username
                    <input className="inp" value={passwordForm.data.username} onChange={(event) => passwordForm.setData('username', event.target.value)} required />
                </label>
                <label className="block text-sm mb-3">
                    Current password
                    <input type="password" className="inp" value={passwordForm.data.current} onChange={(event) => passwordForm.setData('current', event.target.value)} required />
                </label>
                <label className="block text-sm mb-3">
                    New password (min 6)
                    <input type="password" className="inp" value={passwordForm.data.new_password} onChange={(event) => passwordForm.setData('new_password', event.target.value)} required />
                </label>
                <div className="md:col-span-3">
                    <button className="btn" disabled={passwordForm.processing}>{passwordForm.processing ? 'Updating…' : 'Update login'}</button>
                </div>
            </form>

            <div className="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3 text-xs mt-4">
                Categories now live in their own module. The legacy category string is kept only for backward compatibility.
            </div>
        </>
    );
}

Settings.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default Settings;
