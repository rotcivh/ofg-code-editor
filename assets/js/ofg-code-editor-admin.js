(function ($) {
  var __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function (text) {
    return text;
  };

  function buildShortcode(language, code) {
    return '[ofgcodeeditor_code language="' + language + '"]' + code + '[/ofgcodeeditor_code]';
  }

  function getSelectedContent(editorId) {
    var textarea = document.getElementById(editorId);

    if (window.tinymce && window.tinymce.get(editorId) && !window.tinymce.get(editorId).isHidden()) {
      return window.tinymce.get(editorId).selection.getContent({ format: 'html' }) || '';
    }

    if (textarea && typeof textarea.selectionStart === 'number' && typeof textarea.selectionEnd === 'number') {
      return textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
    }

    return '';
  }

  function insertIntoEditor(editorId, content) {
    if (window.tinymce && window.tinymce.get(editorId) && !window.tinymce.get(editorId).isHidden()) {
      window.tinymce.get(editorId).execCommand('mceInsertContent', false, content);
      return;
    }

    var textarea = document.getElementById(editorId);

    if (textarea && typeof textarea.selectionStart === 'number' && typeof textarea.selectionEnd === 'number') {
      var start = textarea.selectionStart;
      var end = textarea.selectionEnd;

      textarea.value = textarea.value.substring(0, start) + content + textarea.value.substring(end);
      textarea.selectionStart = start + content.length;
      textarea.selectionEnd = textarea.selectionStart;
      textarea.focus();
      return;
    }

    if (typeof window.QTags !== 'undefined' && QTags.insertContent) {
      QTags.insertContent(content);
      return;
    }

    if (window.wp && window.wp.media && window.wp.media.editor && typeof window.wp.media.editor.insert === 'function') {
      window.wpActiveEditor = editorId;
      window.wp.media.editor.insert(content);
      return;
    }

    if (textarea) {
      textarea.value += content;
    }
  }

  $(document).on('click', '.ofgcodeeditor-insert-code-shortcode', function (event) {
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

    insertIntoEditor(editorId, buildShortcode(language, getSelectedContent(editorId)));
  });
}(jQuery));
