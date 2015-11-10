<?php

class QiMultiFeatureApi
{

    /**
     * Get the features values for a product
     * @param type $id_product
     * @return type
     */
    public static function getCustomValuesByProductId($id_product)
    {
        return Db::getInstance()->executeS('
            SELECT *
            FROM '._DB_PREFIX_.'feature_product fp
            LEFT JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value)
            LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fv.id_feature_value)
            WHERE fv.custom = 1
            AND fp.id_product = '.pSQL((int)$id_product).'
        ');
    }

    /**
     * Add a custom feature to a product
     * @param int $id_product
     * @param int $id_feature
     * @param int $id_lang
     * @param array $values
     */
    public static function addCustomFeatureToProductId($id_product, $id_feature, $id_lang, $values)
    {
        if (empty($id_product) || empty($id_feature) || empty($id_lang) || empty($values))
            return false;

        // Create the new custom feature
        $custom_feature_value = new FeatureValue();
        $custom_feature_value->id_feature = (int)$id_feature;
        $custom_feature_value->custom = (bool)true;
        $custom_feature_value->value = $values;
        $custom_feature_value->add();

        // Associate the new feature to product
        self::addFeatureToProduct($id_product, $id_feature, $custom_feature_value->id);
    }

    /**
     * Assciocate the feature / value to a product
     * @param int $id_product
     * @param int $id_feature
     * @param int $id_feature_value
     */
    public static function addFeatureToProduct($id_product, $id_feature, $id_feature_value)
    {
        $datas = array(
            'id_feature' => (int)$id_feature,
            'id_product' => (int)$id_product,
            'id_feature_value' => (int)$id_feature_value);

        return Db::getInstance()->insert('feature_product', $datas);
    }

    /**
     * Get the values for a feature
     * @param int $id_feature
     * @return array
     */
    public static function getValuesForFeatureId($id_feature)
    {
        return Db::getInstance()->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'feature_value` v
            LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` vl ON (v.`id_feature_value` = vl.`id_feature_value` AND vl.`id_lang` = '.pSQL((int)Configuration::get('PS_LANG_DEFAULT')).')
            WHERE v.`id_feature` = '.pSQL((int)$id_feature).' AND v.custom = 0');
    }

    /**
     * Check if the feature have normal values
     * @param type $id_feature
     * @return boolean
     */
    public static function HasThisFeatureNormalValue($id_feature)
    {
        if (empty($id_feature))
            return false;

        $result = Db::getInstance()->getRow('SELECT custom FROM `'._DB_PREFIX_.'feature_value` WHERE `id_feature` = '.pSQL((int)$id_feature).' AND custom = 0');
        if (empty($result))
            return false;

        return true;
    }

}

?>