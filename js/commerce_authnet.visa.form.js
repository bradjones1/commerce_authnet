/**
 * @file
 * Javascript handle for Authorize.net Visa Checkout.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Authorize.net Visa Checkout.
   */
  Drupal.commerceAuthorizeNetVisaForm = function($form, settings) {
    window.onVisaCheckoutReady = function (){
      V.init({
        apikey: settings.visaApiKey,
        paymentRequest: {
          currencyCode: settings.currencyCode,
          total: settings.number
        }
      });
      
      V.on("payment.success", function(payment) {
        $.post(settings.successUrl, {payment: payment}).done(function(data) {
          window.location.href = settings.nextCheckoutStepUrl;
        });
      });
      V.on("payment.error", function(payment, error) {
        $.post(settings.errorUrl, {payment: payment, error: error});
      });
      V.on("payment.cancel", function(payment) {
        $.post(settings.cancelUrl, {payment: payment});
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
