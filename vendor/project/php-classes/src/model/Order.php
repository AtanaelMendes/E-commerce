<?php
namespace Rootdir\Model;

use \Rootdir\DB\Sql;
use \Rootdir\Model as BaseModel;

class Order extends BaseModel {
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

    private static function select(string $query, array $bind = []) : array {
        $DAO = new Sql();
        return $DAO->select($query, $bind);
    }

    private static function query(string $query, array $bind = []) : void {
        $DAO = new Sql();
        $DAO->query($query, $bind);
    }
}