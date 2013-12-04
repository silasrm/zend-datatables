abstract class Application_Model_Abstract {

    protected $_dbTable;

    public function dataTables(array $params = null) 
    {
        $select = $this->_dbTable->select();
        if (!is_null($conditions)) 
        {
            foreach ($conditions as $key => $condition) 
            {
                $select->where($key, $condition);
            }
        }
        $sLimit = null;
        if (isset($params['iDisplayStart']) && $params['iDisplayLength'] != '-1')
        {
        	$select->limit($params['iDisplayStart'], $params['iDisplayLength']);
        }        
       
        return $select->query()->fetchAll();
    }
}    
