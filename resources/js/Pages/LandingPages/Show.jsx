import { Head, useForm, usePage } from '@inertiajs/react';
import ValuationRange from '@/Components/LandingPages/ValuationRange';

export default function Show({ page }) {
    const { flash } = usePage().props;

    const form = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        notes: '',
        consultation_requested: false,
        property: {
            property_type: 'house',
            street: '',
            house_number: '',
            zip: '',
            city: '',
            country: 'DE',
            construction_year: '',
            living_area: '',
            plot_area: '',
            rooms: '',
            features: {},
        },
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(`/${page.slug}/leads`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={page.seo?.title ?? page.title}>
                {page.seo?.description && <meta name="description" content={page.seo.description} />}
            </Head>

            <main className="min-h-screen bg-slate-50 text-slate-900">
                <section className="mx-auto grid max-w-6xl gap-10 px-6 py-16 md:grid-cols-2 md:items-center">
                    <div>
                        <p className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            {page.content?.eyebrow ?? 'Immobilienbewertung'}
                        </p>
                        <h1 className="text-4xl font-bold tracking-tight md:text-5xl">
                            {page.content?.hero_title ?? page.title}
                        </h1>
                        <p className="mt-5 text-lg text-slate-600">
                            {page.content?.hero_text ?? page.description}
                        </p>
                    </div>

                    <form onSubmit={submit} className="rounded-2xl bg-white p-6 shadow-xl">
                        <h2 className="text-xl font-semibold">Kostenlose Ersteinschätzung erhalten</h2>

                        <div className="mt-6 grid grid-cols-2 gap-3">
                            <input className="rounded-lg border p-3" placeholder="Vorname" value={form.data.first_name} onChange={e => form.setData('first_name', e.target.value)} />
                            <input className="rounded-lg border p-3" placeholder="Nachname" value={form.data.last_name} onChange={e => form.setData('last_name', e.target.value)} />
                            <input className="col-span-2 rounded-lg border p-3" placeholder="E-Mail" value={form.data.email} onChange={e => form.setData('email', e.target.value)} />
                            <input className="col-span-2 rounded-lg border p-3" placeholder="Telefon" value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} />
                            <input className="rounded-lg border p-3" placeholder="Straße" value={form.data.property.street} onChange={e => form.setData('property', { ...form.data.property, street: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Nr." value={form.data.property.house_number} onChange={e => form.setData('property', { ...form.data.property, house_number: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="PLZ" value={form.data.property.zip} onChange={e => form.setData('property', { ...form.data.property, zip: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Ort" value={form.data.property.city} onChange={e => form.setData('property', { ...form.data.property, city: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Wohnfläche m²" value={form.data.property.living_area} onChange={e => form.setData('property', { ...form.data.property, living_area: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Grundstück m²" value={form.data.property.plot_area} onChange={e => form.setData('property', { ...form.data.property, plot_area: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Baujahr" value={form.data.property.construction_year} onChange={e => form.setData('property', { ...form.data.property, construction_year: e.target.value })} />
                            <input className="rounded-lg border p-3" placeholder="Zimmer" value={form.data.property.rooms} onChange={e => form.setData('property', { ...form.data.property, rooms: e.target.value })} />
                        </div>

                        <button disabled={form.processing} className="mt-6 w-full rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white disabled:opacity-50">
                            Bewertung berechnen
                        </button>
                    </form>
                </section>

                {flash?.valuation && (
                    <section className="mx-auto max-w-4xl px-6 pb-16">
                        <ValuationRange valuation={flash.valuation} calendlyUrl={page.calendly_url} reportUrl={flash.report_url} />
                    </section>
                )}
            </main>
        </>
    );
}
