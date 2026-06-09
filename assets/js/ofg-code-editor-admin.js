(function ($) {
  var __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function (text) {
    return text;
  };

  function buildShortcode(language) {
    return '[ofg_code language="' + language + '"]\n<!-- ' + __('your code here', 'ofg-code-editor') + ' -->\n[/ofg_code]';
  }

  function insertIntoEditor(editorId, content) {
    if (window.wp && window.wp.media && window.wp.media.editor && typeof window.wp.media.editor.insert === 'function') {
      window.wpActiveEditor = editorId;
      window.wp.media.editor.insert(content);
      return;
    }

    if (window.tinymce && window.tinymce.get(editorId) && !window.tinymce.get(editorId).isHidden()) {
      window.tinymce.get(editorId).execCommand('mceInsertContent', false, content);
      return;
    }

    if (typeof window.QTags !== 'undefined' && QTags.insertContent) {
      QTags.insertContent(content);
      return;
    }

    var textarea = document.getElementById(editorId);
    if (textarea) {
      textarea.value += content;
    }
  }

  $(document).on('click', '.ofg-insert-code-shortcode', function (event) {
    event.preventDefault();

    var editorId = $(this).data('editor') || 'content';
    var language = window.prompt(__('Enter a language slug: html, css, javascript, php, json, bash, sql', 'ofg-code-editor'), 'markup');

    if (language === null) {
      return;
    }

    language = $.trim(language).toLowerCase() || 'plaintext';
    language = language.replace(/[^a-z0-9_-]/g, '');

    if (language === 'html') {
      language = 'markup';
    }

    insertIntoEditor(editorId, buildShortcode(language));
  });
}(jQuery));
