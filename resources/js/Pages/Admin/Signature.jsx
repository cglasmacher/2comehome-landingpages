import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { route } from 'ziggy-js';
import AdminLayout from '@/Components/Admin/AdminLayout';

export default function Signature({ hasSignature }) {
    const { flash, auth } = usePage().props;
    const input = useRef(null);
    const form = useForm({ signature: null });
    const submit = (event) => {
        event.preventDefault();
        form.post(route('admin.signature.update'), {
            forceFormData: true,
            onSuccess: () => { form.reset(); if (input.current) input.current.value = ''; },
        });
    };
    return <AdminLayout title="Meine Unterschrift">
        <Link href={route('admin.dashboard')} className="text-sm text-[#a24a3d]">← Zurück zur Übersicht</Link>
        <section className="mt-6 max-w-2xl rounded-xl border border-[#ddd9d0] bg-white p-6 shadow-sm">
            <h1 className="text-2xl font-semibold text-[#172741]">Meine Unterschrift</h1>
            <p className="mt-3 text-sm leading-6 text-[#74776f]">{auth.user.name} · Diese Unterschrift wird bei deinen Makleraufträgen für 2 COME HOME Immobilien – Maximilian Schäfer eingesetzt.</p>
            {flash?.success && <p role="status" className="mt-4 text-sm text-green-800">{flash.success}</p>}
            {hasSignature && <div className="my-6">
                <img src={`${route('admin.signature.show')}?v=${Date.now()}`} alt="Deine hinterlegte Unterschrift" className="max-h-32 max-w-full border border-[#ddd9d0] bg-white p-3" />
                <button type="button" onClick={() => router.delete(route('admin.signature.destroy'))} className="mt-3 text-sm text-[#a24a3d] underline">Unterschrift entfernen</button>
            </div>}
            <form onSubmit={submit} className="mt-6 space-y-4">
                <label className="block text-sm font-semibold" htmlFor="signature">{hasSignature ? 'Unterschrift ersetzen' : 'Unterschrift hochladen'}</label>
                <input ref={input} id="signature" type="file" accept="image/png,image/jpeg" required onChange={(e) => form.setData('signature', e.target.files[0])} className="block w-full text-sm" />
                <p className="text-xs leading-5 text-[#74776f]">PNG oder JPG, maximal 2 MB und 2400 × 1200 Pixel. Am besten ein zugeschnittenes PNG mit transparentem Hintergrund. Bereits erstellte Verträge behalten ihre bisherige Unterschrift.</p>
                {form.errors.signature && <p role="alert" className="text-sm text-red-800">{form.errors.signature}</p>}
                <button disabled={form.processing} className="rounded-md bg-[#172741] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">{form.processing ? 'Wird gespeichert …' : 'Unterschrift speichern'}</button>
            </form>
        </section>
    </AdminLayout>;
}
