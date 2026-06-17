/* global lsishort */
(function () {
    'use strict';

    var el           = wp.element.createElement;
    var useState     = wp.element.useState;
    var useSelect    = wp.data.useSelect;
    var dispatch     = wp.data.dispatch;
    var richText     = wp.richText;
    var registerFormatType   = richText.registerFormatType;
    var toggleFormat         = richText.toggleFormat;
    var getActiveFormat      = richText.getActiveFormat;
    var useAnchorRef         = wp.richText.useAnchorRef;
    var BlockControls        = wp.blockEditor ? wp.blockEditor.BlockControls : wp.editor.BlockControls;
    var RichTextToolbarButton = wp.blockEditor
        ? wp.blockEditor.RichTextToolbarButton
        : wp.editor.RichTextToolbarButton;
    var Popover     = wp.components.Popover;
    var Button      = wp.components.Button;
    var Spinner     = wp.components.Spinner;
    var Notice      = wp.components.Notice;

    var FORMAT_NAME = 'lsishort/shorten';

    function isValidUrl(str) {
        return /^https?:\/\/.+/i.test((str || '').trim());
    }

    function getSelectedText(value) {
        if (!value) return '';
        var start = value.start;
        var end   = value.end;
        if (start === end) return '';
        return wp.richText.getTextContent(
            wp.richText.slice(value, start, end)
        );
    }

    registerFormatType(FORMAT_NAME, {
        title:     lsishort.i18n.button_title,
        tagName:   'span',
        className: null,
        edit: function (props) {
            var value       = props.value;
            var onChange    = props.onChange;
            var isActive    = props.isActive;

            var selectedText = getSelectedText(value);
            var isUrl        = isValidUrl(selectedText);

            var _useState     = useState(false);
            var loading       = _useState[0];
            var setLoading    = _useState[1];

            var _useStateMsg  = useState(null);
            var notice        = _useStateMsg[0];
            var setNotice     = _useStateMsg[1];

            function handleClick() {
                if (!lsishort.has_token) {
                    setNotice({
                        type: 'error',
                        text: lsishort.i18n.no_token,
                        link: lsishort.settings_url,
                        linkText: lsishort.i18n.no_token_link
                    });
                    return;
                }
                if (!isUrl) {
                    setNotice({ type: 'error', text: lsishort.i18n.no_url });
                    return;
                }

                setLoading(true);
                setNotice(null);

                wp.apiFetch({
                    url:    lsishort.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'lsishort_shorten',
                        nonce:  lsishort.nonce,
                        url:    selectedText.trim()
                    }
                }).then(function (res) {
                    setLoading(false);
                    if (res.success) {
                        var shortUrl   = res.data.short_url;
                        var newValue   = wp.richText.insert(
                            wp.richText.remove(value, value.start, value.end),
                            shortUrl,
                            value.start
                        );
                        onChange(newValue);
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : lsishort.i18n.error;
                        setNotice({ type: 'error', text: msg });
                    }
                }).catch(function () {
                    setLoading(false);
                    setNotice({ type: 'error', text: lsishort.i18n.error });
                });
            }

            var iconEl = el('svg', {
                xmlns:   'http://www.w3.org/2000/svg',
                width:   20,
                height:  20,
                viewBox: '0 0 24 24',
                fill:    'none',
                stroke:  'currentColor',
                strokeWidth: 2,
                strokeLinecap: 'round',
                strokeLinejoin: 'round'
            },
                el('path', { d: 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71' }),
                el('path', { d: 'M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71' })
            );

            var btnEl = el(RichTextToolbarButton, {
                icon:         iconEl,
                title:        lsishort.i18n.button_title,
                onClick:      handleClick,
                isActive:     isActive,
                isDisabled:   !isUrl || loading
            });

            var noticeEl = null;
            if (notice) {
                noticeEl = el(
                    'div',
                    { style: { position: 'absolute', zIndex: 999, minWidth: 260, top: 36, left: 0 } },
                    el(Notice, {
                        status:      notice.type,
                        isDismissible: true,
                        onRemove:    function () { setNotice(null); }
                    },
                        notice.text,
                        notice.link ? el('a', {
                            href:   notice.link,
                            target: '_blank',
                            rel:    'noopener',
                            style:  { marginLeft: 4 }
                        }, notice.linkText) : null
                    )
                );
            }

            return el(
                'div',
                { style: { position: 'relative', display: 'inline-block' } },
                btnEl,
                loading ? el(Spinner) : null,
                noticeEl
            );
        }
    });

})();
