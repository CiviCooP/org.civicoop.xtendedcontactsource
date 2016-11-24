<?php


/**
 * Class to process contact for extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 Oct 2016
 * @license AGPL-3.0
 * @link https://civicoop.plan.io/projects/aivl-civicrm-ontwikkeling-2016/wiki/Contact_Processing_from_Petition
 */
class CRM_Xtendedcontactsource_Contact {

  private $_contactId = NULL;
  private $_targetRecordType = NULL;

  function __construct($contactId) {
    $this->_contactId = $contactId;
    try {
      $this->_targetRecordType = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'activity_contacts',
        'name' => 'Activity Targets',
        'return' => 'value'
      ));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Core option value for record type Activity Target in option group Activity Contacts is corrupted, 
      contact your system administrator');
    }
  }

  /**
   * Method to retrieve the contact source
   *
   * @return string;
   */
  public function getContactSource() {
    // find earliest activity
    $activity = $this->getEarliestActivity();
    // find earliest group membership
    $groupContact = $this->getEarliestGroupContact();
    // show earliest of the two as contact source
    if (isset($activity->activity_date_time)) {
      $activityDate = new DateTime($activity->activity_date_time);
    }
    if (isset($groupContact->group_contact_date)) {
      $groupContactDate = new DateTime($groupContact->group_contact_date);
    }
    if (!isset($groupContactDate) && !isset($activityDate)) {
      return '-';
    }
    if (!isset($activityDate) || empty($activityDate)) {
      return 'Membership of group ' . $groupContact->title . ' with date ' . $groupContactDate->format('d-M-Y');
    }
    if (!isset($groupContactDate) || $activityDate < $groupContactDate) {
      try {
        $activityType = civicrm_api3('OptionValue', 'getvalue', array(
          'option_group_id' => 'activity_type',
          'value' => $activity->activity_type_id,
          'return' => 'label'
        ));
      } catch (CiviCRM_API3_Exception $ex) {
        $activityType = $activity->activity_type_id;
      }
      return 'Activity Type '.$activityType.' on '.$activityDate->format('d-M-Y').' ('.substr($activity->subject, 0, 30).'...)';
    }
    if (!isset($activityDate) || $groupContactDate < $activityDate) {
      return 'Membership of group '.$groupContact->title.' with date '.$groupContactDate->format('d-M-Y');
    }
  }

  /**
   * Get dao with earliest activity where contact = target
   *
   * @return bool|CRM_Core_DAO|object
   */
  private function getEarliestActivity() {
    if ($this->_contactId) {
      $sql = "SELECT a.activity_type_id, a.activity_date_time, a.subject
        FROM civicrm_activity a JOIN civicrm_activity_contact ac ON a.id = ac.activity_id AND 
        ac.record_type_id = %1
        WHERE a.is_deleted = %2 AND a.is_test=%2 AND a.is_current_revision = %3 
        AND a.activity_date_time <> %4 AND ac.contact_id = %5
        ORDER BY a.activity_date_time ASC LIMIT 1";
      $sqlParams = array(
        1 => array($this->_targetRecordType, 'Integer'),
        2 => array(0, 'Integer'),
        3 => array(1, 'Integer'),
        4 => array('1970-01-01 01:00:00', 'String'),
        5 => array($this->_contactId, 'Integer')
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      if ($dao->fetch()) {
        return $dao;
      } else {
        return FALSE;
      }
    }
  }

  /**
   * Method to get the earliest group membership of the contact
   *
   * @return bool|CRM_Core_DAO|object
   */
  private function getEarliestGroupContact() {
    if ($this->_contactId) {
      $sql = "SELECT sh.date AS group_contact_date, g.title FROM civicrm_group_contact gc 
        JOIN civicrm_subscription_history sh ON gc.contact_id = sh.contact_id AND gc.group_id = sh.group_id 
          AND gc.status = sh.status 
        JOIN civicrm_group g ON gc.group_id = g.id 
        WHERE gc.contact_id = %1 AND gc.status = %2 ORDER BY sh.date ASC LIMIT 1";
      $sqlParams = array(
        1 => array($this->_contactId, 'Integer'),
        2 => array('Added', 'String')
      );
      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      if ($dao->fetch()) {
        return $dao;
      } else {
        return FALSE;
      }
    }
  }

  /**
   * Method to process the civicrm pageRun hook
   * @param $contactId
   * @param $content
   * @return bool|string
   */
  public static function pageRun(&$page) {
    $pageName = $page->getVar('_name');
    // only if contact summary
    if ($pageName == 'CRM_Contact_Page_View_Summary') {
      $contactId = $page->getVar('_contactId');
      $contact = new CRM_Xtendedcontactsource_Contact($contactId);
      $xtendedContactSource = $contact->getContactSource();
      if ($xtendedContactSource) {
        $page->assign('xtendedContactSource', $xtendedContactSource);
        CRM_Core_Region::instance('page-body')->add(array(
          'template' => 'CRM/Xtendedcontactsource/XtendedContactSource.tpl'));
      }
    }
  }
}