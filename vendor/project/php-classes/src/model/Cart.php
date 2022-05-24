<?php
namespace Rootdir\Model;

use \Rootdir\DB\Sql;
use \Rootdir\Model as BaseModel;
use \Rootdir\Model\User;

class Cart extends BaseModel {

    const SESSION = "Cart";

    public static function getFromSession() {
        $cart = new Cart();
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]["idcart"] > 0) {
            $cart->get((int)$_SESSION[Cart::SESSION]["idcart"]);
        } else {
            $cart->getFromSessionID();
            if (!(int)$cart->getidcart() > 0) {
                $data = [
                    "dessessionid" => session_id()
                ];

                $user = User::getFromSession();

                if ($user->checkLogin()) {
                    $user = User::getFromSession();
                    $data["iduser"] = $user->getiduser();
                }

                $cart->setData($data);
                $cart->save();
                $cart->setToSession();
            }
        }
        return $cart;
    }

    public function setToSession() {
        $_SESSION[Cart::SESSION] = $this->expose();
    }

    public function getFromSessionID() {
        $result = self::select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
            "dessessionid" => session_id()
        ]);
        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function get(int $idcart) {
        $result = self::select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
            "idcart" => $idcart
        ]);
        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function save() {
        $result = self::select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
            "idcart" => $this->getidcart(),
            "dessessionid" => $this->getdessessionid(),
            "iduser" => $this->getiduser(),
            "deszipcode" => $this->getdeszipcode(),
            "vlfreight" => $this->getvlfreight(),
            "nrdays" => $this->getnrdays()
        ]);

        $this->setData($result[0]);
    }

    public function addProduct(Product $produto) {
        self::query("INSERT INTO tb_cartsproducts(idcart, idproduct) VALUES(:idcart, :idproduct)". [
            "idcart" => $this->getidcart(),
            "idproduct" => $produto->getidproduct()
        ]);
    }

    public function removeProd(Product $produto, bool $all = false) {
        if ($all) {
            self::query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
                "idcart" => $this->getidcart(),
                "idproduct" => $produto->getidproduct()
            ]);
        } else {
            self::query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
                "idcart" => $this->getidcart(),
                "idproduct" => $produto->getidproduct()
            ]);
        }
    }

    public function getProducts()
	{
            $rows = self::select(
            "SELECT     b.idproduct      ,
                        b.desproduct     ,
                        b.vlprice        ,
                        b.vlwidth        ,
                        b.vlheight       ,
                        b.vllength       ,
                        b.vlweight       ,
                        b.desurl         ,
                        COUNT(*) AS nrqtd,
                        SUM(b.vlprice) AS vltotal
			FROM        tb_cartsproducts a
			            INNER JOIN tb_products b
                        ON  (
                                a.idproduct = b.idproduct
                            )
			WHERE       a.idcart = :idcart
            AND         a.dtremoved IS NULL
			GROUP BY    b.idproduct     ,
                        b.desproduct    ,
                        b.vlprice       ,
                        b.vlwidth       ,
                        b.vlheight      ,
                        b.vllength      ,
                        b.vlweight      ,
                        b.desurl
			ORDER BY    b.desproduct
		", [
			':idcart'=>$this->getidcart()
		]);

		return Product::checkList($rows);

	}

    private static function select(string $query, array $bind = []) : array {
        $DAO = new Sql();
        return $DAO->select($query, $bind);
    }

    private static function query(string $query, array $bind = []) : void {
        $DAO = new Sql();
        $DAO->query($query, $bind);
    }
}