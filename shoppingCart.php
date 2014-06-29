<?php
/**
 * Created by Kotenko Nikita <kotenko@samsonos.com>
 * on 24.06.14 at 11:35
 */

namespace samson\commerce;

use samson\activerecord\dbRecord;
use samson\core\CompressableService;

class shoppingCart extends CompressableService
{
    public $id = 'shoppingcart';

    /** @var  Count form field */
    public $postCount = 'count';

    /** @var string DB order table */
    public $dbOrderTable = 'order';

    /** @var string DB order item table */
    public $dbItemTable = 'item';

    public $dbUserIdField = 'client_id';

    /** @var  Adding cart item handler */
    public $addingItemHandler = '';

    public $addingItemResponce = array('status'=>0);

    public $itemsList = array();

    /** Prepare module environment */
    public function prepare()
    {

    }

    /** @see \samson\core\ModuleConnector::init() */
    public function init(array $params = array())
    {
        // Вызовем родительсикй метод
        parent::init( $params );

        // Все ок
        return true;
    }

    public function __async_add($product_id)
    {
        $count = 1;
        if (isset($_POST[$this->postCount])) {
            $count = $_POST[$this->postCount];
        }

        $this->addItem($product_id, $count);

        return $this->addingItemResponce;
    }

    public function get()
    {
        $this->updateItemsList();
        return $this->itemsList;
    }

    public function clear()
    {
        if (isset($_SESSION['__samson_commerce_shopping_cat'])) {
            unset($_SESSION['__samson_commerce_shopping_cat']);
        }
        $this->itemsList = array();
    }

    public function count()
    {
        $this->updateItemsList();
        $count = 0;
        foreach ($this->itemsList as $item) {
            $count += $item->count;
        }
        return $count;
    }

    public function update()
    {
        $this->itemsList = array();
        if (isset($_POST['item'])){

            for($i=0; $i<sizeof($_POST['item']); $i++){
                if ($_POST['count'][$i] > 0) {
                    $item = new shoppingCartItem();
                    $item->count = $_POST['count'][$i];
                    $item->product_id = $_POST['item'][$i];
                    $this->itemsList[$_POST['item'][$i]] = $item;
                }
            }
        }
        $this->updateShoppingCart();
    }

    private function addItem($productID, $count)
    {
        $this->updateItemsList();
        if (!isset($this->itemsList[$productID])) {
            $item = new shoppingCartItem();
            $item->count = $count;
            $item->product_id = $productID;
            $this->itemsList[$productID] = $item;
        } else {
            $item = & $this->itemsList[$productID];
            $item->count += $count;
        }
        $this->addingItemResponce['status'] = 1;
        if (function_exists($this->addingItemHandler)) {
            call_user_func($this->addingItemHandle);
        }
        $this->updateShoppingCart();
    }

    private function updateItemsList()
    {
        $this->itemsList = array();
        if (isset($_SESSION['__samson_commerce_shopping_cat'])) {
            foreach ($_SESSION['__samson_commerce_shopping_cat'] as $id=>$count) {
                $item = new shoppingCartItem();
                $item->count = $count;
                $item->product_id = $id;
                $this->itemsList[$id] = $item;
            }
        }
    }

    private function updateShoppingCart()
    {
        if (isset($_SESSION['__samson_commerce_shopping_cat'])) {
            unset($_SESSION['__samson_commerce_shopping_cat']);
        }
        $this->addingItemResponce['count'] = 0;
        $this->addingItemResponce['sum'] = 0;
        if (sizeof($this->itemsList)) {
            foreach ($this->itemsList as $id=>$item){
                $_SESSION['__samson_commerce_shopping_cat'][$id] = $item->count;
                $this->addingItemResponce['count'] += $item->count;
                $this->addingItemResponce['sum'] += ($item->price*$item->count);
            }
        }
    }


}