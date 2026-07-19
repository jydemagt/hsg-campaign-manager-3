jQuery(document).ready(function($) {
    // Existing code...

    $('.duplicate-campaign').on('click', function(e) {
        e.preventDefault();
        var campaignId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hsgcm_duplicate_campaign',
                id: campaignId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to duplicate campaign.');
                }
            },
            error: function() {
                alert('An error occurred while duplicating the campaign.');
            }
        });
    });
});