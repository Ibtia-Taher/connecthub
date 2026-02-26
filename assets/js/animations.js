/**
 * GSAP Animations
 * Smooth animations for UI interactions
 */

// Check if GSAP is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gsap !== 'undefined') {
        console.log('✅ GSAP loaded successfully');
    } else {
        console.error('❌ GSAP not loaded!');
    }
});

/**
 * Animate new post appearing
 */
function animateNewPost(element) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for animateNewPost');
        return;
    }
    
    console.log('Animating new post');
    
    gsap.from(element, {
        duration: 0.5,
        opacity: 0,
        y: -30,
        scale: 0.95,
        ease: 'power2.out'
    });
}

/**
 * Animate post deletion
 */
async function animatePostDeletion(element) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for animatePostDeletion');
        element.remove();
        return;
    }
    
    console.log('Animating post deletion');
    
    return new Promise((resolve) => {
        gsap.to(element, {
            duration: 0.4,
            opacity: 0,
            x: 100,
            height: 0,
            marginBottom: 0,
            paddingTop: 0,
            paddingBottom: 0,
            ease: 'power2.in',
            onComplete: () => {
                element.remove();
                resolve();
            }
        });
    });
}

/**
 * Animate like button
 */
function animateLike(button) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for animateLike');
        return;
    }
    
    console.log('Animating like button');
    
    gsap.fromTo(button, 
        { scale: 1 },
        { 
            scale: 1.3,
            duration: 0.2,
            yoyo: true,
            repeat: 1,
            ease: 'elastic.out(1, 0.3)'
        }
    );
}

/**
 * Animate comment appearing
 */
function animateNewComment(element) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for animateNewComment');
        return;
    }
    
    console.log('Animating new comment');
    
    gsap.from(element, {
        duration: 0.4,
        opacity: 0,
        x: -20,
        ease: 'power2.out'
    });
}

/**
 * Animate rating stars
 */
function animateRating(starsContainer) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for animateRating');
        return;
    }
    
    console.log('Animating rating stars');
    
    const stars = starsContainer.querySelectorAll('.star');
    gsap.fromTo(stars,
        { scale: 1, rotation: 0 },
        {
            scale: 1.3,
            rotation: 360,
            duration: 0.5,
            stagger: 0.08,
            ease: 'back.out(1.7)',
            onComplete: () => {
                gsap.to(stars, {
                    scale: 1,
                    rotation: 0,
                    duration: 0.2
                });
            }
        }
    );
}

/**
 * Pulse animation for notifications
 */
function pulseElement(element) {
    if (typeof gsap === 'undefined') {
        console.warn('GSAP not available for pulseElement');
        return;
    }
    
    gsap.fromTo(element,
        { scale: 1 },
        {
            scale: 1.05,
            duration: 0.3,
            yoyo: true,
            repeat: 3,
            ease: 'power1.inOut'
        }
    );
}