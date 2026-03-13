/**
 * Blocks
 */
(function () {
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { createElement } = wp.element;

    registerBlockType('support-genix/archive-docs', {
        title: __('SG: Archive Docs', 'support-genix'),
        icon: {
            src: 'archive',
            foreground: '#7229dd',
        },
        category: 'support-genix',
        description: __('Archive docs template of Support Genix (Knowledge Base).', 'support-genix'),
        parent: ['core/post-content'],
        supports: {
            inserter: true,
            multiple: false,
            reusable: false,
            className: true,
        },
        edit: function (props) {
            return createElement(
                'div',
                { className: "support-genix-block-editor" },
                createElement(
                    'p',
                    {},
                    __('Archive Docs - Support Genix (Knowledge Base)', 'support-genix')
                ),
                createElement(
                    'p',
                    {},
                    __('This block is designed exclusively for use with archive page templates!', 'support-genix')
                )
            );
        },
        save: function () {
            return null;
        }
    });

    registerBlockType('support-genix/single-docs', {
        title: __('SG: Single Docs', 'support-genix'),
        icon: {
            src: 'text',
            foreground: '#7229dd',
        },
        category: 'support-genix',
        description: __('Single docs template of Support Genix (Knowledge Base).', 'support-genix'),
        parent: ['core/post-content'],
        supports: {
            inserter: true,
            multiple: false,
            reusable: false,
            className: true,
        },
        edit: function (props) {
            return createElement(
                'div',
                { className: "support-genix-block-editor" },
                createElement(
                    'p',
                    {},
                    __('Single Docs - Support Genix (Knowledge Base)', 'support-genix')
                ),
                createElement(
                    'p',
                    {},
                    __('This block is designed exclusively for use with single page templates!', 'support-genix')
                )
            );
        },
        save: function () {
            return null;
        }
    });
})();