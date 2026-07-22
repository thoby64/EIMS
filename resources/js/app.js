const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
        backdrop?.classList.toggle('hidden');
    });
});
