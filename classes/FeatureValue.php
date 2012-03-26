<?php
class FeatureValue extends FeatureValueCore
{
	/**
	 * 
	 * @param $id_feature_value
	 * @return $id_feature
	 */
	public function getFeatureId($id_feature_value){
		
		$queries = Db::getInstance()->ExecuteS('
	                           SELECT id_feature FROM `' . _DB_PREFIX_ . 'feature_value`
	                           WHERE id_feature_value = '.$id_feature_value);
		
		foreach($queries as $query){
			$id_feature = $query['id_feature'];
		}
		return $id_feature;
	}
	
}