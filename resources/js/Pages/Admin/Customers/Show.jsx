import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { dhakaDate } from '../../../lib/datetime';

function CustomerShow({ customer }) {
    return (
        <>
            <Head title={`Customer · ${customer.name}`} />

            <div className="mb-5">
                <Link href="/admin/customers" className="text-sm text-pur font-semibold">
                    ← All customers
                </Link>
                <h1 className="font-serif text-3xl font-bold mt-1">{customer.name}</h1>
                <p className="text-sm text-gray-500">
                    {customer.phone} · customer since {dhakaDate(customer.created_at)}
                </p>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                <div className="bg-white rounded-xl p-4">
                    <div className="text-xs text-gray-500">Total orders</div>
                    <div className="text-2xl font-bold gtext">{customer.total_orders}</div>
                </div>
                <div className="bg-white rounded-xl p-4">
                    <div className="text-xs text-gray-500">Total purchase</div>
                    <div className="text-2xl font-bold gtext">
                        ৳{Number(customer.total_purchase).toLocaleString()}
                    </div>
                </div>
                <div className="bg-white rounded-xl p-4">
                    <div className="text-xs text-gray-500">Last order</div>
                    <div className="text-lg font-bold">
                        {dhakaDate(customer.last_order_at)}
                    </div>
                </div>
                <div className="bg-white rounded-xl p-4">
                    <div className="text-xs text-gray-500">Orders shown</div>
                    <div className="text-2xl font-bold gtext">{customer.orders.length}</div>
                </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm p-4">
                <h2 className="font-semibold mb-3">Order history</h2>
                <div className="overflow-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th className="px-2 py-2">Order</th>
                                <th className="px-2 py-2">Date</th>
                                <th className="px-2 py-2">Items</th>
                                <th className="px-2 py-2">Total</th>
                                <th className="px-2 py-2">Status</th>
                                <th className="px-2 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customer.orders.map((order) => (
                                <tr key={order.id} className="border-t border-gray-100">
                                    <td className="px-2 py-2 font-semibold">{order.order_number || `#${order.id}`}</td>
                                    <td className="px-2 py-2 text-gray-500">
                                        {dhakaDate(order.created_at)}
                                    </td>
                                    <td className="px-2 py-2">{order.items_count}</td>
                                    <td className="px-2 py-2 font-semibold">
                                        ৳{Number(order.total).toLocaleString()}
                                    </td>
                                    <td className="px-2 py-2">
                                        <span className="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                                            {order.status}
                                        </span>
                                    </td>
                                    <td className="px-2 py-2 text-right">
                                        <Link
                                            href={`/admin/orders/${order.id}`}
                                            className="text-pur text-sm font-semibold"
                                        >
                                            View order
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {customer.orders.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="px-2 py-8 text-center text-gray-500">
                                        No orders yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

CustomerShow.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default CustomerShow;
