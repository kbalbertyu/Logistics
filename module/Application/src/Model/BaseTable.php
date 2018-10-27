<?php
namespace Application\Model;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Select;
use Zend\Log\Logger;

/**
 *
 * @author Albert Yu
 *
 */
class BaseTable {
    public const SETTING_TABLE = 'settings';
    public const USER_TABLE = 'user';
    public const TEAM_TABLE = 'team';
    public const PRODUCT_TABLE = 'products';
    public const BRAND_TABLE = 'brand';
    public const PACKAGE_TABLE = 'packages';
    public const CHARGE_TABLE = 'charges';
    public const SHIPPING_TABLE = 'shipping';
    public const ADDRESS_TABLE = 'address';
    public const BOX_TABLE = 'boxes';

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
        $select = $this->selectTable();
        if (!empty($columns)) {
            $select->columns($columns);
        }
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
        $rows = $this->getRowsByFields($fieldValueMappings, $columns);
        return $rows->count() ? $rows->current() : null;
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
            throw new $e;
        }
    }

    public function delete($id) {
        try {
            return $this->deleteBy([$this->primary => $id]);
        } catch (\Exception $e) {
            $this->logger->err(sprintf('Delete %s record failed: %s -> %s',
                $this->getTable(), $id, $e->getMessage()));
            throw new $e;
        }
    }

    public function deleteBy($where) {
        return $this->tableGateway->delete($where);
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
     * @param null|string $alias the table alias
     * @return Select
     */
    protected function selectTable($alias = null) {
        $select = new Select();
        $select->from(empty($alias) ? $this->getTable() : [$alias => $this->getTable()]);
        return $select;
    }

    protected function getInsertId() {
        return $this->tableGateway->getLastInsertValue();
    }

    /**
     * @return BaseModel
     */
    protected function getModel() {
        return $this->tableGateway->getResultSetPrototype()->getArrayObjectPrototype();
    }

    protected function where() {
        return new Where();
    }
}

