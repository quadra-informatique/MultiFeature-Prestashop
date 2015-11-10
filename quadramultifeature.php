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
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once _PS_MODULE_DIR_.'quadramultifeature/classes/QiMultiFeatureApi.php';


class QuadraMultiFeature extends Module
{

	public function __construct()
	{
		$this->name = 'quadramultifeature';
		$this->author = 'Quadra Informatique';
		$this->tab = 'administration';
		$this->version = '1.3.0';

		parent::__construct();

		$this->displayName = $this->l('Configure caracteristics for products');
		$this->description = $this->l('Add several value for the same caracteristic of a product');
	}

	public function install()
	{
		$id_lang_en = LanguageCore::getIdByIso('en');
		$id_lang_fr = LanguageCore::getIdByIso('fr');
		$this->installModuleTab('AdminMultifeature', array($id_lang_fr => 'Caractéristiques multiples', $id_lang_en => 'Multi features'), 9);
		$query = 'ALTER TABLE '._DB_PREFIX_.'feature_product DROP PRIMARY KEY ,
        ADD PRIMARY KEY ( `id_feature` , `id_product` , `id_feature_value` )';

		if (!Db::getInstance()->Execute($query))
			return false;
		if (parent::install() == false)
			return false;
		return true;
	}

	private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
	{
		$tab = new Tab();
		$tab->name = $tab_name;
		$tab->class_name = $tab_class;
		$tab->module = $this->name;
		$tab->id_parent = (int)$id_tab_parent;
		if (!$tab->save())
			return false;
		return true;
	}

	private function uninstallModuleTab($tab_class)
	{
		$id_tab = Tab::getIdFromClassName($tab_class);
		if ($id_tab != 0)
		{
			$tab = new Tab($id_tab);
			$tab->delete();
			return true;
		}
		return false;
	}

	public function uninstall()
	{
		$this->uninstallModuleTab('AdminMultifeature');
		if (!parent::uninstall())
			return false;
		return true;
	}
}
