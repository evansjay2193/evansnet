document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');

    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });

    // Close Mobile Menu on Larger Screens
    window.addEventListener('resize', () => {
        const width = window.innerWidth;

        if (width > 768 && navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
        }
    });

    // Form Validation
    const postForm = document.getElementById('post-form');
    if (postForm) {
        postForm.addEventListener('submit', (event) => {
            const content = document.getElementById('content').value.trim();

            if (content === '') {
                alert('Content is required.');
                event.preventDefault();
                return;
            }

            // Additional validation for file type or other requirements can be added here
        });
    }

    // Scroll to Top Button
    const backToTopButton = document.querySelector('.back-to-top');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });

    // Scroll to Top Functionality
    if (backToTopButton) {
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
