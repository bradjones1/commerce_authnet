/**
 * @file
 * Javascript to handle authorize.net forms.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the comerceAuthorizeNetForm behavior.
   */
  Drupal.behaviors.commerceAuthorizeNetForm = {
    attach: function (context) {
      var $form = $('.authorize-net-accept-js-form', context).closest('form').once('authorize-net-accept-js-processed');
      if ($form.length === 0) {
        return;
      }
      var $submit = $form.find('input.button--primary');
      $submit.prop('disabled', false);
      var settings = drupalSettings.commerceAuthorizeNet;
      if (settings.paymentMethodType == 'credit_card') {
        Drupal.commerceAuthorizeNetAcceptForm($form, settings);
      }
      else if (settings.paymentMethodType == 'authnet_echeck') {
        Drupal.commerceAuthorizeNetEcheckForm($form, settings);
      }
    },
    detach: function (context) {
      var $form = $('.authorize-net-accept-js-form').closest('form');
      $form.removeOnce('authorize-net-accept-js-processed');
      $form.off('submit.authnet');
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceAuthorizeNetError: function (message) {
      return $('<div class="messages messages--error"></div>').html(message);
    }
  });

})(jQuery, Drupal, drupalSettings);
