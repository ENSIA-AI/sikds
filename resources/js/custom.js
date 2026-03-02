window.addEventListener('success', function(e) {
    console.log('Event received!', e.detail);
});


$(document).ready(function() {
    $('#filiere').select2({
        theme: 'tailwindcss-4',
    });
});
