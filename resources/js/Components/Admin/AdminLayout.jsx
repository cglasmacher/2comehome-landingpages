import { Head, Link, router, usePage } from '@inertiajs/react';

export default function AdminLayout({ title, children }) {
    const { auth } = usePage().props;

    const logout = () => router.post(route('admin.logout'));

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-[#f4f2ed] text-[#26313a]">
                <header className="border-b border-[#d8d4cb] bg-[#172741] text-white">
                    <div className="mx-auto flex min-h-20 w-[min(1240px,calc(100%-40px))] items-center justify-between gap-6">
                        <div className="flex items-center gap-6">
                            <Link href={route('admin.dashboard')} className="flex items-center">
                                <img src="/images/logo-2comehome.png" alt="2 COME HOME" className="h-12 w-auto object-contain" />
                            </Link>
                            <div className="hidden border-l border-white/20 pl-6 sm:block">
                                <div className="text-[10px] font-semibold uppercase tracking-[.18em] text-white/60">Backoffice</div>
                                <div className="mt-1 text-sm font-medium">Immobilienbewertungen</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="hidden text-xs text-white/65 md:inline">{auth?.user?.email}</span>
                            <button type="button" onClick={logout} className="rounded-md border border-white/25 px-3 py-2 text-xs font-semibold hover:bg-white/10">
                                Abmelden
                            </button>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-[min(1240px,calc(100%-40px))] py-8">
                    {children}
                </main>
            </div>
        </>
    );
}
