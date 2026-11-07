import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function Settings({ adminUsername }) {
    const { settings, auth } = usePage().props;

    const settingsForm = useForm({
        name: settings.name ?? '',
        ar: settings.ar ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        wa: settings.wa ?? '',
        ship: settings.ship ?? 60,
        cats: settings.cats ?? '',
        currency: settings.currency ?? 'BDT',
        order_prefix: settings.order_prefix ?? 'GA',
        cod_enabled: settings.cod_enabled ?? '1',
        stock_display: settings.stock_display ?? '1',
        top: settings.top ?? '',
        title: settings.title ?? '',
        sub: settings.sub ?? '',
        promo_title: settings.promo_title ?? '',
        promo_text: settings.promo_text ?? '',
        why_choose_us: settings.why_choose_us ?? '',
        about_title: settings.about_title ?? '',
        about_body: settings.about_body ?? '',
        fb: settings.fb ?? '',
        instagram: settings.instagram ?? '',
        youtube: settings.youtube ?? '',
        logo: null,
        favicon: null,
        hero_image: null,
        about_image: null,
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

    const field = (key, label) => (
        <label key={key} className="block text-sm mb-3">
            {label}
            <input className="inp" value={settingsForm.data[key]} onChange={(event) => settingsForm.setData(key, event.target.value)} />
            {settingsForm.errors[key] && <span className="text-xs text-red-600">{settingsForm.errors[key]}</span>}
        </label>
    );

    return (
        <>
            <Head title="Settings" />

            <div className="mb-5">
                <h1 className="font-serif text-3xl font-bold">Settings</h1>
                <p className="text-sm text-gray-500 mt-1">Store content, branding, delivery and admin login</p>
            </div>

            <form onSubmit={saveSettings} className="space-y-4 mb-6">
                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">General</h2>
                    <div className="grid md:grid-cols-2 gap-x-5">
                        {field('name', 'Store name')}
                        {field('ar', 'Arabic name')}
                        {field('phone', 'Phone')}
                        {field('email', 'Email')}
                        {field('address', 'Address')}
                        {field('wa', 'WhatsApp number (880…)')}
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <label className="block text-sm">
                            Logo
                            <input type="file" accept="image/*" className="inp" onChange={(event) => settingsForm.setData('logo', event.target.files[0])} />
                            {settings.logo && <img src={`/storage/${settings.logo}`} alt="Logo" className="h-12 mt-2 object-contain" />}
                        </label>
                        <label className="block text-sm">
                            Favicon
                            <input type="file" accept=".ico,image/*" className="inp" onChange={(event) => settingsForm.setData('favicon', event.target.files[0])} />
                            {settings.favicon ? <img src={`/storage/${settings.favicon}`} alt="Favicon" className="h-8 mt-2 object-contain" /> : <div className="text-xs text-gray-400 mt-1">Uses the Gen Aura brand mark when empty.</div>}
                        </label>
                    </div>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">Store</h2>
                    <div className="grid md:grid-cols-2 gap-x-5">
                        {field('currency', 'Currency')}
                        {field('ship', 'Delivery charge (৳)')}
                        {field('order_prefix', 'Order number prefix')}
                        {field('cats', 'Legacy category list (manage in Categories)')}
                        <label className="block text-sm mb-3">
                            COD
                            <select className="inp" value={settingsForm.data.cod_enabled} onChange={(event) => settingsForm.setData('cod_enabled', event.target.value)}>
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </label>
                        <label className="block text-sm mb-3">
                            Stock display
                            <select className="inp" value={settingsForm.data.stock_display} onChange={(event) => settingsForm.setData('stock_display', event.target.value)}>
                                <option value="1">Show low stock</option>
                                <option value="0">Hide</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">Homepage</h2>
                    <div className="grid md:grid-cols-2 gap-x-5">
                        {field('top', 'Announcement text')}
                        {field('title', 'Hero title')}
                        {field('sub', 'Hero subtitle')}
                        {field('promo_title', 'Promo title')}
                        {field('promo_text', 'Promo text')}
                        {field('why_choose_us', 'Why choose us (separate with |)')}
                    </div>
                    <label className="block text-sm">
                        Hero image
                        <input type="file" accept="image/*" className="inp" onChange={(event) => settingsForm.setData('hero_image', event.target.files[0])} />
                        {settings.hero_image && <img src={`/storage/${settings.hero_image}`} alt="Hero" className="h-20 mt-2 rounded-lg object-cover" />}
                    </label>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">About page</h2>
                    {field('about_title', 'About title')}
                    <label className="block text-sm mb-3">
                        About body
                        <textarea className="inp" rows="4" value={settingsForm.data.about_body} onChange={(event) => settingsForm.setData('about_body', event.target.value)} />
                    </label>
                    <label className="block text-sm">
                        About image
                        <input type="file" accept="image/*" className="inp" onChange={(event) => settingsForm.setData('about_image', event.target.files[0])} />
                        {settings.about_image && <img src={`/storage/${settings.about_image}`} alt="About" className="h-20 mt-2 rounded-lg object-cover" />}
                    </label>
                </div>

                <div className="bg-white rounded-2xl p-5">
                    <h2 className="font-semibold mb-3">Social links</h2>
                    <div className="grid md:grid-cols-3 gap-x-5">
                        {field('fb', 'Facebook URL')}
                        {field('instagram', 'Instagram URL')}
                        {field('youtube', 'YouTube URL')}
                    </div>
                </div>

                <button className="btn" disabled={settingsForm.processing}>
                    {settingsForm.processing ? 'Saving…' : 'Save settings'}
                </button>
            </form>

            <form onSubmit={savePassword} className="bg-white rounded-2xl p-5 grid md:grid-cols-3 gap-x-5">
                <h2 className="font-semibold md:col-span-3 mb-2">Change admin login</h2>
                {auth?.admin?.must_change_password && (
                    <div className="md:col-span-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3 text-sm mb-3">
                        You are using the default password. Change it below before using the rest of the admin panel.
                    </div>
                )}
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
