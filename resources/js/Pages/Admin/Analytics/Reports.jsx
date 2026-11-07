import { Head } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import DateRangeFilter from '../../../Components/DateRangeFilter';

function Reports({ groups, range }) {
    return (
        <>
            <Head title="Analytics reports" />

            <div className="flex items-end justify-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 className="font-serif text-3xl font-bold">Reports</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Reports respect the selected date range. PDF/Excel export is the next slice.
                    </p>
                </div>
                <DateRangeFilter range={range} url="/admin/analytics/reports" />
            </div>

            <div className="grid md:grid-cols-2 gap-4">
                {groups.map((group) => (
                    <div key={group.group} className="bg-white rounded-2xl p-4">
                        <h2 className="font-semibold mb-3">{group.group} reports</h2>
                        {group.items.map((item) => (
                            <div key={item} className="flex justify-between gap-2 border-t border-gray-100 py-2 text-sm first:border-t-0 first:pt-0">
                                <span>{item}</span>
                                <span className="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">Export soon</span>
                            </div>
                        ))}
                    </div>
                ))}
            </div>

            <div className="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3 text-xs mt-4">
                All numbers come from recorded analytics and order data. Exports will reuse the same date filter so the file matches the screen.
            </div>
        </>
    );
}

Reports.layout = (page) => <AdminLayout>{page}</AdminLayout>;

export default Reports;
