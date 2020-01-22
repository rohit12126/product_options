<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 12/29/16
 * Time: 1:48 PM
 */

namespace App\Core\Service;

use App\Core\Models\OrderCore\ShippingOption;
use App\Core\Service\Ups\Request;
use DateTime;
use App\Core\Models\OrderCore\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ups\AddressValidation;
use Ups\Entity\Address;
use Ups\Entity\InvoiceLineTotal;
use Ups\Exception\RequestException as UpsRequestException;
use Ups\Rate;
use Ups\Entity\Shipment;
use Ups\Entity\Shipper;
use Ups\Entity\ShipFrom;
use Ups\Entity\Package;
use Ups\Entity\PackagingType;
use Ups\Entity\RateRequest;
use Ups\Entity\CustomerClassification;
use Ups\Entity\PickupType;
use Ups\TimeInTransit;
use Ups\Entity\TimeInTransitRequest;
use Ups\Entity\AddressArtifactFormat;
use Ups\Entity\ShipmentWeight;
use Ups\Entity\UnitOfMeasurement;

class Ups
{
    public function __construct()
    {
        $logger = new Logger('ups');
        $logger->pushHandler(
            new StreamHandler(
                storage_path() . '/logs/UPS.log', Logger::DEBUG,
                false
            )
        );
        $this->_upsLog = $logger;
        $this->_logger = new Log();
    }


    public function getShippingQuotes
    (
        Address $toAddress,
        $letterWeight = 0,
        $boxWeight = 0,
        $letterCount = 0,
        $boxCount = 0,
        $itemCount = 1,
        $markupPercentage = 0
    )
    {
        $config = config('app.server_config');
        $shippingQuotes = array();
        $rate = new Rate(
            $config['ups']['apiKey'],
            $config['ups']['userId'],
            $config['ups']['password'],
            false,
            $this->_upsLog
        );
        $upsRequest = new Request();
        $rate->setRequest($upsRequest);

        try {
            $shipments = array();
            if ($letterWeight > 0 && ceil($letterCount) > 0) {
                $letterShipment = new Shipment();
                $letterShipment->showNegotiatedRates();
                $packageCount = ceil($letterCount);
                $letter = new Package();
                $letter->getPackagingType()->setCode(PackagingType::PT_UPSLETTER);
                $letter->getPackageWeight()->setWeight(ceil($letterWeight / $packageCount));
                $letterShipment->setNumOfPiecesInShipment($packageCount);
                $letterShipment->addPackage($letter);
                array_push($shipments, $letterShipment);
            }
            if ($boxWeight > 0 && ceil($boxCount) > 0) {
                $boxShipment = new Shipment();
                $boxShipment->showNegotiatedRates();
                $packageCount = ceil($boxCount);
                $box = new Package();
                $box->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
                $box->getPackageWeight()->setWeight(ceil($boxWeight / $packageCount));
                $boxShipment->setNumOfPiecesInShipment($packageCount);
                $boxShipment->addPackage($box);
                array_push($shipments, $boxShipment);
            }

            $classification = new CustomerClassification();
            $classification->setCode($config['ups']['customerClassificationCode']);
            $pickup = new PickupType();
            $pickup->setCode($config['ups']['pickupTypeCode']);
            if (defined('UPS_USE_FAILOVER')) {
                throw new UpsRequestException('Use Failover');
            }
            foreach ($shipments as $shipment) {
                $shipment = $this->_setShipmentDetails($shipment, $toAddress);
                $rateRequest = new RateRequest();
                $rateRequest->setShipment($shipment);
                $rateRequest->setCustomerClassification($classification);
                $rateRequest->setPickupType($pickup);

                foreach ($rate->shopRates($rateRequest)->RatedShipment as $shippingRate) {
                    $quotedRate = (
                    is_null($shippingRate->NegotiatedRates)
                        ? $shippingRate->TotalCharges->MonetaryValue
                        : $shippingRate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue
                    );
                    $total = ($markupPercentage / 100 + 1) * $quotedRate;
                    // multi-item shipment discount
                    if ($itemCount > 1) {
                        $total = $total * 0.9;
                    }
                    if ($code = $shippingRate->Service->getCode()) {
                        if (
                            !isset($shippingQuotes[$code]) ||
                            $shippingQuotes[$code]['total'] > round($total, 2)
                        ) {
                            $shippingQuotes[$code]['total'] = round($total, 2);
                        }
                    }
                }

            }
            return $shippingQuotes;
        } catch (UpsRequestException $e) {
            $this->_logger->logError('Core', $e->getMessage() . ' -- ' . var_export($rate, true));
            if (!defined('UPS_USE_FAILOVER')) {
                define('UPS_USE_FAILOVER', true);
            }
            foreach ($shipments as $shipment) {
                $shipment = $this->_setShipmentDetails($shipment, $toAddress);
                $shippingQuotes = $this->getFailOverQuotes($shipment, $itemCount, $markupPercentage);
            }
            return $shippingQuotes;
        } catch (\Exception $e) {
            $this->_logger->logError('Core', $e->getMessage() . ' -- ' . var_export($rate, true));
            throw new \Exception('Unable to retrieve UPS Rates');
        }
    }

    private function _setShipmentDetails($shipment, $toAddress)
    {
        $config = config('app.server_config');
        $shipper = new Shipper();
        $shipper->setShipperNumber($config['ups']['accountNumber']);
        $address = new Address();
        $address->setStateProvinceCode($config['ups']['sender.stateProvinceCode']);
        $address->setPostalCode($config['ups']['shipper.postalCode']);
        $address->setCountryCode($config['ups']['shipper.countryCode']);
        // validate address to find out commercial (1) or residential (2)
        $validator = $this->validateAddress($toAddress);
//          if ('2' == $validator->getAddressClassification()->code) {
        $toAddress->setResidentialAddressIndicator(1);
//          }
        $shipFrom = new ShipFrom();
        $shipFrom->setAddress($address);
        $shipper->setAddress($address);
        $shipment->setShipper($shipper);
        $shipment->setShipFrom($shipFrom);
        $shipTo = $shipment->getShipTo();
        $shipTo->setAddress($toAddress);
        return $shipment;
    }

    public function getEtas
    (
        Address $toAddress,
        $totalWeight,
        $shipmentPieceCount,
        $totalValue,
        $shipmentDate
    )
    {
        $config = config('app.server_config');
        $shippingQuotes = array();
        $timeInTransit = new TimeInTransit(
            $config['ups']['apiKey'],
            $config['ups']['userId'],
            $config['ups']['password'],
            false,
            null,
            $this->_upsLog
        );

        $upsRequest = new Request();
        $timeInTransit->setRequest($upsRequest);
        try {
            $transitRequest = new TimeInTransitRequest();
            $fromAddress = new AddressArtifactFormat();
            $fromAddress->setPostcodePrimaryLow($config['ups']['shipper.postalCode']);
            $fromAddress->setStateProvinceCode($config['ups']['sender.stateProvinceCode']);
            $fromAddress->setCountryCode($config['ups']['shipper.countryCode']);
            $transitRequest->setTransitFrom($fromAddress);
            $address = new AddressArtifactFormat();
            $address->setPostcodePrimaryLow($toAddress->getPostalCode());
            $address->setStateProvinceCode($toAddress->getStateProvinceCode());
            $address->setCountryCode($toAddress->getCountryCode());
            $transitRequest->setTransitTo($address);
            // Weight
            $shipmentWeight = new ShipmentWeight;
            $shipmentWeight->setWeight($totalWeight);
            $unit = new UnitOfMeasurement;
            $unit->setCode(UnitOfMeasurement::PROD_POUNDS);
            $shipmentWeight->setUnitOfMeasurement($unit);
            $transitRequest->setShipmentWeight($shipmentWeight);
            $transitRequest->setTotalPackagesInShipment((int)$shipmentPieceCount);
            // Pickup date
            $shipDate = new DateTime();
            $shipDate->setTimestamp($shipmentDate);
            $transitRequest->setPickupDate($shipDate);
            // InvoiceLines
            $invoiceLineTotal = new InvoiceLineTotal;
            $invoiceLineTotal->setMonetaryValue($totalValue);
            $invoiceLineTotal->setCurrencyCode('USD');
            $transitRequest->setInvoiceLineTotal($invoiceLineTotal);
            if (defined('UPS_USE_FAILOVER')) {
                throw new UpsRequestException('Use Failover');
            }

            foreach ($timeInTransit->getTimeInTransit($transitRequest)->ServiceSummary as $serviceSummary) {
                $code = null;
                switch ($serviceSummary->Service->getCode()) {
                    case '2DA':
                        $code = '02';
                        break;
                    case '2DAS':
                        $code = '02';
                        break;
                    case '2DM':
                        $code = '59';
                        break;
                    case '3DS':
                        $code = '12';
                        break;
                    case 'GND':
                        $code = '03';
                        break;
                    case '1DA':
                        $code = '01';
                        break;
                    case '1DAS':
                        $code = '01';
                        break;
                    case '1DM':
                        $code = '14';
                        break;
                    case '1DMS':
                        $code = '14';
                        break;
                    case '1DP':
                        $code = '13';
                        break;
                    case '03':
                        $code = '11';
                        break;
                    case '05':
                        $code = '08';
                        break;
                    case '01':
                        $code = '07';
                        break;
                    case '21':
                        $code = '54';
                        break;
                    case '28':
                        $code = '65';
                        break;
                }
                $shippingQuotes[$code]['eta'] = date(
                    'D, M j, Y',
                    strtotime(
                        $serviceSummary->EstimatedArrival->Date . ' ' . $serviceSummary->EstimatedArrival->Time
                    )
                );
            }

        } catch (UpsRequestException $e) {
            $this->_logger->logError('Core', $e->getMessage());
            if (!defined('UPS_USE_FAILOVER')) {
                define('UPS_USE_FAILOVER', true);
            }
            //TODO - Get UPS failover estimates
            return [];
        } catch (\Exception $e) {
            $this->_logger->logError('Core', $e->getMessage() . ' -- ' . var_export($timeInTransit, true));
            throw new \Exception('Unable to retrieve UPS ETAs');
        }
        return $shippingQuotes;
    }

    public function validateAddress(Address $address)
    {
        $config = config('app.server_config');
        $validator = new AddressValidation(
            $config['ups']['apiKey'],
            $config['ups']['userId'],
            $config['ups']['password'],
            false,
            null,
            $this->_upsLog
        );

        $validator->activateReturnObjectOnValidate();
        $upsRequest = new Request();
        $validator->setRequest($upsRequest);
        try {
            if (defined('UPS_USE_FAILOVER')) {
                throw new UpsRequestException('Use Failover');
            }
            $response = $validator->validate(
                $address, $requestOption =
                AddressValidation::REQUEST_OPTION_ADDRESS_VALIDATION_AND_CLASSIFICATION,
                1
            );
            return $response;
        } catch (UpsRequestException $e) {
            //Just return a success result if service is down
            $this->_logger->logError('Core', $e->getMessage() . ' -- ' . var_export($validator, true));
            if (!defined('UPS_USE_FAILOVER')) {
                define('UPS_USE_FAILOVER', true);
            }
            return ['connection_error' => true];
        } catch (\Exception $e) {
            $this->_logger->logError('Core', $e->getMessage() . ' -- ' . var_export($validator, true));
            throw new \Exception('Unable to Validate Address');
        }
    }

    /**
     * Get UPS failover shipping quote.
     * @param $shipment
     * @param $itemCount
     * @param $markupPercentage
     * @return array
     */
    public function getFailOverQuotes($shipment, $itemCount, $markupPercentage)
    {
        $shippingQuotes = [];
        $package = $shipment->getPackages()[0];
        $packageCode = $package->getPackagingType()->getCode();
        $packageWeight = $package->getPackageWeight()->getWeight();

        /*
             * zipcode prefix to multiplier (more cost the further away from our prefix '9').
             * Exception: Alaska & Hawaii should have its own & higher multiplier
             */
        $multiplierTable = [
            '9'   => 1.00,
            '8'   => 1.15,
            '7'   => 1.30,
            '6'   => 1.45,
            '5'   => 1.60,
            '4'   => 1.75,
            '3'   => 1.90,
            '2'   => 2.05,
            '1'   => 2.20,
            '0'   => 2.35,
            '995' => 2.75, // Alaska Start
            '996' => 2.75,
            '997' => 2.75,
            '998' => 2.75,
            '999' => 2.75,
            '967' => 3.15, // Hawaii Start
            '968' => 3.15,
        ];

        // determine to use 3 digit prefix or 1 digit prefix
        $recipientAddress = $shipment->getShipTo()->getAddress();
        $zipPrefix = substr($recipientAddress->getPostalCode(), 0, 3);
        if (!array_key_exists($zipPrefix, $multiplierTable)) {
            $zipPrefix = substr($recipientAddress->getPostalCode(), 0, 1);
        }

        $shippingOptions = ShippingOption::where('vendor', 'ups')->get();

        foreach ($shippingOptions as $shippingOption) {
            $failOverPricing = $shippingOption->failOverPricing->where('package_code', $packageCode)->where
            ('weight', '<=', $packageWeight)->sortByDesc('weight')->first();

            if (!is_null($failOverPricing)) {
                // ground shipping exception for AK & HI, higher multiplier
                if (
                    in_array($recipientAddress->getStateProvinceCode(), ['AK', 'HI']) &&
                    '03' == $packageCode
                ) {
                    $quotedRate = $failOverPricing->price * $multiplierTable[$zipPrefix] * 1.55;
                } else {
                    $quotedRate = $failOverPricing->price * $multiplierTable[$zipPrefix];
                }

                $total = ($markupPercentage / 100 + 1) * $quotedRate;
                // multi-item shipment discount
                if ($itemCount > 1) {
                    $total = $total * 0.9;
                }

                if (
                    !isset($shippingQuotes[$shippingOption->service_code]) ||
                    $shippingQuotes[$shippingOption->service_code]['total'] > round($total, 2)
                ) {
                    $shippingQuotes[$shippingOption->service_code]['total'] = round($total, 2);
                }
            }
        }

        return $shippingQuotes;
    }
}