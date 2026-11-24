import { Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Components/Admin/AdminLayout';
import { route } from 'ziggy-js';

const input = 'w-full min-w-0 rounded-lg border border-[#d8d5cd] bg-white px-3 py-2.5 text-sm text-[#172741] outline-none focus:border-[#9c563b] focus:ring-2 focus:ring-[#9c563b]/10';
const label = 'mb-1.5 block text-xs font-semibold text-[#525851]';

function TextField({ title, name, value, setData, errors, ...rest }) {
    return (
        <div className="min-w-0">
            <label className={label} htmlFor={'letter-' + name}>{title}</label>
            <input id={'letter-' + name} name={name} className={input} value={value ?? ''} onChange={(event) => setData(name, event.target.value)} aria-invalid={Boolean(errors[name])} {...rest} />
            {errors[name] && <p className="mt-1 text-xs text-[#a02f27]">{errors[name]}</p>}
        </div>
    );
}

function TextArea({ title, name, value, setData, errors, hint, rows = 4, maxLength }) {
    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between gap-3">
                <label className={label + ' mb-0'} htmlFor={'letter-' + name}>{title}</label>
                <span className="text-[11px] text-[#8b8d86]">{(value ?? '').length} / {maxLength}</span>
            </div>
            <textarea id={'letter-' + name} name={name} rows={rows} maxLength={maxLength} value={value ?? ''} onChange={(event) => setData(name, event.target.value)} className={input + ' leading-relaxed'} aria-invalid={Boolean(errors[name])} required />
            {hint && <p className="mt-1 text-[11px] text-[#858982]">{hint}</p>}
            {errors[name] && <p className="mt-1 text-xs text-[#a02f27]">{errors[name]}</p>}
        </div>
    );
}

export default function ValuationLetterEdit({ lead, fields, saved = false, photos = {}, preview_url, download_url }) {
    const { flash } = usePage().props;
    const defaults = { ...fields };
    for (let slot = 1; slot <= 4; slot++) {
        defaults['photo_' + slot] = null;
        defaults['remove_photo_' + slot] = false;
    }
    const { data, setData, post, processing, errors, progress, isDirty } = useForm(defaults);

    const submit = (event) => {
        event.preventDefault();
        post(route('admin.leads.letter.store', lead.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                for (let slot = 1; slot <= 4; slot++) {
                    setData('photo_' + slot, null);
                }
            },
        });
    };

    const hasAllPhotos = [1, 2, 3, 4].every((slot) => {
        return photos?.[slot] && !data['remove_photo_' + slot] || Boolean(data['photo_' + slot]);
    });

    return (
        <AdminLayout title={'Anschreiben · Lead #' + lead.id}>
            <div className="mb-6">
                <Link href={lead.edit_url} className="text-xs font-semibold text-[#a24a3d] hover:underline">← Zur Bewertung zurück</Link>
            </div>
            <div className="mb-6 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div>
                    <p className="text-[10px] font-semibold uppercase tracking-[.16em] text-[#a24a3d]">2 COME HOME · Geschäftskorrespondenz</p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight text-[#172741]">Immobilienbewertung als Anschreiben</h1>
                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-[#74776f]">Zweiseitiger Brief nach der vorgegebenen Vorlage mit Firmenlogo, Wasserzeichen, Ansprechpartner, Objektbeschreibung, Marktwert und vier Fotos. Die Werte sind unabhängig von der automatischen Ersteinschätzung editierbar.</p>
                    <p className="mt-2 text-xs text-[#8b8d86]">{lead.name || 'Lead #' + lead.id} · {lead.address || 'Adresse noch ergänzen'}</p>
                </div>
                {saved && (
                    <div className="flex flex-wrap gap-2">
                        <a href={preview_url} target="_blank" rel="noopener noreferrer" className="rounded-md border border-[#172741] px-4 py-2.5 text-sm font-semibold text-[#172741]">PDF ansehen</a>
                        <a href={download_url} className="rounded-md bg-[#172741] px-4 py-2.5 text-sm font-semibold text-white">PDF herunterladen</a>
                    </div>
                )}
            </div>

            {flash?.success && <div role="status" className="mb-5 rounded-lg border border-[#b9d1b9] bg-[#f0f7ef] px-4 py-3 text-sm text-[#426145]">{flash.success}</div>}
            {lead.source_status === 'stale' && <p className="mb-5 rounded-lg border border-[#e8cf9e] bg-[#fff8e8] px-4 py-3 text-sm text-[#795516]">Die automatische Einwertung ist aktuell als veraltet markiert. Prüfe die Marktwerte im Anschreiben bitte eigenständig.</p>}
            {!saved && <p className="mb-5 rounded-lg border border-[#ddd9d0] bg-[#f8f7f3] px-4 py-3 text-sm text-[#696c65]">Die PDF-Vorschau steht bereit, sobald du das Anschreiben einmal gespeichert hast.</p>}

            <form onSubmit={submit} className="space-y-6">
                <section className="rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">01 · Empfänger und Ansprechpartner</h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextField title="Empfänger / Anschriftzeile *" name="recipient_name" value={data.recipient_name} setData={setData} errors={errors} maxLength={180} required />
                        <TextField title="Datum des Anschreibens *" name="issued_on" type="date" value={data.issued_on} setData={setData} errors={errors} required />
                        <TextField title="Ansprechpartner *" name="adviser_name" value={data.adviser_name} setData={setData} errors={errors} maxLength={120} required />
                        <TextField title="Funktion" name="adviser_role" value={data.adviser_role} setData={setData} errors={errors} maxLength={80} />
                        <TextField title="Mobiltelefon" name="adviser_mobile" value={data.adviser_mobile} setData={setData} errors={errors} maxLength={60} />
                        <TextField title="E-Mail-Adresse" name="adviser_email" type="email" value={data.adviser_email} setData={setData} errors={errors} maxLength={180} />
                    </div>
                </section>

                <section className="rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">02 · Immobilie und Objektbeschreibung</h2>
                    <p className="mb-4 text-xs leading-5 text-[#858982]">Adresse und Wohnfläche werden aus den zuvor bearbeiteten Immobiliendaten übernommen. Die folgenden Ergänzungen sind speziell für dieses Anschreiben.</p>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextField title="Objektbezeichnung *" name="property_label" value={data.property_label} setData={setData} errors={errors} maxLength={100} required />
                        <TextField title="Lage im Gebäude" name="floor_location" value={data.floor_location} setData={setData} errors={errors} maxLength={120} placeholder="z. B. 1. Obergeschoss, rechts" />
                        <div className="sm:col-span-2">
                            <TextField title="Zimmer / Raumaufteilung" name="room_summary" value={data.room_summary} setData={setData} errors={errors} maxLength={170} placeholder="z. B. 3 Zimmer zzgl. Küche, Diele und Badezimmer" />
                        </div>
                    </div>
                    <div className="mt-5">
                        <TextArea title="Objektbeschreibung · Seite 1 *" name="object_description" value={data.object_description} setData={setData} errors={errors} rows={6} maxLength={530} hint="Die Vorlage hat einen begrenzten Textraum. Bitte den Text prägnant halten; Absätze bleiben erhalten." />
                    </div>
                </section>

                <section className="rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">03 · Marktwerteinschätzung</h2>
                    <TextArea title="Einleitung zur Marktwerteinschätzung *" name="market_description" value={data.market_description} setData={setData} errors={errors} rows={3} maxLength={310} />
                    <div className="mt-5 grid gap-4 sm:grid-cols-3">
                        <TextField title="Marktwert von (€) *" name="value_low" type="number" min="1" step="0.01" value={data.value_low} setData={setData} errors={errors} required />
                        <TextField title="Marktwert bis (€) *" name="value_high" type="number" min="1" step="0.01" value={data.value_high} setData={setData} errors={errors} required />
                        <TextField title="Orientierungswert (€) *" name="orientation_value" type="number" min="1" step="0.01" value={data.orientation_value} setData={setData} errors={errors} required />
                    </div>
                    <p className="mt-2 text-xs text-[#858982]">Der Orientierungswert muss innerhalb der Marktwertspanne liegen. Der Preis je m² wird aus der gespeicherten Wohnfläche berechnet.</p>
                </section>

                <section className="rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">04 · Bewertungshinweise · Seite 2</h2>
                    <TextArea title="Objektspezifische Hinweise *" name="valuation_notes" value={data.valuation_notes} setData={setData} errors={errors} rows={5} maxLength={450} hint="Der rechtliche Hinweis zu § 194 BauGB steht in der Vorlage bereits fest." />
                    <div className="mt-5 max-w-xs">
                        <TextField title="Bewertungsstichtag *" name="valuation_date" type="date" value={data.valuation_date} setData={setData} errors={errors} required />
                    </div>
                </section>

                <section className="rounded-xl border border-[#ddd9d0] bg-white p-5 shadow-sm sm:p-6">
                    <h2 className="text-lg font-semibold text-[#172741]">05 · Fotos für Seite 2</h2>
                    <p className="mb-5 mt-2 text-xs leading-5 text-[#858982]">Vier Bildpositionen wie in der Vorlage: Foto 1 im Hochformat, Fotos 2–4 im Querformat. Hochgeladene Bilder werden auf das jeweilige Format zugeschnitten und geschützt gespeichert. JPG/PNG, maximal 8 MB je Foto.</p>
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {[1, 2, 3, 4].map((slot) => {
                            const current = photos?.[slot];
                            const selected = data['photo_' + slot];
                            const removed = data['remove_photo_' + slot];
                            return (
                                <div key={slot} className="min-w-0 rounded-lg border border-[#e5e0d8] bg-[#faf9f6] p-3">
                                    <p className="mb-2 text-xs font-semibold text-[#172741]">Foto {slot} · {slot === 1 ? 'Hochformat' : 'Querformat'}</p>
                                    <div className={'mb-3 flex items-center justify-center overflow-hidden rounded bg-[#e9e6e0] ' + (slot === 1 ? 'aspect-[3/4]' : 'aspect-[4/3]')}>
                                        {current && !removed
                                            ? <img src={current} alt={'Gespeichertes Foto ' + slot} className="h-full w-full object-cover" />
                                            : <span className="px-2 text-center text-xs text-[#95988f]">{selected ? 'Neues Foto ausgewählt' : 'Noch kein Foto'}</span>}
                                    </div>
                                    <input type="file" accept="image/jpeg,image/png" onChange={(event) => setData('photo_' + slot, event.target.files?.[0] ?? null)} className="w-full min-w-0 text-[11px] text-[#6e716a] file:mr-2 file:rounded file:border-0 file:bg-[#172741] file:px-2 file:py-1.5 file:text-[11px] file:font-semibold file:text-white" />
                                    {selected && <p className="mt-1 break-all text-[11px] text-[#456044]">{selected.name}</p>}
                                    {current && <label className="mt-3 flex items-center gap-2 text-xs text-[#73776e]"><input type="checkbox" checked={Boolean(removed)} onChange={(event) => setData('remove_photo_' + slot, event.target.checked)} />Vorhandenes Foto entfernen</label>}
                                    {errors['photo_' + slot] && <p className="mt-1 text-xs text-[#a02f27]">{errors['photo_' + slot]}</p>}
                                </div>
                            );
                        })}
                    </div>
                    {!hasAllPhotos && <p className="mt-4 text-xs text-[#856329]">Für die identische vierteilige Bildanordnung fehlen noch Fotos. Das Anschreiben kann auch ohne vollständig bestückten Bildbereich gespeichert werden.</p>}
                </section>

                <div className="sticky bottom-3 z-10 flex flex-wrap items-center gap-3 rounded-xl border border-[#d7d3c9] bg-white/95 p-4 shadow-lg">
                    <button type="submit" disabled={processing} className="rounded-md bg-[#b44637] px-6 py-3 text-sm font-semibold text-white hover:bg-[#963b2f] disabled:opacity-60">
                        {processing ? 'Wird gespeichert …' : 'Anschreiben speichern'}
                    </button>
                    {saved && <a href={preview_url} target="_blank" rel="noopener noreferrer" className="rounded-md border border-[#172741] px-4 py-3 text-sm font-semibold text-[#172741]">PDF-Vorschau</a>}
                    {saved && <a href={download_url} className="rounded-md border border-[#172741] px-4 py-3 text-sm font-semibold text-[#172741]">PDF herunterladen</a>}
                    {isDirty && <span className="text-xs text-[#a24a3d]">Ungespeicherte Änderungen</span>}
                    {progress && <span className="text-xs text-[#74776f]">Upload: {progress.percentage}%</span>}
                </div>
            </form>
        </AdminLayout>
    );
}
