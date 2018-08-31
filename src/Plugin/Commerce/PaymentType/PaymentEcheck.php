<?php

namespace Drupal\commerce_authnet\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_echeck",
 *   label = @Translation("eCheck"),
 *   workflow = "payment_manual",
 * )
 */
class PaymentEcheck extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
