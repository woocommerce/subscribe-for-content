(function ( $ ) {
    'use strict';

    $(document).ready(function() {

        /**
         * After API key has been added, we can get lists for it.
         */
        $('.wtsfc-settings .mailchimp-api-key').on('keyup', function () {
            var $disabled = ( $(this).val() == '' ) ? true : false;
            $('.wtsfc-settings .mailchimp-get-lists-button').prop('disabled', $disabled);
        });

        /**
         * Get Lists button.
         */
        $('.wtsfc-settings .mailchimp-get-lists-button').on('click', function (e) {
            e.preventDefault();

            var $this = $(this);
            var $api_key = $('.wtsfc-settings .mailchimp-api-key').val();

            $this.prop('disabled', true).attr('value', 'Getting Lists').after('<img class="loading-icon" src="' + wtsfc_params.loading_img + '" />');

            /**
             * Data to post.
             */
            var post_data = {
                action: 'wtsfc_get_mailchimp_lists',
                security: wtsfc_params.security,
                api_key: $api_key
            };

            /**
             * Here we make the actual Ajax request.
             */
            $.ajax({
                type: 'POST',
                url: wtsfc_params.ajax_url,
                cache: false,
                dataType: 'json',
                data: post_data,
                success: function (data) {

                    // remove loading icon
                    $this.next().remove();

                    /**
                     * Check we have a response before continuing.
                     */
                    if (null !== data) {
                        var data = $.parseJSON(data);
                        /**
                         * If we have one that's successful, let you know.
                         * If not, show an error with the reason why.
                         */
                        if (true == data.success) {
                            console.log(data);

                            // add lists select
                            $this.after('<select name="wtsfc_settings[wtsfc_mailchimp_list]" id="mailchimp_list"></select>');
                            $.each(data.lists.data, function (key, value) {
                                $('select#mailchimp_list').append(
                                    '<option value="' + value.id + '">' + value.name + '</option>'
                                );
                            });

                            // remove this
                            $this.remove();
                            //$this.after('<span class="done-message">Done indexing!</span>');
                        } else {
                            if (true == data.error) {
                                $this.after('<div class="error">' + data.reason + '</div>');
                                $this.prop('disabled', false);
                            }
                        }
                    }
                }
            });
        });

        /**
         * Show list ID below after select list.
         * @todo also show interest groups.
         */
        $('.wtsfc-settings #mailchimp_list').on('change', function(e) {
            e.preventDefault();
            $(this).siblings('.list-id').remove(); // remove previous list id descriptions first
            $(this).after('<em class="description list-id">This list\'s ID is <strong>' + $(this).val() + '</strong>.</em>');
        });

        /**
         * Show interest group interests.
         */
        $('.wtsfc-settings #mailchimp_group').on('change', function(e) {
            e.preventDefault();
            var $selected = $(this).find('option:selected').val();
            $('.wtsfc-settings ul.interests').css('display', 'none');
            if ( $selected !== 'none' ) {
                $('.wtsfc-settings .interests-description').css('display', 'block');
                $('.wtsfc-settings ul.interests-' + $selected).css('display', 'block');
            } else {
                $('.wtsfc-settings .interests-description').css('display', 'none');
            }
        });

    });

}( jQuery ));