<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/11/2018
 * Time: 3:04 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\BrandTable;
use Logistics\Model\History;
use Logistics\Model\ProductTable;
use Logistics\Model\HistoryTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;

/**
 * @property HistoryTable table
 * @property ProductTable productTable
 */
class InventoryController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(HistoryTable::class);
        $this->productTable = $this->getTableModel(ProductTable::class);
        $this->nav = 'inventory';
    }

    public function indexAction() {
        $this->title = 'Inventory History Records';
        $histories = $this->table->getInventoryList();
        return new ViewModel([
            'histories' => $histories
        ]);
    }

    public function productsAction() {
        $this->title = 'Product List';
        $products = $this->productTable->getProducts();
        return new ViewModel([
            'products' => $products
        ]);
    }

    public function addAction() {
        $output = [
            'valid' => false,
            'message' => ''
        ];

        // Process Type
        $type = $this->params()->fromQuery('type');
        if (empty($type)) {
            $this->flashMessenger()->addErrorMessage('Invalid parameter.');
            $this->redirect()->toRoute('inventory');
        }
        $output['type'] = $type;
        $this->title = $type == 'in' ? 'Receive(收货)' : 'Send Out(发货)';

        // Team
        $teamId = $this->params()->fromQuery('teamId');
        if (!empty($teamId)) {
            $team = $this->getTableModel(TeamTable::class)->getRowById($teamId);
            $output['team'] = $team;
        }

        // Product
        $productId = $this->params()->fromQuery('productId');
        if (!empty($productId)) {
            $product = $this->productTable->getRowById($productId);
            $output['product'] = $product;
            $brand = $this->getTableModel(BrandTable::class)->getRowById($product->brandId);
            $output['brand'] = $brand;
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $data['type'] = $type;
            $data['teamId'] = $teamId;
            $validate = History::validate($data);
            if (!$validate->isValid()) {
                $output['post'] = $data;
                $output['message'] = nl2br($validate->stringify());
                return new ViewModel($output);
            }
            try {
                $data['brandId'] = is_numeric($data['brand']) ? $data['brand'] :
                    $this->getTableModel(BrandTable::class)->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                $data['username'] = $this->user;
                $this->table->saveInventory($data);
                $this->flashMessenger()->addSuccessMessage('Inventory saved successfully.');
                $this->redirect()->toRoute('inventory');
            } catch (\Exception $e) {
                $output['message'] = $e->getMessage();
            }
        }
        $output['teams'] = $this->getTableModel(TeamTable::class)->getTeamListForSelection();
        return new ViewModel($output);
    }

    public function editAction() {
        $output = [
            'valid' => false,
            'message' => ''
        ];
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $output['message'] = 'Please provide the ID.';
            return new ViewModel($output);
        }
        $output['inventory'] = $this->table->getRowById($id);
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $validate = History::validate($data);
            if (!$validate->isValid()) {
                $output['post'] = $data;
                $output['message'] = nl2br($validate->stringify());
                return new ViewModel($output);
            }
            try {
                $data['brandId'] = is_numeric($data['brand']) ? $data['brand'] :
                    $this->getTableModel(BrandTable::class)->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                $data['username'] = $this->user;
                $this->table->saveInventory($data);
                $this->flashMessenger()->addSuccessMessage('History saved successfully.');
                $this->redirect()->toRoute('inventory');
            } catch (\Exception $e) {
                $output['message'] = $e->getMessage();
            }
        }
        $output['teams'] = $this->getTableModel(TeamTable::class)->getTeamListForSelection();
        return new ViewModel($output);
    }

    public function getItemNamesAction() {
        $this->useJsonLayout();
        $term = $this->params()->fromQuery('term');
        $data = $this->productTable->search($term);
        return $this->renderJsonView($data);
    }

    public function getBrandNamesAction() {
        $this->useJsonLayout();
        $term = $this->params()->fromQuery('term');
        $data = $this->getTableModel(BrandTable::class)->search($term);
        return $this->renderJsonView($data);
    }

    public function getBrandAction() {
        $this->useJsonLayout();
        $id = $this->params()->fromRoute('id');
        $data = [];
        if (!empty($id)) {
            $data = $this->getTableModel(BrandTable::class)->getRowById($id)->toArray();
        }
        return $this->renderJsonView($data);
    }
}