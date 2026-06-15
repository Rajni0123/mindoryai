// Mobile menu open/close
const openMenu = document.getElementById('openMenu');
const closeMenu = document.getElementById('closeMenu');
const mobileMenu = document.getElementById('mobileMenu');

openMenu.addEventListener('click', () => {
    mobileMenu.classList.remove('h-0');
    mobileMenu.classList.add('h-92');
});

closeMenu.addEventListener('click', () => {
    mobileMenu.classList.remove('h-92');
    mobileMenu.classList.add('h-0');
});