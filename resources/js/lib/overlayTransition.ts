let splashPlayedInSpaSession = false;
let onboardingCompletedInSpaSession = false;
let supportsDisplayTransition: boolean | undefined;

export function hasPlayedSplashInSession(): boolean {
    return splashPlayedInSpaSession;
}

export function markSplashPlayedInSession(played = true): void {
    splashPlayedInSpaSession = played;
}

export function hasCompletedOnboardingInSession(): boolean {
    return onboardingCompletedInSpaSession;
}

export function markOnboardingCompletedInSession(completed = true): void {
    onboardingCompletedInSpaSession = completed;
}

/**
 * Probes whether the browser genuinely keeps an element visible during a
 * discrete `display` transition (`transition-behavior: allow-discrete`).
 * Works around Firefox 129+ parsing `allow-discrete` without transitioning `display`.
 */
export function canTransitionDisplay(): boolean {
    if (supportsDisplayTransition !== undefined) {
        return supportsDisplayTransition;
    }

    if (
        typeof window === 'undefined' ||
        !window.CSS?.supports?.('transition-behavior', 'allow-discrete') ||
        !document.body
    ) {
        return false;
    }

    const probe = document.createElement('div');
    probe.style.cssText =
        'transition: display 1s allow-discrete !important; display: block;';
    document.body.appendChild(probe);
    void getComputedStyle(probe).display;
    probe.style.display = 'none';
    supportsDisplayTransition = getComputedStyle(probe).display === 'block';
    probe.remove();

    return supportsDisplayTransition;
}

/**
 * Smoothly runs the CSS exit transition on an overlay element before hiding it,
 * separating `[data-closing]` from `[hidden]` when discrete `display` transitions
 * are unsupported.
 */
export async function hideOverlayElement(
    el: HTMLElement | null,
    timeoutMs = 600,
): Promise<void> {
    if (!el) {
        return;
    }

    if (canTransitionDisplay()) {
        el.hidden = true;
    } else {
        el.setAttribute('data-closing', '');
    }

    const animations = el.getAnimations();

    if (animations.length > 0) {
        await Promise.race([
            Promise.allSettled(
                animations.map((animation) => animation.finished),
            ),
            new Promise((resolve) => setTimeout(resolve, timeoutMs)),
        ]);
    }

    if (el.hasAttribute('data-closing')) {
        el.removeAttribute('data-closing');
        el.hidden = true;
    }
}
