/**
 * Qudra AccessKit WP — Admin JS
 * Handles: color pickers with live preview, corner selector, language tabs, pill toggles.
 */
(function ($) {
  'use strict';

  $(function () {

    // ── Color Pickers ───────────────────────────────────────────────────────
    $('.qak-color-picker').wpColorPicker({
      change: function (event, ui) {
        var target = $(this).data('target');
        var color  = ui.color.toString();
        updatePreview(target, color);
      },
      clear: function () {
        // On clear, reset to default
        updatePreview($(this).data('target'), '');
      }
    });

    function updatePreview(target, color) {
      var previewBtn  = $('#qak-preview-btn');
      var previewSvg  = previewBtn.find('svg');

      if (target === 'bg') {
        previewBtn.css('background', color || '#1E6264');
        // Also update panel header in preview if present
      } else if (target === 'icon') {
        previewSvg.css('stroke', color || '#ffffff');
        previewSvg.find('circle:last-child').css('fill', color || '#ffffff');
      }
    }

    // ── Pill Group (size selector) ──────────────────────────────────────────
    $(document).on('change', '.qak-pill input[type="radio"]', function () {
      var group = $(this).closest('.qak-pill-group');
      group.find('.qak-pill').removeClass('qak-pill--active');
      $(this).closest('.qak-pill').addClass('qak-pill--active');

      // Update preview button size
      var sizes = { small: '48px', medium: '56px', large: '64px' };
      var sz = sizes[$(this).val()] || '56px';
      $('#qak-preview-btn').css({ width: sz, height: sz });
    });

    // ── Corner Selector ─────────────────────────────────────────────────────
    $(document).on('click', '.qak-corner', function () {
      var pos = $(this).data('pos');
      $('.qak-corner').removeClass('qak-corner--active');
      $(this).addClass('qak-corner--active');
      $(this).find('input[type="radio"]').prop('checked', true);

      // Update label
      var label = pos.replace(/-/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
      $('#qak-pos-label').text(label);
    });

    // ── Language Tabs ───────────────────────────────────────────────────────
    $(document).on('click', '.qak-lang-tab', function () {
      var lang = $(this).data('lang');
      $('.qak-lang-tab').removeClass('qak-lang-tab--active');
      $(this).addClass('qak-lang-tab--active');
      $('.qak-lang-panel').removeClass('qak-lang-panel--active');
      $('.qak-lang-panel[data-lang="' + lang + '"]').addClass('qak-lang-panel--active');
    });

    // ── Toggle switch rows (admin) ──────────────────────────────────────────
    // The hidden input trick ensures unchecked = "0" is submitted.
    // Pairs: hidden "0" before each checkbox.
    // WP naturally handles this — we just need to ensure the visual is correct on load.
    $(document).on('change', '.qak-toggle-switch input[type="checkbox"]', function () {
      // Nothing extra needed; CSS :checked handles the visual.
    });

    // ── Visibility mode toggle ──────────────────────────────────────────────
    $(document).on('change', 'input[name="visibility_mode"]', function () {
      if ($(this).val() === 'selected') {
        $('#qak-page-picker').removeAttr('hidden');
      } else {
        $('#qak-page-picker').attr('hidden', true);
      }
    });

    // ── Page list search ────────────────────────────────────────────────────
    $(document).on('input', '#qak-page-search', function () {
      var q = $(this).val().toLowerCase();
      $('#qak-page-list .qak-page-item').each(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
      });
    });

    // ── Select all / Clear all ──────────────────────────────────────────────
    $(document).on('click', '#qak-select-all', function () {
      $('#qak-page-list .qak-page-item:visible input[type="checkbox"]').prop('checked', true);
    });
    $(document).on('click', '#qak-select-none', function () {
      $('#qak-page-list .qak-page-item:visible input[type="checkbox"]').prop('checked', false);
    });

    // ── Save confirmation ───────────────────────────────────────────────────
    var saveBtn = $('.qak-btn-save');
    $('form.qak-admin-form').on('submit', function () {
      saveBtn.text('Saving…').prop('disabled', true);
    });

  });

}(jQuery));
