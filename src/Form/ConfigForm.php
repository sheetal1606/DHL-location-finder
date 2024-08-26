<?php

namespace Drupal\dhl_location_finder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements config form for dhl location finder api key.
 */
class ConfigForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['dhl_location_finder.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'dhl_location_finder_config_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('dhl_location_finder.settings');
        $form['api_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API Key'),
        '#default_value' => $config->get('api_key'),
        '#description' => $this->t(
            "API key from DHL. Please visit <a target='_blank' href=':url'>DHL</a> to get your api key.",
            [
            ':url' => 'https://developer.dhl.com/user/login?action=create-app',
            ]
        ),
        '#required' => true,
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);
        $this->config('dhl_location_finder.settings')
            ->set('api_key', $form_state->getValue('api_key'))
            ->save();
    }
}
