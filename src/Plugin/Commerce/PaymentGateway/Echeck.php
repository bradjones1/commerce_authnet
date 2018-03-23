<?php

namespace Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use CommerceGuys\AuthNet\DataTypes\TransactionRequest;
use CommerceGuys\AuthNet\DataTypes\BillTo;
use Drupal\commerce_payment\Entity\PaymentInterface;
use CommerceGuys\AuthNet\DataTypes\ShipTo;
use CommerceGuys\AuthNet\DataTypes\Order as OrderDataType;
use CommerceGuys\AuthNet\CreateTransactionRequest;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Exception\HardDeclineException;

/**
 * Provides the Authorize.net echeck payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "authorizenet_echeck",
 *   label = "Authorize.net (Echeck)",
 *   display_label = "Authorize.net Echeck",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_authnet\PluginForm\EcheckAddForm",
 *   },
 *   payment_method_types = {"authnet_echeck"},
 * )
 */
class Echeck extends OnsiteBase {

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    $order = $payment->getOrder();
    $owner = $payment_method->getOwner();

    // Transaction request.
    $transaction_request = new TransactionRequest([
      'transactionType' => ($capture) ? TransactionRequest::AUTH_CAPTURE : TransactionRequest::AUTH_ONLY,
      'amount' => $payment->getAmount()->getNumber(),
    ]);

    list($data_descriptor, $data_value) = explode('|', $payment_method->getRemoteId());
    $payment_data = [
      'opaqueData' => [
        'dataDescriptor' => $data_descriptor,
        'dataValue' => $data_value,
      ],
    ];
    $transaction_request->addData('payment', $payment_data);

    /** @var \Drupal\address\AddressInterface $address */
    $address = $payment_method->getBillingProfile()->address->first();
    $bill_to = [
      // @todo how to allow customizing this.
      'firstName' => $address->getGivenName(),
      'lastName' => $address->getFamilyName(),
      'company' => $address->getOrganization(),
      'address' => substr($address->getAddressLine1() . ' ' . $address->getAddressLine2(), 0, 60),
      'country' => $address->getCountryCode(),
      // @todo support adding phone and fax
    ];
    if ($address->getLocality() != '') {
      $bill_to['city'] = $address->getLocality();
    }
    if ($address->getAdministrativeArea() != '') {
      $bill_to['state'] = $address->getAdministrativeArea();
    }
    if ($address->getPostalCode() != '') {
      $bill_to['zip'] = $address->getPostalCode();
    }
    $transaction_request->addDataType(new BillTo($bill_to));

    if (\Drupal::moduleHandler()->moduleExists('commerce_shipping') && $order->hasField('shipments') && !($order->get('shipments')->isEmpty())) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
      $shipments = $payment->getOrder()->get('shipments')->referencedEntities();
      $first_shipment = reset($shipments);
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $shipping_address */
      $shipping_address = $first_shipment->getShippingProfile()->address->first();
      $ship_data = [
        // @todo how to allow customizing this.
        'firstName' => $shipping_address->getGivenName(),
        'lastName' => $shipping_address->getFamilyName(),
        'address' => substr($shipping_address->getAddressLine1() . ' ' . $shipping_address->getAddressLine2(), 0, 60),
        'country' => $shipping_address->getCountryCode(),
        'company' => $shipping_address->getOrganization(),
      ];
      if ($shipping_address->getLocality() != '') {
        $ship_data['city'] = $shipping_address->getLocality();
      }
      if ($shipping_address->getAdministrativeArea() != '') {
        $ship_data['state'] = $shipping_address->getAdministrativeArea();
      }
      if ($shipping_address->getPostalCode() != '') {
        $ship_data['zip'] = $shipping_address->getPostalCode();
      }
      $transaction_request->addDataType(new ShipTo($ship_data));
    }

    // Adding order information to the transaction.
    $transaction_request->addOrder(new OrderDataType([
      'invoiceNumber' => $order->getOrderNumber() ?: $order->id(),
    ]));
    $transaction_request->addData('customerIP', $order->getIpAddress());

    // Adding line items.
    $line_items = $this->getLineItems($order);
    foreach ($line_items as $line_item) {
      $transaction_request->addLineItem($line_item);
    }

    // Adding tax information to the transaction.
    $transaction_request->addData('tax', $this->getTax($order)->toArray());

    $request = new CreateTransactionRequest($this->authnetConfiguration, $this->httpClient);
    $request->setTransactionRequest($transaction_request);
    $response = $request->execute();

    if ($response->getResultCode() != 'Ok') {
      $this->logResponse($response);
      $message = $response->getMessages()[0];
      switch ($message->getCode()) {
        case 'E00040':
          $payment_method->delete();
          throw new PaymentGatewayException('The provided payment method is no longer valid');

        default:
          throw new PaymentGatewayException($message->getText());
      }
    }

    if (!empty($response->getErrors())) {
      $message = $response->getErrors()[0];
      throw new HardDeclineException($message->getText());
    }

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($response->transactionResponse->transId);
    // @todo Find out how long an authorization is valid, set its expiration.
    $payment->save();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Needs kernel test
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      'data_descriptor', 'data_value',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    // Reusing echecks is not supported at the moment.
    // @see https://community.developer.authorize.net/t5/Integration-and-Testing/Accept-JS-and-ACH/td-p/55874
    $payment_method->setReusable(FALSE);
    $payment_method->setRemoteId($payment_details['data_descriptor'] . '|' . $payment_details['data_value']);
    // OpaqueData expire after 15min. We reduce that time by 5s to account for
    // the time it took to do the server request after the JS tokenization.
    $expires = $this->time->getRequestTime() + (15 * 60) - 5;
    $payment_method->setExpiresTime($expires);

    $payment_method->save();
  }

}
