<?php

namespace Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
enum GetProcessStatusEntityIdEventType: string
{
    case CONFIRM_SHIPMENT = 'CONFIRM_SHIPMENT';
    case CREATE_SHIPMENT = 'CREATE_SHIPMENT';
    case CANCEL_ORDER = 'CANCEL_ORDER';
    case CHANGE_TRANSPORT = 'CHANGE_TRANSPORT';
    case HANDLE_RETURN_ITEM = 'HANDLE_RETURN_ITEM';
    case CREATE_RETURN_ITEM = 'CREATE_RETURN_ITEM';
    case CREATE_INBOUND = 'CREATE_INBOUND';
    case DELETE_OFFER = 'DELETE_OFFER';
    case CREATE_OFFER = 'CREATE_OFFER';
    case UPDATE_OFFER = 'UPDATE_OFFER';
    case UPDATE_OFFER_STOCK = 'UPDATE_OFFER_STOCK';
    case UPDATE_OFFER_PRICE = 'UPDATE_OFFER_PRICE';
    case CREATE_OFFER_EXPORT = 'CREATE_OFFER_EXPORT';
    case UNPUBLISHED_OFFER_REPORT = 'UNPUBLISHED_OFFER_REPORT';
    case CREATE_PRODUCT_CONTENT = 'CREATE_PRODUCT_CONTENT';
    case CREATE_SUBSCRIPTION = 'CREATE_SUBSCRIPTION';
    case UPDATE_SUBSCRIPTION = 'UPDATE_SUBSCRIPTION';
    case DELETE_SUBSCRIPTION = 'DELETE_SUBSCRIPTION';
    case SEND_SUBSCRIPTION_TST_MSG = 'SEND_SUBSCRIPTION_TST_MSG';
    case CREATE_SHIPPING_LABEL = 'CREATE_SHIPPING_LABEL';
    case CREATE_REPLENISHMENT = 'CREATE_REPLENISHMENT';
    case UPDATE_REPLENISHMENT = 'UPDATE_REPLENISHMENT';
    case CREATE_CAMPAIGN = 'CREATE_CAMPAIGN';
    case UPDATE_CAMPAIGN = 'UPDATE_CAMPAIGN';
    case CREATE_AD_GROUP = 'CREATE_AD_GROUP';
    case UPDATE_AD_GROUP = 'UPDATE_AD_GROUP';
    case CREATE_TARGET_PRODUCT = 'CREATE_TARGET_PRODUCT';
    case UPDATE_TARGET_PRODUCT = 'UPDATE_TARGET_PRODUCT';
    case CREATE_NEGATIVE_KEYWORD = 'CREATE_NEGATIVE_KEYWORD';
    case DELETE_NEGATIVE_KEYWORD = 'DELETE_NEGATIVE_KEYWORD';
    case CREATE_KEYWORD = 'CREATE_KEYWORD';
    case UPDATE_KEYWORD = 'UPDATE_KEYWORD';
    case DELETE_KEYWORD = 'DELETE_KEYWORD';
    case REQUEST_PRODUCT_DESTINATIONS = 'REQUEST_PRODUCT_DESTINATIONS';
    case CREATE_SOV_SEARCH_TERM_REPORT = 'CREATE_SOV_SEARCH_TERM_REPORT';
    case CREATE_SOV_CATEGORY_REPORT = 'CREATE_SOV_CATEGORY_REPORT';
    case UPLOAD_INVOICE = 'UPLOAD_INVOICE';
    case CREATE_CAMPAIGN_PERFORMANCE_REPORT = 'CREATE_CAMPAIGN_PERFORMANCE_REPORT';
}
