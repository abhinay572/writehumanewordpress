(function() {
    'use strict';

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var Button = wp.components.Button;
    var Spinner = wp.components.Spinner;
    var select = wp.data.select;
    var dispatch = wp.data.dispatch;
    var registerPlugin = wp.plugins.registerPlugin;
    var __ = wp.i18n.__;

    function WriteHumaneSidebar() {
        var _useState = useState('balanced'),
            mode = _useState[0],
            setMode = _useState[1];
        var _useState2 = useState('professional'),
            tone = _useState2[0],
            setTone = _useState2[1];
        var _useState3 = useState(false),
            loading = _useState3[0],
            setLoading = _useState3[1];
        var _useState4 = useState(null),
            result = _useState4[0],
            setResult = _useState4[1];
        var _useState5 = useState(null),
            error = _useState5[0],
            setError = _useState5[1];

        function getContentFromBlocks() {
            var blocks = select('core/block-editor').getBlocks();
            var texts = [];
            blocks.forEach(function(block) {
                if (block.name === 'core/paragraph' || block.name === 'core/heading' || block.name === 'core/list') {
                    var content = block.attributes.content || block.attributes.values || '';
                    if (content) {
                        texts.push(content);
                    }
                }
                // Check inner blocks
                if (block.innerBlocks && block.innerBlocks.length) {
                    block.innerBlocks.forEach(function(inner) {
                        var innerContent = inner.attributes.content || '';
                        if (innerContent) {
                            texts.push(innerContent);
                        }
                    });
                }
            });
            return texts.join('\n\n');
        }

        function handleHumanize() {
            var content = getContentFromBlocks();
            if (!content.trim()) {
                setError(__('No text content found in blocks.', 'writehumane-ai-humanizer'));
                return;
            }

            setLoading(true);
            setError(null);
            setResult(null);

            fetch(whahEditor.restUrl + 'humanize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': whahEditor.nonce,
                },
                body: JSON.stringify({
                    text: content,
                    mode: mode,
                    tone: tone,
                }),
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    setResult(data);
                } else {
                    setError(data.message || 'Humanization failed.');
                }
            })
            .catch(function(err) {
                setError(err.message || 'Request failed.');
            })
            .finally(function() {
                setLoading(false);
            });
        }

        function handleReplace() {
            if (!result || !result.text) return;

            var blocks = select('core/block-editor').getBlocks();
            var humanizedParts = result.text.split('\n\n').filter(function(p) { return p.trim(); });
            var partIndex = 0;

            blocks.forEach(function(block) {
                if ((block.name === 'core/paragraph' || block.name === 'core/heading') && partIndex < humanizedParts.length) {
                    dispatch('core/block-editor').updateBlockAttributes(block.clientId, {
                        content: humanizedParts[partIndex],
                    });
                    partIndex++;
                }
            });

            setResult(null);
            setError(null);
        }

        var sidebarIcon = el('svg', { width: 20, height: 20, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 },
            el('path', { d: 'M12 20h9' }),
            el('path', { d: 'M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z' })
        );

        return el(Fragment, {},
            el(PluginSidebarMoreMenuItem, { target: 'writehumane-sidebar', icon: sidebarIcon }, __('AI Humanizer', 'writehumane-ai-humanizer')),
            el(PluginSidebar, { name: 'writehumane-sidebar', title: __('WriteHumane AI Humanizer', 'writehumane-ai-humanizer'), icon: sidebarIcon },
                el(PanelBody, { title: __('Humanize Content', 'writehumane-ai-humanizer'), className: 'whah-gutenberg-panel', initialOpen: true },
                    el(SelectControl, {
                        label: __('Mode', 'writehumane-ai-humanizer'),
                        value: mode,
                        options: [
                            { label: __('Light', 'writehumane-ai-humanizer'), value: 'light' },
                            { label: __('Balanced', 'writehumane-ai-humanizer'), value: 'balanced' },
                            { label: __('Aggressive', 'writehumane-ai-humanizer'), value: 'aggressive' },
                        ],
                        onChange: setMode,
                    }),
                    el(SelectControl, {
                        label: __('Tone', 'writehumane-ai-humanizer'),
                        value: tone,
                        options: [
                            { label: __('Professional', 'writehumane-ai-humanizer'), value: 'professional' },
                            { label: __('Casual', 'writehumane-ai-humanizer'), value: 'casual' },
                            { label: __('Academic', 'writehumane-ai-humanizer'), value: 'academic' },
                            { label: __('Friendly', 'writehumane-ai-humanizer'), value: 'friendly' },
                        ],
                        onChange: setTone,
                    }),
                    el(Button, {
                        isPrimary: true,
                        onClick: handleHumanize,
                        disabled: loading,
                    },
                        loading ? el(Fragment, {}, el('span', { className: 'whah-spinner' }), __('Humanizing...', 'writehumane-ai-humanizer'))
                                : __('Humanize Content', 'writehumane-ai-humanizer')
                    ),

                    // Result preview
                    result && el('div', {},
                        el('div', { className: 'whah-result-preview' }, result.text.substring(0, 500) + (result.text.length > 500 ? '...' : '')),
                        el('div', { className: 'whah-word-change' }, result.input_words + ' words → ' + result.output_words + ' words'),
                        el(Button, { isPrimary: true, onClick: handleReplace }, __('Replace Content', 'writehumane-ai-humanizer')),
                        el(Button, { isSecondary: true, onClick: function() { setResult(null); } }, __('Keep Original', 'writehumane-ai-humanizer'))
                    ),

                    // Error
                    error && el('div', { className: 'whah-error' }, error)
                )
            )
        );
    }

    registerPlugin('writehumane-ai-humanizer', {
        render: WriteHumaneSidebar,
    });
})();
