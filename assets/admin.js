/* global lsishort, tinymce */
(function ($) {
    'use strict';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    function isValidUrl(str) {
        return /^https?:\/\/.+/i.test(str.trim());
    }

    function shortenUrl(url, onSuccess, onError) {
        $.post(lsishort.ajax_url, {
            action: 'lsishort_shorten',
            nonce:  lsishort.nonce,
            url:    url
        })
        .done(function (res) {
            if (res.success) {
                onSuccess(res.data.short_url);
            } else {
                var msg = (res.data && res.data.message) ? res.data.message : lsishort.i18n.error;
                if (res.data && res.data.settings_url) {
                    onError(msg, res.data.settings_url);
                } else {
                    onError(msg, null);
                }
            }
        })
        .fail(function () {
            onError(lsishort.i18n.error, null);
        });
    }

    // -------------------------------------------------------------------------
    // TinyMCE toolbar button state helper (called from tinymce-plugin.js)
    // -------------------------------------------------------------------------

    window.lsishortGetSelectedUrl = function (editor) {
        var selectedText = editor.selection.getContent({ format: 'text' }).trim();
        return isValidUrl(selectedText) ? selectedText : null;
    };

    window.lsishortShortenSelection = function (editor) {
        var url = window.lsishortGetSelectedUrl(editor);
        if (!url) {
            editor.windowManager.alert(lsishort.i18n.no_url);
            return;
        }
        if (!lsishort.has_token) {
            editor.windowManager.alert(
                lsishort.i18n.no_token + '\n' + lsishort.settings_url
            );
            return;
        }
        shortenUrl(url,
            function (shortUrl) {
                editor.selection.setContent(shortUrl);
            },
            function (msg) {
                editor.windowManager.alert(lsishort.i18n.error + msg);
            }
        );
    };

    // -------------------------------------------------------------------------
    // Meta box: shorten post URL
    // -------------------------------------------------------------------------

    $(document).on('click', '#lsishort-shorten-post-btn', function () {
        var $btn     = $(this);
        var postUrl  = $btn.data('post-url');
        var postId   = $('#post_ID').val();
        var $spinner = $('#lsishort-meta-spinner');
        var $result  = $('#lsishort-meta-result');

        if (!postUrl || !isValidUrl(postUrl)) {
            $result.html('<span class="lsishort-error">' + lsishort.i18n.no_url + '</span>');
            return;
        }

        if (!lsishort.has_token) {
            $result.html(
                '<span class="lsishort-error">' + lsishort.i18n.no_token + ' ' +
                '<a href="' + lsishort.settings_url + '">' + lsishort.i18n.no_token_link + '</a></span>'
            );
            return;
        }

        $btn.attr('disabled', true);
        $spinner.show();
        $result.html('');

        shortenUrl(postUrl,
            function (shortUrl) {
                $spinner.hide();
                $btn.removeAttr('disabled');

                $result.html(
                    '<p>' + lsishort.i18n.short_url + '<br>' +
                    '<a href="' + shortUrl + '" target="_blank" rel="noopener">' + shortUrl + '</a> ' +
                    '<button type="button" class="button button-small lsishort-copy-btn" data-url="' + shortUrl + '">' +
                        lsishort.i18n.copy +
                    '</button></p>'
                );

                if (postId) {
                    $.post(lsishort.ajax_url, {
                        action:    'lsishort_save_meta',
                        nonce:     lsishort.nonce,
                        post_id:   postId,
                        short_url: shortUrl
                    });
                }
            },
            function (msg, settingsUrl) {
                $spinner.hide();
                $btn.removeAttr('disabled');
                var link = settingsUrl
                    ? ' <a href="' + settingsUrl + '">' + lsishort.i18n.no_token_link + '</a>'
                    : '';
                $result.html('<span class="lsishort-error">' + lsishort.i18n.error + msg + link + '</span>');
            }
        );
    });

    // -------------------------------------------------------------------------
    // Copy button
    // -------------------------------------------------------------------------

    $(document).on('click', '.lsishort-copy-btn', function () {
        var $btn = $(this);
        var url  = $btn.data('url');

        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                $btn.text(lsishort.i18n.copied);
                setTimeout(function () { $btn.text(lsishort.i18n.copy); }, 2000);
            });
        } else {
            var $tmp = $('<textarea>').val(url).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            $btn.text(lsishort.i18n.copied);
            setTimeout(function () { $btn.text(lsishort.i18n.copy); }, 2000);
        }
    });

    // -------------------------------------------------------------------------
    // Settings page: test connection
    // -------------------------------------------------------------------------

    $('#lsishort-test-btn').on('click', function () {
        var $btn    = $(this);
        var $result = $('#lsishort-test-result');

        $btn.attr('disabled', true);
        $result.text(lsishort.i18n.testing).css('color', '');

        $.post(lsishort.ajax_url, {
            action: 'lsishort_test',
            nonce:  lsishort.nonce
        })
        .done(function (res) {
            if (res.success) {
                $result.text(res.data.message).css('color', 'green');
            } else {
                $result.text((res.data && res.data.message) ? res.data.message : 'Error').css('color', 'red');
            }
        })
        .fail(function () {
            $result.text('Request failed.').css('color', 'red');
        })
        .always(function () {
            $btn.removeAttr('disabled');
        });
    });

})(jQuery);
