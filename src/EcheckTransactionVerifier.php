<?php

namespace Drupal\commerce_authnet;

use Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\Echeck;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Verify echeck transaction states.
 */
class EcheckTransactionVerifier implements PaymentProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new EcheckTransactionVerifier object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayments() {
    // Get all echeck payment gateways corresponding to pending echeck
    // payments.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $query = $payment_storage->getQuery();
    $payment_ids = $query->condition('type', 'payment_echeck')
      ->condition('state', 'pending')
      ->execute();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $payment_storage->loadMultiple($payment_ids);
    $gateway_plugins = [];
    foreach ($payments as $payment) {
      if ($payment->getPaymentGateway()->getPluginId() === 'authorizenet_echeck' && empty($gateway_plugins[$payment->getPaymentGatewayId()])) {
        $gateway_plugins[$payment->getPaymentGatewayId()] = $payment->getPaymentGateway()->getPlugin();
      }
    }

    // Get settled transactions.
    $return = [];
    $now = date('Y-m-d\TH:i:s', $this->time->getCurrentTime());
    $two_days_ago = date('Y-m-d\TH:i:s', $this->time->getCurrentTime() - 480 * 3600);
    /** @var Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\Echeck $plugin */
    foreach ($gateway_plugins as $plugin) {
      $return += $plugin->getSettledTransactions($two_days_ago, $now);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(PaymentInterface $payment) {
    $gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    if (!$gateway_plugin instanceof Echeck) {
      // This should never happen, but just in case.
      return NULL;
    }
    if ($payment->getState() !== 'completed') {
      $gateway_plugin->capturePayment($payment);
    }
  }

}
