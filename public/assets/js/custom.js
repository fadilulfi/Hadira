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
 * Navigation polish for the server-rendered HADIRA pages.
 * Keep regular link navigation (and its auth/CSRF/redirect behavior), while
 * showing a small loading indicator and preserving sidebar scroll position.
 */
(function () {
  'use strict';

  var sidebarScrollKey = 'hadira:sidebar-scroll-top';
  var scrollSaveTimer = null;

  function readSidebarScroll() {
    try {
      return window.sessionStorage.getItem(sidebarScrollKey);
    } catch (error) {
      return null;
    }
  }

  function saveSidebarScroll() {
    var wrapper = document.querySelector('.sidebar .sidebar-wrapper');
    if (!wrapper) {
      return;
    }

    try {
      window.sessionStorage.setItem(sidebarScrollKey, String(wrapper.scrollTop || 0));
    } catch (error) {
      // Navigation remains usable if storage is unavailable.
    }
  }

  function restoreSidebarScroll() {
    var saved = readSidebarScroll();
    var scrollTop = saved === null ? NaN : parseInt(saved, 10);
    if (!Number.isFinite(scrollTop) || scrollTop < 0) {
      return;
    }

    function applySavedPosition() {
      var wrapper = document.querySelector('.sidebar .sidebar-wrapper');
      if (wrapper) {
        wrapper.scrollTop = scrollTop;
      }
    }

    window.requestAnimationFrame(applySavedPosition);
    window.setTimeout(applySavedPosition, 100);
  }

  function createProgressIndicator() {
    var progress = document.getElementById('hadira-navigation-progress');
    if (progress) {
      return progress;
    }

    progress = document.createElement('div');
    progress.id = 'hadira-navigation-progress';
    progress.setAttribute('aria-hidden', 'true');

    var bar = document.createElement('span');
    bar.className = 'hadira-navigation-progress__bar';
    progress.appendChild(bar);
    document.body.appendChild(progress);

    return progress;
  }

  function shouldShowProgress(event, link) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return false;
    }

    if (link.hasAttribute('download') || (link.target && link.target.toLowerCase() !== '_self')) {
      return false;
    }

    var href = link.getAttribute('href');
    if (!href || /^\s*javascript:/i.test(href)) {
      return false;
    }

    var destination;
    try {
      destination = new URL(link.href, window.location.href);
    } catch (error) {
      return false;
    }

    if (destination.origin !== window.location.origin || !/^https?:$/.test(destination.protocol)) {
      return false;
    }

    return destination.href !== window.location.href;
  }

  function initNavigationPolish() {
    var wrapper = document.querySelector('.sidebar .sidebar-wrapper');
    var progress = createProgressIndicator();

    if (wrapper) {
      wrapper.addEventListener('scroll', function () {
        window.clearTimeout(scrollSaveTimer);
        scrollSaveTimer = window.setTimeout(saveSidebarScroll, 100);
      }, { passive: true });

      restoreSidebarScroll();
    }

    document.addEventListener('click', function (event) {
      var link = event.target.closest('.sidebar a[href]');
      if (!link || !shouldShowProgress(event, link)) {
        return;
      }

      saveSidebarScroll();
      progress.classList.add('is-visible');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavigationPolish, { once: true });
  } else {
    initNavigationPolish();
  }
})();

