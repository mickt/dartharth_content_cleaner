Simple cleaner of long formatted field in Drupal 9

This module clean html-code from class, id, img tag, make from link text and remowe scripts.

If after cleaner you see html code on frontend of page, you need to change content filter to yours in src/Form/ContentCleanerSettingsForm.php line 87 - 'format' => 'html',
