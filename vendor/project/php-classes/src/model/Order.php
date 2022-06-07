<?php
namespace Rootdir\Model;

use \Rootdir\DB\Sql;
use \Rootdir\Model as BaseModel;
use \Rootdir\Model\Cart;

class Order extends BaseModel {
    const SUCCESS = "Order-Success";
    const ERROR = "Order-Error";

    public function save() {
        $result = self::select("CALL sp_orders_save(
            :idorder,
            :idcart,
            :iduser,
            :idstatus,
            :idaddress,
            :vltotal
        )", [
            "idorder" => $this->getidorder(),
            "idcart" => $this->getidcart(),
            "iduser" => $this->getiduser(),
            "idstatus" => $this->getidstatus(),
            "idaddress" => $this->getidaddress(),
            "vltotal" => $this->getvltotal()
        ]);

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public function get(int $idorder) {
        $result = self::select(
            "SELECT     *
            FROM        tb_orders a
                        INNER JOIN tb_ordersstatus b USING(idstatus)
                        INNER JOIN tb_carts c USING(idcart)
                        INNER JOIN tb_users d ON d.iduser = a.iduser
                        INNER JOIN tb_addresses e USING(idaddress)
                        INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE       a.idorder = :idorder
        ", [
            "idorder" => $idorder
        ]);

        if (count($result) > 0) {
            $this->setData($result[0]);
        }
    }

    public static function listAll() {
        $result = self::select(
            "SELECT     *
            FROM        tb_orders a
                        INNER JOIN tb_ordersstatus b USING(idstatus)
                        INNER JOIN tb_carts c USING(idcart)
                        INNER JOIN tb_users d ON d.iduser = a.iduser
                        INNER JOIN tb_addresses e USING(idaddress)
                        INNER JOIN tb_persons f ON f.idperson = d.idperson
            ORDER BY a.dtregister DESC
        ");

        if (count($result) > 0) {
            return $result;
        }
        return [];
    }

    public function delete() {
        self::query("DELETE FROM tb_orders WHERE idorder = :idorder", [
            "idorder" => $this->getidorder()
        ]);
    }

    public function getCart() {
        $cart = new Cart();
        $cart->get((int)$this->getidcart());
        return $cart;
    }

    public static function setMsgError($msg) {
        $_SESSION[self::ERROR] = $msg;
    }

    public static function getMsgError() :string {
        $msg =  (!empty($_SESSION[self::ERROR]) ? $_SESSION[self::ERROR] : "");
        self::clearMsgError();
        return $msg;
    }

    public static function clearMsgError() {
        $_SESSION[self::ERROR] = null;
    }

    public static function setSuccess($msg) {
        $_SESSION[self::SUCCESS] = $msg;
    }

    public static function getSuccess() :string {
        $msg =  (!empty($_SESSION[self::SUCCESS]) ? $_SESSION[self::SUCCESS] : "");
        self::clearSuccess();
        return $msg;
    }

    public static function clearSuccess() {
        $_SESSION[self::SUCCESS] = null;
    }

    public static function getPaginationAdmin(int $page = 1, int $pageItems = 3, ?string $search = "") {
        $start = (($page - 1) * $pageItems);
        $bind = [];
        $sqlWhere = "";

        if (!empty($search)) {
            $sqlWhere = "WHERE (a.idorder = :idorder OR f.desperson = :search1)";
            $bind = [
                "idorder" => $search,
                "search1" => "%$search%"
            ];
        }

        $result = self::select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM    tb_orders a
                    INNER JOIN tb_ordersstatus b USING(idstatus)
                    INNER JOIN tb_carts c USING(idcart)
                    INNER JOIN tb_users d ON d.iduser = a.iduser
                    INNER JOIN tb_addresses e USING(idaddress)
                    INNER JOIN tb_persons f ON f.idperson = d.idperson
            {$sqlWhere}
            ORDER BY a.dtregister DESC
            LIMIT {$start}, {$pageItems}", $bind
        );

        $qtorders = self::select("SELECT FOUND_ROWS() as qtorders");

        $pages = [];
        for ($pg=1; $pg <= ceil($qtorders[0]["qtorders"] / $pageItems); $pg++) {
            array_push($pages, [
                "href" => "/gestao/orders?".http_build_query([
                    "page" => $pg,
                    "search" => $search
                ]),
                "text" => $pg
            ]);
        }
        
        return [
            "data" => $result,
            "pages" => $pages
        ];
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