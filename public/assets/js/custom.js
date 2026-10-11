function setAjaxData(object = null) {
  var data = {};
  data[BaseConfig.csrfTokenName] = $('meta[name="X-CSRF-TOKEN"]').attr('content');
  if (object != null) {
    Object.assign(data, object);
  }
  return data;
}

function setSerializedData(serializedData) {
  serializedData.push({ name: BaseConfig.csrfTokenName, value: $('meta[name="X-CSRF-TOKEN"]').attr('content') });
  return serializedData;
}

//delete item
function deleteItem(url, id, message) {
  swal({
    text: message,
    icon: "warning",
    buttons: [BaseConfig.textCancel, BaseConfig.textOk],
    dangerMode: true,
  }).then(function (willDelete) {
    if (willDelete) {
      var data = {
        'id': id,
      };
      $.ajax({
        type: 'POST',
        url: BaseConfig.baseURL + url,
        data: setAjaxData(data),
        success: function (response) {
          location.reload();
        },
        error: function (xhr, status, thrown) {
          console.log(xhr);
          console.log(status);
          console.log(thrown);
        },
      });
    }
  });
};

function fetchKelasJurusanData(type, target) {
  const url = type === 'kelas' ? BaseConfig.baseURL + 'admin/kelas/list-data' : BaseConfig.baseURL + 'admin/jurusan/list-data';
  const textProcessing = type === 'kelas' ? 'Daftar kelas muncul disini' : 'Daftar Jurusan muncul disini';

  $(target).html('<div id="loadingSpinner" class="spinner"></div><p class="text-center mt-3">' + textProcessing + '</p>');

  $.ajax({
    url: url,
    type: 'post',
    data: setAjaxData({}),
    success: function (response) {
      if (response.result === 1) {
        $(target).html(response.htmlContent);
      } else {
        $(target).html('<p class="text-center mt-3">Data tidak ditemukan</p>');
      }
    },
    error: function (xhr, status, thrown) {
      $(target).html('<p class="text-center mt-3">' + thrown + '</p>');
    },
    complete: function () {
      $('#loadingSpinner').hide();
    }
  });
}

//delete selected posts
function deleteSelectedSiswa(message) {
  swal({
      text: message,
      icon: "warning",
      buttons: [BaseConfig.textCancel, BaseConfig.textOk],
      dangerMode: true,
  }).then(function (willDelete) {
      if (willDelete) {
          var siswaIds = [];
          $("input[name='checkbox-table']:checked").each(function () {
              siswaIds.push(this.value);
          });
          var data = {
              'siswa_ids': siswaIds,
          };
          $.ajax({
              type: 'POST',
              url: BaseConfig.baseURL + '/admin/siswa/deleteSelectedSiswa',
              data: setAjaxData(data),
              success: function (response) {
                  location.reload();
              }
          });
      }
  });
};

$(document).on('click', '#checkAll', function () {
  $('input:checkbox').not(this).prop('checked', this.checked);
});

$(document).on('click', '.checkbox-table', function () {
  if ($(".checkbox-table").is(':checked')) {
    $(".btn-table-delete").show();
  } else {
    $(".btn-table-delete").hide();
  }
});

/**
 * Preserve sidebar scroll position when a page with custom scripts needs a
 * normal browser navigation. AJAX visits keep the existing sidebar mounted,
 * so this is mainly for full-page fallbacks and manual refreshes.
 */
(function preserveSidebarScroll() {
  'use strict';
  var storageKey = 'hadira:sidebar-scroll-top';
  var saveTimer = null;

  function getWrapper() {
    return document.querySelector('.sidebar .sidebar-wrapper');
  }

  function savePosition() {
    var wrapper = getWrapper();
    if (!wrapper) return;
    try {
      window.sessionStorage.setItem(storageKey, String(wrapper.scrollTop || 0));
    } catch (error) {
      // The application remains usable when session storage is unavailable.
    }
  }

  function restorePosition() {
    var saved;
    try {
      saved = window.sessionStorage.getItem(storageKey);
    } catch (error) {
      return;
    }
    var position = saved === null ? NaN : parseInt(saved, 10);
    if (!Number.isFinite(position) || position < 0) return;

    function apply() {
      var wrapper = getWrapper();
      if (wrapper) wrapper.scrollTop = position;
    }
    window.requestAnimationFrame(apply);
    window.setTimeout(apply, 100);
  }

  function init() {
    var wrapper = getWrapper();
    if (wrapper) {
      wrapper.addEventListener('scroll', function () {
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(savePosition, 100);
      }, { passive: true });
      restorePosition();
    }
    window.addEventListener('pagehide', savePosition);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

/**
 * Persistent-shell navigation for HADIRA.
 *
 * Only ordinary same-origin navigation links in the sidebar are enhanced.
 * Page-specific-script views fall back to normal navigation so their scripts
 * aren't re-executed inside a long-lived JavaScript runtime.
 */
(function () {
  'use strict';

  var pageSelector = '#hadira-page-content';
  var scriptsSelector = '#hadira-page-scripts';
  var navNamespace = '.hadiraPage';
  var activePageController = null;
  var pageIntervals = new Set();
  var pageGlobalRestores = [];
  var navigationController = null;
  var navigationSequence = 0;
  var currentPageUrl = window.location.href;

  var progress = document.getElementById('hadira-navigation-progress');
  if (!progress) {
    progress = document.createElement('div');
    progress.id = 'hadira-navigation-progress';
    progress.setAttribute('aria-hidden', 'true');
    var progressBar = document.createElement('span');
    progressBar.className = 'hadira-navigation-progress__bar';
    progress.appendChild(progressBar);
    document.body.appendChild(progress);
  }

  var nativeDocumentAdd = document.addEventListener.bind(document);
  var nativeWindowAdd = window.addEventListener.bind(window);
  document.addEventListener = function (type, listener, options) {
    if (activePageController && typeof AbortController !== 'undefined') {
      options = withPageSignal(options, activePageController.signal);
    }
    return nativeDocumentAdd(type, listener, options);
  };
  window.addEventListener = function (type, listener, options) {
    if (activePageController && typeof AbortController !== 'undefined') {
      options = withPageSignal(options, activePageController.signal);
    }
    return nativeWindowAdd(type, listener, options);
  };

  function withPageSignal(options, signal) {
    if (options && typeof options === 'object') {
      if (options.signal) return options;
      return Object.assign({}, options, { signal: signal });
    }
    return { capture: options === true, signal: signal };
  }

  var nativeSetInterval = window.setInterval.bind(window);
  var nativeClearInterval = window.clearInterval.bind(window);
  window.setInterval = function () {
    var id = nativeSetInterval.apply(window, arguments);
    if (activePageController) pageIntervals.add(id);
    return id;
  };
  window.clearInterval = function (id) {
    pageIntervals.delete(id);
    return nativeClearInterval(id);
  };

  if (window.jQuery) {
    var originalJQueryOn = window.jQuery.fn.on;
    window.jQuery.fn.on = function (types) {
      var isPersistentTarget = false;
      this.each(function () {
        if (this === document || this === window || this === document.body) {
          isPersistentTarget = true;
        }
      });

      if (activePageController && isPersistentTarget && typeof types === 'string') {
        var namespaced = types.split(/\s+/).map(function (type) {
          if (!type || type.indexOf(navNamespace) !== -1) return type;
          return type + navNamespace;
        }).join(' ');
        var args = Array.prototype.slice.call(arguments);
        args[0] = namespaced;
        return originalJQueryOn.apply(this, args);
      }

      return originalJQueryOn.apply(this, arguments);
    };
  }

  function disposePage() {
    if (activePageController) {
      activePageController.abort();
      activePageController = null;
    }

    pageIntervals.forEach(function (id) {
      nativeClearInterval(id);
    });
    pageIntervals.clear();

    // Restore globals exposed by the previous page's inline event handlers.
    while (pageGlobalRestores.length) {
      var restorePageGlobal = pageGlobalRestores.pop();
      try { restorePageGlobal(); } catch (error) {}
    }

    if (window.jQuery) {
      window.jQuery(document).off(navNamespace);
      window.jQuery(window).off(navNamespace);
      window.jQuery(document.body).off(navNamespace);
    }
  }

  function beginPage() {
    disposePage();
    if (typeof AbortController !== 'undefined') {
      activePageController = new AbortController();
    }
  }

  function markCurrentPageStyles() {
    Array.prototype.forEach.call(
      document.head.querySelectorAll('style, link[rel~="stylesheet"]'),
      function (node) {
        if (!node.hasAttribute('data-hadira-global-style')) {
          node.setAttribute('data-hadira-page-style', 'true');
        }
      }
    );
  }

  function setLoading(isLoading) {
    var page = document.querySelector(pageSelector);
    if (page) {
      page.classList.toggle('is-loading', isLoading);
      page.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }
    progress.classList.toggle('is-visible', isLoading);
  }

  function isPlainNavigationClick(event, link) {
    if (event.defaultPrevented || event.button !== 0 ||
        event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.hasAttribute('download') || (link.target && link.target.toLowerCase() !== '_self')) return false;

    var href = link.getAttribute('href');
    if (!href || /^\s*javascript:/i.test(href) || href.charAt(0) === '#') return false;

    var url;
    try {
      url = new URL(link.href, window.location.href);
    } catch (error) {
      return false;
    }

    if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return false;
    if (url.pathname.replace(/\/+$/, '') === window.location.pathname.replace(/\/+$/, '') &&
        url.search === window.location.search && url.hash === window.location.hash) return false;
    if (/\/logout\/?$|\/(download|print)(\/|$)|\/backup\/(db|photos)\/backup(\/|$)/i.test(url.pathname)) return false;
    return url;
  }

  function copyPageStyles(nextDocument, targetUrl, sequence) {
    var newNodes = Array.prototype.slice.call(
      nextDocument.head.querySelectorAll('style, link[rel~="stylesheet"]')
    ).filter(function (node) {
      return !node.hasAttribute('data-hadira-global-style');
    });

    var pending = [];
    newNodes.forEach(function (source) {
      var clone = source.cloneNode(true);
      clone.removeAttribute('data-hadira-page-style');
      clone.setAttribute('data-hadira-pending-page-style', String(sequence));

      if (clone.tagName === 'LINK') {
        var rawHref = source.getAttribute('href');
        if (rawHref) clone.href = new URL(rawHref, targetUrl).href;
        pending.push(new Promise(function (resolve) {
          var finished = false;
          var finish = function () {
            if (finished) return;
            finished = true;
            resolve();
          };
          clone.addEventListener('load', finish, { once: true });
          clone.addEventListener('error', finish, { once: true });
          window.setTimeout(finish, 1800);
        }));
      }

      document.head.appendChild(clone);
    });

    return Promise.all(pending).then(function () {
      var pendingSelector = '[data-hadira-pending-page-style="' + String(sequence) + '"]';
      if (sequence !== navigationSequence) {
        Array.prototype.forEach.call(document.head.querySelectorAll(pendingSelector), function (node) {
          node.remove();
        });
        return;
      }

      Array.prototype.forEach.call(
        document.head.querySelectorAll('[data-hadira-page-style]'),
        function (node) { node.remove(); }
      );
      Array.prototype.forEach.call(
        document.head.querySelectorAll(pendingSelector),
        function (node) {
          node.removeAttribute('data-hadira-pending-page-style');
          node.setAttribute('data-hadira-page-style', 'true');
        }
      );
    });
  }

  function copyCsrfMeta(nextDocument) {
    var current = document.querySelector('meta[name="X-CSRF-TOKEN"]');
    var next = nextDocument.querySelector('meta[name="X-CSRF-TOKEN"]');
    if (current && next) current.setAttribute('content', next.getAttribute('content') || '');
  }

  function updateActiveNavigation(context, currentUrl) {
    Array.prototype.forEach.call(
      document.querySelectorAll('.sidebar a[data-hadira-nav][data-nav-context]'),
      function (link) {
        var linkContext = link.getAttribute('data-nav-context');
        var active = linkContext === context ||
          (linkContext === 'teacher-absensi-kelas' && context === 'teacher-absensi-kelas-form');

        if (!context && currentUrl) {
          try {
            var target = new URL(link.href, window.location.href);
            var current = new URL(currentUrl, window.location.href);
            active = target.pathname.replace(/\/+$/, '') === current.pathname.replace(/\/+$/, '') &&
              target.search === current.search;
          } catch (error) {
            active = false;
          }
        }

        var item = link.closest('.nav-item');
        if (item) item.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
      }
    );
  }

  function copyAttributes(source, destination) {
    Array.prototype.forEach.call(source.attributes, function (attribute) {
      destination.setAttribute(attribute.name, attribute.value);
    });
  }

  function scriptNeedsFullLoad(scripts) {
    return scripts.some(function (script) {
      var body = script.textContent || '';
      return /\bDOMContentLoaded\b/.test(body) ||
        /\b(?:window|self)\s*\.\s*onload\s*=/.test(body) ||
        /\b(?:window|self)\s*\.\s*addEventListener\s*\(\s*['"]load['"]/.test(body);
    });
  }


  function collectInlineHandlerNames(pageRoot) {
    var names = new Set();
    if (!pageRoot) return names;

    var nodes = [pageRoot];
    Array.prototype.push.apply(nodes, pageRoot.querySelectorAll('*'));

    nodes.forEach(function (node) {
      Array.prototype.forEach.call(node.attributes || [], function (attribute) {
        if (!/^on/i.test(attribute.name)) return;

        var callPattern = /(?:^|[^\w$.])([A-Za-z_$][\w$]*)\s*\(/g;
        var match;
        while ((match = callPattern.exec(attribute.value || '')) !== null) {
          names.add(match[1]);
        }
      });
    });

    return names;
  }

  function wrapInlinePageScript(sourceText, handlerNames) {
    var declaredNames = new Set();
    var declarationPattern = /\bfunction\s+([A-Za-z_$][\w$]*)\s*\(|\b(?:var|let|const)\s+([A-Za-z_$][\w$]*)\b/g;
    var match;

    while ((match = declarationPattern.exec(sourceText)) !== null) {
      declaredNames.add(match[1] || match[2]);
    }

    var exports = [];
    handlerNames.forEach(function (name) {
      if (!declaredNames.has(name)) return;
      exports.push(
        'if (typeof ' + name + ' !== "undefined" && window.HadiraNavigation) {' +
        ' window.HadiraNavigation.exposePageFunction(' + JSON.stringify(name) + ', ' + name + '); }'
      );
    });

    return '(function () {\n' + sourceText + '\n' + exports.join('\n') + '\n})();';
  }

  function runOneScript(source, scriptContainer, targetUrl, handlerNames) {
    return new Promise(function (resolve) {
      var script = document.createElement('script');
      copyAttributes(source, script);

      var rawSrc = source.getAttribute('src');
      if (rawSrc) {
        script.src = new URL(rawSrc, targetUrl).href;
        script.async = false;
        script.onload = resolve;
        script.onerror = resolve;
        window.setTimeout(resolve, 5000);
        scriptContainer.appendChild(script);
        return;
      }

      script.textContent = wrapInlinePageScript(source.textContent || '', handlerNames || new Set());
      scriptContainer.appendChild(script);
      resolve();
    });
  }

  async function runPageScripts(sourceScripts, targetUrl, pageRoot) {
    var handlerNames = collectInlineHandlerNames(pageRoot);
    var oldContainer = document.querySelector(scriptsSelector);
    if (oldContainer) oldContainer.remove();

    var newContainer = document.createElement('div');
    newContainer.id = 'hadira-page-scripts';
    newContainer.setAttribute('data-hadira-page-scripts', '');
    newContainer.hidden = true;
    document.body.appendChild(newContainer);

    for (var i = 0; i < sourceScripts.length; i++) {
      await runOneScript(sourceScripts[i], newContainer, targetUrl, handlerNames);
    }
  }

  async function navigate(url, pushHistory) {
    if (navigationController) navigationController.abort();
    navigationController = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var thisSequence = ++navigationSequence;
    var signal = navigationController ? navigationController.signal : undefined;
    var requestedUrl = new URL(url, window.location.href);

    setLoading(true);

    try {
      var response = await fetch(requestedUrl.href, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'text/html', 'X-Hadira-Navigation': '1' },
        signal: signal
      });
      var html = await response.text();
      if (thisSequence !== navigationSequence) return;

      var nextDocument = new DOMParser().parseFromString(html, 'text/html');
      var nextPage = nextDocument.querySelector(pageSelector);
      var nextScriptsContainer = nextDocument.querySelector(scriptsSelector);
      var sourceScripts = [];

      if (nextPage) {
        Array.prototype.forEach.call(nextPage.querySelectorAll('script'), function (script) {
          sourceScripts.push(script.cloneNode(true));
          script.remove();
        });
      }
      if (nextScriptsContainer) {
        Array.prototype.forEach.call(nextScriptsContainer.querySelectorAll('script'), function (script) {
          sourceScripts.push(script.cloneNode(true));
        });
      }

      var destinationRequiresFullNavigation = nextDocument.body &&
        nextDocument.body.getAttribute('data-hadira-full-navigation') === 'true';

      var nextPageBody = nextPage && nextPage.classList.contains('content') ? nextPage : (nextPage && nextPage.querySelector('.content'));

      if (!response.ok || !nextPage || !nextScriptsContainer || !nextPageBody ||
          destinationRequiresFullNavigation || scriptNeedsFullLoad(sourceScripts)) {
        window.location.assign(response.url || requestedUrl.href);
        return;
      }

      var finalUrl = new URL(response.url || requestedUrl.href, window.location.href);
      if (!finalUrl.hash && requestedUrl.hash) finalUrl.hash = requestedUrl.hash;
      if (finalUrl.origin !== window.location.origin) {
        window.location.assign(finalUrl.href);
        return;
      }

      await copyPageStyles(nextDocument, finalUrl.href, thisSequence);
      if (thisSequence !== navigationSequence) return;

      var pageTitle = nextPage.getAttribute('data-page-title') || nextDocument.title || 'HADIRA';
      var pageContext = nextPage.getAttribute('data-page-context') || '';
      var currentPage = document.querySelector(pageSelector);
      if (!currentPage) {
        window.location.assign(finalUrl.href);
        return;
      }

      disposePage();
      var insertedPage = document.importNode(nextPage, true);
      insertedPage.classList.add('is-entering');
      insertedPage.classList.remove('is-loading');
      insertedPage.setAttribute('aria-busy', 'false');
      currentPage.replaceWith(insertedPage);

      document.title = nextDocument.title || pageTitle;
      var navbarTitle = document.getElementById('hadira-page-title');
      if (navbarTitle) navbarTitle.textContent = pageTitle;
      copyCsrfMeta(nextDocument);
      updateActiveNavigation(pageContext, finalUrl.href);

      if (pushHistory) {
        window.history.pushState({ hadiraNavigation: true }, '', finalUrl.href);
      }
      currentPageUrl = finalUrl.href;

      window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
      beginPage();
      await runPageScripts(sourceScripts, finalUrl.href, insertedPage);

      window.setTimeout(function () {
        var page = document.querySelector(pageSelector);
        if (page) page.classList.remove('is-entering');
      }, 320);
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      if (thisSequence === navigationSequence) {
        window.location.assign(requestedUrl.href);
      }
    } finally {
      if (thisSequence === navigationSequence) setLoading(false);
    }
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || typeof target.closest !== 'function') return;
    var link = target.closest('.sidebar a[data-hadira-nav][href]');
    if (!link) return;

    // Don't keep a script-heavy source page alive during partial navigation.
    if (document.body.getAttribute('data-hadira-full-navigation') === 'true') return;

    var navigationUrl = isPlainNavigationClick(event, link);
    if (!navigationUrl) return;

    event.preventDefault();
    navigate(navigationUrl.href, true);
  });

  window.addEventListener('popstate', function () {
    var targetUrl = new URL(window.location.href);
    var currentUrl = new URL(currentPageUrl, window.location.href);
    if (targetUrl.origin === currentUrl.origin &&
        targetUrl.pathname === currentUrl.pathname &&
        targetUrl.search === currentUrl.search) {
      currentPageUrl = targetUrl.href;
      return;
    }
    navigate(targetUrl.href, false);
  });

  function exposePageFunction(name, value) {
    if (!name || typeof value === 'undefined') return;

    var descriptor;
    try {
      descriptor = Object.getOwnPropertyDescriptor(window, name);
      window[name] = value;
      pageGlobalRestores.push(function () {
        try {
          if (window[name] !== value) return;
          if (descriptor) Object.defineProperty(window, name, descriptor);
          else delete window[name];
        } catch (error) {}
      });
    } catch (error) {
      // Keep the page usable if a browser/global property cannot be changed.
    }
  }

  window.HadiraNavigation = {
    beginPage: beginPage,
    exposePageFunction: exposePageFunction
  };

  markCurrentPageStyles();
})();
