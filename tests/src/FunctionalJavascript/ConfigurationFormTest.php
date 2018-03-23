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
   * Tests creating a payment gateway.
   */
  public function testCreateGateway() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');
    $radio_button = $this->getSession()->getPage()->findField('Authorize.net');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $values = [
      'id' => 'authorize_net_us',
      'label' => 'Authorize.net US',
      'plugin' => 'authorizenet_acceptjs',
      'configuration[authorizenet][api_login]' => '5KP3u95bQpv',
      'configuration[authorizenet][transaction_key]' => '346HZ32z3fP4hTG2',
      'configuration[authorizenet][client_key]' => 'test-client-key',
      'configuration[authorizenet][mode]' => 'test',
      'status' => 1,
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('Saved the Authorize.net US payment gateway.');
    $payment_gateway = PaymentGateway::load('authorize_net_us');
    $this->assertEquals('authorize_net_us', $payment_gateway->id());
    $this->assertEquals('Authorize.net US', $payment_gateway->label());
    $this->assertEquals('authorizenet_acceptjs', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $config = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('5KP3u95bQpv', $config['api_login']);
    $this->assertEquals('346HZ32z3fP4hTG2', $config['transaction_key']);
    $this->assertEquals('test-client-key', $config['client_key']);
  }

}
