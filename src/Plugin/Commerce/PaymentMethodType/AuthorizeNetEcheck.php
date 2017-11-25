<?php

namespace Drupal\commerce_authnet\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the Authorize.net eCheck payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "authnet_echeck",
 *   label = @Translation("eCheck"),
 *   create_label = @Translation("New Authorize.net eCheck"),
 * )
 */
class AuthorizeNetEcheck extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return $this->t('Authorize.net eCheck');
  }

  /**
   * The account types.
   */
  public static function getAccountTypes() {
    return [
      'checking' => 'checking',
      'saving' => 'saving',
      'business_checking' => 'business checking',
    ];
  }

}
