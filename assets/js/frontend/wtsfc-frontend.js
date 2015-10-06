(function ( $ ) {
    'use strict';

    // Cookie.js - Copyright (c) 2012 Florian H., https://github.com/js-coder https://github.com/js-coder/cookie.js
    !function(e,t){var n=function(){return n.get.apply(n,arguments)},r=n.utils={isArray:Array.isArray||function(e){return Object.prototype.toString.call(e)==="[object Array]"},isPlainObject:function(e){return!!e&&Object.prototype.toString.call(e)==="[object Object]"},toArray:function(e){return Array.prototype.slice.call(e)},getKeys:Object.keys||function(e){var t=[],n="";for(n in e)e.hasOwnProperty(n)&&t.push(n);return t},escape:function(e){return String(e).replace(/[,;"\\=\s%]/g,function(e){return encodeURIComponent(e)})},retrieve:function(e,t){return e==null?t:e}};n.defaults={},n.expiresMultiplier=86400,n.set=function(n,i,s){if(r.isPlainObject(n))for(var o in n)n.hasOwnProperty(o)&&this.set(o,n[o],i);else{s=r.isPlainObject(s)?s:{expires:s};var u=s.expires!==t?s.expires:this.defaults.expires||"",a=typeof u;a==="string"&&u!==""?u=new Date(u):a==="number"&&(u=new Date(+(new Date)+1e3*this.expiresMultiplier*u)),u!==""&&"toGMTString"in u&&(u=";expires="+u.toGMTString());var f=s.path||this.defaults.path;f=f?";path="+f:"";var l=s.domain||this.defaults.domain;l=l?";domain="+l:"";var c=s.secure||this.defaults.secure?";secure":"";e.cookie=r.escape(n)+"="+r.escape(i)+u+f+l+c}return this},n.remove=function(e){e=r.isArray(e)?e:r.toArray(arguments);for(var t=0,n=e.length;t<n;t++)this.set(e[t],"",-1);return this},n.empty=function(){return this.remove(r.getKeys(this.all()))},n.get=function(e,n){n=n||t;var i=this.all();if(r.isArray(e)){var s={};for(var o=0,u=e.length;o<u;o++){var a=e[o];s[a]=r.retrieve(i[a],n)}return s}return r.retrieve(i[e],n)},n.all=function(){if(e.cookie==="")return{};var t=e.cookie.split("; "),n={};for(var r=0,i=t.length;r<i;r++){var s=t[r].split("=");n[decodeURIComponent(s[0])]=decodeURIComponent(s[1])}return n},n.enabled=function(){if(navigator.cookieEnabled)return!0;var e=n.set("_","_").get("_")==="_";return n.remove("_"),e},typeof define=="function"&&define.amd?define(function(){return n}):typeof exports!="undefined"?exports.cookie=n:window.cookie=n}(document);

    $(document).ready(function() {

        var $form = $('#wtsfc.subscribe-for-content-box form#subscribe');

        /**
         * Update group interest input value on select change.
         */
        $form.find('select.group-interest').on('change', function(e) {
            e.preventDefault();
            $form.find('.interests').val($(this).find('option:selected').text());
        });

        /**
         * Handle form submission.
         */
        $form.on('submit', function(e) {
            e.preventDefault();

            // vars
            var $this = $(this);
            var $email = $form.find('.email-input').val();
            var $list = $form.find('.list-id').val();
            var $group = $form.find('.group-id').val();
            var $interests = $form.find('.interests').val();
            var $current_post = $form.find('.current-post').val();

            // remove any previous errors
            $this.siblings('.error').remove();

            // loading/disabled
            $this.parent().addClass('loading');
            $this.find('.submit-subscribe').prop('disabled', true);

            /**
             * Data to post.
             */
            var post_data = {
                action: 'wtsfc_subscribe_list',
                security: wtsfc_frontend_params.security,
                email: $email,
                list: $list,
                group: $group,
                interests: $interests,
                current_post: $current_post
            };

            /**
             * Here we make the actual Ajax request.
             */
            $.ajax({
                type: 'POST',
                url: wtsfc_frontend_params.ajax_url,
                cache: false,
                dataType: 'json',
                data: post_data,
                success: function (data) {

                    // remove loading/disabled
                    $this.parent().removeClass('loading');
                    $this.find('.submit-subscribe').prop('disabled', false);

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

                            /**
                             * Set the cookie (using cookie.js).
                             */
                            if (null !== data.cookie) {
                                cookie.set( data.cookie.key , data.cookie.value, {
                                   expires: 365,
                                   path: data.cookie.path,
                                   secure: false
                                });
                            }

                            var $box = $this.closest('#wtsfc');

                            /**
                             * Successful subscription message.
                             */
                            $box.addClass('subscribed-success').html(
                                '<h3>' + wtsfc_frontend_params.message_thankyou + '</h3>' +
                                '<p>' + wtsfc_frontend_params.message_loading + '</p>' +
                                '<img src="' + wtsfc_frontend_params.loading_img + '" />'
                            );

                            /**
                             * Data to post.
                             */
                            var post_data = {
                                action: 'wtsfc_get_full_content',
                                security: wtsfc_frontend_params.security,
                                current_post: $current_post
                            };

                            /**
                             * Here we make the actual Ajax request.
                             */
                            $.ajax({
                                type: 'POST',
                                url: wtsfc_frontend_params.ajax_url,
                                cache: false,
                                dataType: 'json',
                                data: post_data,
                                success: function (data) {
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
                                            var $content = $(data.the_content).filter('.wtsfc-hidden-content').html();
                                            $box.replaceWith('<div class="wtsfc-hidden-content">' + $content + '</div>');
                                        } else {
                                            if (true == data.error) {
                                                $this.after('<div class="error">' + data.reason + '</div>');
                                            }
                                        }
                                    }
                                }
                            });

                        } else {
                            if (true == data.error) {
                                $this.after('<div class="error">' + data.reason + '</div>');
                            }
                        }
                    }
                }
            });

        });

    });

}( jQuery ));