window.addEventListener('DOMContentLoaded', (event) => {
    const links = ['sql', 'history', 'find'];
    links.forEach(link => {
        document.getElementById(`toplink_${link}`)?.addEventListener('click', function(event) {
            event.preventDefault();
            window.open(
                this.getAttribute('href'),
                `${link}::5432:allow`,
                'toolbar=no,width=700,height=500,resizable=yes,scrollbars=yes'
            ).focus();
        });
    });
});
