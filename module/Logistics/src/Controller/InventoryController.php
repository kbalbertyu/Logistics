<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/11/2018
 * Time: 3:04 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Application\Model\BaseModel;
use Exception;
use Logistics\Model\BrandTable;
use Logistics\Model\ChargeTable;
use Logistics\Model\Package;
use Logistics\Model\Product;
use Logistics\Model\ProductTable;
use Logistics\Model\PackageTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property PackageTable table
 * @property ProductTable productTable
 * @property TeamTable teamTable
 * @property BrandTable brandTable
 */
class InventoryController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(PackageTable::class);
        $this->productTable = $this->getTableModel(ProductTable::class);
        $this->teamTable = $this->getTableModel(TeamTable::class);
        $this->brandTable = $this->getTableModel(BrandTable::class);
        $this->nav = 'inventory';
    }

    public function indexAction() {
        $this->title = 'Package List';
        $this->addOutPut('packages', $this->table->getPackageList());
        return $this->renderView();
    }

    public function productsAction() {
        $this->title = 'Product List';
        $this->addOutPut('products', $this->productTable->getProducts());
        return $this->renderView();
    }

    public function addAction() {
        $this->addOutPut([
            'valid' => false,
            'message' => ''
        ]);

        // Process Type
        $type = $this->params()->fromQuery('type');
        if (empty($type)) {
            $this->flashMessenger()->addErrorMessage('Invalid parameter.');
            $this->redirect()->toRoute('inventory');
        }
        $output['type'] = $type;
        $this->addOutPut('type', $type);
        $this->title = $type == 'in' ? 'Receive New Package' : 'Ship Package';

        // Team
        $teamId = $this->params()->fromQuery('teamId');
        if (!empty($teamId)) {
            $team = $this->teamTable->getRowById($teamId);
            $this->addOutPut('team', $team);
        }

        // Product
        $productId = $this->params()->fromRoute('id');
        if (!empty($productId)) {
            $product = $this->productTable->getRowById($productId);
            $this->addOutPut('product', $product);

            $brand = $this->brandTable->getRowById($product->brandId);
            $this->addOutPut('brand', $brand);
        }

        if ($this->getRequest()->isPost()) {
            if (empty($team)) {
                $this->flashMessenger()->addErrorMessage('Please select a business team.');
                $this->redirect()->refresh();
                return;
            }
            $data = $this->getRequest()->getPost()->toArray();
            $data['type'] = $type;
            $data['teamId'] = $teamId;
            if (!empty($product)) {
                $data['itemName'] = $product->id;
            }
            if (!empty($brand)) {
                $data['brand'] = $brand->id;
            }
            $validate = Package::validate($data);
            if (!$validate->isValid()) {
                $this->addOutPut([
                    'post' => $data,
                    'message' => nl2br($validate->stringify())
                ]);
                return $this->renderView();
            }
            try {
                $data['brandId'] = $this->brandTable->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                $data['username'] = $this->user;
                BaseModel::filterNumericColumns($data, Package::NUMERIC_COLUMNS);
                $packageId = $this->table->savePackage($data);
                try {
                    $this->productTable->updateQtyAndFees($data);
                    $this->flashMessenger()->addSuccessMessage('Inventory saved successfully.');
                    $this->redirect()->toRoute('inventory');
                } catch (Exception $e) {
                    $this->table->delete($packageId);
                }
            } catch (Exception $e) {
                $this->addOutPut('message', $e->getMessage());
            }
        }
        $this->addOutPut('teams', $this->teamTable->getTeamListForSelection());
        return $this->renderView();
    }

    public function editAction() {
        // Inventory Record
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage('Please provide the ID.');
            $this->redirect()->refresh();
            return;
        }
        $package = $this->table->getRowById($id);
        if (empty($package)) {
            $this->flashMessenger()->addErrorMessage('Invalid package ID: ' . $id);
            $this->redirect()->refresh();
        }
        $this->title = 'Edit Package (ID: ' . $id . ')';
        $this->addOutPut('package', $package);

        // Product Info
        $product = $this->productTable->getRowById($package->productId);
        $this->addOutPut('product', $product);

        // Brand
        $this->addOutPut('brand', $this->brandTable->getRowById($product->brandId));

        // Team
        $this->addOutPut('team', $this->teamTable->getRowById($product->teamId));

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $data['teamId'] = $product->teamId;
            $data['type'] = $package->type;
            $validate = Package::validate($data);
            if (!$validate->isValid()) {
                $this->flashMessenger()->addErrorMessage(nl2br($validate->stringify()));
                $this->redirect()->refresh();
            }
            try {
                $data['brandId'] = is_numeric($data['brand']) ? $data['brand'] :
                    $this->getTableModel(BrandTable::class)->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                BaseModel::filterNumericColumns($data, Package::NUMERIC_COLUMNS);
                $this->table->savePackage($data, $id);

                $this->productTable->updateQtyAndFees($data, $package, $product);
                $this->flashMessenger()->addSuccessMessage('Package saved successfully.');
                $this->redirect()->refresh();
            } catch (Exception $e) {
                $this->flashMessenger()->addSuccessMessage('Package saved failed: ' . $e->getMessage());
                $this->redirect()->refresh();
            }
        }
        $this->addOutPut('teams', $this->teamTable->getTeamListForSelection());
        return $this->renderView();
    }

    public function editProductAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage('Please provide a product ID.');
            $this->redirect()->toRoute('inventory', ['action' => 'products']);
        }
        $product = $this->productTable->getRowById($id);
        if (empty($product)) {
            $this->flashMessenger()->addErrorMessage('Invalid product ID.');
            $this->redirect()->toRoute('inventory', ['action' => 'products']);
        }
        $this->addOutPut('product', $product);
        $this->addOutPut('team', $this->teamTable->getRowById($product->teamId));
        $this->addOutPut('brand', $this->brandTable->getRowById($product->brandId));
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $validate = Product::validate($data);
            if (!$validate->isValid()) {
                $this->flashMessenger()->addErrorMessage(nl2br($validate->stringify()));
                $this->redirect()->refresh();
                return;
            }
            $data['brandId'] = $this->brandTable->getBrandId($data['brand']);
            if ($product->brandId == $data['brandId'] &&
                $product->itemName == $data['itemName']) {
                $this->flashMessenger()->addSuccessMessage('Product info not changed.');
                $this->redirect()->refresh();
                return;
            }
            $this->productTable->update([
                'itemName' => $data['itemName'],
                'brandId' => $data['brandId']
            ], $id);
            $this->flashMessenger()->addSuccessMessage('Product info updated.');
            $this->redirect()->refresh();
            return;
        }
        return $this->renderView();
    }

    public function chargeAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage('Invalid product ID.');
            $this->redirect()->toRoute('inventory', ['action' => 'products']);
            return;
        }
        $product = $this->productTable->getRowById($id);
        if (empty($product)) {
            $this->flashMessenger()->addErrorMessage('Invalid product ID.');
            $this->redirect()->toRoute('inventory', ['action' => 'products']);
            return;
        }
        if ($this->getRequest()->isPost()) {
            $amount = $this->getRequest()->getPost('amount');
            if ($amount > $product->feesDue) {
                $this->flashMessenger()->addErrorMessage(sprintf('Charging amount %.2f is greater than the due amount %.2f.',
                    $amount, $product->feesDue));
                $this->redirect()->refresh();
                return;
            }
            $this->productTable->update([
                'feesDue' => ($product->feesDue - $amount)
            ], $id);
            $this->getTableModel(ChargeTable::class)->add([
                'amount' => $amount,
                'productId' => $id,
                'teamId' => $product->teamId,
                'date' => date('Y-m-d H:i:s')
            ]);
            $this->flashMessenger()->addSuccessMessage(sprintf('$%.2f is charged.', $amount));
            $this->redirect()->refresh();
        }
        $this->addOutPut('product', $product);
        $this->addOutPut('team', $this->teamTable->getRowById($product->teamId));
        return $this->renderView();
    }

    public function deleteProductAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage('Invalid product ID.');
        } else {
            $this->productTable->delete($id);
            $this->table->deleteBy(['productId' => $id]);
            $this->flashMessenger()->addSuccessMessage('The product and its packages are deleted.');
        }

        $this->redirect()->toRoute('inventory', ['action' => 'products']);
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