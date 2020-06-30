/**
 * @file mark_outdated.js
 */

(function (Drupal) {

  'use strict';

  /**
   * Visually mark outdated rows and disable vbo checkbox
   *
   * @type {Object}
   */
  Drupal.behaviors.searchApiMarkOutdatedAddClass = {
    attach: function (context, settings) {
      document.querySelectorAll('div[data-is-outdated="1"]').forEach(function(item) {
        item.closest('tr').classList.add('search-api-outdated');
      });
    }
  };


}(Drupal));
