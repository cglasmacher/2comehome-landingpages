import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { route } from 'ziggy-js';
import AdminLayout from '@/Components/Admin/AdminLayout';

const inputStyle = 'w-full rounded-md border border-[#d8d5cd] bg-white px-3 py-2.5 text-base disabled:bg-[#f7f7f5]';
const buttonStyle = 'inline-block rounded-md bg-[#172741] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50';
const statusLabels = { draft: 'Entwurf', created: 'Erstellt · zur Unterschrift bereit', partially_signed: 'Teilweise unterschrieben', signed: 'Vollständig unterschrieben' };

function endDate(start, months) {
    if (!start || !months) return '';
    const [y, m, d] = start.split('-').map(Number);
    const last = new Date(Date.UTC(y, m - 1 + Number(months) + 1, 0)).getUTCDate();
    const date = new Date(Date.UTC(y, m - 1 + Number(months), Math.min(d, last)));
    date.setUTCDate(date.getUTCDate() - 1);
    return date.toISOString().slice(0, 10);
}

export default function ContractEdit({ lead, contract, fields, agents, broker }) {
    const { flash } = usePage().props;
    const form = useForm(fields);
    const [busy, setBusy] = useState(false);
    const [copyStatus, setCopyStatus] = useState('');
    const [finalizePrompt, setFinalizePrompt] = useState(false);
    const [revokePrompt, setRevokePrompt] = useState(false);
    const locked = contract && contract.status !== 'draft';
    const agent = agents.find((a) => String(a.id) === String(form.data.agent_id));
    const save = (event) => {
        event.preventDefault();
        if (contract) form.put(route('admin.contracts.update', contract.id));
        else form.post(route('admin.leads.contracts.store', lead.id));
    };
    const action = (name) => router.post(route(`admin.contracts.${name}`, contract.id), {}, {
        onStart: () => setBusy(true), onFinish: () => { setBusy(false); setFinalizePrompt(false); setRevokePrompt(false); },
    });
    const sellerChange = (index, key, value) => form.setData('sellers', form.data.sellers.map((s, i) => i === index ? { ...s, [key]: value } : s));
    const field = (name, label, type = 'text', extra = {}) => <div>
        <label htmlFor={name} className="mb-2 block text-xs font-semibold">{label}</label>
        <input id={name} type={type} value={form.data[name] ?? ''} onChange={(e) => form.setData(name, e.target.value)} className={inputStyle} {...extra} />
    </div>;
    return <AdminLayout title={contract ? `Maklerauftrag #${contract.id}` : 'Maklerauftrag erstellen'}>
        <Link href={route('admin.leads.edit', lead.id)} className="text-sm text-[#a24a3d]">← Zurück zum Kunden</Link>
        <div className="my-6 flex flex-wrap items-start justify-between gap-4">
            <div><h1 className="text-2xl font-semibold text-[#172741]">{contract ? `Maklerauftrag #${contract.id}` : 'Maklerauftrag erstellen'}</h1>
                <p className="mt-2 text-sm">{broker}</p>
                <p className="mt-2 text-sm text-[#74776f]">{contract ? statusLabels[contract.status] : 'Neuer Entwurf'} · {[lead.first_name, lead.last_name].filter(Boolean).join(' ')}</p>
            </div>
            {contract && <div className="flex flex-wrap gap-2">
                <a className={buttonStyle} href={route('admin.contracts.pdf', contract.id)} target="_blank" rel="noreferrer">PDF-Vorschau</a>
                {locked && <a className={buttonStyle} href={route('admin.contracts.pdf', { contract: contract.id, download: 1 })}>PDF herunterladen</a>}
                {locked && <button type="button" disabled={busy} className={buttonStyle} onClick={() => action('duplicate')}>Neue Fassung</button>}
            </div>}
        </div>
        {flash?.success && <p role="status" className="mb-5 text-sm text-green-800">{flash.success}</p>}
        {Object.keys(form.errors).length > 0 && <div role="alert" className="mb-5 rounded-lg border border-red-300 bg-white p-4 text-sm text-red-800">Bitte die folgenden Angaben prüfen:<ul className="mt-2 list-disc pl-5">{Object.entries(form.errors).map(([key, message]) => <li key={key}>{key}: {message}</li>)}</ul></div>}
        <form onSubmit={save}>
            <fieldset disabled={locked || form.processing} className="space-y-6 disabled:opacity-80">
                <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">Verkäufer</h2>
                    {form.data.sellers.map((seller, index) => <div key={index} className="mb-6 space-y-3 border-b border-[#ebe8e1] pb-5">
                        <div className="flex items-center justify-between"><h3 className="text-sm font-semibold">Verkäufer {index + 1}</h3>
                            {form.data.sellers.length > 1 && <button type="button" onClick={() => form.setData('sellers', form.data.sellers.filter((_, i) => i !== index))} className="text-xs text-[#a24a3d]">Entfernen</button>}
                        </div>
                        <label className="block text-xs font-semibold">Vollständiger Name<input required value={seller.name} onChange={(e) => sellerChange(index, 'name', e.target.value)} className={`${inputStyle} mt-2`} maxLength={255} /></label>
                        <label className="block text-xs font-semibold">Anschrift des Verkäufers<textarea required value={seller.address} onChange={(e) => sellerChange(index, 'address', e.target.value)} className={`${inputStyle} mt-2`} rows={2} maxLength={1000} /></label>
                        <label className="block text-xs font-semibold">E-Mail-Adresse (optional)<input type="email" value={seller.email ?? ''} onChange={(e) => sellerChange(index, 'email', e.target.value)} className={`${inputStyle} mt-2`} maxLength={255} /></label>
                    </div>)}
                    {!locked && form.data.sellers.length < 10 && <button type="button" onClick={() => form.setData('sellers', [...form.data.sellers, { name: '', address: '', email: '' }])} className="text-sm font-semibold text-[#a24a3d]">+ Weiteren Verkäufer hinzufügen</button>}
                </section>
                <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">Objekt und Konditionen</h2>
                    <div className="grid gap-5 sm:grid-cols-2">
                        {field('property_description', 'Objektbezeichnung', 'text', { maxLength: 255 })}
                        {field('property_address', 'Vollständige Objektanschrift', 'text', { required: true, maxLength: 1000 })}
                        {field('offer_price', 'Angebotspreis (€)', 'number', { min: '0.01', max: '999999999999.99', step: '0.01' })}
                        {field('commission_percent', 'Verkäuferprovision inkl. MwSt. (%)', 'number', { required: true, min: 0, max: 100, step: '0.01' })}
                        <div><label htmlFor="starts_on" className="mb-2 block text-xs font-semibold">Vertragsbeginn</label><input id="starts_on" type="date" required value={form.data.starts_on} onChange={(e) => form.setData({ ...form.data, starts_on: e.target.value, ends_on: endDate(e.target.value, form.data.duration_months) })} className={inputStyle} /></div>
                        <div><label htmlFor="duration_months" className="mb-2 block text-xs font-semibold">Laufzeit (Monate)</label><input id="duration_months" type="number" required min={1} max={120} value={form.data.duration_months} onChange={(e) => form.setData({ ...form.data, duration_months: e.target.value, ends_on: endDate(form.data.starts_on, e.target.value) })} className={inputStyle} /></div>
                        {field('ends_on', 'Vertragsende / frühester Kündigungstermin', 'date', { required: true, min: form.data.starts_on })}
                        {field('notice_months', 'Kündigungsfrist (Monate zum Monatsende)', 'number', { required: true, min: 1, max: 120 })}
                        {field('renewal_months', 'Automatische Verlängerung (Monate, 0 = keine)', 'number', { required: true, min: 0, max: 120 })}
                        {field('signing_place', 'Unterschriftsort des Maklers', 'text', { required: true, maxLength: 255 })}
                    </div>
                    <p className="mt-4 text-xs leading-5 text-[#74776f]">Der Angebotspreis ist unabhängig vom Schätzwert. Das Vertragsende wird aus Beginn und Laufzeit berechnet. Ein individuell vereinbartes Enddatum kannst du direkt eintragen; dieses Datum ist für den Vertrag maßgeblich.</p>
                </section>
                <section className="rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-[#172741]">Bearbeiter und Vereinbarungen</h2>
                    <label className="mb-2 block text-xs font-semibold" htmlFor="agent_id">Unterzeichnender Bearbeiter</label>
                    <select id="agent_id" required value={form.data.agent_id ?? ''} onChange={(e) => form.setData('agent_id', e.target.value)} className={inputStyle}><option value="">Bitte auswählen</option>{agents.map((a) => <option key={a.id} value={a.id}>{a.name}{a.has_signature ? '' : ' · Unterschrift fehlt'}</option>)}</select>
                    {!agent?.has_signature && !locked && <p className="mt-3 text-sm text-[#a24a3d]">Vor dem Fertigstellen muss der Bearbeiter seine Unterschrift unter „Meine Unterschrift“ hochladen.</p>}
                    <label htmlFor="special_agreements" className="mb-2 mt-5 block text-xs font-semibold">Besondere Vereinbarungen (optional)</label>
                    <textarea id="special_agreements" rows={5} value={form.data.special_agreements ?? ''} onChange={(e) => form.setData('special_agreements', e.target.value)} maxLength={10000} className={inputStyle} />
                </section>
                {!locked && <button disabled={form.processing} className={buttonStyle}>{form.processing ? 'Wird gespeichert …' : 'Entwurf speichern'}</button>}
            </fieldset>
        </form>
        {contract && !locked && <section className="mt-6 rounded-xl border border-[#ddd9d0] bg-white p-6">
            <h2 className="text-lg font-semibold">Vertrag fertigstellen</h2>
            <p className="my-3 text-sm leading-6 text-[#74776f]">Prüfe die PDF-Vorschau. Beim Fertigstellen werden die gespeicherten Angaben und die Unterschrift des Bearbeiters festgehalten. Änderungen erfordern anschließend eine neue Fassung.</p>
            {form.isDirty && <p className="mb-3 text-sm text-[#a24a3d]">Bitte zuerst deine Änderungen speichern.</p>}
            {!finalizePrompt ? <button disabled={busy || form.processing || form.isDirty || !agent?.has_signature || !form.data.offer_price} onClick={() => setFinalizePrompt(true)} className={buttonStyle}>Vertrag fertigstellen</button> : <div className="flex flex-wrap items-center gap-3"><span className="text-sm">Vertrag mit den gespeicherten Angaben verbindlich vorbereiten?</span><button disabled={busy} onClick={() => action('finalize')} className={buttonStyle}>Jetzt fertigstellen</button><button disabled={busy} onClick={() => setFinalizePrompt(false)} className="text-sm underline">Zurück</button></div>}
        </section>}
        {locked && <section className="mt-6 rounded-xl border border-[#ddd9d0] bg-white p-6">
            <h2 className="text-lg font-semibold">Unterzeichnung</h2>
            <p className="mt-2 text-sm text-[#74776f]">Persönliche Links, gültig bis {contract.signing_expires_at}. Jeder Verkäufer unterschreibt über seinen eigenen Link.</p>
            {contract.links_revoked && <p className="mt-3 text-sm text-[#a24a3d]">Die Links sind gesperrt.</p>}
            <div className="mt-5 space-y-5">{contract.signers.map((s) => <div key={s.id} className="border-b border-[#ebe8e1] pb-4">
                <p className="text-sm font-semibold">{s.name}</p><p className="mt-1 text-xs text-[#74776f]">{s.signed_at ? `Unterschrieben am ${s.signed_at}` : 'Unterschrift ausstehend'}</p>
                {s.url && <div className="mt-3 flex flex-wrap gap-2"><input aria-label={`Unterschriftslink für ${s.name}`} readOnly value={s.url} className={`${inputStyle} max-w-xl text-xs`} onFocus={(e) => e.target.select()} /><button type="button" className="text-sm underline" onClick={async () => { try { await navigator.clipboard.writeText(s.url); setCopyStatus(`Link für ${s.name} kopiert.`); } catch { setCopyStatus('Bitte den Link im Feld markieren und kopieren.'); } }}>Link kopieren</button></div>}
            </div>)}</div>
            <p role="status" className="mt-3 text-sm text-green-800">{copyStatus}</p>
            <a href={route('admin.contracts.pdf', { contract: contract.id, original: 1 })} target="_blank" rel="noreferrer" className="mt-4 inline-block text-sm underline">Originalfassung vor Verkäuferunterschriften</a>
            {!contract.links_revoked && <div className="mt-5">{!revokePrompt ? <button type="button" onClick={() => setRevokePrompt(true)} className="text-sm text-[#a24a3d] underline">Alle Unterschriftslinks sperren</button> : <div className="flex flex-wrap items-center gap-3"><span className="text-sm">Alle Links dieses Auftrags dauerhaft sperren?</span><button disabled={busy} type="button" onClick={() => action('revoke')} className={buttonStyle}>Links sperren</button><button type="button" disabled={busy} onClick={() => setRevokePrompt(false)} className="text-sm underline">Abbrechen</button></div>}</div>}
        </section>}
    </AdminLayout>;
}
