import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function HeroLink({ href, preview, className, children }) {
    if (!href || preview) {
        return <span className={className}>{children}</span>;
    }

    if (/^https?:\/\//i.test(href)) {
        return (
            <a href={href} className={className} target="_blank" rel="noreferrer">
                {children}
            </a>
        );
    }

    return (
        <Link href={href} className={className}>
            {children}
        </Link>
    );
}

function ArrowLeft() {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M15 6l-6 6 6 6" />
        </svg>
    );
}

function ArrowRight() {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 6l6 6-6 6" />
        </svg>
    );
}

export default function StoreHero({ images = [], settings = {}, preview = false, device = null, copy: copyOverride = null }) {
    const page = usePage();
    const copy = copyOverride || page.props.settings || {};
    const [index, setIndex] = useState(0);
    const [hoverPaused, setHoverPaused] = useState(false);
    const [hidden, setHidden] = useState(false);
    const [reduced, setReduced] = useState(false);
    const rootRef = useRef(null);
    const startX = useRef(null);
    const swiped = useRef(false);

    const count = images.length;
    const safeIndex = count > 0 ? Math.min(index, count - 1) : 0;
    const active = count > 0 ? images[safeIndex] : null;
    const second = count > 1 ? images[(safeIndex + 1) % count] : null;
    const third = count > 2 ? images[(safeIndex + 2) % count] : null;
    const transition = settings.transition || 'fade';
    const speed = Number(settings.speed) > 0 ? Number(settings.speed) : 0.7;
    const pauseOnHover = settings.pause_hover !== false;
    const paused = (pauseOnHover && hoverPaused) || hidden;
    const showControls = !preview && count > 1;
    const showDots = showControls && settings.show_dots !== false;
    const showArrows = showControls && settings.show_arrows !== false;

    useEffect(() => {
        if (index > count - 1) {
            setIndex(0);
        }
    }, [count, index]);

    useEffect(() => {
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = () => setReduced(media.matches);

        update();
        media.addEventListener('change', update);

        return () => media.removeEventListener('change', update);
    }, []);

    useEffect(() => {
        const update = () => setHidden(document.hidden);

        update();
        document.addEventListener('visibilitychange', update);

        return () => document.removeEventListener('visibilitychange', update);
    }, []);

    useEffect(() => {
        if (preview || !settings.autoplay || count < 2 || paused) {
            return undefined;
        }

        const timer = setTimeout(() => {
            setIndex((current) => (current + 1) % count);
        }, Math.max(2000, Number(settings.duration) || 6500));

        return () => clearTimeout(timer);
    }, [preview, settings.autoplay, settings.duration, count, paused, safeIndex]);

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return undefined;
        }

        root.classList.remove('hg-anim');
        void root.offsetWidth;
        root.classList.add('hg-anim');

        return undefined;
    }, [safeIndex, transition, speed]);

    useEffect(() => {
        if (count < 2) {
            return;
        }

        const next = images[(safeIndex + 1) % count];

        if (next?.url) {
            const image = new Image();
            image.src = next.url;
        }
    }, [safeIndex, count, images]);

    const go = (next) => {
        if (count < 2) {
            return;
        }

        setIndex(((next % count) + count) % count);
    };

    if (!count) {
        return null;
    }

    return (
        <section
            ref={rootRef}
            className={`hg-hero${device === 'mobile' ? ' hg-hero--mobile' : ''}${preview ? ' hg-hero--preview' : ''}`}
            data-transition={transition}
            style={{ '--hg-speed': `${speed}s` }}
            aria-roledescription="carousel"
            aria-label="Featured collection"
            onMouseEnter={() => pauseOnHover && setHoverPaused(true)}
            onMouseLeave={() => setHoverPaused(false)}
            onFocus={() => pauseOnHover && setHoverPaused(true)}
            onBlur={() => setHoverPaused(false)}
            onPointerDown={(event) => {
                startX.current = event.clientX;
                swiped.current = false;
            }}
            onPointerUp={(event) => {
                if (startX.current === null) {
                    return;
                }

                const delta = event.clientX - startX.current;
                startX.current = null;

                if (Math.abs(delta) > 45) {
                    swiped.current = true;
                    go(safeIndex + (delta < 0 ? 1 : -1));
                }
            }}
            onPointerCancel={() => {
                startX.current = null;
            }}
            onClickCapture={(event) => {
                if (swiped.current) {
                    event.preventDefault();
                    event.stopPropagation();
                    swiped.current = false;
                }
            }}
        >
            <span className="hg-blob hg-blob-1" />
            <span className="hg-blob hg-blob-2" />
            <span className="hg-blob hg-blob-3" />
            <span className="hg-ring hg-ring-1" />
            <span className="hg-ring hg-ring-2" />

            <div className="hg-inner">
                <div className="hg-copy">
                    {copy.top && (
                        <span className="hg-badge">
                            <i className="hg-pulse" />
                            {copy.top}
                        </span>
                    )}

                    <h1 className="hg-title">{copy.title || 'Wear Your Aura. Dress With Confidence.'}</h1>

                    {copy.sub && <p className="hg-desc">{copy.sub}</p>}

                    <div className="hg-actions">
                        <Link href="/shop" className="hg-btn hg-btn-primary">
                            Shop now
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </Link>
                        <Link href="/reviews" className="hg-btn hg-btn-ghost">
                            Reviews
                        </Link>
                    </div>

                    <div className="hg-trust">
                        <span className="hg-trust-item">
                            <span className="hg-trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 8.7l5.4-.8L12 3z" />
                                </svg>
                            </span>
                            Premium quality
                        </span>
                        <span className="hg-trust-item">
                            <span className="hg-trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" />
                                    <circle cx="7" cy="18" r="1.6" />
                                    <circle cx="17.5" cy="18" r="1.6" />
                                </svg>
                            </span>
                            Fast delivery
                        </span>
                        <span className="hg-trust-item">
                            <span className="hg-trust-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 3l7 3v5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3z" />
                                    <path d="M9 11.5l2 2 4-4" />
                                </svg>
                            </span>
                            Secure payment
                        </span>
                    </div>
                </div>

                <div
                    className="hg-stage"
                    tabIndex={0}
                    aria-label="Product images. Use left and right arrow keys."
                    onKeyDown={(event) => {
                        if (event.key === 'ArrowLeft') {
                            event.preventDefault();
                            go(safeIndex - 1);
                        }
                        if (event.key === 'ArrowRight') {
                            event.preventDefault();
                            go(safeIndex + 1);
                        }
                    }}
                >
                    <span className="hg-glow" />

                    {second && (
                        <div key={`left-${second.id ?? safeIndex}`} className="hg-card hg-card-left" aria-hidden="true">
                            <img src={second.url} alt="" loading="lazy" />
                            <span className="hg-shine" />
                        </div>
                    )}

                    {third && (
                        <div key={`right-${third.id ?? safeIndex}`} className="hg-card hg-card-right" aria-hidden="true">
                            <img src={third.url} alt="" loading="lazy" />
                            <span className="hg-shine" />
                        </div>
                    )}

                    <HeroLink
                        key={`main-${active.id ?? safeIndex}`}
                        href="/shop"
                        preview={preview}
                        className="hg-card hg-card-main"
                    >
                        <img
                            src={active.url}
                            alt={copy.title || 'GEN AURA'}
                            loading={safeIndex === 0 ? 'eager' : 'lazy'}
                            fetchPriority={safeIndex === 0 ? 'high' : 'auto'}
                        />
                        <span className="hg-shine" />
                    </HeroLink>

                    <span className="hg-spark hg-spark-1" />
                    <span className="hg-spark hg-spark-2" />
                    <span className="hg-spark hg-spark-3" />
                </div>
            </div>

            {showControls && (
                <div className="hg-controls">
                    <div className="hg-count">
                        <b>{String(safeIndex + 1).padStart(2, '0')}</b>
                        <span>/ {String(count).padStart(2, '0')}</span>
                    </div>

                    {showDots && (
                        <div className="hg-dots" role="tablist" aria-label="Choose image">
                            {images.map((image, dotIndex) => (
                                <button
                                    key={image.id ?? dotIndex}
                                    type="button"
                                    role="tab"
                                    aria-label={`Go to image ${dotIndex + 1}`}
                                    aria-selected={safeIndex === dotIndex}
                                    className={safeIndex === dotIndex ? 'on' : ''}
                                    onClick={() => go(dotIndex)}
                                />
                            ))}
                        </div>
                    )}

                    {showArrows && (
                        <div className="hg-arrows">
                            <button type="button" className="hg-arrow" aria-label="Previous image" onClick={() => go(safeIndex - 1)}>
                                <ArrowLeft />
                            </button>
                            <button type="button" className="hg-arrow" aria-label="Next image" onClick={() => go(safeIndex + 1)}>
                                <ArrowRight />
                            </button>
                        </div>
                    )}
                </div>
            )}

        </section>
    );
}
