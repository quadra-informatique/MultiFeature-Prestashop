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

include_once(PS_ADMIN_DIR . '/tabs/AdminCatalog.php');

class AdminMultiFeature extends AdminTab {

    private $module = 'quadramultifeature';
	
    public function __construct() {
        $this->table = 'product';
		$this->className = 'Product';
        $this->lang = true;
        $this->edit = true;
        //$this->view = true;
        $this->delete = false;
        $this->deleted = false;

       $this->fieldsDisplay = array(
       		//'id_product' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 20),
       		'name' => array('title' => $this->l('NOM'), 'width' => 220,'filter_key' => 'b!name'),
			'reference' => array('title' => $this->l('REFERENCE'),'align' => 'center', 'width' => 20)
        );
        	
        parent::__construct();
    }
    
	function displayForm($token = NULL) {
		
        global $currentIndex, $cookie;
        parent::displayForm();  

        //$obj object courant =>product on which we clicks
		if (!($obj = $this->loadObject(true)))
			return;
		
		$product_features = Product::getFeaturesStatic($obj->id);	
		$features = Feature::getFeatures($cookie->id_lang);
		$current_product = new Product($obj->id);
		$product_name = $current_product->name[$cookie->id_lang];

		echo '
			<form action="'.$currentIndex.'&submitAddMultiFeature=1&token='.Tools::getValue('token').'" method="post" enctype="multipart/form-data" name="product" id="product">
			<input type="hidden" name="idProduct" id="idProduct" value="'.$obj->id.'" />';
		echo '<h3>'.$product_name.'</h3>';
		echo '
			<table class="table" style="width: 600px; margin-top: 10px">
			<tr><th>'.$this->l('Caracteristics').'</th><th>'.$this->l('Defined values').'</th></tr>';
			if($features)
				foreach ($features as $feature){
			echo 
			'<tr>
				<td class="col-left">'.$feature['name'].'<br/></td>
				<td>
					<div id="divMultifeatures">';
					$list_features = FeatureValue::getFeatureValuesWithLang($cookie->id_lang,$feature['id_feature']);
					
					if($list_features)
						foreach ($list_features as $feat){
							$this->_html = '<input type="checkbox" style="margin-left:20px;"';
							
							foreach ($product_features as $product_feature){
								if($product_feature['id_feature_value'] == $feat['id_feature_value']){
									$this->_html .= 'checked="checked"';
								}
							}
							$this->_html .= 'name="feature_value[]" value="'.$feat['id_feature_value'].'">'.$feat['value'].'<br/>'; 
							echo $this->_html;
						}	
			echo '</div>
				</td>
			</tr>';
			}
			echo '
			<tr>
				<td colspan="2" style="text-align:center;border:none;">
					<input type="submit" value="'.$this->l('Save').'" name="submitAddMultiFeature" class="button" />
				</td>
			</tr>
			</table></form>';
    }
	/**
	 * Display list
	 *
	 * @global string $currentIndex Current URL in order to keep current Tab
	 */
	public function displayList()
	{
		global $currentIndex;

		$this->displayTop();
		//if ($this->edit AND (!isset($this->noAdd) OR !$this->noAdd)) // Add new on top of table
		//echo '<br /><a href="'.$currentIndex.'&add'.$this->table.'&token='.$this->token.'"><img src="../img/admin/add.gif" border="0" /> '.$this->l('Add new').'</a><br /><br />';
		/* Append when we get a syntax error in SQL query */
		if ($this->_list === false)
		{
			$this->displayWarning($this->l('Bad SQL query'));
			return false;
		}

		/* Display list header (filtering, pagination and column names) */
		$this->displayListHeader();
		if (!sizeof($this->_list))
			echo '<tr><td class="center" colspan="'.(sizeof($this->fieldsDisplay) + 2).'">'.$this->l('No items found').'</td></tr>';

		/* Show the content of the table */
		$this->displayListContent();

		/* Close list table and submit button */
		$this->displayListFooter();
	}
	/**
     * 
     * @param $id_product
     * @param $id_feature
     * @param $id_feature_values
     * @return delete previous records for the given id_product and id_feature and insert new records.
     */
	public function saveMultiFeature($id_product,$id_feature_values){

		global $currentIndex, $cookie;
    	$id_lang = $cookie->id_lang;
    	
		$query = 'DELETE FROM `'._DB_PREFIX_.'feature_product` WHERE id_product = '.(int)$id_product;
			
		if (!Db::getInstance()->Execute($query)){
			return false;
		}
    	
		foreach($id_feature_values as $id_feature_value){
			$id_feature = FeatureValue::getFeatureId($id_feature_value);
				$quer ='INSERT INTO  `' . _DB_PREFIX_ . 'feature_product` (
													`id_feature`,
		                                            `id_product` ,
		                                            `id_feature_value`
		                                            )VALUES (
		                                ' .$id_feature . ',            
		                                ' .$id_product . ',
		                               ' . $id_feature_value . '
		                                )';
	    	if (!Db::getInstance()->Execute($quer))
	            return false;
		}
		return true;
    }
    
	public function displayListContent($token = NULL) {
    
    	global $currentIndex, $cookie;
    	$id_lang = $cookie->id_lang;
    	
    	$id_category = 1; // default categ
		
		$irow = 0;
//		if ($this->_list /*AND isset($this->fieldsDisplay['position'])*/) {
//			$positions = array_map(create_function('$elem', 'return (int)($elem[\'position\']);'), $this->_list);
//		    sort($positions);
//		}
		if ($this->_list) {
		    $isCms = false;
		    if (preg_match('/cms/Ui', $this->identifier))
			$isCms = true;
		    $keyToGet = 'id_' . ($isCms ? 'cms_' : '') . 'category' . (in_array($this->identifier, array('id_category', 'id_cms_category')) ? '_parent' : '');

		    foreach ($this->_list AS $tr)
		    {
				$id = $tr[$this->identifier];
				echo '<tr' . (array_key_exists($this->identifier, $this->identifiersDnd) ? ' id="tr_' . (($id_category = (int) (Tools::getValue('id_' . ($isCms ? 'cms_' : '') . 'category', '1'))) ? $id_category : '') . '_' . $id . '"' : '') . ($irow++ % 2 ? ' class="alt_row"' : '') . '>
				<td class="center">';
				if ($this->delete AND (!isset($this->_listSkipDelete) OR !in_array($id, $this->_listSkipDelete)))
					echo '<input type="checkbox" name="' . $this->table . 'Box[]" value="' . $id . '" class="noborder" />';
					echo '</td>';
	    	
		    	foreach ($this->fieldsDisplay AS $key => $params) {
		       		$tmp = explode('!', $key);
		            $key = isset($tmp[1]) ? $tmp[1] : $tmp[0];
		
		            echo '<td ' . (isset($params['position']) ? ' id="td_' . (isset($id_category) AND $id_category ? $id_category : 0) . '_' . $id . '"' : '') . ' class="' . ((!isset($this->noLink) OR !$this->noLink) ? 'pointer' : '') . ((isset($params['position']) AND $this->_orderBy == 'position') ? ' dragHandle' : '') . (isset($params['align']) ? ' ' . $params['align'] : '') . '" ';
		            if (!isset($params['position']) AND (!isset($this->noLink) OR !$this->noLink))
		               echo ' onclick="document.location = \'' . $currentIndex . '&' . $this->identifier . '=' . $id . ($this->view ? '&update' : '&update') . $this->table . '&token=' . ($token != NULL ? $token : $this->token) . '\'">' . (isset($params['prefix']) ? $params['prefix'] : '');
		            else
		               echo '>';
//		    		if ($key =="id_product"){
//		    			echo $tr[$key];
//		    		}            
		    		if ($key =="name"){
		    			echo $tr[$key];
		    		}
		    		if ($key =="reference"){
		    			echo $tr[$key];
		    		}
		    	}
		    	if ($this->edit OR $this->delete OR ($this->view AND $this->view !== 'noActionColumn')) {
		        	echo '<td class="center" style="white-space: nowrap;">';
		               if ($this->edit)
		                  $this->_displayEditLink($token, $id);
		               if ($this->delete AND (!isset($this->_listSkipDelete) OR !in_array($id, $this->_listSkipDelete)))
		                  $this->_displayDeleteLink($token, $id);
		               if ($this->duplicate)
		                  $this->_displayDuplicate($token, $id);
		               echo '</td>';
		       }
      		   echo '</tr>';
            }
        }
    }

	public function postProcess($token = NULL) {
    	
		if (Tools::isSubmit('submitAddMultiFeature')) {
        	$idProduct = (int) Tools::getValue('idProduct');
        	$id_feature_values = Tools::getValue('feature_value');
        	$this->saveMultiFeature($idProduct,$id_feature_values);
        }else{
        	parent::postProcess();
        }
    }
}

?>
