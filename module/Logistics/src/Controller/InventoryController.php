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
use Application\Model\Tools;
use Exception;
use Logistics\Model\AddressTable;
use Logistics\Model\BrandTable;
use Logistics\Model\ChargeTable;
use Logistics\Model\Package;
use Logistics\Model\Product;
use Logistics\Model\ProductTable;
use Logistics\Model\PackageTable;
use Logistics\Model\Shipping;
use Logistics\Model\ShippingTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property PackageTable table
 * @property ProductTable productTable
 * @property TeamTable teamTable
 * @property BrandTable brandTable
 * @property ShippingTable shippingTable
 * @property AddressTable addressTable
 */
class InventoryController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(PackageTable::class);
        $this->productTable = $this->getTableModel(ProductTable::class);
        $this->teamTable = $this->getTableModel(TeamTable::class);
        $this->brandTable = $this->getTableModel(BrandTable::class);
        $this->shippingTable = $this->getTableModel(ShippingTable::class);
        $this->addressTable = $this->getTableModel(AddressTable::class);
        $this->nav = 'inventory';
    }

    public function indexAction() {
        $this->title = $this->__('package.list');
        $this->addOutPut('packages', $this->table->getPackageList());
        return $this->renderView();
    }

    public function productsAction() {
        $this->title = $this->__('product.list');
        $this->addOutPut('products', $this->productTable->getProducts());
        return $this->renderView();
    }

    public function viewAction() {
        $this->title = $this->__('shipping.detail');
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('package.id.empty'));
            $this->redirect()->toRoute('inventory');
            return;
        }

        // Package
        $package = $this->table->getRowById($id);
        if (empty($package)) {
            $this->flashMessenger()->addErrorMessage($this->__('package.id.invalid', ['id' => $id]));
            $this->redirect()->toRoute('inventory');
            return;
        }
        $this->addOutPut('package', $package);
        $this->addOutPut('statusList', Package::STATUS_LIST);

        // Shipping
        $shipping = $this->shippingTable->getRowByFields(['packageId' => $id]);
        $this->addOutPut('shipping', $shipping);

        // Product
        $product = $this->productTable->getRowById($package->productId);
        $this->addOutPut('product', $product);

        // Team
        $team = $this->teamTable->getRowById($product->teamId);
        $this->addOutPut('team', $team);

        // Brand
        $brand = $this->brandTable->getRowById($product->brandId);
        $this->addOutPut('brand', $brand);

        // Address
        $this->addOutPut('address', $this->addressTable->getRowById($shipping->addressId));

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();

            // Upload attachments
            try {
                if (isset($_FILES['productLabel']) && $_FILES['productLabel']['size'] > 0) {
                    $data['productLabel'] = Tools::uploadAttachment($_FILES['productLabel']);
                }
                if (isset($_FILES['amazonShippingLabel']) && $_FILES['amazonShippingLabel']['size'] > 0) {
                    $data['amazonShippingLabel'] = Tools::uploadAttachment($_FILES['amazonShippingLabel']);
                }
            } catch (Exception $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
                $this->redirect()->refresh();
                return;
            }

            // Save to package
            BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
            $data['productId'] = $product->id;
            $this->table->savePackage($data, $id);

            // Save to address
            try {
                $addressUseCount = !$package->addressId ? 0 :
                    $this->shippingTable->getAddressUseCount($package->addressId);
                $data['teamId'] = $team->id;
                $data['addressId'] = $this->addressTable->saveAddress($data, $shipping->addressId,
                    $addressUseCount > 1 ? false : true);
            } catch (Exception $e) {
                $this->logger->err('Unable to save address.');
            }

            // Save to shipping
            $data['packageId'] = $package->id;
            $this->shippingTable->saveShipping($data, $shipping->id);
            if ($shipping->productLabel && $data['productLabel'] && $shipping->productLabel != $data['productLabel']) {
                Shipping::deleteAttachment($shipping->productLabel);
            }
            if ($shipping->amazonShippingLabel && $data['amazonShippingLabel'] && $shipping->amazonShippingLabel != $data['amazonShippingLabel']) {
                Shipping::deleteAttachment($shipping->amazonShippingLabel);
            }

            // Update QTY adn fees to product
            $this->productTable->updateQtyAndFees($data, $package, $product, $shipping);
            $this->flashMessenger()->addSuccessMessage($this->__('shipping.info.updated'));
            $this->redirect()->refresh();
            return;
        }
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
            $this->flashMessenger()->addErrorMessage($this->__('invalid.parameter'));
            $this->redirect()->toRoute('inventory');
        }
        $output['type'] = $type;
        $this->addOutPut('type', $type);
        $this->title = $type == Package::PROCESS_TYPE_IN ?
            $this->__('receive.package') : $this->__('ship.package.request');

        // Product
        $productId = $this->params()->fromRoute('id');
        if (!empty($productId)) {
            $product = $this->productTable->getRowById($productId);
            $teamId = $product->teamId;
            $this->addOutPut('product', $product);

            $brand = $this->brandTable->getRowById($product->brandId);
            $this->addOutPut('brand', $brand);
        } else {
            $teamId = $this->params()->fromQuery('teamId');
        }

        // Team
        if (!empty($teamId)) {
            $team = $this->teamTable->getRowById($teamId);
            $this->addOutPut('team', $team);
        }

        // Ship out packages should access from products list page
        // With product ID and team ID
        if ($type == Package::PROCESS_TYPE_OUT &&
            (empty($team) || empty($product))) {
            $this->flashMessenger()->addErrorMessage($this->__('invalid.parameter'));
            $this->toProductsPage();
            return;
        }

        if ($this->getRequest()->isPost()) {
            if (empty($team)) {
                $this->flashMessenger()->addErrorMessage($this->__('select.team'));
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
                BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
                $packageId = $this->table->savePackage($data);
                try {
                    $this->productTable->updateQtyAndFees($data);
                    $message = $type == Package::PROCESS_TYPE_IN ?
                        $this->__('package.add.success') : $this->__('package.ship.request.success');
                    $this->flashMessenger()->addSuccessMessage($message);
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
            $this->flashMessenger()->addErrorMessage($this->__('package.id.empty'));
            $this->redirect()->refresh();
            return;
        }
        $package = $this->table->getRowById($id);
        if (empty($package)) {
            $this->flashMessenger()->addErrorMessage($this->__('package.id.invalid', ['id' => $id]));
            $this->redirect()->refresh();
        }
        $this->title = $this->__('edit.package.id', ['id' => $id]);
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
                BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
                $this->table->savePackage($data, $id);

                $this->productTable->updateQtyAndFees($data, $package, $product);
                $message = $package->type == Package::PROCESS_TYPE_IN ?
                    $this->__('package.edit.success') : $this->__('package.ship.request.edit.success');
                $this->flashMessenger()->addSuccessMessage($message);
                $this->redirect()->refresh();
            } catch (Exception $e) {
                $this->flashMessenger()->addSuccessMessage('package.save.failed', ['message' => $e->getMessage()]);
                $this->redirect()->refresh();
            }
        }
        $this->addOutPut('teams', $this->teamTable->getTeamListForSelection());
        return $this->renderView();
    }

    public function editProductAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('product.id.empty'));
            $this->toProductsPage();
        }
        $product = $this->productTable->getRowById($id);
        if (empty($product)) {
            $this->flashMessenger()->addErrorMessage($this->__('product.id.invalid', ['id' => $id]));
            $this->toProductsPage();
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
                $this->flashMessenger()->addErrorMessage($this->__('product.info.not.changed'));
                $this->redirect()->refresh();
                return;
            }
            $this->productTable->update([
                'itemName' => $data['itemName'],
                'brandId' => $data['brandId']
            ], $id);
            $this->flashMessenger()->addSuccessMessage($this->__('product.info.updated'));
            $this->redirect()->refresh();
            return;
        }
        return $this->renderView();
    }

    public function chargeAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('product.id.empty'));
            $this->toProductsPage();
            return;
        }
        $product = $this->productTable->getRowById($id);
        if (empty($product)) {
            $this->flashMessenger()->addErrorMessage($this->__('product.id.invalid', ['id' => $id]));
            $this->toProductsPage();
            return;
        }
        if ($this->getRequest()->isPost()) {
            $amount = $this->getRequest()->getPost('amount');
            if ($amount > $product->feesDue) {
                $this->flashMessenger()->addErrorMessage($this->__('amount.greater.than.due.amount',
                    ['amount' => round($amount, 2), 'due' => round($product->feesDue, 2)]));
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
            $this->flashMessenger()->addSuccessMessage($this->__('amount.is.charged', ['amount' => round($amount, 2)]));
            $this->redirect()->refresh();
        }
        $this->addOutPut('product', $product);
        $this->addOutPut('team', $this->teamTable->getRowById($product->teamId));
        return $this->renderView();
    }

    public function deleteProductAction() {
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('product.id.empty'));
        } else {
            $this->productTable->delete($id);
            $this->table->deleteBy(['productId' => $id]);
            $this->flashMessenger()->addSuccessMessage($this->__('product.deleted'));
        }

        $this->toProductsPage();
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

    private function toProductsPage() {
        $this->redirect()->toRoute('inventory', ['action' => 'products']);
    }
}