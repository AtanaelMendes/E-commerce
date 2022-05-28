<?php
namespace Rootdir\Model;

use \Rootdir\DB\Sql;
use \Rootdir\Model as BaseModel;
use \Rootdir\Model\User;

class Cart extends BaseModel {

    const SESSION = "Cart";
    const SESSION_ERROR = "CartError";

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
        self::query("INSERT INTO tb_cartsproducts(idcart, idproduct) VALUES(:idcart, :idproduct)", [
            "idcart" => $this->getidcart(),
            "idproduct" => $produto->getidproduct()
        ]);
        $this->getCalculateTotal();
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
        $this->getCalculateTotal();
    }

    public function getProductsTotals() {
        $result = self::select(
            "SELECT     SUM(vlprice)    AS vlprice,
                        SUM(vlwidth)    AS vlwidth,
                        SUM(vlheight)   AS vlheight,
                        SUM(vllength)   AS vllength,
                        SUM(vlweight)   AS vlweight,
                        COUNT(*)        AS qtprod
            FROM        tb_products a
                        INNER JOIN tb_cartsproducts b USING(idproduct)
            WHERE b.idcart = :idcart
            AND dtremoved IS NULL", [
                "idcart" => $this->getidcart()
            ]
        );

        if (count($result[0]) > 0) {
            return $result[0];
        }

        return [];
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
                        COUNT(*) AS qtprod,
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

    public function setFreight($zipcode) {
        $zipcode = str_replace("-", "", $zipcode);
        $totals = $this->getProductsTotals();

        if ($totals["qtprod"] > 0) {
            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

			$params = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'78550001',
				'sCepDestino'=>$zipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);

            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$params);
            $result = $xml->Servicos->cServico;

			if ($result->MsgErro != '') {
				Cart::setMsgError($result->MsgErro);
			} else {
				Cart::clearMsgError();
			}

            $this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($zipcode);

			$this->save();
			return $result;
        } else {
            // TODO
        }
    }

    public static function formatValueToDecimal($value):float {
        $value = str_replace(".", "", $value);
        return str_replace(",", ".", $value);
    }

    public static function setMsgError($msg) {
        $_SESSION[self::SESSION_ERROR] = $msg;
    }

    public static function getMsgError() {
        $msg =  (!empty($_SESSION[self::SESSION_ERROR]) ? $_SESSION[self::SESSION_ERROR] : "");
        self::clearMsgError();
        return $msg;
    }

    public static function clearMsgError() {
        $_SESSION[self::SESSION_ERROR] = null;
    }

    public function updateFreight() {
        if (!empty($this->getdeszipcode())) {
            $this->setFreight($this->getdeszipcode());
        }
    }

    public function expose() {
        $this->getCalculateTotal();
        return parent::expose();
    }

    public function getCalculateTotal() {
        $this->updateFreight();
        $totals = $this->getProductsTotals();
        $this->setvlsubtotal($totals["vlprice"]);
        $this->setvltotal($totals["vlprice"]+$this->getvlfreight());
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