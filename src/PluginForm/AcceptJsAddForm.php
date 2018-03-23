<?php

namespace Drupal\commerce_authnet\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_authnet\Plugin\Commerce\PaymentMethodType\AuthorizeNetEcheck;

class AcceptJsAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Alter the form with AuthorizeNet Accept JS specific needs.
    $element['#attributes']['class'][] = 'authorize-net-accept-js-form';
    /** @var \Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\AuthorizeNetBase $plugin */
    $plugin = $this->plugin;

    if ($plugin->getMode() == 'test') {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-sandbox';
    }
    else {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-production';
    }
    $element['#attached']['library'][] = 'commerce_authnet/form-accept';
    $element['#attached']['drupalSettings']['commerceAuthorizeNet'] = [
      'clientKey' => $plugin->getConfiguration()['client_key'],
      'apiLoginID' => $plugin->getConfiguration()['api_login'],
      'paymentMethodType' => 'credit_card',
    ];

    // Fields placeholder to be built by the JS.
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#attributes' => [
        'placeholder' => '•••• •••• •••• ••••',
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'none',
        'id' => 'credit-card-number',
        'required' => 'required',
      ],
      '#label_attributes' => [
        'class' => [
          'js-form-required',
          'form-required',
        ],
      ],
      '#maxlength' => 20,
      '#size' => 20,
    ];

    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'textfield',
      '#title' => t('Month'),
      '#attributes' => [
        'placeholder' => 'MM',
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'none',
        'id' => 'expiration-month',
        'required' => 'required',
      ],
      '#label_attributes' => [
        'class' => [
          'js-form-required',
          'form-required',
        ],
      ],
      '#maxlength' => 2,
      '#size' => 3,
    ];

    $element['expiration']['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $element['expiration']['year'] = [
      '#type' => 'textfield',
      '#title' => t('Year'),
      '#attributes' => [
        'placeholder' => 'YY',
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'none',
        'id' => 'expiration-year',
        'required' => 'required',
      ],
      '#label_attributes' => [
        'class' => [
          'js-form-required',
          'form-required',
        ],
      ],
      '#maxlength' => 2,
      '#size' => 3,
    ];

    $element['security_code'] = [
      '#type' => 'textfield',
      '#title' => t('CVV'),
      '#attributes' => [
        'placeholder' => '•••',
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'none',
        'id' => 'cvv',
        'required' => 'required',
      ],
      '#label_attributes' => [
        'class' => [
          'js-form-required',
          'form-required',
        ],
      ],
      '#maxlength' => 4,
      '#size' => 4,
    ];

    // To display validation errors.
    $element['payment_errors'] = [
      '#type' => 'markup',
      '#markup' => '<div id="payment-errors"></div>',
      '#weight' => -200,
    ];

    // Populated by the JS library after receiving a response from AuthorizeNet.
    $element['data_descriptor'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['accept-js-data-descriptor'],
      ],
    ];
    $element['data_value'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['accept-js-data-value'],
      ],
    ];
    $element['last4'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['accept-js-data-last4'],
      ],
    ];
    $element['expiration_month'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['accept-js-data-month'],
      ],
    ];
    $element['expiration_year'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['accept-js-data-year'],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
    $values = $form_state->getValues();
    if (!empty($values['contact_information']['email'])) {
      // then we are dealing with anonymous user. Adding a customer email.
      $payment_details = $values['payment_information']['add_payment_method']['payment_details'];
      $payment_details['customer_email'] = $values['contact_information']['email'];
      $form_state->setValue(['payment_information', 'add_payment_method', 'payment_details'], $payment_details);
    }
  }

}
