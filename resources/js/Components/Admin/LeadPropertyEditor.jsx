import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

const fieldClass = 'w-full min-w-0 rounded-md border border-[#d8d5cd] bg-white px-3 py-2.5 text-sm text-[#172741] focus:border-[#a24a3d] focus:outline-none focus:ring-2 focus:ring-[#a24a3d]/15';
const labelClass = 'mb-1.5 block text-xs font-semibold text-[#525851]';

function PropertyField({ id, label, error, ...props }) {
    return (
        <div className="min-w-0">
            <label htmlFor={id} className={labelClass}>{label}</label>
            <input id={id} className={fieldClass} aria-invalid={Boolean(error)} {...props} />
            {error && <p className="mt-1 text-xs text-[#a02f27]" role="alert">{error}</p>}
        </div>
    );
}

export default function LeadPropertyEditor({ leadId, property = {}, propertyTypes = [], valuation = {} }) {
    const { data, setData, put, processing, errors, isDirty, defaults } = useForm({
        property_type: property.property_type ?? '',
        street: property.street ?? '',
        house_number: property.house_number ?? '',
        zip: property.zip ?? '',
        city: property.city ?? '',
        country: property.country ?? 'DE',
        construction_year: property.construction_year ?? '',
        living_area: property.living_area ?? '',
        plot_area: property.plot_area ?? '',
        rooms: property.rooms ?? '',
        number_of_units: property.number_of_units ?? '',
        recalculate: false,
    });

    const isApartment = data.property_type === 'wohnung';
    const isMultiFamily = data.property_type === 'mehrfamilienhaus';
    const stale = valuation.status === 'stale';

    const changeType = (value) => {
        setData({
            ...data,
            property_type: value,
            plot_area: value === 'wohnung' ? '' : data.plot_area,
            number_of_units: value === 'mehrfamilienhaus' ? data.number_of_units : '',
        });
    };

    const submit = (event) => {
        event.preventDefault();
        put(route('admin.leads.property.update', leadId), {
            preserveScroll: true,
            onSuccess: () => {
                defaults({ ...data, recalculate: false });
                setData('recalculate', false);
            },
        });
    };

    return (
        <section className="mb-6 rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
            <div className="mb-5 border-b border-[#ebe8e1] pb-4">
                <p className="text-[10px] font-semibold uppercase tracking-[.15em] text-[#a24a3d]">Immobiliendaten</p>
                <h2 className="mt-1.5 text-xl font-semibold text-[#172741]">Objektparameter bearbeiten</h2>
                <p className="mt-1 text-xs leading-5 text-[#74776f]">
                    Korrigierte Daten erscheinen in der Objektübersicht und in neu erzeugten PDF-Berichten.
                    Bereits versandte E-Mails und bestehende Makleraufträge werden nicht rückwirkend verändert.
                </p>
            </div>

            {stale && (
                <div role="status" className="mb-5 rounded-md border border-[#e8cf9e] bg-[#fff9eb] px-4 py-3 text-sm text-[#795516]">
                    Die gespeicherte Bewertung basiert noch auf früheren Objektdaten. Bitte manuell aktualisieren
                    oder beim Speichern eine neue Bewertung anfordern.
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="min-w-0">
                        <label htmlFor="admin-property-type" className={labelClass}>Immobilienart *</label>
                        <select id="admin-property-type" className={fieldClass} required value={data.property_type} onChange={(e) => changeType(e.target.value)} aria-invalid={Boolean(errors.property_type)}>
                            <option value="">Bitte wählen</option>
                            {propertyTypes.map((type) => <option value={type.value} key={type.value}>{type.label}</option>)}
                        </select>
                        {errors.property_type && <p role="alert" className="mt-1 text-xs text-[#a02f27]">{errors.property_type}</p>}
                    </div>
                    <PropertyField id="admin-street" label="Straße" value={data.street} maxLength={255} onChange={(e) => setData('street', e.target.value)} error={errors.street} />
                    <PropertyField id="admin-house-number" label="Hausnummer" value={data.house_number} maxLength={30} onChange={(e) => setData('house_number', e.target.value)} error={errors.house_number} />
                    <PropertyField id="admin-zip" label="PLZ" value={data.zip} maxLength={20} onChange={(e) => setData('zip', e.target.value)} error={errors.zip} />
                    <PropertyField id="admin-city" label="Ort" value={data.city} maxLength={255} onChange={(e) => setData('city', e.target.value)} error={errors.city} />
                    <div className="min-w-0">
                        <label htmlFor="admin-country" className={labelClass}>Land *</label>
                        <select id="admin-country" className={fieldClass} required value={data.country} onChange={(e) => setData('country', e.target.value)} aria-invalid={Boolean(errors.country)}>
                            <option value="DE">Deutschland</option>
                            <option value="AT">Österreich</option>
                            <option value="CH">Schweiz</option>
                        </select>
                        {errors.country && <p role="alert" className="mt-1 text-xs text-[#a02f27]">{errors.country}</p>}
                    </div>
                    <PropertyField id="admin-living-area" label="Wohnfläche (m²)" type="number" min="1" max="800000" step="1" value={data.living_area} onChange={(e) => setData('living_area', e.target.value)} error={errors.living_area} />
                    {!isApartment && <PropertyField id="admin-plot-area" label="Grundstücksgröße (m²)" type="number" min="1" max="100000" step="1" value={data.plot_area} onChange={(e) => setData('plot_area', e.target.value)} error={errors.plot_area} />}
                    <PropertyField id="admin-construction-year" label="Baujahr" type="number" min="1700" max={new Date().getFullYear()} step="1" value={data.construction_year} onChange={(e) => setData('construction_year', e.target.value)} error={errors.construction_year} />
                    <PropertyField id="admin-rooms" label="Zimmer" type="number" min="1" max="100" step="1" value={data.rooms} onChange={(e) => setData('rooms', e.target.value)} error={errors.rooms} />
                    {isMultiFamily && <PropertyField id="admin-number-of-units" label="Wohneinheiten *" type="number" min="2" max="1000" step="1" required value={data.number_of_units} onChange={(e) => setData('number_of_units', e.target.value)} error={errors.number_of_units} />}
                </div>

                <label className="flex cursor-pointer items-start gap-3 rounded-md border border-[#e6e0d6] bg-[#f9f8f5] p-4">
                    <input type="checkbox" className="mt-0.5 h-4 w-4 accent-[#a24a3d]" checked={data.recalculate} onChange={(e) => setData('recalculate', e.target.checked)} />
                    <span className="text-xs leading-5 text-[#525851]">
                        <strong className="block font-semibold text-[#172741]">Bewertung nach dem Speichern neu berechnen</strong>
                        Optionaler erneuter Aufruf der konfigurierten Bewertungsanbieter. Kann API-Kosten verursachen.
                        {valuation.provider === 'manual' && <span className="mt-1 block font-semibold text-[#a24a3d]">Achtung: Die aktuelle manuelle Bewertung wird bei erfolgreicher Neuberechnung überschrieben.</span>}
                    </span>
                </label>

                <div className="flex flex-wrap items-center gap-3">
                    <button type="submit" disabled={processing} className="rounded-md bg-[#172741] px-5 py-3 text-sm font-semibold text-white hover:bg-[#223a5e] disabled:opacity-60">
                        {processing ? 'Wird gespeichert …' : data.recalculate ? 'Speichern und neu berechnen' : 'Objektdaten speichern'}
                    </button>
                    {isDirty && <span className="text-xs text-[#a24a3d]">Nicht gespeicherte Änderungen</span>}
                </div>
                <p className="text-xs leading-5 text-[#777a72]">
                    Eine geänderte Immobilie wird nicht automatisch erneut an onOffice übertragen.
                    Bereits erstellte Makleraufträge bleiben unverändert.
                </p>
            </form>
        </section>
    );
}
