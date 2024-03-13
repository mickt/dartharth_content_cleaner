<?php

namespace Drupal\dartharth_content_cleaner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\filter\Entity\FilterFormat;


class ContentCleanerSettingsForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return ['dartharth_content_cleaner.settings'];
    }

    public function getFormId() {
        return 'dartharth_content_cleaner_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        // Get a list of content types.
        $content_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
        $content_type_options = [];
        foreach ($content_types as $content_type) {
            $content_type_options[$content_type->id()] = $content_type->label();
        }

        // Add content type select element.
        $form['content_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Content type'),
            '#options' => $content_type_options,
            '#required' => TRUE,
        ];

        // Get a list of text formats.
        $text_formats = FilterFormat::loadMultiple();
        $text_format_options = [];
        foreach ($text_formats as $text_format) {
            $text_format_options[$text_format->id()] = $text_format->label();
        }

        // Add text format select element.
        $form['text_format'] = [
            '#type' => 'select',
            '#title' => $this->t('Text format'),
            '#options' => $text_format_options,
            '#required' => TRUE,
            '#description' => $this->t('Select the text format for the cleaned content.'),
        ];

        // Add cleaning options fieldset.
        $form['cleaning_options'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Cleaning options'),
        ];

        // Add cleaning option checkboxes.
        $form['cleaning_options']['remove_scripts'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Remove scripts'),
        ];

        $form['cleaning_options']['remove_links'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Remove links'),
        ];

        $form['cleaning_options']['remove_images'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Remove images'),
        ];

        // Add submit button.
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Start cleaning'),
        ];

        return parent::buildForm($form, $form_state);
    }


    public function submitForm(array &$form, FormStateInterface $form_state) {
        $content_type = $form_state->getValue('content_type');
        $remove_scripts = $form_state->getValue('remove_scripts');
        $remove_links = $form_state->getValue('remove_links');
        $remove_images = $form_state->getValue('remove_images');
        $content_filter = $form_state->getValue('text_format');

        // Get the list of nodes of the selected content type.
        $query = \Drupal::entityQuery('node')->condition('type', $content_type);
        $nids = $query->execute();
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');

        // Counter for corrected nodes.
        $corrected_nodes = 0;

        foreach ($nids as $nid) {
            /** @var \Drupal\node\NodeInterface $node */
            $node = $node_storage->load($nid);
            if ($node) {
                // Clean up the node's body field.
                $body_value = $node->get('body')->value;
                $body_value_cleaned = $this->cleanUpHtml($body_value, $remove_scripts, $remove_links, $remove_images);
                $node->set('body', [
                    'value' => $body_value_cleaned,
                    'format' => $content_filter,
                ]);

                // Save the node.
                $node->save();

                // Increment the counter.
                $corrected_nodes++;
            }
        }


        // Set a message indicating the number of corrected nodes.
        $this->messenger()->addMessage($this->t('@count nodes have been cleaned.', ['@count' => $corrected_nodes]));
    }


    private function cleanUpHtml($html, $remove_scripts, $remove_links, $remove_images) {
        // Remove scripts if the option is selected.
        if ($remove_scripts) {
            $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/', '', $html);
        }

        // Remove links if the option is selected.
        if ($remove_links) {
            $html = preg_replace('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"([^>]*)>(.*?)<\/a>/', '$3', $html);
        }

        // Remove images if the option is selected.
        if ($remove_images) {
            $html = preg_replace('/<img\s+.*?>/', '', $html);
        }

        // Remove unnecessary attributes (classes, ids, styles).
        $html = preg_replace('/(<[^>]+) (class|id|style)=".*?"/i', '$1', $html);

        return $html;
    }


}
