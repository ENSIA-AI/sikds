import $ from 'jquery';

$(document).ready(function() {
    if (document.getElementById('filiere')) {
        $('#filiere').select2({
            theme: 'tailwindcss-4',
        });
    }
});
