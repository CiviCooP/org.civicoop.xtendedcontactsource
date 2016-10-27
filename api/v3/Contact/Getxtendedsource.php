<?php

/**
 * Contact.Getxtendedsource API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_contact_Getxtendedsource_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
}

/**
 * Contact.Getxtendedsource API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contact_Getxtendedsource($params) {
  if (array_key_exists('contact_id', $params)) {
    $returnValues['contact_id'] = $params['contact_id'];
    $xtendedContactSource = new CRM_Xtendedcontactsouce_Contact($params['contact_id']);
    $returnValues['xtended_contact_source'] = $xtendedContactSource->getContactSource();
    return civicrm_api3_create_success($returnValues, $params, 'Contact', 'getxtendedsource');
  } else {
    throw new API_Exception('Parameter contact_id is mandatory', 1000);
  }
}

