/**
 * @file
 * Contains the definition of the behaviour jsPrintLink.
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.jsPrintLink = {
        attach: function (context, settings) {
            $('#print-current-page').click(function(){
                window.print().stopPropagation();
            });
        }
    };
})(jQuery, Drupal, drupalSettings);