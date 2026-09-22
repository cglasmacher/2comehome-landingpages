import { Head, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('admin.login.store'));
    };

    return (
        <>
            <Head title="Admin Login" />
            <div className="flex min-h-screen items-center justify-center bg-[#f4f2ed] px-5 py-12">
                <div className="w-full max-w-md overflow-hidden rounded-xl border border-[#ddd8ce] bg-white shadow-[0_24px_70px_rgba(23,39,65,.10)]">
                    <div className="bg-[#172741] px-8 py-7 text-white">
                        <img src="/images/logo-2comehome.png" alt="2 COME HOME" className="h-14 w-auto object-contain" />
                        <p className="mt-5 text-[10px] font-semibold uppercase tracking-[.18em] text-white/55">Backoffice</p>
                        <h1 className="mt-2 text-2xl font-semibold">Immobilienbewertungen</h1>
                    </div>

                    <form onSubmit={submit} className="space-y-5 p-8">
                        <div>
                            <label htmlFor="email" className="mb-2 block text-xs font-semibold text-[#4e554f]">E-Mail</label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="username"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="w-full rounded-md border border-[#d8d7cf] px-3 py-3 text-sm outline-none focus:border-[#b44637] focus:ring-2 focus:ring-[#b44637]/10"
                                required
                                autoFocus
                            />
                            {errors.email && <p className="mt-2 text-xs text-[#a02f27]">{errors.email}</p>}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-2 block text-xs font-semibold text-[#4e554f]">Passwort</label>
                            <input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className="w-full rounded-md border border-[#d8d7cf] px-3 py-3 text-sm outline-none focus:border-[#b44637] focus:ring-2 focus:ring-[#b44637]/10"
                                required
                            />
                            {errors.password && <p className="mt-2 text-xs text-[#a02f27]">{errors.password}</p>}
                        </div>

                        <label className="flex items-center gap-2 text-xs text-[#6d7169]">
                            <input type="checkbox" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} className="accent-[#b44637]" />
                            Angemeldet bleiben
                        </label>

                        <button disabled={processing} className="w-full rounded-md bg-[#b44637] px-4 py-3 text-sm font-semibold text-white hover:bg-[#94392d] disabled:opacity-60">
                            {processing ? 'Anmeldung läuft …' : 'Anmelden'}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
