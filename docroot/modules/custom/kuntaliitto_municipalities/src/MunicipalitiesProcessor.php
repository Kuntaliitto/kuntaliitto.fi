<?php

namespace Drupal\kuntaliitto_municipalities;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\media_entity\Entity\Media;

/**
 * Class MunicipalitiesProcessor.
 *
 * @package Drupal\kuntaliitto_municipalities
 */
class MunicipalitiesProcessor implements MunicipalitiesProcessorInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * @param array $user
   * @return string
   */
  public function process(array $municipality) {
    foreach ($municipality as $municipality_info_key => $municipality_info) {
      $municipality[$municipality_info_key] = trim($municipality_info);
    }
    $query = $this->entityQuery->get('taxonomy_term')
      ->condition('vid', 'municipalities')
      ->condition('name', $municipality['nimi']);
    $result = $query->execute();
    $tid = array_values($result);
    if (count($tid) == 1) {
      $tid = $tid[0];
      return $this->updateMunicipality($tid, $municipality);
    }
    elseif (count($tid) > 1) {
      return 'Found more than one contact information for user';
    }
    else {
      $tid = $this->createMunicipality($municipality);
      if (!$tid) {
        return 'Could not create contact information';
      }
      else {
        return $tid;
      }
    }
  }

  /**
   * @param array $municipality
   * @return string
   */
  public function createMunicipality(array $municipality) {
    $translations = ['en', 'sv'];
    $term = Term::create(['vid' => 'municipalities']);
    $term = $this->setTermValues($term, $municipality);
    foreach ($translations as $language) {
      $term = $term->addTranslation($language);
      $term = $this->setTermValues($term, $municipality, $language);
      $term->save();
    }
    $result = $term->save();
    if ($result) {
      return 'Created';
    }
    return 'Could not create Municipality term';
  }

  /**
   * @param $nid
   * @param array $user
   * @param $uid
   * @return string
   */
  public function updateMunicipality($tid, array $municipality) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    $status = '';
    $languages = ['fi', 'en', 'sv'];
    foreach ($languages as $langcode) {
      $translations = array_keys($term->getTranslationLanguages());
      if (!in_array($langcode, $translations)) {
        $term->addTranslation($langcode);
      }
      $term = $term->getTranslation($langcode);
      if (!$term->field_do_not_update_from_ad->value) {
        $term = $this->setTermValues($term, $municipality, $langcode);
        $term->save();
        $status .= $langcode . ' Updated ';
      }
      else {
        $status .= $langcode . ' Locked ';
      }
    }
    $result = $term->save();
    if ($result) {
      return $status;
    }
    return 'Could not update municipality';
  }

  /**
   *
   */
  public function setTermValues(TermInterface $term, $municipality, $lang = 'fi') {

    $contact_dir = 'Yhteystietohakemisto';
    switch ($lang) {
      case 'fi':
        $term->langcode->value = $lang;
        break;

      case 'en':
        $contact_dir = 'Contact directory';
        // $municipality['nimi'] = !empty($municipality['ORG_NAME']) ? $municipality['ORG_NAME'] : $municipality['nimi'];.
        $municipality['EMAIL_GEN'] = !empty($municipality['EMAIL_GEN_EN']) ? $municipality['EMAIL_GEN_EN'] : $municipality['EMAIL_GEN'];
        $municipality['URL'] = !empty($municipality['URL_EN']) ? $municipality['URL_EN'] : $municipality['URL'];
        $municipality['PUHELIN'] = $municipality['PUH_EN'];
        break;

      case 'sv':
        $contact_dir = 'Kontakta katalog';
        $municipality['nimi'] = $municipality['namn'];
        $municipality['EMAIL_GEN'] = !empty($municipality['EMAIL_GEN_SE']) ? $municipality['EMAIL_GEN_SE'] : $municipality['EMAIL_GEN'];
        $municipality['URL'] = !empty($municipality['URL_SE']) ? $municipality['URL_SE'] : $municipality['URL'];
        $municipality['KAYNTI_OS'] = $municipality['VISIT_ADDR'];
        $municipality['POSTI_OS'] = $municipality['POST_ADDR'];
        $municipality['PTOIMIP'] = $municipality['PKONTOR'];
        break;
    }

    switch ($municipality['KIELI']) {
      case '1':
        $municipality['KIELI'] = 'finnish';
        break;

      case '2':
        $municipality['KIELI'] = 'swedish';
        break;

      case '3':
        $municipality['KIELI'] = 'bi-finnish';
        break;

      case '4':
        $municipality['KIELI'] = 'bi-swedish';
        break;
    }

    // $term->parent->target_id = 0;.
    $term->name->value = $municipality['nimi'];
    // $term->field_area->value = $municipality[''];
    //    $term->field_designer->value = $this->getTerm('Contact person', 'Designer');.
    $term->field_e_mail_address->value = $municipality['EMAIL_GEN'];
    $term->field_internet_address = $this->validatedUrl($municipality['URL']);
    $term->field_language_of_municipality->value = $municipality['KIELI'];
    $term->field_logo = [$this->createImageMedia($municipality['KNO'], $municipality['KNO'])];
    // $term->field_logo_description->value  = Null;.
    $term->field_mailing_address->value = '<p>' . $municipality['POSTI_OS'] . ' <br /> ' . $municipality['POSTINRO'] . ' <br /> ' . $municipality['PTOIMIP'] . '</p>';
    $term->field_mailing_address->format = 'filtered_html';
    $term->field_municipality_number->value = $municipality['KNO'];
    // $term->field_municipality_type->value = $municipality['kuntamuoto'];.
    $term->field_phone->value = $municipality['PUHELIN'];
    $term->field_visiting_address->value = '<p>' . $municipality['KAYNTI_OS'] . '</p>';
    $term->field_visiting_address->format = 'filtered_html';
    $contact_info_link = $this->validatedUrl($municipality['YHTEYSTIEDOT']);
    if (!empty($contact_info_link)) {
      $contact_info_link['title'] = $contact_dir;
    }
    $term->field_contact_directory = $contact_info_link;

    return $term;
  }

  /**
   *
   */
  public function validatedUrl($url_string) {
    $url_storage = [];
    if (strncasecmp($url_string, 'http', 4) != 0 && !empty(trim($url_string))) {
      $url_string = 'http://' . $url_string;
    }
    $url_storage['uri'] = !empty($url_string) ? $url_string : '';
    if (!empty(trim($url_string))) {
      $url_storage['title'] = parse_url($url_string, PHP_URL_HOST);
    }
    else {
      $url_storage['title'] = '';
    }

    return $url_storage;
  }

  /**
   * @param $term
   * @param $vocabulary
   * @return string
   */
  public function getTerm($term, $vocabulary) {
    if (!empty($term)) {
      $term_id = $this->entityQuery->get('taxonomy_term')
        ->condition('name', $term)
        ->condition('vid', $vocabulary)
        ->execute();
      if (count($term_id) > 0) {
        return reset($term_id);
      }
      /** @var \Drupal\taxonomy\Entity\Term $term_entity */
      $term_entity = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->create([
          'parent' => [],
          'langcode' => 'fi',
          'name' => $term,
          'vid' => $vocabulary,
        ]);
      $result = $term_entity->save();
      if ($result) {
        return $term_entity->id();
      }
      else {
        return NULL;
      }
    }
    else {
      return NULL;
    }
  }

  /**
   *
   */
  public function createImageMedia($kno, $title) {
    // $kno = trim($kno);
    //    if (strlen($kno) == 2) {
    //      $kno = '0' . $kno;
    //    }.
    $title = strtolower($title);
    $title = preg_replace('@[^a-z0-9_]+@', '_', $title);
    $query = $this->entityQuery->get('media')
      ->condition('name', $kno)
      ->condition('bundle', 'image');
    $result = $query->execute();
    $result = array_values($result);
    if (count($result) > 0) {
      return $result[0];
    }
    $data = @file_get_contents('http://www.kunnat.net/SiteCollectionImages/Vaakunat/' . $kno . '.gif');
    if (strlen($data) != 0) {
      $file = file_save_data($data, 'public://media/profile_pictures/' . $kno . '.gif', FILE_EXISTS_REPLACE);
      $file->setFilename($kno . '.gif');
      $file->save();
      $image_media = Media::create([
        'bundle'            => 'image',
        'name'              => $kno,
        'uid'               => '1',
        'langcode'          => 'fi',
        'status'            => Media::PUBLISHED,
        'field_image_file'  => [
          'target_id' => $file->id(),
          'alt'       => $title,
          'title'     => $title,
        ],
      ]);
      $result = $image_media->save();
      return $result ? $image_media->id() : NULL;
    }
    else {
      return NULL;
    }
  }

}
