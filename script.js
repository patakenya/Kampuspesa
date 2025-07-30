document.addEventListener('DOMContentLoaded', function() {
  const lazyImages = document.querySelectorAll('.lazy-load');
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) img.src = img.dataset.src;
          if (img.dataset.srcset) img.srcset = img.dataset.srcset;
          img.classList.remove('lazy-load');
          img.classList.add('lazy-loaded');
          img.style.opacity = '0';
          img.style.transition = 'opacity 0.3s ease-in-out';
          img.onload = () => { img.style.opacity = '1'; };
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '100px 0px', threshold: 0.01 });
    lazyImages.forEach(img => imageObserver.observe(img));
  } else {
    lazyImages.forEach(img => {
      if (img.dataset.src) img.src = img.dataset.src;
      if (img.dataset.srcset) img.srcset = img.dataset.srcset;
      img.classList.remove('lazy-load');
      img.classList.add('lazy-loaded');
    });
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('nav a');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      navLinks.forEach(lnk => {
        lnk.classList.remove('text-primary');
        lnk.classList.add('text-gray-600');
      });
      this.classList.remove('text-gray-600');
      this.classList.add('text-primary');
    });
  });
});