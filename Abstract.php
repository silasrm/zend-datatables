<?php

abstract class Application_Model_Abstract
{
    private static $_db = null;
    protected $_dbTable;

    public function getDb()
    {
        if( is_null(self::$_db) )
            self::$_db = Zend_Db_Table::getDefaultAdapter();

        return self::$_db;
    }

    public function getDbTable()
    {
        return $this->_dbTable;
    }

    public function count(array $conditions = null)
    {
        $sql = $this->_dbTable
                    ->getAdapter()
                    ->select()
                    ->from( $this->_dbTable->getName(), array( 'total' => 'COUNT(id)' ) );

        $this->_trataCondicoes( $sql, $conditions );

        $data = $sql->query()->fetch();

        return $data['total'];
    }

    protected function _trataCondicoes( Zend_Db_Select &$sql, array $conditions = null )
    {
        if (!is_null($conditions))
        {
            foreach ($conditions as $key => $condition)
            {
                if( !is_array($condition) )
                {
                    if( !is_numeric( $key ) )
                    {
                        $sql->where($key, $condition);
                    }
                    else
                    {
                        $sql->where($condition);
                    }
                }
                else
                {
                    if( array_key_exists(1, $condition) && $condition[1] == 'OR' )
                    {
                        $sql->orWhere($key, $condition[0]);
                    }
                    else
                    {
                        $sql->where($key, $condition);
                    }
                }
            }
        }
    }

    /**
     * Implementação do datasource para o DataTables
     *
     * @see http://datatables.net/examples/data_sources/server_side.html
     * @param array $params Variáveis do DataTables:
     *    - iDisplayStart: registro de início da contagem do limit
     *    - iDisplayLength: total de itens para retornar
     *    - iSortingCols: quantidade de colunas de ordenação
     *    - iSortCol_*: mapeador do contador das colunas de ordenação
     *    - bSortable_*: mapeador das colunas de ordenação, se estiver com valor true, é para ordenar por ela
     *    - sSortDir_*: mapeador da direção da ordem da coluna
     *    - sSearch: valor da busca geral
     *    - bSearchable_*: mapeador do contador das colunas com filtragem individual, se o valor é true, é pra filtrar
     *    - sSearch_*: valor da busca no campo individual
     * @param  string $pk nome da coluna de Primary Key
     * @param  array $cols nome das colunas que irão retornar e serão colunas no próprio datatables
     * @return  array coleção de dados de informações necessitadas para o funcionamento correto do DataTables
     */
    public function dataTables(array $params, $pk, $cols)
    {
        // Adiciona o SQL_CALC_FOUND_ROWS antes da primeira coluna;
        $_cols = $cols;
        $_cols = array_merge(
            array(new Zend_Db_Expr('SQL_CALC_FOUND_ROWS ' . $pk)),
            $_cols
        );

        $select = $this->getDbTable()->select();
        $select->from($this->getDbTable()->getName(), $_cols);

        // Filtro geral
        if( isset($params['sSearch']) && $params['sSearch'] != "" )
        {
            $where = array();
            for( $i=0 ; $i<count($cols) ; $i++ )
            {
                $where[] = $cols[$i]." LIKE '%" . $params['sSearch'] . "%'";
            }

            if(count($where) > 0 && !empty($where))
            {
                $select->where('( ' . implode(' OR ', $where) . ' )');
            }
        }

        // Filtro individual
        for( $i=0 ; $i<count($cols) ; $i++ )
        {
            if( isset($params['bSearchable_'.$i])
                && isset($params['sSearch_'.$i])
                && $params['bSearchable_'.$i] == "true"
                && $params['sSearch_'.$i] != '' )
            {
                $select->where($cols[$i] . ' LIKE ?', '%' . $params['sSearch_'.$i] . '%');
            }
        }

        // Ordenação
        if( isset( $params['iSortCol_0'] ) )
        {
            for( $i=0 ; $i<intval( $params['iSortingCols'] ) ; $i++ )
            {
                if( $params[ 'bSortable_'.intval($params['iSortCol_'.$i]) ] == "true" )
                {
                    $select->order($cols[intval($params['iSortCol_'.$i])] . ' ' . $params['sSortDir_'.$i]);
                }
            }
        }

        // Cria o limit
        if (isset($params['iDisplayStart']) && $params['iDisplayLength'] != '-1')
        {
            $select->limit($params['iDisplayLength'], $params['iDisplayStart']);
        }

        // Dados filtrados com limite
        $dados = $select->query()->fetchAll();


        // Pega o total de dados filtrados geral, sem o limite
        $selectFoundRows = $this->getDb()->query('SELECT FOUND_ROWS() as total');
        $dadosFoundRows = $selectFoundRows->fetch();
        $totalFiltrado = $dadosFoundRows['total'];

        // Pega o total de dados sem filtros
        $total = $this->count(null, $pk);

        return array(
            "sEcho" => intval($params['sEcho']),
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $totalFiltrado,
            "aaData" => $this->_dataTablesTransforma($dados, $cols)
        );
    }

    protected function _dataTablesTransforma($dados, $cols)
    {
        $retorno = array();

        foreach($dados as $r)
        {
            $linha = array();
            for ( $i=0 ; $i<count($cols) ; $i++ )
            {
                $linha[] = $r[$cols[$i]];
            }

            $retorno[] = $linha;
        }

        return $retorno;
    }
}