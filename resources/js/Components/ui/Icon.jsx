const paths = {
    arrow: 'M5 12h14m-6-6 6 6-6 6',
    check: 'm5 12 4 4L19 6',
    home: 'm3 10 9-7 9 7v10H3V10Zm6 10v-7h6v7',
    building: 'M5 21V3h14v18M9 7h1m4 0h1M9 11h1m4 0h1M9 15h1m4 0h1M9 21v-3h6v3',
    land: 'm3 16 5-8 4 5 3-3 6 6v4H3v-4Z',
    phone: 'M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a15 15 0 0 1-7-7l2-2-2-5Z',
    calendar: 'M5 5h14a2 2 0 0 1 2 2v13H3V7a2 2 0 0 1 2-2ZM7 3v4m10-4v4M3 11h18m-14 4h3m4 0h3',
    file: 'M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 12h8m-8 4h6',
    pin: 'M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0ZM12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
    lock: 'M6 10h12v11H6V10Zm2 0V6a4 4 0 0 1 8 0v4m-4 5v2',
    close: 'm6 6 12 12M6 18 18 6',
};

export default function Icon({ name, size = 22, className = '' }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className={className}><path d={paths[name] ?? paths.home} /></svg>;
}
