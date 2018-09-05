<?php

namespace Drupal\Tests\commerce_authnet\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the Authorize.net payment configuration form.
 *
 * @group commerce_authnet
 */
class ConfigurationFormTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_authnet',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_payment_gateway',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating an Accept.JS payment gateway.
   */
  public function testCreateAcceptJSGateway() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');
    $this->saveHtmlOutput();
    $radio_button = $this->getSession()->getPage()->findField('Authorize.net (Accept.js)');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $values = [
      'id' => 'authorizenet_acceptjs',
      'label' => 'Authorize.net AcceptJS',
      'plugin' => 'authorizenet_acceptjs',
      'configuration[authorizenet_acceptjs][api_login]' => '64EZ77a2w8',
      'configuration[authorizenet_acceptjs][transaction_key]' => '2rrbVvBR6949En2d',
      'configuration[authorizenet_acceptjs][client_key]' => '2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6',
      'configuration[authorizenet_acceptjs][mode]' => 'test',
      'status' => 1,
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('Saved the Authorize.net AcceptJS payment gateway.');
    $payment_gateway = PaymentGateway::load('authorizenet_acceptjs');
    $this->assertEquals('authorizenet_acceptjs', $payment_gateway->id());
    $this->assertEquals('Authorize.net AcceptJS', $payment_gateway->label());
    $this->assertEquals('authorizenet_acceptjs', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $config = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('64EZ77a2w8', $config['api_login']);
    $this->assertEquals('2rrbVvBR6949En2d', $config['transaction_key']);
    $this->assertEquals('2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6', $config['client_key']);
  }

  /**
   * Tests creating an ECheck payment gateway.
   */
  public function testCreateECheckGateway() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');
    $this->saveHtmlOutput();
    $radio_button = $this->getSession()->getPage()->findField('Authorize.net (Echeck)');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $values = [
      'id' => 'authorizenet_echeck',
      'label' => 'Authorize.net Echeck',
      'plugin' => 'authorizenet_echeck',
      'configuration[authorizenet_echeck][api_login]' => '64EZ77a2w8',
      'configuration[authorizenet_echeck][transaction_key]' => '2rrbVvBR6949En2d',
      'configuration[authorizenet_echeck][client_key]' => '2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6',
      'configuration[authorizenet_echeck][mode]' => 'test',
      'status' => 1,
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('Saved the Authorize.net Echeck payment gateway.');
    $payment_gateway = PaymentGateway::load('authorizenet_echeck');
    $this->assertEquals('authorizenet_echeck', $payment_gateway->id());
    $this->assertEquals('Authorize.net Echeck', $payment_gateway->label());
    $this->assertEquals('authorizenet_echeck', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $config = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('64EZ77a2w8', $config['api_login']);
    $this->assertEquals('2rrbVvBR6949En2d', $config['transaction_key']);
    $this->assertEquals('2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6', $config['client_key']);
  }

  /**
   * Tests creating a Visa Checkout payment gateway.
   */
  public function testCreateVisaCheckoutGateway() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');
    $this->saveHtmlOutput();
    $radio_button = $this->getSession()->getPage()->findField('Authorize.net (Visa Checkout)');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $values = [
      'id' => 'authorizenet_visa_checkout',
      'label' => 'Authorize.net (Visa Checkout)',
      'plugin' => 'authorizenet_visa_checkout',
      'configuration[authorizenet_visa_checkout][api_login]' => '64EZ77a2w8',
      'configuration[authorizenet_visa_checkout][transaction_key]' => '2rrbVvBR6949En2d',
      'configuration[authorizenet_visa_checkout][client_key]' => '2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6',
      'configuration[authorizenet_visa_checkout][visa_checkout_api_key]' => 'I3IW0JTMU1JOIY90KBT721-L5zdL2VQ7GQ6tPA-xvjmOLO-Xo',
      'configuration[authorizenet_visa_checkout][mode]' => 'test',
      'status' => 1,
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('Saved the Authorize.net (Visa Checkout) payment gateway.');
    $payment_gateway = PaymentGateway::load('authorizenet_visa_checkout');
    $this->assertEquals('authorizenet_visa_checkout', $payment_gateway->id());
    $this->assertEquals('Authorize.net Visa Checkout', $payment_gateway->label());
    $this->assertEquals('authorizenet_visa_checkout', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $config = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('64EZ77a2w8', $config['api_login']);
    $this->assertEquals('2rrbVvBR6949En2d', $config['transaction_key']);
    $this->assertEquals('2fejMFQEzA2cg6C5wV3Kz398S94XkPbS56RU2Zq2tfjcmDhDVp8h8XmZ49JQLbY6', $config['client_key']);
  }
}
