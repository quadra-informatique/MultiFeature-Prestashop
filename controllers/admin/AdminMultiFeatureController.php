<?php

/**
 * ---------------------------------------------------------------------------------
 *
 * 1997-2015 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author    Quadra Informatique <ecommerce@quadra-informatique.fr>
 * @copyright 1997-2015 Quadra Informatique
 * @version Release: $Revision: 1.2.0 $
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * ---------------------------------------------------------------------------------
 */
class AdminMultifeatureController extends ModuleAdminController
{

    public function __construct()
    {
        $this->module = 'quadramultifeature';
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = true;
        $this->edit = true;
        $this->delete = false;
        $this->deleted = false;
        $this->bootstrap = true;
        $this->addRowAction('edit');
        parent::__construct();
        $this->context = Context::getContext();

        parent::__construct();

        $this->bulk_actions = array(
            'mark' => array(
                'text' => $this->l('Set this / these product(s) as ticket(s)'),
                'icon' => 'icon-power-off',
            ),
            'unmark' => array(
                'text' => $this->l('Unset this / these product(s) as ticket(s)'),
                'icon' => 'icon-power-off text-danger',
            )
        );

        $this->fields_list = array(
            'id_product' => array(
                'title' => $this->l('Id'),
                'align' => 'left',
                'type' => 'int',
                'width' => 30,
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'left',
                'filter_key' => 'b!name',
                'width' => 220,
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'align' => 'left',
                'width' => 30,
            )
        );
    }

    

    public function renderForm()
    {
        if (!($obj = $this->loadObject(true)))
            return;

        $features = Feature::getFeatures($this->context->language->id);
        $current_product = new Product($obj->id);
        $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');


        $this->fields_form = array(
            'legend' => array(
                'title' => $current_product->name[(int)$id_lang_default],
                'icon' => 'icon-info-sign'
            ),
            'input' => array(),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitAddMultiFeature'
            ),
        );

        foreach ($features as $feature) {

            $has_normal_value = QiMultiFeatureApi::HasThisFeatureNormalValue((int)$feature['id_feature']);

            // If the feature have custom feature value, make an input, else make a checkbox selector
            if ($has_normal_value == false) {
                $this->fields_form['input'][$feature['id_feature']] = array(
                    'type' => 'text',
                    'label' => $feature['name'],
                    'lang' => true,
                    'name' => $feature['id_feature'],
                );
            } else {
                $this->fields_form['input'][$feature['id_feature']] = array(
                    'type' => 'checkbox',
                    'label' => $feature['name'],
                    'lang' => true,
                    'name' => $feature['id_feature'],
                    'values' => array(
                        'query' => array(),
                        'id' => 'id_feature_value',
                        'name' => 'name',
                    )
                );

                // Add checkbox value
                $feature_values = QiMultiFeatureApi::getValuesForFeatureId($feature['id_feature']);
                foreach ($feature_values as $value) {
                    $this->fields_form['input'][$value['id_feature']]['values']['query'][] = array(
                        'id_feature_value' => $value['id_feature_value'],
                        'name' => $value['value'],
                    );
                }
            }
        }

        return parent::renderForm();
    }

    /**
     * Assign the values of field
     * @param type $obj
     * @return type
     */
    public function getFieldsValue($obj)
    {
        $this->fields_value = parent::getFieldsValue($obj);
        $product_features = Product::getFeaturesStatic($obj->id);
        foreach ($product_features as $p_feature) {
            $this->fields_value[$p_feature['id_feature'].'_'.$p_feature['id_feature_value']] = true;
        }

        $product_custom_features = QiMultiFeatureApi::getCustomValuesByProductId((int)$obj->id);

        foreach ($product_custom_features as $custom_feature) {
            $this->fields_value[$custom_feature['id_feature']][$custom_feature['id_lang']] = $custom_feature['value'];
        }



        return $this->fields_value;
    }
    


    public function postProcess()
    {
        if (Tools::isSubmit('submitAddMultiFeature')) {
            $product = new Product((int)Tools::getValue('id_product'));

            // Delete all features
            $product->deleteFeatures();

            // Get the features
            $features = Feature::getFeatures($this->context->language->id);
            $languages = Language::getLanguages(false);
            foreach ($features as $feature) {
                // Verify if the feature have normal value (not custom)
                $has_normal_value = QiMultiFeatureApi::HasThisFeatureNormalValue((int)$feature['id_feature']);

                // If the feature have custom feature value, make an input, else make a checkbox selector
                if ($has_normal_value == false) {
                    $values = array();
                    // For each language, we get the post value
                    foreach ($languages as $language) {
                        if (Tools::getIsset($feature['id_feature'].'_'.$language['id_lang'])) {
                            $value_lang = Tools::getValue($feature['id_feature'].'_'.$language['id_lang']);
                            if (!empty($value_lang))
                                $values[$language['id_lang']] = $value_lang;
                        }
                    }
                    // Add the feature custom value to the product
                    if (!empty($values))
                        QiMultiFeatureApi::addCustomFeatureToProductId($product->id, $feature['id_feature'], $language['id_lang'], $values);
                } else {
                    // Add checkbox value
                    $feature_values = QiMultiFeatureApi::getValuesForFeatureId($feature['id_feature']);
                    foreach ($feature_values as $feature_value) {
                        $value_chk = Tools::getValue($feature['id_feature'].'_'.$feature_value['id_feature_value']);
                        // Add the feature value to the product
                        if (!empty($value_chk))
                            QiMultiFeatureApi::addFeatureToProduct($product->id, $feature['id_feature'], $feature_value['id_feature_value']);
                    }
                }
            }
        } else
            parent::postProcess();
    }

}
