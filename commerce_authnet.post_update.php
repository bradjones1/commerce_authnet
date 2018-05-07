<?php

/**
 * @file
 * Post update functions for commerce_authnet.
 */

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_order\Entity\Order;

/**
 * Separate echeck payment gateways from accept.js and update all affected
 * payment_methods and payments.
 */
function commerce_authnet_post_update_echeck(&$sandbox) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $payment_gateway_storage = $entity_type_manager->getStorage('commerce_payment_gateway');
  $payment_method_storage = $entity_type_manager->getStorage('commerce_payment_method');
  $payment_storage = $entity_type_manager->getStorage('commerce_payment');
  $order_storage = $entity_type_manager->getStorage('commerce_order');

  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['echeck_payment_methods'] = [];
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $gateways */
    $gateways = $payment_gateway_storage->loadMultiple();
    foreach ($gateways as $gateway) {
      if ($gateway->getPluginId() !== 'authorizenet') {
        continue;
      }
      $config = $gateway->getPluginConfiguration();
      unset($config['transaction_type']);
      $original_label = $gateway->label();
      foreach ($config['payment_method_types'] as $payment_method_type) {
        if ($payment_method_type === 'credit_card') {
          // We only rename ids and labels if both credit card and echeck was
          // enabled.
          $gateway->setPluginId('authorizenet_acceptjs');
          // Changing the plugin ID will reset the configuration, put it back.
          $gateway->setPluginConfiguration($config);
          if (count($config['payment_method_types']) > 1) {
            $gateway->set('label', $original_label . ' ' . t('Credit card'));
            $new_config = $config;
            $new_config['display_label'] .= ' ' . t('Credit card');
            unset($new_config['payment_method_types']['authnet_echeck']);
            $gateway->setPluginConfiguration($new_config);
          }
        }
        // Echeck.
        else {
          // If the gateway had both credit_card and echeck enabled, we keep
          // the original gateway for credit_card and create a new one here
          // for echeck.
          if (count($config['payment_method_types']) > 1) {
            $new_gateway = $gateway->createDuplicate();
            $new_config = $config;
            $new_gateway->setPluginId('authorizenet_echeck');
            $new_gateway->set('id', $gateway->id() . '_echeck');
            $new_gateway->set('label', $gateway->label() . ' ' . t('Echeck'));
            $new_config['display_label'] .= ' ' . t('Echeck');
            $sandbox['echeck_payment_methods'] += $payment_method_storage->getQuery()
              ->condition('type', 'authnet_echeck')
              ->condition('payment_gateway', $gateway->id())
              ->execute();
            unset($new_config['payment_method_types']['credit_card']);
            $new_gateway->setPluginConfiguration($new_config);
            $new_gateway->save();
          }
          else {
            $gateway->setPluginId('authorizenet_echeck');
            $gateway->setPluginConfiguration($config);
          }
        }
      }
      $gateway->save();
    }
    $sandbox['max'] = count($sandbox['echeck_payment_methods']);
  }

  // Update echeck orders, payment_methods and payments in batch.
  for ($i = 1; $i <= 20; $i++) {
    if (empty($sandbox['echeck_payment_methods'])) {
      break;
    }
    $payment_method_id = array_shift($sandbox['echeck_payment_methods']);
    $payment_method = $payment_method_storage->load($payment_method_id);
    $new_payment_gateway_id = $payment_method->getPaymentGatewayId() . '_echeck';
    $payment_method->set('payment_gateway', $new_payment_gateway_id);
    $payment_method->save();

    $payment_ids = $payment_storage->getQuery()
      ->condition('payment_method', $payment_method->id())
      ->execute();
    foreach ($payment_ids as $payment_id) {
      $payment = $payment_storage->load($payment_id);
      $payment->set('payment_gateway', $new_payment_gateway_id);
      $payment->save();
    }
    $order_ids = $order_storage->getQuery()
      ->condition('payment_method', $payment_method->id())
      ->execute();
    foreach ($order_ids as $order_id) {
      $order = $order_storage->load($order_id);
      $order->set('payment_gateway', $new_payment_gateway_id);
      $order->save();
    }
    $sandbox['progress']++;
  }

  if (!empty($sandbox['max']) && count($sandbox['echeck_payment_methods']) > 0) {
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / count($sandbox['echeck_payment_methods']));
  }
  else {
    $sandbox['#finished'] = 1;
  }

  return t('All Authorize.net gateways, payment methods and payments have been updated.');
}
