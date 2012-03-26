<?php
/*
* 1997-2012 Quadra Informatique
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0) that is available
* through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
* If you are unable to obtain it through the world-wide-web, please send an email
* to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
*
*  @author Quadra Informatique SARL <ecommerce@quadra-informatique.fr>
*  @copyright 1997-2012 Quadra Informatique
*  @version Release: $Revision: 1.0 $
*  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
*/

class QuadraMultiFeature extends Module {

    public function __construct() {
        $this->name = 'quadramultifeature';
        $this->author = 'Quadra Informatique';
        $this->tab = 'quadra';
        $this->version = 1.1;

        parent::__construct();

        $this->displayName = $this->l('Configure caracteristics for products');
        $this->description = $this->l('Add several value for the same caracteristic of a product');
    }
    
     function install() {
        
        $tab = new Tab();
        $tab->id_parent = 1;
        $tab->name = array(Language::getIdByIso('fr') => 'Caractéristiques multiples');

        $tab->class_name = 'AdminMultiFeature';
        $tab->module = 'quadramultifeature';
        $tab->add();
        
        $query = 'ALTER TABLE '. _DB_PREFIX_ .'feature_product DROP PRIMARY KEY ,
        ADD PRIMARY KEY ( `id_feature` , `id_product` , `id_feature_value` )';
       
        /*$query2 = 'ALTER TABLE '._DB_PREFIX_ .'feature_product DROP INDEX `id_feature_value`';*/
        
        if (!Db::getInstance()->Execute($query) /*|| !Db::getInstance()->Execute($query2)*/)
            return false;
        
        if (parent::install() == false)
            return false;    
            
		return true;   
     }
     
	function uninstall() {
    	
        if(!parent::uninstall())
        	return false;
        return true;
    }
    
	
}  