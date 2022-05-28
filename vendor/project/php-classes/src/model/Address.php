<?php
namespace Rootdir\Model;

use \Rootdir\DB\Sql;
use \Rootdir\Model as BaseModel;

class Address extends BaseModel {
    const SESSION_ERROR = "AddressError";

	public static function getCEP($nrcep) {
		$nrcep = str_replace("-", "", $nrcep);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);

		return $data;
	}

	public function loadFromCEP($nrcep) {
		$data = Address::getCEP($nrcep);
		if (isset($data['logradouro']) && $data['logradouro']) {
			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);
		}
	}

	public function save() {
		$sql = new Sql();

		$results = $sql->select(
			"CALL sp_addresses_save(
				:idaddress,
				:idperson,
				:desaddress,
				:desnumber,
				:descomplement,
				:descity,
				:desstate,
				:descountry,
				:deszipcode,
				:desdistrict
			)", [
			'idaddress'=>$this->getidaddress(),
			'idperson'=>$this->getidperson(),
			'desaddress'=>$this->getdesaddress(), //utf8_decode($this->getdesaddress()),
			'desnumber'=>$this->getdesnumber(),
			'descomplement'=>$this->getdescomplement(), //utf8_decode($this->getdescomplement()),
			'descity'=>$this->getdescity(), //utf8_decode($this->getdescity()),
			'desstate'=>$this->getdesstate(), //utf8_decode($this->getdesstate()),
			'descountry'=>$this->getdescountry(), //utf8_decode($this->getdescountry()),
			'deszipcode'=>$this->getdeszipcode(),
			'desdistrict'=>$this->getdesdistrict()
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public static function setMsgError($msg) {
		$_SESSION[Address::SESSION_ERROR] = $msg;
	}

	public static function getMsgError() {
		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";
		Address::clearMsgError();
		return $msg;
	}

	public static function clearMsgError() {
		$_SESSION[Address::SESSION_ERROR] = NULL;
	}

	public static function verifyAddressRequest(array $request) {
		if (empty($request["zipcode"]) && strlen($request["zipcode"]) < 8) {
			Address::setMsgError("informe o CEP");
			header("Location: /checkout");
			exit;
		}
		if (empty($request["desaddress"]) && strlen($request["desaddress"]) < 5) {
			Address::setMsgError("informe o endereço");
			header("Location: /checkout");
			exit;
		}
		if (empty($request["desdistrict"]) && strlen($request["desdistrict"]) < 4) {
			Address::setMsgError("informe o bairro");
			header("Location: /checkout");
			exit;
		}
		if (empty($request["descity"]) && strlen($request["descity"]) < 4) {
			Address::setMsgError("informe a cidade");
			header("Location: /checkout");
			exit;
		}
		if (empty($request["desstate"]) && strlen($request["desstate"]) < 2) {
			Address::setMsgError("informe o estado");
			header("Location: /checkout");
			exit;
		}
		if (empty($request["descountry"]) && strlen($request["descountry"]) < 2) {
			Address::setMsgError("informe o país");
			header("Location: /checkout");
			exit;
		}
	}
}