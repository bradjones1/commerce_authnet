<?php

namespace Drupal\commerce_authnet\PluginForm\AuthorizeNet;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_authnet\Plugin\Commerce\PaymentMethodType\AuthorizeNetEcheck;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment_method = $this->entity;
    if ($payment_method->bundle() === 'authnet_echeck') {
      $form['payment_details'] = $this->buildEcheckForm($form['payment_details'], $form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Alter the form with AuthorizeNet Accept JS specific needs.
    $element['#attributes']['class'][] = 'authorize-net-accept-js-form';
    /** @var \Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\AuthorizeNetInterface $plugin */
    $plugin = $this->plugin;

    if ($plugin->getMode() == 'test') {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-sandbox';
    }
    else {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-production';
    }
    $element['#attached']['library'][] = 'commerce_authnet/form-accept';
    $element['#attached']['drupalSettings']['commerceAuthorizeNet'] = [
      'clientKey' => $plugin->getClientKey(),
      'apiLoginID' => $plugin->getApiLogin(),
      'paymentMethodType' => 'credit_card',
    ];

    // Fields placeholder to be built by the JS.
    $element['credit_card_number'] = [
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

  /**
   * Builds the eCheck form.
   */
  public function buildEcheckForm(array $element, FormStateInterface $form_state) {
    // Alter the form with AuthorizeNet Accept JS specific needs.
    $element['#attributes']['class'][] = 'authorize-net-accept-js-form';
    /** @var \Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\AuthorizeNetInterface $plugin */
    $plugin = $this->plugin;

    if ($plugin->getMode() == 'test') {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-sandbox';
    }
    else {
      $element['#attached']['library'][] = 'commerce_authnet/accept-js-production';
    }
    $element['#attached']['library'][] = 'commerce_authnet/form-echeck';
    $element['#attached']['drupalSettings']['commerceAuthorizeNet'] = [
      'clientKey' => $plugin->getClientKey(),
      'apiLoginID' => $plugin->getApiLogin(),
      'paymentMethodType' => 'authnet_echeck',
    ];

    $element['routing_number'] = [
      '#type' => 'textfield',
      '#title' => t('Routing number'),
      '#description' => t("The bank's routing number."),
      '#attributes' => [
        'class' => ['authnet-echeck-routing-number'],
      ],
    ];
    $element['account_number'] = [
      '#type' => 'textfield',
      '#title' => t('Bank account'),
      '#description' => t('The bank account number.'),
      '#attributes' => [
        'class' => ['authnet-echeck-account-number'],
      ],
    ];
    $element['name_on_account'] = [
      '#type' => 'textfield',
      '#title' => t('Name on account'),
      '#description' => t('The name of the person who holds the bank account.'),
      '#attributes' => [
        'class' => ['authnet-echeck-name-on-account'],
      ],
    ];
    $element['account_type'] = [
      '#type' => 'select',
      '#title' => t('Account type'),
      '#description' => t('The type of bank account. Currently only WEB eCheck ACH transactions are supported.'),
      '#options' => AuthorizeNetEcheck::getAccountTypes(),
      '#attributes' => [
        'class' => ['authnet-echeck-account-type'],
      ],
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEcheckForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitEcheckForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

}
