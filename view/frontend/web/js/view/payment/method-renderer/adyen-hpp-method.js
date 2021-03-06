/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2015 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Adyen_Payment/js/action/set-payment-method',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/storage',
        'Magento_Checkout/js/model/url-builder',
        'Adyen_Payment/js/model/adyen-payment-service',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko, $, Component, setPaymentMethodAction, selectPaymentMethodAction, quote, checkoutData, additionalValidators, storage, urlBuilder, adyenPaymentService, customer, fullScreenLoader) {
        'use strict';
        var brandCode = ko.observable(null);
        var paymentMethod = ko.observable(null);

        return Component.extend({
            self: this,
            defaults: {
                template: 'Adyen_Payment/payment/hpp-form',
                brandCode: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'brandCode',
                        'issuerId'
                    ]);
                return this;
            },
            initialize: function () {
                this._super();

                fullScreenLoader.startLoader();

                // reset variable:
                adyenPaymentService.setPaymentMethods();

                // retrieve payment methods
                var serviceUrl,
                    payload;

                if(customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/retrieve-adyen-payment-methods', {});
                } else {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/retrieve-adyen-payment-methods', {
                        cartId: quote.getQuoteId()
                    });
                }

                payload = {
                    cartId: quote.getQuoteId(),
                    shippingAddress: quote.shippingAddress()
                };

                storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function (response) {
                        adyenPaymentService.setPaymentMethods(response);
                        fullScreenLoader.stopLoader();
                    }
                ).fail(function(error) {
                    console.log(JSON.stringify(error));
                    fullScreenLoader.stopLoader();
                });
            },
            getAdyenHppPaymentMethods: function() {
                var self = this;

                var paymentMethods = adyenPaymentService.getAvailablePaymentMethods();

                var paymentList = _.map(paymentMethods, function(value) {

                        if(value.brandCode == "ideal") {
                            return {
                                'value': value.brandCode,
                                'name': value,
                                'method': self.item.method,
                                'issuerIds':  value.issuers,
                                'issuerId': ko.observable(null),
                                getCode: function() {
                                    return self.item.method;
                                },
                                validate: function () {
                                    return self.validate();
                                }
                            }
                        } else {
                            return {
                                'value': value.brandCode,
                                'name': value,
                                'method': self.item.method,
                                getCode: function() {
                                    return self.item.method;
                                },
                                validate: function () {
                                    return self.validate();
                                }
                            }
                        }
                    }
                );
                return paymentList;
            },
            /** Redirect to adyen */
            continueToAdyen: function () {
                if (this.validate() && additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction();
                    return false;
                }
            },
            continueToAdyenBrandCode: function() {
                // set payment method to adyen_hpp
                var self = this;

                if (this.validate() && additionalValidators.validate()) {

                    // for ideal add brand_code in request
                    if(brandCode() == "ideal") {
                        var  data = {
                            "method": self.method,
                            "po_number": null,
                            "additional_data": {
                                issuer_id: this.issuerId(),
                                brand_code: self.value
                            }
                        };
                    } else {
                        var  data = {
                            "method": self.method,
                            "po_number": null,
                            "additional_data": {
                                brand_code: self.value
                            }
                        };
                    }

                    selectPaymentMethodAction(data);
                    setPaymentMethodAction();
                }

                return false;
            },
            selectPaymentMethodBrandCode: function() {
                var self = this;

                // set payment method to adyen_hpp
                var  data = {
                    "method": self.method,
                    "po_number": null,
                    "additional_data": {
                        brand_code: self.value,
                    }
                };

                // set the brandCode
                brandCode(self.value);

                // set payment method
                paymentMethod(self.method);

                selectPaymentMethodAction(data);
                checkoutData.setSelectedPaymentMethod(self.method);

                return true;
            },
            isBrandCodeChecked: ko.computed(function () {

                if(!quote.paymentMethod()) {
                  return null;
                }

                if(quote.paymentMethod().method == paymentMethod()) {
                    return brandCode();
                }
                return null;
            }),
            isPaymentMethodSelectionOnAdyen: function() {
                return window.checkoutConfig.payment.adyenHpp.isPaymentMethodSelectionOnAdyen;
            },
            validate: function () {
                return true;
            }
        });
    }
);
