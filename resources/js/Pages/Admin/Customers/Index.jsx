import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { dhakaDate } from '../../../lib/datetime';

function Customers({ customers, filters }) {
    const [q, setQ] = useState(filters?.q ?? '');

    const search = (event) => {
        event.preventDefault();
        router.get('/admin/customers', { q }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <>
            <Head title="Customers" />

            <div className="flex items-end justify-between gap-3 flex-wrap mb-5">
                <div>
                    <h1 className="font-serif text-3xl font-bold">Customers</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Created from orders · separate from anonymous analytics
                    </p>
                </div>
                <form onSubmit={search} className="flex gap-2">
                    <input
                        className="border rounded-full px-4 py-2 text-sm"
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Search name or phone"
                    />
                    <button className="btn btn-sm">Search</button>
                </form>
            </div>

            <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div className="overflow-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wide text-gray-500 bg-purple-50/40">
                                <th className="px-3 py-2">Customer</th>
                                <th className="px-3 py-2">Phone</th>
                                <th className="px-3 py-2">Orders</th>
                                <th className="px-3 py-2">Total purchase</th>
                                <th className="px-3 py-2">Last order</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.map((customer) => (
                                <tr key={customer.id} className="border-t border-gray-100">
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <span className="w-8 h-8 rounded-full grad text-white grid place-items-center text-xs font-bold">
                                                {customer.name?.charAt(0)?.toUpperCase() || 'C'}
                                            </span>
                                            <b>{customer.name}</b>
                                        </div>
                                    </td>
                                    <td className="px-3 py-2 text-gray-500">{customer.phone}</td>
                                    <td className="px-3 py-2">{customer.total_orders}</td>
                                    <td className="px-3 py-2 font-semibold">
                                        ৳{Number(customer.total_purchase).toLocaleString()}
                                    </td>
                                    <td className="px-3 py-2 text-gray-500">{dhakaDate(customer.last_order_at)}</td>
                                    <td className="px-3 py-2 text-right">
                                        <Link
                                            href={`/admin/customers/${customer.id}`}
                                            className="text-pur text-sm font-semibold"
                                        >
                                            View history
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {customers.data.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="px-3 py-10 text-center text-gray-500">
                                        No customers yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="flex gap-1 flex-wrap justify-end mt-3">
                {customers.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`px-3 py-1.5 rounded-lg border text-sm font-semibold ${
                                link.active ? 'grad text-white border-transparent' : 'bg-white text-gray-600'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="px-3 py-1.5 rounded-lg border text-sm text-gray-400"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )
                )}
            </div>
        </>
    );
}

Customers.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default Customers;
