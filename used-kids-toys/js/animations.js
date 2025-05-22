// Premium Scroll Reveal Animation
document.addEventListener('DOMContentLoaded', function() {
    // Get all elements with the 'reveal' class
    const reveals = document.querySelectorAll('.reveal');

    // Function to check if element is in viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.8 &&
            rect.bottom >= 0
        );
    }

    // Function to handle scroll reveal with stagger effect
    function handleScrollReveal() {
        reveals.forEach((element, index) => {
            if (isInViewport(element)) {
                setTimeout(() => {
                    element.classList.add('active');
                    element.style.opacity = '1';
                }, index * 100); // Stagger effect
            }
        });
    }

    // Add scroll event listener with throttling
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScrollReveal();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Initial check for elements in viewport
    handleScrollReveal();
});

// Premium Animation Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to main content with blur effect
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.classList.add('fade-in');
        mainContent.style.opacity = '1';
    }

    // Add slide-up animation to product cards with stagger effect
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.classList.add('slide-up');
        card.style.animationDelay = `${index * 0.15}s`;
        card.style.opacity = '1';
    });

    // Add premium hover-lift effect to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('hover-lift');
        card.style.opacity = '1';
    });

    // Add premium image hover effect
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.classList.add('img-hover');
        img.style.opacity = '1';
    });

    // Add reveal class to sections with stagger effect
    const sections = document.querySelectorAll('section');
    sections.forEach((section, index) => {
        section.classList.add('reveal');
        section.style.animationDelay = `${index * 0.2}s`;
        section.style.opacity = '1';
    });

    // Add floating animation to feature icons
    const featureIcons = document.querySelectorAll('.feature i');
    featureIcons.forEach((icon, index) => {
        icon.style.animation = `float 3s ease-in-out ${index * 0.2}s infinite`;
    });

    // Add shine effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.backgroundPosition = '200% 0';
        });
    });

    // Add pulse animation to price tags
    const priceTags = document.querySelectorAll('.price-tag');
    priceTags.forEach(tag => {
        tag.addEventListener('mouseover', function() {
            this.style.animation = 'pulse 1s ease-in-out';
        });
        tag.addEventListener('animationend', function() {
            this.style.animation = '';
        });
    });

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add parallax effect to hero section
    const hero = document.querySelector('.hero');
    if (hero) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        });
    }

    // Add loading animation to images
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('fade-in');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
}); 