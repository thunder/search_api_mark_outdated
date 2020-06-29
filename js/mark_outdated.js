/**
 * @file mark_outdated.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Visually mark outdated rows and disable vbo checkbox
   *
   * @type {Object}
   */
  Drupal.behaviors.searchApiMarkOutdatedContent = {
    attach: function (context, settings) {
      var outdated = settings.searchApiOutdatedContent || [];

      if (outdated.length) {
        $('[data-thunder-search-api-id]', context)
          .filter(function () {
            return outdated.indexOf($(this).data('thunder-search-api-id')) !== -1;
          })
          .closest('tr').css('background-color', '#fff4f4')
          .find('.views-field-views-bulk-operations-bulk-form input[type="checkbox"]')
          .prop('disabled', true);
      }
    }
  };


}(jQuery, Drupal));
