import { Head, useForm } from '@inertiajs/react';
import SignaturePad from '@/Components/Contracts/SignaturePad';

export default function Sign({ seller, signedAt, complete, broker, propertyAddress, pdfUrl, submitUrl }) {
    const form = useForm({ signature: '', signing_place: '', accepted: false, early_start_requested: false, withdrawal_loss_acknowledged: false });
    const submit = (event) => { event.preventDefault(); form.post(submitUrl, { preserveScroll: true }); };
    return <>
        <Head title="Maklerauftrag unterzeichnen"><meta name="robots" content="noindex,nofollow" /><meta name="referrer" content="no-referrer" /></Head>
        <div className="min-h-screen bg-[#f4f2ed] px-4 py-8 text-[#26313a]">
            <main className="mx-auto max-w-3xl">
                <img src="/images/logo-2comehome.png" alt="2 COME HOME" className="mb-8 h-16 w-auto" />
                <h1 className="text-3xl font-semibold text-[#172741]">Ihr Maklerauftrag</h1>
                <p className="mt-3 text-sm leading-6">{broker}<br />{propertyAddress}</p>
                <section className="my-6 rounded-xl border border-[#ddd9d0] bg-white p-5 sm:p-7">
                    <p className="text-lg font-semibold">{seller}</p>
                    {signedAt ? <><p role="status" className="mt-3 text-sm leading-6 text-green-800">Vielen Dank. Ihre Unterschrift wurde am {signedAt} Uhr gespeichert.{complete ? ' Der Auftrag ist vollständig unterschrieben.' : ' Weitere Verkäuferunterschriften stehen noch aus.'}</p><p className="mt-3 text-sm">Hier können Sie den aktuellen Vertrag öffnen und als PDF speichern.</p></> : <p className="mt-3 text-sm leading-6">Bitte lesen Sie den vollständigen Maklerauftrag einschließlich AGB und Widerrufsbelehrung, bevor Sie unterschreiben.</p>}
                    <a href={pdfUrl} target="_blank" rel="noreferrer" className="mt-5 inline-block rounded-md bg-[#172741] px-5 py-3 text-sm font-semibold text-white">Vollständigen Vertrag als PDF öffnen</a>
                </section>
                {!signedAt && <form onSubmit={submit} className="space-y-6 rounded-xl border border-[#ddd9d0] bg-white p-5 sm:p-7">
                    <h2 className="text-xl font-semibold text-[#172741]">Maklerauftrag unterzeichnen</h2>
                    {Object.keys(form.errors).length > 0 && <div role="alert" className="text-sm text-red-800"><ul className="list-disc space-y-2 pl-5">{Object.entries(form.errors).map(([key, message]) => <li key={key}>{message}</li>)}</ul></div>}
                    <label className="flex items-start gap-3 text-sm leading-6"><input type="checkbox" required checked={form.data.accepted} onChange={(e) => form.setData('accepted', e.target.checked)} className="mt-1 size-5 shrink-0" />Ich habe den Maklerauftrag einschließlich AGB und Widerrufsbelehrung gelesen und stimme dem Vertragsinhalt zu.</label>
                    <label className="flex items-start gap-3 text-sm leading-6"><input type="checkbox" checked={form.data.early_start_requested} onChange={(e) => form.setData({ ...form.data, early_start_requested: e.target.checked, withdrawal_loss_acknowledged: false })} className="mt-1 size-5 shrink-0" />Ich verlange ausdrücklich, dass der Makler bereits vor Ablauf der Widerrufsfrist mit der Dienstleistung beginnt. (Optional)</label>
                    {form.data.early_start_requested && <label className="flex items-start gap-3 text-sm leading-6"><input type="checkbox" required checked={form.data.withdrawal_loss_acknowledged} onChange={(e) => form.setData('withdrawal_loss_acknowledged', e.target.checked)} className="mt-1 size-5 shrink-0" />Ich bestätige meine Kenntnis davon, dass ich mein Widerrufsrecht bei vollständiger Vertragserfüllung durch den Makler verliere.</label>}
                    <label htmlFor="signing_place" className="block text-sm font-semibold">Ort der Unterzeichnung<input id="signing_place" required maxLength={255} value={form.data.signing_place} onChange={(e) => form.setData('signing_place', e.target.value)} className="mt-2 block w-full rounded-md border border-[#d8d5cd] px-3 py-3 font-normal" /></label>
                    <SignaturePad disabled={form.processing} onChange={(value) => form.setData('signature', value)} />
                    <button disabled={form.processing || !form.data.signature || !form.data.accepted} className="w-full rounded-md bg-[#b44637] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">{form.processing ? 'Wird gespeichert …' : 'Maklerauftrag verbindlich unterzeichnen'}</button>
                </form>}
            </main>
        </div>
    </>;
}
