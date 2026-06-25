(function () {
  function escapeHtml(value) {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function normalizeLanguage(language) {
    if (language === 'html') {
      return 'markup';
    }

    return language || 'plaintext';
  }

  function getGrammar(language) {
    var commonKeywords = /\b(function|return|if|else|for|while|switch|case|break|continue|default|new|try|catch|finally|throw|class|extends|const|let|var|import|export|from|async|await|static|public|private|protected)\b/g;

    var grammars = {
      markup: {
        comment: [/<!--[\s\S]*?-->/g],
        string: [/("[^"]*"|'[^']*')/g],
        keyword: [/(<\/?)([a-zA-Z][\w:-]*)/g, /\b([a-zA-Z-:]+)(=)/g],
        number: []
      },
      css: {
        comment: [/\/\*[\s\S]*?\*\//g],
        string: [/("[^"]*"|'[^']*')/g],
        keyword: [/\b(@media|@supports|@keyframes|@font-face|@import|@layer|@container)\b/g, /(^|[{};\s])([.#]?[a-zA-Z_-][\w-]*)(?=\s*\{)/gm, /\b([a-z-]+)(?=\s*:)/g],
        number: [/\b\d+(?:\.\d+)?(?:px|rem|em|%|vh|vw|deg|fr|s|ms)?\b/g]
      },
      javascript: {
        comment: [/\/\*[\s\S]*?\*\//g, /(^|[^:])\/\/.*$/gm],
        string: [/("(?:\\.|[^"])*"|'(?:\\.|[^'])*'|`(?:\\.|[^`])*`)/g],
        keyword: [commonKeywords, /\b(true|false|null|undefined|this|super)\b/g],
        number: [/\b\d+(?:\.\d+)?\b/g]
      },
      php: {
        comment: [/\/\*[\s\S]*?\*\//g, /(^|[^:])\/\/.*$/gm, /#.*$/gm],
        string: [/("(?:\\.|[^"])*"|'(?:\\.|[^'])*')/g],
        keyword: [/\b(function|return|if|else|elseif|foreach|for|while|switch|case|break|continue|default|new|try|catch|finally|throw|class|extends|public|private|protected|static|namespace|use|echo|print)\b/g, /\b(true|false|null)\b/g, /(\$[a-zA-Z_][\w]*)/g],
        number: [/\b\d+(?:\.\d+)?\b/g]
      },
      json: {
        comment: [],
        string: [/("(?:\\.|[^"])*")/g],
        keyword: [/\b(true|false|null)\b/g],
        number: [/\b-?\d+(?:\.\d+)?(?:e[+-]?\d+)?\b/gi]
      },
      bash: {
        comment: [/#.*$/gm],
        string: [/("(?:\\.|[^"])*"|'(?:\\.|[^'])*')/g],
        keyword: [/\b(if|then|else|fi|for|do|done|case|esac|function|return|in|echo|exit)\b/g, /(\$[A-Za-z_][\w]*)/g],
        number: [/\b\d+\b/g]
      },
      sql: {
        comment: [/--.*$/gm, /\/\*[\s\S]*?\*\//g],
        string: [/("(?:\\.|[^"])*"|'(?:\\.|[^'])*')/g],
        keyword: [/\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|JOIN|LEFT|RIGHT|INNER|OUTER|ON|ORDER|GROUP|BY|HAVING|LIMIT|OFFSET|CREATE|ALTER|DROP|TABLE|INTO|VALUES|SET|AND|OR|NOT|NULL|AS|DISTINCT)\b/gi],
        number: [/\b\d+(?:\.\d+)?\b/g]
      },
      plaintext: {
        comment: [],
        string: [],
        keyword: [],
        number: []
      }
    };

    return grammars[ normalizeLanguage(language) ] || grammars.plaintext;
  }

  function stashPattern(source, expression, className, tokens) {
    return source.replace(expression, function (match, prefix, inner, suffix) {
      if (typeof inner === 'string' && typeof suffix === 'undefined' && typeof prefix === 'string' && match !== prefix) {
        var tokenIndex = tokens.length;
        tokens.push({ className: className, value: escapeHtml(inner) });
        return prefix + '___OFGCODEEDITOR_TOKEN_' + tokenIndex + '___';
      }

      var tokenValue = match;
      if (typeof prefix === 'string' && typeof inner === 'string' && typeof suffix === 'string') {
        tokenValue = inner;
      }
      var markerIndex = tokens.length;
      tokens.push({ className: className, value: escapeHtml(tokenValue) });
      return '___OFGCODEEDITOR_TOKEN_' + markerIndex + '___';
    });
  }

  function restoreTokens(source, tokens) {
    return source.replace(/___OFGCODEEDITOR_TOKEN_(\d+)___/g, function (_, index) {
      var token = tokens[Number(index)];
      return token ? '<span class="ofgcodeeditor-code-token ofgcodeeditor-code-token--' + token.className + '">' + token.value + '</span>' : _;
    });
  }

  function stashInlinePattern(source, expression, className, tokens) {
    return source.replace(expression, function () {
      var args = Array.prototype.slice.call(arguments);
      var match = args[0];
      var prefix = '';
      var value = match;

      if (args.length > 3 && typeof args[1] === 'string' && typeof args[2] === 'string') {
        prefix = args[1];
        value = args[2];
      }

      var tokenIndex = tokens.length;
      tokens.push({ className: className, value: value });

      return prefix + '___OFGCODEEDITOR_TOKEN_' + tokenIndex + '___';
    });
  }

  function highlightCode(code, language) {
    var grammar = getGrammar(language);
    var tokens = [];
    var highlighted = code;

    grammar.comment.forEach(function (pattern) {
      highlighted = stashPattern(highlighted, pattern, 'comment', tokens);
    });

    grammar.string.forEach(function (pattern) {
      highlighted = stashPattern(highlighted, pattern, 'string', tokens);
    });

    highlighted = escapeHtml(highlighted);

    grammar.keyword.forEach(function (pattern) {
      highlighted = stashInlinePattern(highlighted, pattern, 'keyword', tokens);
    });

    grammar.number.forEach(function (pattern) {
      highlighted = stashInlinePattern(highlighted, pattern, 'number', tokens);
    });

    highlighted = stashInlinePattern(highlighted, /(=&gt;|=&amp;gt;|===|!==|==|!=|=&lt;|&lt;=|&gt;=|[{}()[\],.:+\-*\/%=<>])/g, 'operator', tokens);

    return restoreTokens(highlighted, tokens);
  }

  function readCode(block) {
    var source = block.querySelector('.ofgcodeeditor-code-block__code-source');

    if (source) {
      return source.textContent || '';
    }

    var lines = block.querySelectorAll('.ofgcodeeditor-code-block__line-text');

    return Array.prototype.map.call(lines, function (line) {
      var value = line.textContent || '';
      return value === '\u00a0' ? '' : value;
    }).join('\n');
  }

  function renderHighlightedCode(block) {
    var language = normalizeLanguage(block.getAttribute('data-language'));
    var code = readCode(block);
    var lines = block.querySelectorAll('.ofgcodeeditor-code-block__line-text');
    var highlightedLines = highlightCode(code, language).split('\n');

    Array.prototype.forEach.call(lines, function (line, index) {
      line.innerHTML = highlightedLines[index] || '&nbsp;';
    });
  }

  function markCopied(button) {
    var copiedLabel = button.getAttribute('data-copied-label') || 'Copied';
    var defaultLabel = button.getAttribute('data-copy-label') || button.textContent;

    button.textContent = copiedLabel;
    button.classList.add('is-copied');

    window.setTimeout(function () {
      button.textContent = defaultLabel;
      button.classList.remove('is-copied');
    }, 1800);
  }

  function fallbackCopy(text, button) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    markCopied(button);
  }

  function initHighlights() {
    var blocks = document.querySelectorAll('.ofgcodeeditor-code-block');

    Array.prototype.forEach.call(blocks, function (block) {
      renderHighlightedCode(block);
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.ofgcodeeditor-code-block__copy');

    if (!button) {
      return;
    }

    event.preventDefault();

    var block = button.closest('.ofgcodeeditor-code-block');
    if (!block) {
      return;
    }

    var text = readCode(block);

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        markCopied(button);
      }).catch(function () {
        fallbackCopy(text, button);
      });
      return;
    }

    fallbackCopy(text, button);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHighlights);
  } else {
    initHighlights();
  }
}());
