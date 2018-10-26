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
use Logistics\Model\Address;
use Logistics\Model\AddressTable;
use Logistics\Model\BoxTable;
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
 * @property ChargeTable chargeTable
 * @property BoxTable boxTable
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
        $this->chargeTable = $this->getTableModel(ChargeTable::class);
        $this->boxTable = $this->getTableModel(BoxTable::class);
        $this->nav = 'inventory';
    }

    public function indexAction() {
        $type = $this->params()->fromQuery('type', 'in');
        $itemName = $this->params()->fromQuery('itemName', '');
        $teamId = $this->params()->fromQuery('teamId', 0);
        $carrier = $this->params()->fromQuery('carrier', '');
        $this->title = $this->__('nav.packages.'.$type);
        $this->addOutPut([
            'type' => $type,
            'itemName' => $itemName,
            'teamId' => $teamId,
            'carrier' => $carrier,
            'packages' => $this->table->getPackageList($this->userObject, $type, [
                'itemName' => $itemName,
                'teamId' => $teamId,
                'carrier' => $carrier
            ]),
            'teams' => $this->teamTable->getTeamListForSelection(),
            'carriers' => $this->shippingTable->getCarriers()
        ]);
        return $this->renderView();
    }

    public function downloadInvoiceAction() {
        $this->useBlankLayout();
        $packageIds = $this->params()->fromQuery('packageIds');
        $packageIds = empty($packageIds) ? [] : explode(',', $packageIds);
        $this->addOutPut('packages', $this->table->getInvoice($packageIds));
        return $this->renderView();
    }

    public function productsAction() {
        $this->title = $this->__('product.list');
        $itemName = $this->params()->fromQuery('itemName', '');
        $teamId = $this->params()->fromQuery('teamId', 0);
        $products = $this->productTable->getProducts($this->userObject, $teamId, $itemName);
        $productIds = array_column($products->toArray(), 'id');
        $this->addOutPut([
            'itemName' => $itemName,
            'teamId' => $teamId,
            'products' => $products,
            'teams' => $this->teamTable->getTeamListForSelection(),
            'feeList' => $this->table->getProductFeeList($productIds)
        ]);
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

        if ($package->type == Package::PROCESS_TYPE_IN) {
            $this->redirect()->toRoute('inventory', ['action' => 'edit', 'id' => $id]);
            return;
        }

        $this->addOutPut('package', $package);
        $this->addOutPut('statusList', Package::STATUS_LIST);

        if (!$this->userObject->allowTeamAccess($package->teamId)) {
            return $this->redirectDOS();
        }

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

        $this->addOutPut('attachments', Shipping::ATTACHMENTS);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();

            // Upload attachments
            try {
                foreach (Shipping::ATTACHMENTS as $field => $label) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['size'] > 0) {
                        $data[$field] = Tools::uploadAttachment($_FILES[$field]);
                    }
                }
            } catch (Exception $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
                $this->redirect()->refresh();
                return;
            }

            // Save to package
            BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
            $data['productId'] = $product->id;
            if (($product->qty + $package->qty) < $data['qty']) {
                $this->flashMessenger()->addErrorMessage($this->__('package.qty.over.inventory', [
                        'qty' => $data['qty'],
                        'maximum' => $product->qty
                    ]));
                $this->redirect()->refresh();
                return;
            }
            $this->table->savePackage($data, $this->userObject->isManager(), $id);

            // If qty changed, reset the boxes
            $qtyNeeded = $data['qty'] - $package->qty;
            if ($qtyNeeded != 0) {
                $data['packageId'] = $id;
                $data['qtyNeeded'] = $qtyNeeded;
                $this->boxTable->shipOutBoxes($data);
            }

            // Save to address
            if ($this->userObject->isManager() && Address::isValid($data)) {
                try {
                    $addressUseCount = !$package->addressId ? 0 :
                        $this->shippingTable->getAddressUseCount($package->addressId);
                    $data['teamId'] = $team->id;
                    $data['addressId'] = $this->addressTable->saveAddress($data, $shipping->addressId,
                        $addressUseCount > 1 ? false : true);
                } catch (Exception $e) {
                    $this->logger->err('Unable to save address.');
                }
            }

            // Save to shipping
            $data['packageId'] = $package->id;
            $data['serviceFee'] = Shipping::calcServiceFee($data);
            $this->shippingTable->saveShipping($data, $shipping->id);
            foreach (Shipping::ATTACHMENTS as $field => $label) {
                if ($shipping->$field && $data[$field] && $shipping->$field != $data[$field]) {
                    Shipping::deleteAttachment($shipping->$field);
                }
            }

            // Update QTY adn fees to product
            $this->productTable->updateQtyAndFees($data, $package, $product, $shipping);
            $this->flashMessenger()->addSuccessMessage($this->__('shipping.info.updated'));
            $this->redirect()->toRoute('inventory', ['action' => 'index'], ['query' => ['type' => Package::PROCESS_TYPE_OUT, 'teamId' => $package->teamId]]);
            return;
        }
        $this->addOutPut('requirements', Shipping::REQUIREMENT_COLUMNS);
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

            // Check team for non-manager members
            if (!$this->userObject->allowTeamAccess($teamId)) {
                return $this->redirectDOS();
            }

            $brand = $this->brandTable->getRowById($product->brandId);
            $this->addOutPut('brand', $brand);
        } else {
            $teamId = $this->params()->fromQuery('teamId');

            // Only managers can receive packages
            if (($view = $this->onlyManagers()) != null) {
                return $view;
            }
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
                if ($type == Package::PROCESS_TYPE_OUT &&
                    !empty($product) && $product->qty < $data['qty']) {
                    $this->flashMessenger()->addErrorMessage($this->__('package.qty.over.inventory', [
                        'qty' => $data['qty'],
                        'maximum' => $product->qty
                    ]));
                    $this->redirect()->refresh();
                    return;
                }
                $data['brandId'] = $this->brandTable->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                $data['username'] = $this->user;
                BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
                $packageId = $this->table->savePackage($data, $this->userObject->isManager());
                try {
                    $data['packageId'] = $packageId;
                    if ($type == Package::PROCESS_TYPE_IN) {
                        $this->boxTable->saveReceivedBoxes($data);
                    } else {
                        $data['qtyNeeded'] = $data['qty'];
                        $this->boxTable->shipOutBoxes($data);
                    }
                    $this->productTable->updateQtyAndFees($data);
                    $message = $type == Package::PROCESS_TYPE_IN ?
                        $this->__('package.add.success') : $this->__('package.ship.request.success');
                    $this->flashMessenger()->addSuccessMessage($message);

                    $type == Package::PROCESS_TYPE_OUT ?
                        $this->redirect()->toRoute('inventory', ['action' => 'view', 'id' => $packageId]) :
                        $this->redirect()->toRoute('inventory', ['action' => 'index'], ['query' => ['type' => Package::PROCESS_TYPE_IN, 'teamId' => $teamId]]);
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

        // Check team for non-manager members
        if (!$this->userObject->allowTeamAccess($package->teamId)) {
            return $this->redirectDOS();
        }

        if ($package->type == Package::PROCESS_TYPE_OUT) {
            $this->redirect()->toRoute('inventory', ['action' => 'view', 'id' => $id]);
            return;
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

            if ($package->type == Package::PROCESS_TYPE_OUT && $product->qty < $data['qty']) {
                $this->flashMessenger()->addErrorMessage($this->__('package.qty.over.inventory', [
                    'qty' => $data['qty'],
                    'maximum' => $product->qty
                ]));
                $this->redirect()->refresh();
                return;
            }
            try {
                $data['brandId'] = is_numeric($data['brand']) ? $data['brand'] :
                    $this->getTableModel(BrandTable::class)->getBrandId($data['brand']);
                $data['productId'] = $this->productTable->getProductId($data);
                BaseModel::formatNumericColumns($data, Package::NUMERIC_COLUMNS);
                $this->table->savePackage($data, $this->userObject->isManager(), $id);
                $data['packageId'] = $id;
                $this->boxTable->saveReceivedBoxes($data);

                $this->productTable->updateQtyAndFees($data, $package, $product);
                $message = $package->type == Package::PROCESS_TYPE_IN ?
                    $this->__('package.edit.success') : $this->__('package.ship.request.edit.success');
                $this->flashMessenger()->addSuccessMessage($message);
                $this->redirect()->toRoute('inventory', ['action' => 'index'], ['query' => ['type' => Package::PROCESS_TYPE_IN, 'teamId' => $product->teamId]]);
            } catch (Exception $e) {
                $this->flashMessenger()->addErrorMessage($this->__('package.save.failed', ['message' => $e->getMessage()]));
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

        // Check team for non-manager members
        if (!$this->userObject->allowTeamAccess($product->teamId)) {
            return $this->redirectDOS();
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
            $this->redirect()->toRoute('inventory', ['action' => 'products'], ['query' => ['type' => Package::PROCESS_TYPE_IN, 'teamId' => $product->teamId]]);
            return;
        }
        return $this->renderView();
    }

    public function chargeAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('team.id.empty'));
            $this->toProductsPage();
            return;
        }
        $team = $this->teamTable->getRowById($id);
        if (empty($team)) {
            $this->flashMessenger()->addErrorMessage($this->__('team.id.invalid', ['id' => $id]));
            $this->redirect()->toRoute('team');
            return;
        }

        $chargedTotal = $this->chargeTable->getChargedTotal($id);
        $fees = $this->table->getTeamFee($id);
        $amountDue = $fees->shippingFee + $fees->serviceFee + $fees->customs - $chargedTotal;
        $this->addOutPut([
            'team' => $team,
            'amountDue' => $amountDue,
        ]);

        if ($this->getRequest()->isPost()) {
            $amount = $this->getRequest()->getPost('amount');
            if ($amount > $amountDue) {
                $this->flashMessenger()->addErrorMessage($this->__('amount.greater.than.due.amount',
                    ['amount' => round($amount, 2), 'due' => round($amountDue, 2)]));
                $this->redirect()->refresh();
                return;
            }
            $this->chargeTable->add([
                'amount' => $amount,
                'productId' => $id,
                'teamId' => $id,
                'date' => date('Y-m-d H:i:s')
            ]);
            $this->flashMessenger()->addSuccessMessage($this->__('amount.is.charged', ['amount' => round($amount, 2)]));
            $this->redirect()->refresh();
        }
        return $this->renderView();
    }

    public function deleteProductAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
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