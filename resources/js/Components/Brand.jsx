import { Link } from '@inertiajs/react';

export default function Brand({ settings, big = false }) {
    const size = big ? 110 : 40;

    return (
        <Link href="/" className="flex flex-col items-center leading-none">
            {settings.logo ? (
                <img
                    src={`/storage/${settings.logo}`}
                    alt={settings.name}
                    className={big ? 'h-24 object-contain' : 'h-10 object-contain'}
                />
            ) : (
                <svg width={size} height={size} viewBox="0 0 120 120">
                    <defs>
                        <linearGradient id="lg" x1=".1" y1="0" x2=".9" y2="1">
                            <stop offset="0" stopColor="#8E3FD0" />
                            <stop offset=".55" stopColor="#B03A9A" />
                            <stop offset="1" stopColor="#E3A04A" />
                        </linearGradient>
                    </defs>
                    <circle cx="60" cy="60" r="50" fill="none" stroke="url(#lg)" strokeWidth="7" />
                    <path d="M24 84 Q52 72 60 34 Q68 72 96 84" fill="none" stroke="url(#lg)" strokeWidth="5" strokeLinecap="round" />
                </svg>
            )}
            <span className={`ar text-gold ${big ? 'text-4xl mt-2' : 'text-lg'}`}>{settings.ar}</span>
            <span className={`font-serif text-gold tracking-wide ${big ? 'text-4xl' : 'text-xl'}`}>{settings.name}</span>
        </Link>
    );
}
