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

    var brandName = (typeof whahEditor !== 'undefined') ? whahEditor.brandName : 'WriteHumane';

    function WriteHumanePanel() {
        var _s = useState(whahEditor.defaultMode);
        var mode = _s[0], setMode = _s[1];
        var _s2 = useState(whahEditor.defaultTone);
        var tone = _s2[0], setTone = _s2[1];
        var _s3 = useState(false);
        var loading = _s3[0], setLoading = _s3[1];
        var _s4 = useState(null);
        var preview = _s4[0], setPreview = _s4[1];
        var _s5 = useState('');
        var error = _s5[0], setError = _s5[1];
        var _s6 = useState(null);
        var stats = _s6[0], setStats = _s6[1];

        function getContent() {
            var blocks = select('core/block-editor').getBlocks();
            var texts = [];
            blocks.forEach(function(block) {
                if (block.attributes && block.attributes.content) {
                    texts.push(block.attributes.content);
                }
                // Check inner blocks
                if (block.innerBlocks) {
                    block.innerBlocks.forEach(function(inner) {
                        if (inner.attributes && inner.attributes.content) {
                            texts.push(inner.attributes.content);
                        }
                    });
                }
            });
            return texts.join('\n\n');
        }

        function humanize() {
            var text = getContent();
            if (!text.trim()) {
                setError('No content found. Please add some text first.');
                return;
            }

            setLoading(true);
            setError('');
            setPreview(null);

            fetch(whahEditor.restUrl + 'humanize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': whahEditor.nonce
                },
                body: JSON.stringify({ text: text, mode: mode, tone: tone })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    setPreview(data.text);
                    setStats({ input: data.input_words, output: data.output_words });
                } else {
                    setError(data.message || 'Something went wrong.');
                }
            })
            .catch(function() {
                setError('Connection error. Please try again.');
            })
            .finally(function() {
                setLoading(false);
            });
        }

        function replaceContent() {
            if (!preview) return;

            var blocks = select('core/block-editor').getBlocks();
            var paragraphs = preview.split(/\n\n+/);
            var blockIds = [];

            // Collect paragraph block IDs
            blocks.forEach(function(block) {
                if (block.name === 'core/paragraph') {
                    blockIds.push(block.clientId);
                }
            });

            // Replace existing paragraph blocks and add new ones
            paragraphs.forEach(function(para, i) {
                if (i < blockIds.length) {
                    dispatch('core/block-editor').updateBlockAttributes(blockIds[i], {
                        content: para.trim()
                    });
                } else {
                    var newBlock = wp.blocks.createBlock('core/paragraph', {
                        content: para.trim()
                    });
                    dispatch('core/block-editor').insertBlocks(newBlock);
                }
            });

            // Remove extra old blocks
            if (blockIds.length > paragraphs.length) {
                var toRemove = blockIds.slice(paragraphs.length);
                dispatch('core/block-editor').removeBlocks(toRemove);
            }

            setPreview(null);
            setStats(null);
        }

        return el(Fragment, null,
            el(PluginSidebarMoreMenuItem, { target: 'writehumane-sidebar' }, brandName + ' Humanizer'),
            el(PluginSidebar, {
                name: 'writehumane-sidebar',
                title: brandName + ' AI Humanizer',
                icon: 'edit-large'
            },
                el(PanelBody, { title: 'Humanize Content', initialOpen: true, className: 'whah-gutenberg-panel' },
                    el(SelectControl, {
                        label: 'Mode',
                        value: mode,
                        options: [
                            { label: 'Light — subtle polish', value: 'light' },
                            { label: 'Balanced — best all-around', value: 'balanced' },
                            { label: 'Aggressive — full rewrite', value: 'aggressive' }
                        ],
                        onChange: setMode
                    }),
                    el(SelectControl, {
                        label: 'Tone',
                        value: tone,
                        options: [
                            { label: 'Professional', value: 'professional' },
                            { label: 'Casual', value: 'casual' },
                            { label: 'Academic', value: 'academic' },
                            { label: 'Friendly', value: 'friendly' }
                        ],
                        onChange: setTone
                    }),
                    el(Button, {
                        isPrimary: true,
                        isBusy: loading,
                        disabled: loading,
                        onClick: humanize,
                        style: { width: '100%', justifyContent: 'center' }
                    }, loading ? el(Spinner, null) : 'Humanize Content'),

                    error && el('div', { style: { color: '#dc2626', marginTop: '10px', fontSize: '13px' } }, error),

                    preview && el('div', null,
                        el('div', { className: 'whah-gutenberg-preview' }, preview),
                        stats && el('div', { className: 'whah-gutenberg-stats' },
                            stats.input + ' words in → ' + stats.output + ' words out'
                        ),
                        el('div', { className: 'whah-gutenberg-actions' },
                            el(Button, { isPrimary: true, onClick: replaceContent }, 'Replace Content'),
                            el(Button, { isSecondary: true, onClick: function() { setPreview(null); setStats(null); } }, 'Keep Original')
                        )
                    )
                )
            )
        );
    }

    wp.plugins.registerPlugin('writehumane-humanizer', {
        render: WriteHumanePanel,
        icon: 'edit-large'
    });
})();
