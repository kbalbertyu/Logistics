<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 10/24/2018
 * Time: 11:01 PM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class BoxTable extends BaseTable {

    public const DAYS_START_CHARGE = 10;

    private const STORAGE_FEE_PER_VOLUME = 10;

    public function getStorageFees($teamIds) {
        if (empty($teamIds)) {
            return new ResultSet(new Box());
        }
        $daysDiff = 'DATEDIFF(dateOut, dateIn) - ' . self::DAYS_START_CHARGE;
        $daysDiffExp = new Expression($daysDiff);
        $where = $this->where()
            ->in('b.teamId', $teamIds)
            ->greaterThan($daysDiffExp, 0)
            ->in('p.status', Package::SHIPPED_STATUS);
        $select = $this->selectTable('b')
            ->join(['p' => BaseTable::PACKAGE_TABLE], 'b.outPackageId=p.id', [])
            ->columns([
                'teamId',
                'fee' => new Expression('SUM(volume * (' . $daysDiff . ') * ' . self::STORAGE_FEE_PER_VOLUME . ')')
            ])
            ->where($where)
            ->group('b.teamId');
        return $this->tableGateway->selectWith($select);
    }

    public function getByShippedPackage($packageIds, $outDate = null) {
        if (empty($packageIds)) {
            return new ResultSet(new Box());
        }
        $daysDiff = new Expression('DATEDIFF(dateOut, dateIn) - ' . self::DAYS_START_CHARGE);
        $where = $this->where()
            ->in('outPackageId', $packageIds)
            ->greaterThan($daysDiff, 0);
        if (!empty($outDate)) {
            $where->like('b.dateOut', $outDate . '%');
        }
        $select = $this->selectTable('b')
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'b.productId=p.id', ['itemName'], Select::JOIN_LEFT)
            ->join(['t' => BaseTable::TEAM_TABLE], 'p.teamId=t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->columns([
                'qtyOut' => new Expression('SUM(qtyOut)'),
                'volume' => new Expression('SUM(volume)'),
                'weight' => new Expression('SUM(weight)'),
                'count' => new Expression('COUNT(*)'),
                'days' => $daysDiff,
                'dateIn',
                'dateOut',
                'outPackageId'
            ])
            ->where($where)
            ->group(new Expression('CONCAT(outPackageId,dateIn,dateOut)'))
            ->order('dateOut DESC');
        return $this->tableGateway->selectWith($select);
    }

    public function saveReceivedBoxes($data) {
        $data['caseQty'] = (int) trim($data['caseQty']);
        if (!$data['caseQty']) {
            return;
        }
        $boxes = $this->getBoxesByReceivedPackage($data['packageId']);

        $volume = round(($data['width'] * $data['length'] * $data['height'])/1000000, 2);
        $qtyUnit = (int) $data['qty']/$data['caseQty'];

        // Update existing boxes
        if ($boxes->count() > 0) {
            $ids = array_column($boxes->toArray(), 'id');
            $this->updateByWhere([
                'volume' => $volume,
                'weight' => $data['weight'],
                'qty' => $qtyUnit
            ], ['id' => $ids]);
        }

        $diff = $data['caseQty'] - $boxes->count();

        // Box qty not changed, exit the process
        if ($diff == 0) {
            return;
        }

        // Less than existing boxes, delete the extra ones
        if ($diff < 0) {
            $rows = array_slice($boxes->toArray(), $data['caseQty'], abs($diff));
            $boxIds = array_column($rows, 'id');
            $this->deleteBy(['id' => $boxIds]);
            return;
        }

        // More than existing boxes, add the extra ones
        for ($i = 0; $i < $diff; $i++) {
            $this->add([
                'teamId' => $data['teamId'],
                'productId' => $data['productId'],
                'inPackageId' => $data['packageId'],
                'qty' => $qtyUnit,
                'volume' => $volume,
                'weight' => $data['weight'],
                'dateIn' => date('Y-m-d'),
            ]);
        }
    }

    public function shipOutBoxes($data) {
        $qtyNeeded = $data['qtyNeeded'];
        // Qty not changed
        if ($qtyNeeded == 0) {
            return;
        }

        // Qty decreased
        if ($qtyNeeded < 0) {
            $this->resetExtraShippedBoxes($data['packageId'], abs($qtyNeeded));
            return;
        }

        //Else, qty increased
        $unShippedBoxes = $this->getUnShippedBoxesByProduct($data['productId']);

        foreach ($unShippedBoxes as $box) {
            $qty = $box->qty - $box->qtyOut;
            $id = $box->id;

            // If all needed are shipped out
            if ($qtyNeeded == 0) {
                break;
            }

            if ($qtyNeeded >= $qty) {
                $qtyNeeded -= $qty;
                if (!$box->shipped()) {
                    $this->setShipped($id, $data['packageId']);
                }
                $this->setOutQty($id, $box->qty);
                continue;
            }

            $totalOutQty = $qtyNeeded + $box->qtyOut;
            $this->setOutQty($id, $totalOutQty);

            // If half box or more are shipped, treat as all shipped
            if (($totalOutQty)/$box->qty >= 1/2) {
                if (!$box->shipped()) {
                    $this->setShipped($id, $data['packageId']);
                }
            } elseif ($box->shipped()) {
                $this->setUnShipped($id);
            }
            break;
        }
    }

    private function getUnShippedBoxesByProduct($productId) {
        $where = new Where();
        $where->equalTo('productId', $productId)
            ->nest()
            ->equalTo('outPackageId', 0)
            ->or
            ->greaterThan(new Expression('qty-qtyOut'), 0)
            ->unnest();
        $select = $this->selectTable()
            ->where($where)
            ->order('id');
        return $this->tableGateway->selectWith($select);
    }

    private function resetExtraShippedBoxes($packageId, $extraQty) {
        $boxes = $this->getBoxesBy($packageId, 'outPackageId', false);
        foreach ($boxes as $box) {
            if ($extraQty <= 0) {
                break;
            }
            $qtyOut = $box->qtyOut;
            $id = $box->id;

            if ($extraQty >= $qtyOut) {
                $extraQty -= $qtyOut;
                $this->setOutQty($id, 0);
                if ($box->shipped()) {
                    $this->setUnShipped($id);
                }
                continue;
            }

            $remainQty = $qtyOut - $extraQty;
            $this->setOutQty($id, $remainQty);
            if ($remainQty/$box->qty >= 1/2) {
                // If half box or more are shipped, treat as shipped out
                if ($box->shipped()) {
                    $this->setShipped($id, $packageId);
                }
            } else {
                $this->setUnShipped($id);
            }
            break;
        }
    }

    private function getBoxesByReceivedPackage($packageId) {
        return $this->getBoxesBy($packageId, 'inPackageId');
    }

    private function getBoxesBy($value, $field, $asc = true) {
        $select = $this->selectTable()
            ->where([$field => $value])
            ->order('id ' . ($asc ? 'ASC' : 'DESC'));
        return $this->tableGateway->selectWith($select);
    }

    private function setShipped($id, $outPackageId) {
        return $this->update([
            'outPackageId' => $outPackageId,
            'dateOut' => date('Y-m-d')
        ], $id);
    }

    private function setUnShipped($id) {
        return $this->update([
            'outPackageId' => 0,
            'dateOut' => null
        ], $id);
    }

    private function setOutQty($id, $qty) {
        return $this->update([
            'qtyOut' => $qty
        ], $id);
    }
}