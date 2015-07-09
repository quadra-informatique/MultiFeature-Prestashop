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
		$this->fields_list = array(
			'name' => array('title' => $this->l('Name'), 'filter_key' => 'b!name'),
			'reference' => array('title' => $this->l('Reference'), 'align' => 'center')
		);
	}

	public function renderForm()
	{
		if (!($obj = $this->loadObject(true)))
			return;

		$product_features = Product::getFeaturesStatic($obj->id);
		$features = Feature::getFeatures($this->context->language->id);
		$current_product = new Product($obj->id);
		$product_name = $current_product->name[$this->context->language->id];
		$languages = Language::getLanguages(false);
		$id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');

		$ids = '';
		$test = '';
		foreach ($product_features as $product_feature)
		{
			$its = '';
			$custom = $this->getCustomValue($product_feature['id_feature'], $product_feature['id_feature_value']);
			if ($custom == 1)
			{
				$ids = 'txtfeature'.$product_feature['id_feature'].'¤';
				$its .= $ids;
			}
			$test .= $its;
		}
		$idents = Tools::substr($test, 0, strrpos($test, '¤'));

		$this->_html = '<div class="panel col-lg-12">';
		$this->_html .= '
            <form action="'.self::$currentIndex.'&submitAddMultiFeature=1&token='.$this->token.'" method="post" enctype="multipart/form-data"
				name="product" id="product"><input type="hidden" name="id_product" id="id_product" value="'.$obj->id.'" />';
		$this->_html .= '<div class="panel-heading">'.$product_name.'</div>';
		$this->_html .= '<div class="table-responsive clearfix">
            <table class="table">
			<thead>
            <tr><th>'.$this->l('Caracteristics').'</th><th>'.$this->l('Defined values').'</th></tr>
			</thead><tbody>';
		if ($features)
			foreach ($features as $feature)
			{
				$this->_html .=
						'<tr>
                         <td class="col-left">'.$feature['name'].'<br/></td>
                         <td>
                         <div id="divMultifeatures">';
				$list_features = Db::getInstance()->executeS('SELECT *
                                                            FROM `'._DB_PREFIX_.'feature_value` v
                                                            LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` vl
                                                            ON (v.`id_feature_value` = vl.`id_feature_value`
															AND vl.`id_lang` = '.(int)$this->context->language->id.')
                                                            WHERE v.`id_feature` = '.(int)$feature['id_feature']);

				foreach ($list_features as $feat)
				{
					$as_value = false;
					if ($feat['custom'] == 0)
					{

						$this->_html .= '<input type="checkbox"';
						foreach ($product_features as $product_feature)
						{
							if ($product_feature['id_feature_value'] == $feat['id_feature_value'])
								$this->_html .= 'checked="checked"';
						}
						$this->_html .= 'name="feature_value_'.$feat['id_feature'].'[]" value="'.$feat['id_feature_value'].'">'.$feat['value'].'<br/>';

						//cas checkbox et input for the same id_feature

						$id_feat_val = Db::getInstance()->getValue('SELECT `id_feature_value`
                                                            FROM `'._DB_PREFIX_.'feature_product` v
                                                            WHERE v.`id_feature` = '.(int)$feat['id_feature'].' AND `id_product`='.$obj->id);

						$custom = $this->getCustomValue($feat['id_feature'], $id_feat_val);
						if ($custom == 1) //if exist customized value for id_feature =>remove check box
							$this->_html .= '';
					}
					else
					{
						$as_value = false;
						foreach ($product_features as $product_feature)
						{
							if ($product_feature['id_feature_value'] == $feat['id_feature_value'])
							{
								$as_value = true;
								$carac = $this->getValueFeat($product_feature['id_feature_value']);

								foreach ($languages as $language)
								{
									$as_lang_value = false;
									foreach ($carac as $val)
									{
										if ($val['id_lang'] == $language['id_lang'])
										{
											$as_lang_value = true;
											$this->_html .= '<div id="txtfeature'.$feat['id_feature'].'_'.$language['id_lang'].'"
													style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';">';
											$this->_html .= '<textarea  name="txtfeature_'.$feat['id_feature'].'['.$language['id_lang'].']" >';
											$this->_html .= $val['value'];
											$this->_html .= '</textarea>';
											$this->_html .= '</div>';
										}
									}
									if (!$as_lang_value)
									{
										$this->_html .= '<div id="txtfeature'.$feat['id_feature'].'_'.$language['id_lang'].'"
												style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';">';
										$this->_html .= '<textarea  name="txtfeature_'.$feat['id_feature'].'['.$language['id_lang'].']" >';
										$this->_html .= '</textarea>';
										$this->_html .= '</div>';
									}
								}

								$this->_html .= $this->displayFlags($languages, $id_lang_default, $idents, 'txtfeature'.$feat['id_feature'], true);
							}
						}
					}
				}
				if (!$as_value && $feat['custom'] == 1)
				{
					foreach ($languages as $language)
					{

						$this->_html .= '<div id="txtfeature'.$feat['id_feature'].'_'.$language['id_lang'].'"
								style="display: '.($language['id_lang'] == $id_lang_default ? 'block' : 'none').';">';
						$this->_html .= '<textarea  name="txtfeature_'.$feat['id_feature'].'['.$language['id_lang'].']" >';
						$this->_html .= '</textarea>';
						$this->_html .= '</div>';
					}

					$this->_html .= $this->displayFlags($languages, $id_lang_default, 'txtfeature'.$feat['id_feature'].'¤',
							'txtfeature'.$feat['id_feature'], true);
				}
				$this->_html .= '</div>
                        </td>
                    </tr>';
			}
		$this->_html .= '
            <tr>
                <td colspan="2" style="text-align:center;border:none;">
                    <input type="submit" value="'.$this->l('Save').'" name="submitAddMultiFeature" class="btn btn-default" />
                </td>
            </tr></tbody>
            </table></form>
			</div></div>';
		return $this->_html;
	}

	/**
	 *
	 * @param type $id_feature_value
	 * @return null
	 */
	public function getValueFeat($id_feature_value)
	{
		$query = Db::getInstance()->ExecuteS('SELECT `id_lang`,`value` FROM '._DB_PREFIX_.'feature_value_lang
				WHERE `id_feature_value`='.$id_feature_value);
		if (isset($query))
			return $query;
		else
			return null;
	}

	public function displayFlags($languages, $default_language, $ids, $id, $return = false, $use_vars_instead_of_ids = false)
	{
		if (count($languages) == 1)
			return false;
		$output = '
		<div class="displayed_flag">
			<img src="../img/l/'.$default_language.'.jpg" class="pointer" id="language_current_'.$id.'" onclick="toggleLanguageFlags(this);" alt="" />
		</div>
		<div id="languages_'.$id.'" class="language_flags">
			'.$this->l('Choose language:').'<br /><br />';
		foreach ($languages as $language)
			if ($use_vars_instead_of_ids)
				$output .= '<img src="../img/l/'.(int)$language['id_lang'].'.jpg" class="pointer"
					alt="'.$language['name'].'" title="'.$language['name'].'"
					onclick="changeLanguage(\''.$id.'\', '.$ids.', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
			else
				$output .= '<img src="../img/l/'.(int)$language['id_lang'].'.jpg" class="pointer" alt="'.$language['name'].'"
					title="'.$language['name'].'"
					onclick="changeLanguage(\''.$id.'\', \''.$ids.'\', '.$language['id_lang'].', \''.$language['iso_code'].'\');" /> ';
		$output .= '</div>';

		if ($return)
			return $output;
		echo $output;
	}

	/**
	 *
	 * @param string $name
	 * @return get id feature value
	 */
	public function getIdFeatureValue($name, $id_feature)
	{
		$name = "'".str_replace("'", "\'", $name)."'";

		return Db::getInstance()->getRow('SELECT fv.`id_feature_value`,fv.`id_feature`,`id_lang` FROM `'._DB_PREFIX_.'feature_value_lang` fvl
                                            LEFT JOIN `'._DB_PREFIX_.'feature_value` fv ON (fv.`id_feature_value` = fvl.`id_feature_value`)
                                            WHERE `value` LIKE "%'.pSQL($name).'%" AND fv.`id_feature`='.(int)$id_feature);
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitAddMultiFeature'))
		{
			$id_product = (int)Tools::getValue('id_product');
			$product = new Product((int)Tools::getValue('id_product'));
			$product->deleteFeatures();
			$features = Db::getInstance()->executeS('SELECT `id_feature` FROM `'._DB_PREFIX_.'feature`');

			foreach ($features as $id)
			{
				//customized values
				$text_feature = Tools::getValue('txtfeature_'.$id['id_feature']);
				if (!empty($text_feature))
				{
					$id_feature_value = $this->addCustomFeaturesToDB($id['id_feature'], 1);
					foreach ($text_feature as $id_lang => $value)
						$product->addFeaturesCustomToDB($id_feature_value, $id_lang, $value);
					$this->addFeatureToProduct($id['id_feature'], $id_product, $id_feature_value);
				}
				//non customized values
				$id_feature_values = Tools::getValue('feature_value_'.$id['id_feature']);
				if (!empty($id_feature_values))
				{
					foreach ($id_feature_values as $id_feature_value)
						$this->addFeatureToProduct($id['id_feature'], $id_product, $id_feature_value);
				}
			}
		}
		else
			parent::postProcess();
	}

	/**
	 *
	 * @param type $id_feature
	 * @param type $custom
	 * @return add feature in feature_value table
	 */
	public function addCustomFeaturesToDB($id_feature, $custom)
	{
		$row = array('id_feature' => (int)$id_feature, 'custom' => (int)$custom);
		Db::getInstance()->insert('feature_value', $row);
		$id_value = Db::getInstance()->Insert_ID();
		return $id_value;
	}

	/**
	 *
	 * @param type $id_feature
	 * @param type $id_product
	 * @param type $id_feature_value
	 * @return add feature to the product
	 */
	public function addFeatureToProduct($id_feature, $id_product, $id_feature_value)
	{
		$row = array('id_feature' => (int)$id_feature, 'id_product' => (int)$id_product, 'id_feature_value' => (int)$id_feature_value);
		Db::getInstance()->insert('feature_product', $row);
		return true;
	}

	/**
	 *
	 * @param type $id_feature
	 * @param type $id_feature_value
	 * @return null
	 */
	public function getCustomValue($id_feature, $id_feature_value)
	{
		if ((int)$id_feature && (int)$id_feature_value)
		{
			$query = Db::getInstance()->getValue('SELECT `custom` FROM '._DB_PREFIX_.'feature_value WHERE `id_feature`='.(int)$id_feature.'
					AND `id_feature_value`='.(int)$id_feature_value);

			if (isset($query))
				return $query;
		}
		return null;
	}

}
