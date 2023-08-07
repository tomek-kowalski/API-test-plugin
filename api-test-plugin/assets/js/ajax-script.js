jQuery(document).ready(function($) {
    $('#api-form').on('submit', function(e) {
        e.preventDefault(); 

        var fullname = $('#fullname').val();


        var api_url = 'https://orbisius.com/apps/echo/?json=1' + encodeURIComponent(fullname);


        fetch(api_url)
            .then(response => response.json())
            .then(data => {

                console.log('API data:', data);

                $('#target').html(JSON.stringify(data));
            })
            .catch(error => {

                console.error('Error sending API request:', error);
            });
    });
});

