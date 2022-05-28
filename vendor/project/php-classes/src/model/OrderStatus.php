<?php
namespace Rootdir\Model;

use \Rootdir\Model as BaseModel;

class OrderStatus extends BaseModel {
    const EM_ABERTO = 1;
    const AGUARDANDO_PAGAMENTO = 2;
    const PAGO = 3;
    const ENTREGUE = 4;
}