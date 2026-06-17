/* global tinymce, lsishort */
tinymce.PluginManager.add('lsishort', function (editor) {
    'use strict';

    function isValidUrl(str) {
        return /^https?:\/\/.+/i.test((str || '').trim());
    }

    function getIconHtml() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>' +
            '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>' +
            '</svg>';
    }

    editor.addButton('lsishort', {
        tooltip: 'Shorten URL with iShort',
        icon: false,
        image: false,
        text: '',
        html: function () {
            return '<button type="button" id="lsishort-mce-btn" class="mce-btn" title="Shorten URL with iShort" style="padding:2px 4px;">' +
                getIconHtml() +
                '</button>';
        },
        onPostRender: function () {
            var self = this;
            self.disabled(true);

            editor.on('NodeChange SelectionChange', function () {
                var text = editor.selection.getContent({ format: 'text' }).trim();
                self.disabled(!isValidUrl(text));
            });
        },
        onclick: function () {
            var url = editor.selection.getContent({ format: 'text' }).trim();
            if (!isValidUrl(url)) {
                editor.windowManager.alert(lsishort.i18n.no_url);
                return;
            }
            if (!lsishort.has_token) {
                editor.windowManager.alert(lsishort.i18n.no_token + '\n' + lsishort.settings_url);
                return;
            }

            var originalContent = editor.selection.getContent();

            jQuery.post(lsishort.ajax_url, {
                action: 'lsishort_shorten',
                nonce:  lsishort.nonce,
                url:    url
            })
            .done(function (res) {
                if (res.success) {
                    editor.selection.setContent(res.data.short_url);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : lsishort.i18n.error;
                    editor.windowManager.alert(lsishort.i18n.error + msg);
                }
            })
            .fail(function () {
                editor.windowManager.alert(lsishort.i18n.error);
            });
        }
    });
});
