import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Components/Admin/AdminLayout';
import { route } from 'ziggy-js';

const money = (value) => {
    if (!value) return '—';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(Number(value));
};

export default function Dashboard({ leads, filters }) {
    const [search, setSearch] = useState(filters?.search ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get(route('admin.dashboard'), { search }, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout title="Bewertungen">
            <div className="mb-7 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p className="text-[10px] font-semibold uppercase tracking-[.17em] text-[#a24a3d]">2 COME HOME Backoffice</p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-[-.03em] text-[#172741]">Bewertungen</h1>
                    <p className="mt-2 text-sm text-[#74776f]">Leads prüfen, Bewertungen anpassen und PDFs neu erzeugen.</p>
                </div>

                <form onSubmit={submit} className="flex w-full max-w-md gap-2">
                    <input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Name, E-Mail, Ort, Straße, onOffice-ID …"
                        className="min-w-0 flex-1 rounded-md border border-[#d7d4cc] bg-white px-3 py-2.5 text-sm"
                    />
                    <button className="rounded-md bg-[#172741] px-4 py-2.5 text-sm font-semibold text-white">Suchen</button>
                </form>
            </div>

            <div className="overflow-hidden rounded-xl border border-[#ddd9d0] bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[980px] border-collapse text-left">
                        <thead className="bg-[#f8f7f3] text-[10px] uppercase tracking-[.12em] text-[#777a72]">
                            <tr>
                                <th className="px-5 py-4 font-semibold">Lead</th>
                                <th className="px-5 py-4 font-semibold">Immobilie</th>
                                <th className="px-5 py-4 font-semibold">Landingpage</th>
                                <th className="px-5 py-4 font-semibold">Bewertung</th>
                                <th className="px-5 py-4 font-semibold">Quelle</th>
                                <th className="px-5 py-4 font-semibold">onOffice</th>
                                <th className="px-5 py-4 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#ebe8e1]">
                            {leads.data.map((lead) => (
                                <tr key={lead.id} className="hover:bg-[#fbfaf7]">
                                    <td className="px-5 py-4 align-top">
                                        <div className="font-semibold text-[#27313b]">{lead.name}</div>
                                        <div className="mt-1 text-xs text-[#74776f]">{lead.email || '—'}</div>
                                        <div className="mt-1 text-[11px] text-[#9a9c95]">{lead.created_at}</div>
                                    </td>
                                    <td className="px-5 py-4 align-top text-sm">
                                        <div className="font-medium">{lead.property.type || '—'}</div>
                                        <div className="mt-1 text-xs text-[#74776f]">{[lead.property.address, lead.property.location].filter(Boolean).join(', ') || '—'}</div>
                                    </td>
                                    <td className="px-5 py-4 align-top text-xs text-[#666b65]">
                                        <div>{lead.landing_page || '—'}</div>
                                        <div className="mt-1 text-[#9a9c95]">{lead.landing_page_slug}</div>
                                    </td>
                                    <td className="px-5 py-4 align-top">
                                        <div className="font-semibold text-[#172741]">{money(lead.valuation.estimated_value)}</div>
                                        <div className="mt-1 text-xs text-[#777a72]">{money(lead.valuation.range_low)} – {money(lead.valuation.range_high)}</div>
                                    </td>
                                    <td className="px-5 py-4 align-top text-xs text-[#666b65]">{lead.valuation.source_label || '—'}</td>
                                    <td className="px-5 py-4 align-top text-xs text-[#666b65]">
                                        <div><span className="text-[#9a9c95]">Kontakt:</span> {lead.onoffice?.contact_id || '—'}</div>
                                        <div className="mt-1"><span className="text-[#9a9c95]">Immobilie:</span> {lead.onoffice?.estate_id || '—'}</div>
                                    </td>
                                    <td className="px-5 py-4 text-right align-top">
                                        <Link href={lead.edit_url} className="inline-flex rounded-md border border-[#cfcac0] px-3 py-2 text-xs font-semibold text-[#172741] hover:bg-[#f4f2ed]">
                                            Bearbeiten
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {leads.data.length === 0 && (
                                <tr>
                                    <td colSpan="7" className="px-5 py-12 text-center text-sm text-[#777a72]">Keine Bewertungen gefunden.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {leads.links?.length > 3 && (
                    <div className="flex flex-wrap gap-2 border-t border-[#ebe8e1] px-5 py-4">
                        {leads.links.map((link, index) => (
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveScroll
                                    className={`rounded-md px-3 py-2 text-xs ${link.active ? 'bg-[#172741] text-white' : 'border border-[#d8d4cb] text-[#5e635d]'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span key={index} className="px-3 py-2 text-xs text-[#b0b1ac]" dangerouslySetInnerHTML={{ __html: link.label }} />
                            )
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
