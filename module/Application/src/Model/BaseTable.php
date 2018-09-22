<?php
namespace Application\Model;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Zend\Log\Logger;

/**
 *
 * @author Albert Yu
 *
 */
class BaseTable {
    public const SETTING_TABLE = 'settings';

    /**
     *
     * @var AbstractTableGateway
     */
    protected $tableGateway;

    protected static $tableGateways = [];

    protected $primary = 'id';

    /**
     *
     * @var Logger
     */
    protected $logger;

    public function __construct(AbstractTableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $this->logger = BaseModel::getLogger();
    }

    protected function useCustomTableGateway(String $table) {
        self::$tableGateways[$this->tableGateway->getTable()] = $this->tableGateway;
        if (!empty(self::$tableGateways[$table])) {
            $this->tableGateway = self::$tableGateways[$table];
            return;
        }
        $this->tableGateway = $this->initCustomTableGateway($table);
    }

    /**
     * @param string $table
     * @return TableGateway
     */
    private function initCustomTableGateway($table) {
        $dbAdapter = $this->tableGateway->getAdapter();
        return new TableGateway($table, $dbAdapter, null, $this->tableGateway->getResultSetPrototype());
    }

    public function getTable() {
        return $this->tableGateway->getTable();
    }

    public function getCount($where = null) {
        $select = $this->selectTable()
            ->columns(['count' => new Expression('COUNT(*)')])
            ->limit(1);
        if ($where != null) {
            $select->where($where);
        }
        $row = $this->tableGateway->selectWith($select)->current();
        return $row->count;
    }

    /**
     * @param $id
     * @return BaseModel|null
     */
    public function getRowById($id) {
        $rowSet = $this->tableGateway->select([$this->primary => $id]);
        $row = $rowSet->current();
        return $row;
    }

    public function getRowsByIds($ids, array $columns = []) {
        $where = [$this->primary => $ids];
        if (empty($columns)) {
            return $this->tableGateway->select($where);
        }
        return $this->getResults($where, $columns);
    }

    public function getRows($where = null, array $columns = [], $offset = null, $limit = null) {
        $select = $this->selectTable()
            ->columns($columns);
        if ($where) {
            $select->where($where);
        }
        if ($offset) {
            $select->offset($offset);
        }
        if ($limit) {
            $select->limit($limit);
        }
        return $this->tableGateway->selectWith($select);
    }

    public function getRowByFields($fieldValueMappings, array $columns = []) {
        return $this->getRowsByFields($fieldValueMappings, $columns)->current();
    }

    public function getRowsByFields($fieldValueMappings, array $columns = []) {
        if (empty($columns)) {
            return $this->tableGateway->select($fieldValueMappings);
        }
        return $this->getResults($fieldValueMappings, $columns);
    }

    private function getResults($where, $columns) {
        $select = $this->selectTable()
            ->columns($columns)
            ->where($where);
        return $this->tableGateway->selectWith($select);
    }

    public function executeSql($sql) {
        $adapter = $this->tableGateway->getAdapter();
        $result = $adapter->query($sql)->execute();
        return $result;
    }

    public function getColumnValueList($column) {
        $select = $this->selectTable()
            ->columns([$column])
            ->group($column);
        $rows = $this->tableGateway->selectWith($select);
        $accountNos = array_column($rows->toArray(), $column);
        sort($accountNos);
        return $accountNos;
    }

    protected function debugSql(Select $select) {
        echo $select->getSqlString($this->tableGateway->getAdapter()->getPlatform());
    }

    public static function formatDataSetForMultiGraph($rows, $fields) {
        $dataSet = [];

        foreach ($fields as $field) {
            $data = [];
            $data['seriesname'] = BaseModel::deliciousCamelcase($field);
            $values = [];
            foreach ($rows as $each) {
                $values[] = [
                    'value' => $each->$field
                ];
            }
            $data['data'] = $values;
            $dataSet[] = $data;
        }
        return $dataSet;
    }

    public function save($data) {
        try {
            return !$this->getRowById($data[$this->primary]) ?
                $this->tableGateway->insert($data) :
                $this->tableGateway->update($data, [$this->primary => $data[$this->primary]]);
        } catch (\Exception $e) {
            $file = BaseModel::dumpVariable($data, 'BaseTable-save', false, false);
            $this->logger->err(sprintf('Insert data error: %s -> %s', $e->getMessage(), $file));
            throw $e;
        }
    }

    public function add($data) {
        try {
            return $this->tableGateway->insert($data);
        } catch (\Exception $e) {
            $file = BaseModel::dumpVariable($data, 'BaseTable-add', false, false);
            $this->logger->err(sprintf('Insert data error: %s -> %s', $e->getMessage(), $file));
            throw $e;
        }
    }

    public function update($data, $id) {
        return $this->updateByWhere($data, [$this->primary => $id]);
    }

    public function updateByWhere($data, $where) {
        try {
            return $this->tableGateway->update($data, $where);
        } catch (\Exception $e) {
            $file = BaseModel::dumpVariable($data, 'BaseTable-update', false, false);
            $this->logger->err(sprintf('Update data error: %s -> %s', $e->getMessage(), $file));
            throw $e;
        }
    }

    protected function getColumnValueSet($column) {
        $where = new Where();
        $where->notEqualTo($column, '')
            ->isNotNull($column);

        $select = $this->selectTable()
            ->columns([$column])
            ->group($column)
            ->order($column)
            ->where($where);
        $rows = $this->tableGateway->selectWith($select);
        if ($rows->count() == 0) {
            return [];
        }
        return array_column($rows->toArray(), $column);
    }

    /**
     * @return Select
     */
    protected function selectTable() {
        $select = new Select();
        $select->from($this->getTable());
        return $select;
    }

    protected function getInsertId() {
        return $this->tableGateway->getLastInsertValue();
    }
}

