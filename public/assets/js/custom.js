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
 * Persist sidebar UI across Turbo visits, but leave pages with page-specific
 * JavaScript to normal browser navigation. This prevents stale timers,
 * duplicate handlers and global lexical declarations from leaking between
 * dynamically visited pages.
 */
(function () {
  'use strict';

  function normalizedPath(link) {
    try {
      var url = new URL(link.href, window.location.href);
      return url.origin + url.pathname.replace(/\/+$/, '') + (url.search || '');
    } catch (error) {
      return '';
    }
  }

  function rememberDestination(event) {
    var url = event && event.detail && event.detail.url;
    if (url) {
      window.__hadiraTurboDestination = url.href || String(url);
    }
  }

  function syncSidebar(incomingBody) {
    var currentSidebar = document.getElementById('hadira-sidebar');
    var nextSidebar = incomingBody.querySelector('#hadira-sidebar');
    if (!currentSidebar || !nextSidebar) {
      return;
    }

    ['data-turbo', 'data-color', 'data-image'].forEach(function (attribute) {
      var value = nextSidebar.getAttribute(attribute);
      if (value === null) {
        currentSidebar.removeAttribute(attribute);
      } else {
        currentSidebar.setAttribute(attribute, value);
      }
    });

    var nextLinks = Array.from(nextSidebar.querySelectorAll('a[href]'));
    currentSidebar.querySelectorAll('a[href]').forEach(function (link) {
      var path = normalizedPath(link);
      var matching = nextLinks.find(function (nextLink) {
        return normalizedPath(nextLink) === path;
      });
      var oldItem = link.closest('li.nav-item');
      var nextItem = matching && matching.closest('li.nav-item');
      var isActive = !!(nextItem && nextItem.classList.contains('active'));

      if (oldItem) {
        oldItem.classList.toggle('active', isActive);
      }
      if (matching && matching.hasAttribute('aria-current')) {
        link.setAttribute('aria-current', matching.getAttribute('aria-current'));
      } else {
        link.removeAttribute('aria-current');
      }
    });
  }

  document.addEventListener('turbo:before-visit', rememberDestination);
  document.addEventListener('turbo:visit', rememberDestination);

  document.addEventListener('turbo:before-render', function (event) {
    var incomingBody = event.detail && event.detail.newBody;
    if (!incomingBody) {
      return;
    }

    // JS-heavy views use full navigation so their existing scripts keep their
    // normal one-page lifecycle rather than being executed in a long-lived JS realm.
    if (incomingBody.getAttribute('data-hadira-page-scripted') === 'true') {
      event.preventDefault();
      var destination = window.__hadiraTurboDestination || window.location.href;
      window.location.assign(destination);
      return;
    }

    syncSidebar(incomingBody);

    var defaultRender = event.detail.render;
    if (typeof defaultRender === 'function') {
      event.detail.render = function (currentBody, newBody) {
        var reducedMotion = window.matchMedia &&
          window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!reducedMotion && typeof document.startViewTransition === 'function') {
          var transition = document.startViewTransition(function () {
            return defaultRender(currentBody, newBody);
          });
          return transition.updateCallbackDone;
        }

        return defaultRender(currentBody, newBody);
      };
    }
  });

  document.addEventListener('turbo:load', function () {
    window.__hadiraTurboDestination = null;

    // Refresh components bound to the newly rendered content without touching
    // the persistent sidebar or changing form submission behavior.
    if (window.jQuery && window.jQuery.fn.perfectScrollbar) {
      var mainPanel = document.querySelector('.main-panel');
      if (mainPanel) {
        try {
          window.jQuery(mainPanel).perfectScrollbar('update');
          mainPanel.scrollTop = 0;
        } catch (error) {
          // No scrollbar instance on this browser/layout; native scrolling works.
        }
      }
    }
  });
})();
