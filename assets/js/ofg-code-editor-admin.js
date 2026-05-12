(function ($) {
  function buildShortcode(language) {
    return '[ofg_code language="' + language + '"]\n<!-- your code here -->\n[/ofg_code]';
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
    var language = window.prompt('Enter a language slug: html, css, javascript, php, json, bash, sql', 'markup');

    if (language === null) {
      return;
    }

    language = $.trim(language).toLowerCase() || 'plaintext';

    if (language === 'html') {
      language = 'markup';
    }

    insertIntoEditor(editorId, buildShortcode(language));
  });
}(jQuery));
