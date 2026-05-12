(function (blocks, element, blockEditor, components, i18n) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var PlainText = blockEditor.PlainText;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var __ = i18n.__;

  var languages = [
    { label: 'HTML', value: 'markup' },
    { label: 'CSS', value: 'css' },
    { label: 'JavaScript', value: 'javascript' },
    { label: 'PHP', value: 'php' },
    { label: 'JSON', value: 'json' },
    { label: 'Bash', value: 'bash' },
    { label: 'SQL', value: 'sql' },
    { label: 'Text', value: 'plaintext' }
  ];

  function renderLines(code) {
    return (code || '').replace(/\r\n?/g, '\n').split('\n').map(function (line, index) {
      return el('li', { className: 'ofg-code-block__line', key: 'line-' + index },
        el('span', { className: 'ofg-code-block__line-text' }, line === '' ? '\u00a0' : line)
      );
    });
  }

  blocks.registerBlockType('ofg-code-editor/code-block', {
    apiVersion: 2,
    title: __('OFG Code Editor', 'ofg-code-editor'),
    description: __('Display formatted code with a title, line numbers and copy action.', 'ofg-code-editor'),
    icon: 'editor-code',
    category: 'formatting',
    attributes: {
      code: {
        type: 'string',
        source: 'text',
        selector: '.ofg-code-block__code-source'
      },
      language: {
        type: 'string',
        default: 'markup'
      }
    },
    edit: function (props) {
      var attributes = props.attributes;
      var currentLanguage = languages.find(function (item) {
        return item.value === attributes.language;
      }) || languages[0];

      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: __('Code box settings', 'ofg-code-editor'), initialOpen: true },
            el(SelectControl, {
              label: __('Language', 'ofg-code-editor'),
              value: attributes.language,
              options: languages,
              onChange: function (value) {
                props.setAttributes({ language: value });
              }
            })
          )
        ),
        el('div', { className: 'ofg-code-block is-editor-preview', 'data-language': attributes.language || 'markup' },
          el('div', { className: 'ofg-code-block__header' },
            el('span', { className: 'ofg-code-block__title' }, 'OFG Code Editor Plugin'),
            el('span', { className: 'ofg-code-block__language' }, currentLanguage.label),
            el('button', { type: 'button', className: 'ofg-code-block__copy', disabled: true }, __('Copy code', 'ofg-code-editor'))
          ),
          el('div', { className: 'ofg-code-block__body' },
            el('ol', { className: 'ofg-code-block__lines' }, renderLines(attributes.code || ''))
          ),
          el(PlainText, {
            className: 'ofg-code-block__editor-input',
            value: attributes.code,
            onChange: function (value) {
              props.setAttributes({ code: value });
            },
            placeholder: __('Paste HTML, CSS, JavaScript, PHP or any other code here…', 'ofg-code-editor'),
            'aria-label': __('Custom code', 'ofg-code-editor')
          })
        )
      );
    },
    save: function (props) {
      var attributes = props.attributes;
      var currentLanguage = languages.find(function (item) {
        return item.value === attributes.language;
      }) || languages[0];

      return el('div', { className: 'ofg-code-block', 'data-language': attributes.language || 'markup' },
        el('div', { className: 'ofg-code-block__header' },
          el('span', { className: 'ofg-code-block__title' }, 'OFG Code Editor Plugin'),
          el('span', { className: 'ofg-code-block__language' }, currentLanguage.label),
          el('button', {
            type: 'button',
            className: 'ofg-code-block__copy',
            'data-copy-label': 'Copy code',
            'data-copied-label': 'Copied'
          }, 'Copy code')
        ),
        el('div', { className: 'ofg-code-block__body' },
          el('ol', { className: 'ofg-code-block__lines' }, renderLines(attributes.code || ''))
        ),
        el('pre', { className: 'ofg-code-block__code-source', hidden: true }, attributes.code || '')
      );
    }
  });
}(window.wp.blocks, window.wp.element, window.wp.blockEditor || window.wp.editor, window.wp.components, window.wp.i18n));
