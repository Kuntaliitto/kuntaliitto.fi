<?php

namespace Drupal\kuntaliitto_contacts;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\kuntaliitto_contacts\Exception\ContactImportException;
use Drupal\media_entity\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;


/**
 * Class ContactProcessor.
 *
 * @package Drupal\kuntaliitto_contacts
 */
class ContactProcessor implements ContactProcessorInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * ContactProcessor constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Processes import items.
   * @param array $user
   *
   * @return string
   * @throws \Drupal\kuntaliitto_contacts\Exception\ContactImportException
   */
  public function process(array $user) {

    $this->decodeDataSet($user, ['extensionattribute9']);

    $user_id = $this->entityTypeManager
      ->getStorage('user')
      ->getQuery()
      ->condition('mail', $user['mail'])
      ->execute();

    switch (count($user_id)) {
      case 0:
        $user_id = $this->createUser($user);
        break;

      case 1:
        $user_id = reset($user_id);
        break;

      default:
        $message = $this->t('Found more than one user');
        throw new ContactImportException($message);
    }

    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'contact_information')
      ->condition('field_user_reference', $user_id)
      ->execute();

    switch (count($nids)) {
      case 0:
        return $this->createNode($user, $user_id);

      break;
      case 1:
        $nid = reset($nids);
        return $this->updateNode($nid, $user, $user_id);

      break;
      default:
        $message = $this->t('Found more than one contact information for user');
        foreach ($nids as $extra_nid) {
          $message .= $this->t('Node: ') . $extra_nid . PHP_EOL;
        }
        throw new ContactImportException($message);
    }

  }

  /**
   * @param array $data
   * @param array $skip_keys
   */
  public function decodeDataSet(array &$data, array $skip_keys = []) {
    foreach ($data as $key => $value) {
      if (!in_array($key, $skip_keys)) {
        $data[$key] = utf8_decode($value);
      }
    }
  }

  /**
   * @param string $username
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   * @throws \Drupal\kuntaliitto_contacts\Exception\ContactImportException
   */
  public function deactivate($username) {
    $user_id = $this->entityTypeManager
      ->getStorage('user')
      ->getQuery()
      ->condition('name', $username)
      ->execute();

    if (count($user_id) > 1) {
      $message = $this->t('More than one user found');
      throw new ContactImportException($message);
    }

    if (count($user_id) < 1) {
      $message = $this->t('There was no user to deactivate');
      throw new ContactImportException($message);
    }

    /** @var \Drupal\user\Entity\User $user */
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load(reset($user_id));

    $user->block();

    $status = $this->t('Deactivated user');

    $nid = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'contact_information')
      ->condition('field_user_reference.target_id', $user->id())
      ->execute();

    if (count($nid) > 1) {
      $message = $this->t('Found more than one contact information for user');
      throw new ContactImportException($message);
    }
    if (count($nid) < 1) {
      $message = $this->t('There was no contact information');
      throw new ContactImportException($message);
    }

    return $status;

  }

  /**
   * @param array $user
   *
   * @return int|mixed|null|string
   * @throws \Drupal\kuntaliitto_contacts\Exception\ContactImportException
   */
  public function createUser(array $user) {
    /** @var \Drupal\user\Entity\User $user_entity */
    $user_entity = $this->entityTypeManager
      ->getStorage('user')
      ->create();
    $user_entity->setUsername($user['samaccountname']);
    $user_entity->setEmail($user['mail']);
    $user_entity->activate();
    try {
      $user_entity->save();
      return $user_entity->id();
    }
    catch (\Exception $exception) {
      throw new ContactImportException($exception->getMessage());
    }
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   * @param $user
   * @param $uid
   * @param string $lang
   *
   * @return \Drupal\node\NodeInterface
   */
  public function setNodeValues(NodeInterface $node, $user, $uid, $lang = 'fi') {

    $user['extensionattribute7'] = str_replace('[', '<', $user['extensionattribute7']);
    $user['extensionattribute7'] = str_replace(']', '>', $user['extensionattribute7']);

    switch ($lang) {
      case 'fi':
        $node->langcode->value = $lang;
        $node->field_ad_group->value = $user['extensionattribute10'];
        break;

      case 'en':
        $user['title'] = $user['extensionattribute2'];
        $user['info'] = $user['extensionattribute8'];
        $node->field_ad_group->value = $user['extensionattribute12'];
        break;

      case 'sv':
        $user['title'] = $user['extensionattribute1'];
        $user['info'] = $user['extensionattribute7'];
        $node->field_ad_group->value = $user['extensionattribute11'];
        break;
    }

    switch ($user['extensionattribute3']) {
      case 'Julkinen':
        $node->field_do_not_show_responsibiliti->value = 0;
        $node->field_don_t_show_in_contact_dire->value = 0;
        break;

      case 'Osittain julkinen':
        $node->field_do_not_show_responsibiliti->value = 1;
        $node->field_don_t_show_in_contact_dire->value = 0;
        break;

      case 'Sisäinen':
        $node->field_don_t_show_in_contact_dire->value = 1;
        break;

      case 'Salainen':
        $node->field_don_t_show_in_contact_dire->value = 1;
        break;

      default:
        $node->field_do_not_show_responsibiliti->value = 1;
        $node->field_don_t_show_in_contact_dire->value = 1;
    }

    // $file_uri is a current file uri and used for comparing current image and image from feed.
    // If image on feed is updated then also user image in Drupal will be updated.
    $file_uri = NULL;
    if ($node->hasField('field_image')) {
      // Get media entity id from node.
      $fid1 = $node->getTranslation('fi')->get('field_image')->target_id;
      // Get file id from that media file.
      $media_file = Media::load($fid1);
      if (isset($media_file) && $media_file->hasField('field_image_file') && isset($media_file->get('field_image_file')->target_id)) {
        $fid2 = $media_file->get('field_image_file')->target_id;

        // Get uri of that file. We need  it to compare existinx image and image from feed.
        if ($file = File::load((int) $fid2)) {
          $file_uri = $file->getFileUri();
        }
      }
    }

    // Set update info.
    $update_info = explode(';', $user['extensionattribute15']);
    $node->field_update_time_ad->value = $update_info[0];
    $node->field_updated_from_ad->value = $update_info[1];
    $node->field_update_ip_ad->value = $update_info[2];
    $node->field_do_not_show_picture = $user['extensionattribute4'] == 'Julkinen' ? 0 : 1;
    $node->field_do_not_show_mobile = $user['extensionattribute13'] == 'Julkinen' ? 0 : 1;
    $node->field_weight->value = $user['extensionattribute9'] ? 100 + $user['extensionattribute9'] : 200;
    $node->uid->target_id = 1;
    $node->title->value = $user['givenname'] . ' ' . $user['sn'];
    $node->field_additional_information->value = $user['comment'];
    $node->field_additional_information->format = 'filtered_html';
    $node->field_additional_information->value = $user['comment'];
    $node->field_additional_information->format = 'filtered_html';
    $node->field_responsibilities->value = $this->getListItems($user['info']);
    $node->field_responsibilities->format = 'filtered_html';
    $node->field_contact_email->value = $user['mail'];
    $node->field_content_source = [$this->getTerm('Contact information', 'content_types')];
    $node->field_department = $this->getTerm($user['department'], 'department');
    $node->field_division = $this->getTerm($user['division'], 'division');
    $node->field_first_name->value = $user['givenname'];
    $node->field_last_name->value = $user['sn'];
    $node->field_image = [$this->createImageMedia($user['samaccountname'], $user['givenname'] . ' ' . $user['sn'], $file_uri)];
    $node->field_mobile->value = $user['mobile'];
    $node->field_organization = $this->getTerm($user['company'], 'organization');
    $node->field_person_title->value = $user['title'];
    $node->field_phone_number->value = $user['telephonenumber'];
    $node->field_twitter_account->value = $user['facsimiletelephonenumber'];
    $node->field_service_in_swedish->value = $user['extensionattribute6'] == 'Ei' ? 0 : 1;
    $node->field_user_reference = [$uid];

    $node->setPublished(TRUE);
    return $node;
  }

  /**
   * @param $data
   *
   * @return string
   */
  public function getListItems($data) {
    $result = '';
    $data = trim($data);
    if (empty($data)) {
      return $result;
    }
    $list_items = explode("\r\n", $data);
    foreach ($list_items as $item) {
      $result .= '<li>' . $item . '</li>';
    }

    return '<ul>' . $result . '</ul>';
  }

  /**
   * @param array $user
   * @param $uid
   *
   * @return string
   * @throws \Exception
   */
  public function createNode(array $user, $uid) {
    $translations = ['en', 'sv'];
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create(['type' => 'contact_information']);
    $node = $this->setNodeValues($node, $user, $uid);
    foreach ($translations as $language) {
      $node = $node->addTranslation($language);
      $node = $this->setNodeValues($node, $user, $uid, $language);
    }

    $node->save();
    return 'Created';

  }

  /**
   * @param $nid
   * @param array $user
   * @param $uid
   * @return string
   */
  public function updateNode($nid, array $user, $uid) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load($nid);
    $status = '';
    $languages = ['fi', 'en', 'sv'];
    foreach ($languages as $langcode) {
      if ($node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
        if ($node->field_dont_update_from_ad->value == 0) {
          $node = $this->setNodeValues($node, $user, $uid, $langcode);
          $status .= strtoupper($langcode) . ' updated ';
          continue;
        }
        $status .= strtoupper($langcode) . ' locked ';
        continue;
      }

      $node = $node->addTranslation($langcode);
      $node->field_dont_update_from_ad->value = 0;
      $node = $this->setNodeValues($node, $user, $uid, $langcode);
      $status .= strtoupper($langcode) . ' created ';
    }

    $node->save();
    return $status;
  }

  /**
   * @param $term
   * @param $vocabulary
   * @param string $lang
   *
   * @return int|mixed|null|string
   */
  public function getTerm($term, $vocabulary, $lang = 'fi') {
    if (empty($term)) {
      return NULL;
    }
    $term_id = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->getQuery()
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
        'langcode' => $lang,
        'name' => $term,
        'vid' => $vocabulary,
      ]);
    $term_entity->save();
    return $term_entity->id();
  }

  /**
   * @param $username
   * @param $title
   *
   * @return int|null|string
   */
  public function createImageMedia($username, $title, $file_uri) {
    $result = $this->entityTypeManager
      ->getStorage('media')
      ->getQuery()
      ->condition('name', $username)
      ->condition('bundle', 'image')
      ->execute();

    // If there is no profile_pictures directory then create it.
    //$path = "public://media/profile_pictures/";
    if (file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {

    }

    $current_file = file_get_contents($file_uri);
    $data = file_get_contents('http://app.kunnat.net/ldap/person-photo.php?user=' . $username);
    if (strlen($data) == 0) {
      return NULL;
    }
    // When current profile picture is same as imported on then don't do anything.
    if ($current_file && $current_file == $data) {
      return reset($result);
    }

    // Temporary. Tosee on Drush line name of users are changed.
    var_dump($username);


    $file = file_save_data($data, 'public://media/profile_pictures/' . $username . '.jpeg', FILE_EXISTS_REPLACE);
    $file->setFilename($username . '.jpeg');
    $file->save();
    $image_media = $this->entityTypeManager
      ->getStorage('media')
      ->create(
        [
          'bundle' => 'image',
          'name' => $username,
          'uid' => '1',
          'langcode' => 'fi',
          'status' => Media::PUBLISHED,
          'field_image_file'  => [
            'target_id' => $file->id(),
            'alt'       => $title,
            'title'     => $title,
          ],
        ]
      );

    $image_media->save();
    return $image_media->id();
  }

}
