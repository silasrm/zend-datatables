<?php

abstract class Application_Model_Abstract {

    protected $_dbTable;

    public function dataTables(array $params = null, $colunas) 
    {
        $select = $this->_dbTable->select();
        if (!is_null($conditions)) 
        {
            foreach ($conditions as $key => $condition) 
            {
                $select->where($key, $condition);
            }
        }
        
        if (!is_null($params['sSearch'])) 
        {
        	foreach ($colunas as $key => $coluna)
        	{
        		$select->where($coluna . " like '%" . $params['sSearch'] . "%'" );
        	}
        }
        
        if (isset($params['iDisplayStart']) && $params['iDisplayLength'] != '-1')
        {
        	$select->limit($params['iDisplayStart'], $params['iDisplayLength']);
        }        
       
        return $select->query()->select();
    }
}    
