import { Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Components/Admin/AdminLayout';
import { route } from 'ziggy-js';

const money = (value) => {
    if (!value) return '—';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(Number(value));
};

export default function LeadEdit({ lead }) {
    const { flash } = usePage().props;
    const valuation = lead.valuation ?? {};

    const { data, setData, put, processing, errors } = useForm({
        estimated_value: valuation.estimated_value ?? '',
        range_low: valuation.range_low ?? '',
        range_high: valuation.range_high ?? '',
    });

    const submit = (event) => {
        event.preventDefault();
        put(route('admin.leads.valuation.update', lead.id), { preserveScroll: true });
    };

    const address = [
        [lead.property?.street, lead.property?.house_number].filter(Boolean).join(' '),
        [lead.property?.zip, lead.property?.city].filter(Boolean).join(' '),
    ].filter(Boolean).join(', ');

    return (
        <AdminLayout title={`Bewertung #${lead.id}`}>
            <div className="mb-6">
                <Link href={route('admin.dashboard')} className="text-xs font-semibold text-[#a24a3d] hover:underline">← Zurück zu allen Bewertungen</Link>
            </div>

            <div className="mb-7 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <p className="text-[10px] font-semibold uppercase tracking-[.17em] text-[#a24a3d]">{lead.landing_page || 'Landingpage Lead'}</p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-[-.03em] text-[#172741]">
                        {([lead.first_name, lead.last_name].filter(Boolean).join(' ') || 'Lead')} · Bewertung
                    </h1>
                    <p className="mt-2 text-sm text-[#74776f]">{lead.property?.property_type_label || 'Immobilie'} · {address || 'Adresse nicht vollständig'}</p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-lg border border-[#d9d5cc] bg-white p-3 shadow-sm">
                        <div className="mb-2 text-[10px] font-semibold uppercase tracking-[.12em] text-[#8a8d85]">Ersteinschätzung</div>
                        <div className="flex flex-wrap gap-2">
                            <a href={lead.pdf_preview_url} target="_blank" rel="noreferrer" className="rounded-md border border-[#172741] px-3 py-2 text-xs font-semibold text-[#172741] hover:bg-[#f7f7f5]">
                                Ansehen
                            </a>
                            <a href={lead.pdf_download_url} className="rounded-md bg-[#172741] px-3 py-2 text-xs font-semibold text-white hover:bg-[#223a5e]">
                                Download
                            </a>
                        </div>
                    </div>

                    <div className="rounded-lg border border-[#d9d5cc] bg-white p-3 shadow-sm">
                        <div className="mb-2 text-[10px] font-semibold uppercase tracking-[.12em] text-[#a24a3d]">Abschließende Wertermittlung</div>
                        <div className="flex flex-wrap gap-2">
                            <a href={lead.final_pdf_preview_url} target="_blank" rel="noreferrer" className="rounded-md border border-[#b44637] px-3 py-2 text-xs font-semibold text-[#a23b2e] hover:bg-[#fcf5f1]">
                                Ansehen
                            </a>
                            <a href={lead.final_pdf_download_url} className="rounded-md bg-[#b44637] px-3 py-2 text-xs font-semibold text-white hover:bg-[#963b2f]">
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {flash?.success && (
                <div className="mb-6 rounded-lg border border-[#b9d1b9] bg-[#f0f7ef] px-4 py-3 text-sm text-[#426145]">
                    {flash.success}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(340px,.65fr)]">
                <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                    <div className="mb-6 border-b border-[#ebe8e1] pb-5">
                        <p className="text-[10px] font-semibold uppercase tracking-[.15em] text-[#8b8d86]">Manuelle Bewertung</p>
                        <h2 className="mt-2 text-xl font-semibold text-[#172741]">Werte bearbeiten</h2>
                        <p className="mt-2 text-xs leading-5 text-[#74776f]">Nach dem Speichern werden Ergebnisansicht und neu erzeugte PDFs mit diesen Werten ausgegeben.</p>
                    </div>

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label htmlFor="estimated_value" className="mb-2 block text-xs font-semibold text-[#525851]">Schätzwert / Orientierungswert (€)</label>
                            <input id="estimated_value" type="number" min="1" step="100" value={data.estimated_value} onChange={(e) => setData('estimated_value', e.target.value)} className="w-full rounded-md border border-[#d8d5cd] px-3 py-3 text-base" required />
                            {errors.estimated_value && <p className="mt-2 text-xs text-[#a02f27]">{errors.estimated_value}</p>}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="range_low" className="mb-2 block text-xs font-semibold text-[#525851]">Spanne von (€)</label>
                                <input id="range_low" type="number" min="1" step="100" value={data.range_low} onChange={(e) => setData('range_low', e.target.value)} className="w-full rounded-md border border-[#d8d5cd] px-3 py-3 text-base" required />
                                {errors.range_low && <p className="mt-2 text-xs text-[#a02f27]">{errors.range_low}</p>}
                            </div>
                            <div>
                                <label htmlFor="range_high" className="mb-2 block text-xs font-semibold text-[#525851]">Spanne bis (€)</label>
                                <input id="range_high" type="number" min="1" step="100" value={data.range_high} onChange={(e) => setData('range_high', e.target.value)} className="w-full rounded-md border border-[#d8d5cd] px-3 py-3 text-base" required />
                                {errors.range_high && <p className="mt-2 text-xs text-[#a02f27]">{errors.range_high}</p>}
                            </div>
                        </div>

                        <button disabled={processing} className="rounded-md bg-[#b44637] px-5 py-3 text-sm font-semibold text-white hover:bg-[#963b2f] disabled:opacity-60">
                            {processing ? 'Wird gespeichert …' : 'Bewertung speichern'}
                        </button>
                    </form>
                </section>

                <aside className="space-y-6">
                    <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                        <p className="text-[10px] font-semibold uppercase tracking-[.15em] text-[#8b8d86]">Aktueller Stand</p>
                        <dl className="mt-5 space-y-4">
                            <div>
                                <dt className="text-[11px] text-[#8a8d85]">Orientierungswert</dt>
                                <dd className="mt-1 text-xl font-semibold text-[#172741]">{money(valuation.estimated_value)}</dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div><dt className="text-[11px] text-[#8a8d85]">Von</dt><dd className="mt-1 text-sm font-semibold">{money(valuation.range_low)}</dd></div>
                                <div><dt className="text-[11px] text-[#8a8d85]">Bis</dt><dd className="mt-1 text-sm font-semibold">{money(valuation.range_high)}</dd></div>
                            </div>
                            <div><dt className="text-[11px] text-[#8a8d85]">Quelle</dt><dd className="mt-1 text-sm">{valuation.source_label || '—'}</dd></div>
                            <div><dt className="text-[11px] text-[#8a8d85]">Zuletzt geändert</dt><dd className="mt-1 text-sm">{valuation.updated_at || '—'}</dd></div>
                        </dl>
                    </section>

                    <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                        <p className="text-[10px] font-semibold uppercase tracking-[.15em] text-[#8b8d86]">Lead & Objekt</p>
                        <div className="mt-5 space-y-4 text-sm">
                            <div><span className="block text-[11px] text-[#8a8d85]">Kontakt</span><span className="mt-1 block">{lead.email || '—'}<br />{lead.phone || '—'}</span></div>
                            <div>
                                <span className="block text-[11px] text-[#8a8d85]">onOffice</span>
                                <span className="mt-1 block leading-6">
                                    Kontakt-ID: <strong className="font-semibold text-[#172741]">{lead.onoffice?.contact_id || '—'}</strong><br />
                                    Immobilien-ID: <strong className="font-semibold text-[#172741]">{lead.onoffice?.estate_id || '—'}</strong>
                                </span>
                                {lead.onoffice?.synced_at && <span className="mt-1 block text-[11px] text-[#9a9c95]">Letzter Sync: {lead.onoffice.synced_at}</span>}
                            </div>
                            <div><span className="block text-[11px] text-[#8a8d85]">Objekt</span><span className="mt-1 block">{lead.property?.property_type_label || '—'}<br />{address || '—'}</span></div>
                            <div><span className="block text-[11px] text-[#8a8d85]">Eckdaten</span><span className="mt-1 block">Wohnfläche: {lead.property?.living_area || '—'} m²<br />Grundstück: {lead.property?.plot_area || '—'} m²<br />Baujahr: {lead.property?.construction_year || '—'} · Zimmer: {lead.property?.rooms || '—'}</span></div>
                            {lead.notes && <div><span className="block text-[11px] text-[#8a8d85]">Notiz</span><span className="mt-1 block whitespace-pre-wrap leading-6">{lead.notes}</span></div>}
                        </div>
                    </section>
                </aside>
            </div>
        </AdminLayout>
    );
}
