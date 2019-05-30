<?
IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\Type\RandomSequence;

class CSaleOrderLoader
{
	const DEFUALT_VERSION = 2.05;
	const PARTIAL_VERSION = 2.1;

	const DEBUG_FILE = "1c_order_exchange.log";
	const DEBUG_MODE = true;

	var $strError = "";
	var $SumFormat = ".";
	var $QuantityFormat = ".";
	var $sdp = "";
	var $arParams = array();
	var $bNewVersion = false;
	var $arPersonTypesIDs = array();
	var $arExportInfo = array();
	var $arIBInfo = array();

	static $useStoreControl = null;
	static $operationType = "";
	static $versionSchema = "";

	public $strErrorDocument = "";
	public $version1C = "";
	public $xmlid1C = "";
	public $orderIdOrig = "";
	public $orederStatusId = "";
	public $modifyItem = array();
	public $needModifyItem = array();
	public $availableQuentityProduct = array();
	public $availableQuentityProductFromSystem = array();
	public $typeModifyShipment = '';

	public function getLogFileDir()
	{
		return  $_SERVER["DOCUMENT_ROOT"]."/".COption::GetOptionString("main", "upload_dir", "upload")."/1c_exchange/";
	}

	public function getLogFilePath()
	{
		return $this->getLogFileDir().self::DEBUG_FILE;
	}

	public function logMessage($message = '')
	{
		if(!static::DEBUG_MODE || !strlen($message))
			return;

		file_put_contents(
				$this->getLogFilePath(),
				$this->getTimeStampString().' '.$message.PHP_EOL,
				FILE_APPEND
		);
	}
	public function getTimeStampString()
	{
		return '['.date('H:i:s').']';
	}


	public static function onBeforeUpdateOrderWithoutShipmentsPayments(Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		/** @var Bitrix\Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Bitrix\Sale\Order)
		{
			return new Bitrix\Main\EventResult(
					Bitrix\Main\EventResult::ERROR,
					new Bitrix\Sale\ResultError(Bitrix\Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_ORDER_SAVE_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_SAVE_WRONG_ORDER'),
					'sale'
			);
		}
		/** @var Bitrix\Sale\Shipment $shipment */
		foreach($order->getShipmentCollection() as $shipment)
		{
			if (!$shipment->isSystem() && $shipment->getId() == null)
			{
				$shipment->delete();
			}
		}
		/** @var Bitrix\Sale\Payment $payment */
		foreach($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() == null)
			{
				$payment->delete();
			}
		}
		return new Bitrix\Main\EventResult( Bitrix\Main\EventResult::SUCCESS, null, 'sale');
	}

	public static function onBeforeSaveOrderWithoutShipmentsPayments(Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		/** @var Bitrix\Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Bitrix\Sale\Order)
		{
			return new Bitrix\Main\EventResult(
					Bitrix\Main\EventResult::ERROR,
					new Bitrix\Sale\ResultError(Bitrix\Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_ORDER_SAVE_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_SAVE_WRONG_ORDER'),
					'sale'
			);
		}

		$serviceId = \Bitrix\Main\Config\Option::get('sale', '1C_IMPORT_DEFAULT_SHIPMENT_SERVICE', 0);
		if(intval($serviceId)<=0)
		{
			$serviceId = Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			if($serviceId<=0)
			{
				\CSaleDelivery::createNoDeliveryServiceAgent();
				$serviceId = Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			}
		}

		/** @var Bitrix\Sale\Shipment $shipment */
		foreach($order->getShipmentCollection() as $shipment)
		{
			if (!$shipment->isSystem() && $shipment->getId() == null)
			{
				$shipment->delete();
			}
			if ($shipment->isSystem())
				$shipment->setFieldNoDemand('DELIVERY_ID', $serviceId);
		}
		/** @var Bitrix\Sale\Payment $payment */
		foreach($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() == null)
			{
				$payment->delete();
			}
		}
		return new Bitrix\Main\EventResult( Bitrix\Main\EventResult::SUCCESS, null, 'sale');
	}

	function getDocumentId($type,$code,$xmlId1CDocument)
	{
		$result = array();

		switch($type)
		{
			case 'Shipment':
			case 'Payment':
				if(strlen($code)>0)
					$result = array('ID'=>$code);
				break;
			case 'Order':
				if(strlen($code)>0)
					$result = array('ID'=>$this->getOrderIdByDocument($code));
				break;
		}

		if(strlen($code) <= 0 && strlen($xmlId1CDocument) > 0)//try to search document from 1C
		{
			switch($type)
			{
				case 'Shipment':
					if($shimpentCode1C = \Bitrix\Sale\Internals\ShipmentTable::getList(array(
							'select' => array('ID', 'ID_1C','ORDER_ID'),
							'filter' => array('ID_1C' => $xmlId1CDocument)
					))->fetch())
					{
						$result['ID'] = $shimpentCode1C['ID'];
						$result['ORDER_ID'] = $shimpentCode1C['ORDER_ID'];
					}
					break;
				case 'Payment':
					if($paymentCode1C = \Bitrix\Sale\Internals\PaymentTable::getList(array(
							'select' => array('ID', 'ID_1C','ORDER_ID'),
							'filter' => array('ID_1C' => $xmlId1CDocument),
							'order' => array('ID' => 'DESC')
					))->fetch())
					{
						$result['ID'] = $paymentCode1C['ID'];
						$result['ORDER_ID'] = $paymentCode1C['ORDER_ID'];
					}
					break;
				case 'Order':
					if($OrderCode1C = \Bitrix\Sale\Internals\OrderTable::getList(array(
							'select' => array('ID', 'ID_1C'),
							'filter' => array('ID_1C' => $xmlId1CDocument),
							'order' => array('ID' => 'DESC')
					))->fetch())
					{
						$result['ID'] = $OrderCode1C['ID'];
					}
					break;
			}
		}
		return $result;
	}
	function getOrderIdByDocument($orderCode)
	{
		$accountNumberPrefix = \Bitrix\Main\Config\Option::get("sale", "1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX", "");
		if(strlen($orderCode)>0)
		{
			if(intval($orderCode)>0)
			{
				if (!$orderByID = \Bitrix\Sale\Internals\OrderTable::getById($orderCode)->fetch())
				{
					if (!$orderBy1CID = \Bitrix\Sale\Internals\OrderTable::getList(array(
							'select' => array('ID'),
							'filter' => array('ID_1C' => $orderCode),
							'order' => array('ID' => 'DESC')
					))->fetch()
					)
					{
						if (!$orderByAccountNumber = \Bitrix\Sale\Internals\OrderTable::getList(array(
								'select' => array('ID'),
								'filter' => array('ACCOUNT_NUMBER' => $orderCode),
								'order' => array('ID' => 'DESC')
						))->fetch()
						)
						{
							if ($accountNumberPrefix != "")
							{
								if(strpos($orderCode,$accountNumberPrefix)===0)
								{
									$orderCode = substr($orderCode, strlen($accountNumberPrefix));
									if ($orderByID = \Bitrix\Sale\Internals\OrderTable::getById($orderCode)->fetch())
									{
										return $orderByID['ID'];
									}
								}
							}
						}
						else
							return $orderByAccountNumber['ID'];
					}
					else
						return $orderBy1CID['ID'];
				}
				else
					return $orderByID['ID'];
			}
			else
			{
				if (!$orderBy1CID = \Bitrix\Sale\Internals\OrderTable::getList(array(
						'select' => array('ID'),
						'filter' => array('ID_1C' => $orderCode),
						'order' => array('ID' => 'DESC')
				))->fetch()
				)
				{
					if (!$orderByAccountNumber = \Bitrix\Sale\Internals\OrderTable::getList(array(
							'select' => array('ID'),
							'filter' => array('ACCOUNT_NUMBER' => $orderCode),
							'order' => array('ID' => 'DESC')
					))->fetch()
					)
					{
						if ($accountNumberPrefix != "")
						{
							if(strpos($orderCode,$accountNumberPrefix)===0)
							{
								$orderCode = substr($orderCode, strlen($accountNumberPrefix));
								if ($orderByID = \Bitrix\Sale\Internals\OrderTable::getById($orderCode)->fetch())
								{
									return $orderByID['ID'];
								}
							}
						}
					}
					else
						return $orderByAccountNumber['ID'];
				}
				else
					return $orderBy1CID['ID'];
			}
		}
		return false;
	}
	function addBasketForShipment($arDocument, $newBasketItems, \Bitrix\Sale\Shipment $shipment)
	{
		$documentBasketItems = array();
		$documentShipmentItems = array();
		if(!empty($newBasketItems["items"]))
		{
			$arDocumentUpdate = array();
			foreach ($newBasketItems["items"] as $k=>$documentItems)
			{
				foreach ($documentItems as $itemID => $arItem)
				{
					if($arItem["TYPE"] == GetMessage("CC_BSC1_ITEM"))
					{
						$documentBasketItems[][$itemID] = $arItem;
						$arDocumentUpdate[][$itemID] = $arDocument['items'][$k][$itemID];
					}
					else
					{
						$documentShipmentItems = $arItem;
						//$documentShipmentItems['CURRENCY'] = $newBasketItems['CURRENCY'];
						$documentShipmentItems['CURRENCY'] = CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1"));
						$documentShipmentItems['DELIVERY_PRICE'] = $arItem['PRICE'];
					}
				}
			}
			if(count($arDocumentUpdate)>0)
				$arDocument['items'] = $arDocumentUpdate;

			if(is_array($documentBasketItems) && count($documentBasketItems)>0)
				$this->addProduct($arDocument, $documentBasketItems, $documentShipmentItems, $shipment);
		}
	}
	function updateOrderWithoutShipmentsPayments(array $arOrder)
	{
		global $APPLICATION;

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$key = $eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', Array($this, "onBeforeUpdateOrderWithoutShipmentsPayments"));

		$dbOrder = CSaleOrder::GetList(array(), array("ACCOUNT_NUMBER" => $arOrder["ID"]), false, false, array("ID", "LID", "PERSON_TYPE_ID", "PAYED", "DATE_PAYED", "CANCELED", "DATE_CANCELED", "REASON_CANCELED", "STATUS_ID", "DATE_STATUS", "PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE", "PRICE_DELIVERY", "ALLOW_DELIVERY", "DATE_ALLOW_DELIVERY", "PRICE", "CURRENCY", "DISCOUNT_VALUE", "USER_ID", "PAY_SYSTEM_ID", "DELIVERY_ID", "DATE_INSERT", "DATE_INSERT_FORMAT", "DATE_UPDATE", "USER_DESCRIPTION", "ADDITIONAL_INFO", "COMMENTS", "TAX_VALUE", "DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE", "STORE_ID", "ACCOUNT_NUMBER", "VERSION", "VERSION_1C", "ID_1C"));
		if($orderInfo = $dbOrder->Fetch())
		{
			if($arOrder["VERSION_1C"] != $orderInfo["VERSION_1C"] || (strlen($orderInfo["VERSION_1C"]) <= 0 || strlen($arOrder["VERSION_1C"]) <= 0)) // skip update if the same version
			{
				$this->logMessage("Order.VERSION_1C: ".$orderInfo["VERSION_1C"]);
				$this->logMessage("Document.VERSION_1C: ".$arOrder["VERSION_1C"]);
				$this->logMessage("Order.ID_1C: ".$orderInfo["ID_1C"]);
				$this->logMessage("Order.PAYED: ".$orderInfo["PAYED"]);
				$this->logMessage("Order.STATUS_ID: ".$orderInfo["STATUS_ID"]);

				$arOrderFields = array();
				$orderId = $orderInfo["ID"];
				CSaleOrderChange::AddRecord($orderId, "ORDER_1C_IMPORT");
				if($arOrder["ID_1C"] != $orderInfo["ID_1C"])
					$arOrderFields["ID_1C"] = $arOrder["ID_1C"];
				$arOrderFields["VERSION_1C"] = $arOrder["VERSION_1C"];

				if($orderInfo["PAYED"] != "Y" /*&& $orderInfo["ALLOW_DELIVERY"] != "Y"*/ && $orderInfo["STATUS_ID"] != "F")
				{
					$dbOrderTax = CSaleOrderTax::GetList(
							array(),
							array("ORDER_ID" => $orderId),
							false,
							false,
							array("ID", "TAX_NAME", "VALUE", "VALUE_MONEY", "CODE", "IS_IN_PRICE")
					);
					$bTaxFound = false;
					if($arOrderTax = $dbOrderTax->Fetch())
					{
						$bTaxFound = true;
						if(IntVal($arOrderTax["VALUE_MONEY"]) != IntVal($arOrder["TAX"]["VALUE_MONEY"]) || IntVal($arOrderTax["VALUE"]) != IntVal($arOrder["TAX"]["VALUE"]) || ($arOrderTax["IS_IN_PRICE"] != $arOrder["TAX"]["IS_IN_PRICE"]))
						{
							if(IntVal($arOrder["TAX"]["VALUE"])>0)
							{
								$arFields = Array(
										"TAX_NAME" => $arOrder["TAX"]["NAME"],
										"ORDER_ID" => $orderId,
										"VALUE" => $arOrder["TAX"]["VALUE"],
										"IS_PERCENT" => "Y",
										"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
										"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
										"CODE" => "VAT1C",
										"APPLY_ORDER" => "100"
								);
								CSaleOrderTax::Update($arOrderTax["ID"], $arFields);
								$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
							}
							else
							{
								CSaleOrderTax::Delete($arOrderTax["ID"]);
								$arOrderFields["TAX_VALUE"] = 0;
							}
						}
					}

					if(!$bTaxFound)
					{
						if(IntVal($arOrder["TAX"]["VALUE"])>0)
						{
							$arFields = Array(
									"TAX_NAME" => $arOrder["TAX"]["NAME"],
									"ORDER_ID" => $orderId,
									"VALUE" => $arOrder["TAX"]["VALUE"],
									"IS_PERCENT" => "Y",
									"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
									"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"]
							);
							CSaleOrderTax::Add($arFields);
							$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
						}
					}

					$arShoppingCart = array();
					$bNeedUpdate = false;

					$order = \Bitrix\Sale\Order::load($orderId);
					$basket = $order->getBasket();

					$this->logMessage("Document.items: ".print_r($arOrder["items"], true));

					foreach($basket as $basketItem)
					{
						/** @var Bitrix\Sale\BasketItem $basketItem */

						$arFields = Array();
						$foundedBasketItem = false;
						foreach($arOrder["items"] as $k=>$items)
						{
							if($this->existsBasketItem($basketItem, $items))
							{
								$foundedBasketItem = true;

								if($basketItem->getField("QUANTITY") != $items[$basketItem->getField("PRODUCT_XML_ID")]["QUANTITY"])
									$arFields["QUANTITY"] = $items[$basketItem->getField("PRODUCT_XML_ID")]["QUANTITY"];
								if($basketItem->getField("PRICE") != $items[$basketItem->getField("PRODUCT_XML_ID")]["PRICE"])
									$arFields["PRICE"] = $items[$basketItem->getField("PRODUCT_XML_ID")]["PRICE"];
								if($basketItem->getField("VAT_RATE") != $items[$basketItem->getField("PRODUCT_XML_ID")]["VAT_RATE"])
									$arFields["VAT_RATE"] = $items[$basketItem->getField("PRODUCT_XML_ID")]["VAT_RATE"];
								//if($basketItem->getField("DISCOUNT_PRICE") != $items[$basketItem->getField("PRODUCT_XML_ID")]["DISCOUNT_PRICE"])
									$arFields["DISCOUNT_PRICE"] = $items[$basketItem->getField("PRODUCT_XML_ID")]["DISCOUNT_PRICE"];

								if(count($arFields)>0)
								{
									$arFields["ID"] = $basketItem->getField("ID");
									if(DoubleVal($arFields["QUANTITY"]) <= 0)
										$arFields["QUANTITY"] = $basketItem->getField("QUANTITY");
									$bNeedUpdate = true;
									$arShoppingCart[] = $arFields;
								}
								else
								{
									$arShoppingCart[] = array(
											"ID"=>						$basketItem->getField("ID"),
											"QUANTITY"=>				$basketItem->getField("QUANTITY"),
											"CANCEL_CALLBACK_FUNC"=>	$basketItem->getField("CANCEL_CALLBACK_FUNC"),
											"MODULE"=>					$basketItem->getField("MODULE"),
											"PRODUCT_ID"=>				$basketItem->getField("PRODUCT_ID"),
											"PRODUCT_PROVIDER_CLASS"=>	$basketItem->getField("PRODUCT_PROVIDER_CLASS"),
											"RESERVED"=>				$basketItem->getField("RESERVED"),
											"RESERVE_QUANTITY"=>		$basketItem->getField("RESERVE_QUANTITY"),
											"TYPE"=>					$basketItem->getField("TYPE"),
											"SET_PARENT_ID"=>			$basketItem->getField("SET_PARENT_ID"),
											"PRICE"=>					$basketItem->getField("PRICE"),
											"VAT_RATE"=>				$basketItem->getField("VAT_RATE"),
											"DISCOUNT_PRICE"=>			$basketItem->getField("DISCOUNT_PRICE"),
											"PRODUCT_XML_ID"=>			$basketItem->getField("PRODUCT_XML_ID"),
											"PROPS"=>					$this->getBasketProperty($items[$basketItem->getField("PRODUCT_XML_ID")])
									);
								}

								$arOrder["items"][$k][$basketItem->getField("PRODUCT_XML_ID")]["CHECKED"] = "Y";
							}
						}

						if(!$foundedBasketItem)
						{
							if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true" && $orderInfo["CANCELED"] == "N")
							{
								$bNeedUpdate = true;
							}
						}
					}

					if(!empty($arOrder["items"]))
					{
						foreach ($arOrder["items"] as $k=>$items)
						{
							foreach ($items as $itemID => $item)
							{
								if ($item["CHECKED"] != "Y")
								{
									if ($item["TYPE"] == GetMessage("CC_BSC1_ITEM"))
									{
										if ($arBasketFields = $this->prepareProduct4Basket($itemID, $item, $orderId, $orderInfo))
										{
											$arBasketFields['PROPS'] = $this->getBasketProperty($item);
											$arShoppingCart[] = $arBasketFields;
											$bNeedUpdate = true;
										}
									}
									elseif ($item["TYPE"] == GetMessage("CC_BSC1_SERVICE"))
									{
										if (IntVal($item["PRICE"]) != IntVal($orderInfo["PRICE_DELIVERY"]))
											$arOrderFields["PRICE_DELIVERY"] = $item["PRICE"];
									}
								}
							}
						}
					}

					$this->logMessage("Order.NewItems: ".print_r($arShoppingCart, true));

					if($bNeedUpdate)
					{
						$arErrors = array();
						if(!CSaleBasket::DoSaveOrderBasket($orderId, $orderInfo["LID"], $orderInfo["USER_ID"], $arShoppingCart, $arErrors))
						{
							$e = $APPLICATION->GetException();
							if(is_object($e))
								$this->strErrorDocument .= "\r\n ".GetMessage("CC_BSC1_ORDER_ERROR_3", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).$e->GetString();

						}
					}
					$this->logMessage("Document.AMOUNT: ".$arOrder["AMOUNT"]);
					$this->logMessage("Order.AMOUNT: ".$orderInfo["PRICE"]);
					$this->logMessage("Order.AMOUNT: ".$orderInfo["DISCOUNT_VALUE"]);
					$this->logMessage("Document.COMMENT: ".$arOrder["COMMENT"]);
					$this->logMessage("Order.COMMENTS: ".$orderInfo["COMMENTS"]);

					if(DoubleVal($arOrder["AMOUNT"]) > 0 && $arOrder["AMOUNT"] != $orderInfo["PRICE"])
						$arOrderFields["PRICE"] = $arOrder["AMOUNT"];
					if(DoubleVal($orderInfo["DISCOUNT_VALUE"]) > 0)
						$arOrderFields["DISCOUNT_VALUE"] = 0;
					if(strlen($arOrder["COMMENT"]) > 0 && $arOrder["COMMENT"] != $orderInfo["COMMENTS"])
						$arOrderFields["COMMENTS"] = $arOrder["COMMENT"];
					$arOrderFields["UPDATED_1C"] = "Y";
					if(!empty($arOrderFields))
						CSaleOrder::Update($orderId, $arOrderFields);
				}
				else
				{
					$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_4", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).GetMessage("CC_BSC1_FINAL_NOT_EDIT", Array("#ID#" => $orderId));
				}
			}

			$this->logMessage("Document.CANCELED: ".$arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")]);
			$this->logMessage("Document.CANCELED: ".$arOrder["TRAITS"][GetMessage("CC_BSC1_CANCEL")]);
			$this->logMessage("Order.CANCELED: ".$orderInfo["CANCELED"]);

			$arAditFields = Array();
			if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] == "true" || $arOrder["TRAITS"][GetMessage("CC_BSC1_CANCEL")] == "true")
			{
				if($orderInfo["CANCELED"] == "N")
				{
					CSaleOrder::CancelOrder($orderInfo["ID"], "Y", $arOrder["COMMENT"]);
					$arAditFields["UPDATED_1C"] = "Y";
				}
			}
			else
			{
				if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true")
				{
					if($orderInfo["CANCELED"] == "Y")
					{
						CSaleOrder::CancelOrder($orderInfo["ID"], "N", $arOrder["COMMENT"]);
						$arAditFields["UPDATED_1C"] = "Y";
					}
				}
			}


			if(\Bitrix\Main\Config\Option::get("sale", "1C_CHANGE_STATUS_FROM_1C", "") && strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])>0)
			{
				if($orderInfo["STATUS_ID"] != $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])
				{
					CSaleOrder::StatusOrder($orderInfo["ID"], $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")]);
				}
			}

			if(count($arAditFields)>0)
				CSaleOrder::Update($orderInfo["ID"], $arAditFields);
		}
		else
			$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_4", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).GetMessage("CC_BSC1_ORDER_NOT_FOUND", Array("#ID#" => $arOrder["ID"]));

		removeEventHandler('sale', 'OnSaleOrderBeforeSaved', $key);
	}

	function saveOrderCompatibility($arDocument, $arAditFields=array())
	{
		$documentBasketItems = array();
		$this->arPersonTypesIDs = array();
		$this->arExportInfo = array();

		$this->logMessage("Document.items: ".print_r($arDocument["items"], true));

		if(!empty($arDocument["items"]) && strlen($arDocument['AMOUNT'])>0)
		{
			if($this->documentMustHaveProducts($arDocument))
			{
				$arDocument = $this->getUserByProperty($arDocument);
				$this->setPersonTypesID();
				$this->setExportInfo();

				$this->logMessage("personType: ".$arDocument["PERSON_TYPE_ID"]);
				$this->logMessage("personTypesIDs: ".print_r($this->arPersonTypesIDs, true));
				$this->logMessage("exportInfo: ".print_r($this->arExportInfo, true));

				if(IntVal($arDocument["PERSON_TYPE_ID"]) <= 0)
				{
					$arDocument = $this->getPersonType($arDocument);
				}
				if(IntVal($arDocument["PERSON_TYPE_ID"]) > 0)
				{
					$arAgent = $this->getExportInfo($arDocument);

					if(IntVal($arDocument["USER_ID"]) > 0)
					{
						$orderFields = $this->prepareOrderFields($arDocument);

						$arAditFields["DATE_INSERT"] = CDatabase::FormatDate($arDocument["DATE"]." ".$arDocument["TIME"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG));

						foreach($arDocument["items"] as $k=>$items)
						{
							foreach($items as $productID=>$item)
							{
								if($item['TYPE'] <> GetMessage("CC_BSC1_SERVICE"))
								{
									$orderFields["BASKET_ITEMS"][$k] = $this->prepareProduct4Basket($productID, $item, false, $orderFields);
									$orderFields["BASKET_ITEMS"][$k]['PROPS'] = $this->getBasketProperty($item);
								}
							}
						}

						$orderFields["TAX_LIST"] = $this->getOrderTaxList($arDocument);
						$orderFields = $this->getOrderProperty($arAgent, $orderFields, $arDocument);

						$this->logMessage("BASKET_ITEMS: ".print_r($orderFields["BASKET_ITEMS"], true));

						$arOrder = $this->saveOrder($arDocument, $orderFields, $arAditFields);
						return $arOrder;
					}
					else
					{
						$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_4", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).GetMessage("CC_BSC1_ORDER_USER_PROBLEM", Array("#ID#" => $arDocument["ID_1C"]));
						if(!empty($arErrors))
						{
							foreach($arErrors as $v)
							{
								$this->strErrorDocument .= "\n".$v["TEXT"];
							}
						}
					}
				}
				else
				{
					$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_4", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).GetMessage("CC_BSC1_ORDER_PERSON_TYPE_PROBLEM", Array("#ID#" => $arDocument["ID_1C"]));
				}
			}
			else
			{
				$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_BASKET_ITEMS_PROBLEM");
			}
		}
		else
		{
			$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_BASKET_ITEMS_AMOUNT_NULL_PROBLEM");
		}

		return array();
	}
	function Paid(\Bitrix\Sale\Payment $payment, $arDocument)
	{
		$this->logMessage("PaidPayment: ");
		$this->logMessage("Document.PAYED: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED")]);
		$this->logMessage("Document.Date: ".$arDocument["DATE"]);
		$this->logMessage("Document.PAY_VOUCHER_NUM: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]);

		if(intval($payment->getId())>0)
		{
			if ($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED")] == "true")
			{
				if($arDocument["TRAITS"][GetMessage("CC_BSC1_CANCELED")] !== "true" && $arDocument["TRAITS"][GetMessage("CC_BSC1_CANCEL")] !== "true")
				{
					if (!$payment->isPaid())
					{
						$this->logMessage("PaidPayment: Y");
						$payment->setField('PAID', 'Y');
					}
				}

				$date = new Bitrix\Main\Type\Date(CDatabase::FormatDate(str_replace("T", " ", $arDocument["DATE"]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG)));
				$payment->setField("PAY_VOUCHER_DATE", $date);

				if (strlen($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]) > 0)
					$payment->setField("PAY_VOUCHER_NUM", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]);

				return true;
			}
		}
		return false;
	}

	function Ship(\Bitrix\Sale\Shipment $shipment, $arDocument)
	{

		$this->logMessage("ShipShipment: ");
		$this->logMessage("ShipmentId: ".$shipment->getId());
		$this->logMessage("Document.DELIVERY_DATE: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_DATE")]);
		$this->logMessage("Document.DELIVERY_NUM: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")]);
		$this->logMessage("Document.TRACKING_NUMBER: ".$arDocument['TRACKING_NUMBER']);

		/** @var Bitrix\Sale\Order $order */
		$order = $shipment->getCollection()->getOrder();

		if(strlen($arDocument["DATE"])>1)
		{
			if($arDocument["TRAITS"][GetMessage("CC_BSC1_DEDUCTED")] == "true" && !$shipment->isShipped())
			{
				$shipment->setField('ALLOW_DELIVERY','Y');
				$shipment->setField('DEDUCTED','Y');
				$this->logMessage("Shipment.ALLOW_DELIVERY: Y");
				$this->logMessage("Shipment.DEDUCTED: Y");
			}

			$date = new Bitrix\Main\Type\Date(CDatabase::FormatDate(str_replace("T", " ", $arDocument["DATE"]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG)));
			$shipment->setField("DELIVERY_DOC_DATE", $date);

			$statusDelivery = \Bitrix\Main\Config\Option::get("sale", "1C_FINAL_STATUS_ON_DELIVERY", "DA");
			if(strlen($statusDelivery)>0 && $order->getField("STATUS_ID") != "F" && $shipment->getField("STATUS_ID") != $statusDelivery)
			{
				$shipment->setField('STATUS_ID', $statusDelivery);
			}

			if(strlen($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")])>0)
				$shipment->setField("DELIVERY_DOC_NUM", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")]);

			if (isset($arDocument['TRACKING_NUMBER']) && strval($arDocument['TRACKING_NUMBER']) != '')
				$shipment->setField('TRACKING_NUMBER', $arDocument['TRACKING_NUMBER']);
		}
	}
	function saveOrder($arDocument, $orderFields, $arAditFields)
	{
		$arOrder = $arDocument;
		unset($arOrder['ID']);

		if($arOrder["ID"] = CSaleOrder::DoSaveOrder($orderFields, $arAditFields, 0, $arErrors))
		{
			//add/update user profile
			if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
			{
				if($arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"])
					CSaleOrderUserProps::Update($arOrder["USER_PROFILE_ID"], array("VERSION_1C" => $arOrder["AGENT"]["VERSION"], "NAME" => $arOrder["AGENT"]["AGENT_NAME"]));
				$dbUPV = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" =>$arOrder["USER_PROFILE_ID"]));
				while($arUPV = $dbUPV->Fetch())
				{
					$arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arUPV["ORDER_PROPS_ID"]] = array("ID" => $arUPV["ID"], "VALUE" => $arUPV["VALUE"]);
				}
			}

			if(IntVal($arOrder["USER_PROFILE_ID"]) <= 0 || (IntVal($arOrder["USER_PROFILE_ID"]) > 0 && $arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"]))
			{
				$dbOrderProperties = CSaleOrderProps::GetList(
						array("SORT" => "ASC"),
						array(
								"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
								"ACTIVE" => "Y",
								"UTIL" => "N",
								"USER_PROPS" => "Y",
						),
						false,
						false,
						array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
				);
				while ($arOrderProperties = $dbOrderProperties->Fetch())
				{
					$curVal = $orderFields["ORDER_PROP"][$arOrderProperties["ID"]];

					if (strlen($curVal) > 0)
					{
						if (IntVal($arOrder["USER_PROFILE_ID"]) <= 0)
						{
							$arFields = array(
									"NAME" => $arOrder["AGENT"]["AGENT_NAME"],
									"USER_ID" => $arOrder["USER_ID"],
									"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
									"XML_ID" => $arOrder["AGENT"]["ID"],
									"VERSION_1C" => $arOrder["AGENT"]["VERSION"],
							);
							$arOrder["USER_PROFILE_ID"] = CSaleOrderUserProps::Add($arFields);
						}
						if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
						{
							$arFields = array(
									"USER_PROPS_ID" => $arOrder["USER_PROFILE_ID"],
									"ORDER_PROPS_ID" => $arOrderProperties["ID"],
									"NAME" => $arOrderProperties["NAME"],
									"VALUE" => $curVal
							);
							if(empty($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]))
							{
								CSaleOrderUserPropsValue::Add($arFields);
							}
							elseif($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["VALUE"] != $curVal)
							{
								CSaleOrderUserPropsValue::Update($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["ID"], $arFields);
							}
						}
					}
				}
			}
		}
		else
		{
			$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ADD_PROBLEM", Array("#ID#" => $arDocument["XML_1C_DOCUMENT_ID"]));
			if(is_array($arErrors))
				$this->strErrorDocument .= "\n".implode(', ',$arErrors);

		}
		return $arOrder;
	}

	function addProduct($arDocument, $documentBasketItems, $documentShipmentItems, \Bitrix\Sale\Shipment $shipment)
	{

		$this->logMessage("addProduct.documentBasketItems: ".print_r($documentBasketItems, true));
		$this->logMessage("addProduct.shipment: ".$shipment->getId());
		$this->logMessage("addProduct.documentShipmentItems: ".print_r($documentShipmentItems, true));

		/** @var Bitrix\Sale\Order $order */
		$order = $shipment->getCollection()->getOrder();

		foreach($documentBasketItems as $item)
		{
			foreach($item as $itemID=>$arItem)
			{
				$arBasketFields = $this->prepareProduct4Basket($itemID, $arItem, $order->getId(), array('SITE_ID'=>$order->getField('LID'),'CURRENCY'=>$order->getField('CURRENCY'),'LID'=>$order->getField('LID')));
				$item = $order->getBasket()->createItem($arBasketFields["MODULE"], $arBasketFields["PRODUCT_ID"]);

				/** @var Bitrix\Sale\BasketPropertiesCollection $propertyCollection */
				if ($propertyCollection = $item->getPropertyCollection())
				{
					$property = $this->getBasketProperty($arItem);
					if(is_array($property) && count($property)>0)
						$propertyCollection->setProperty($property);
				}

				unset($arBasketFields["ORDER_ID"]);
				unset($arBasketFields["MODULE"]);
				unset($arBasketFields["PRODUCT_ID"]);
				unset($arBasketFields["LID"]);
				unset($arBasketFields["PROPS"]);

				$item->setFields($arBasketFields);
			}
		}

		if (isset($documentShipmentItems['DELIVERY_PRICE']) && floatval($documentShipmentItems['DELIVERY_PRICE']) > 0)
		{
			$basePriceDelivery = $documentShipmentItems['DELIVERY_PRICE'];
			$shipment->setFieldNoDemand('BASE_PRICE_DELIVERY', $basePriceDelivery);
			$shipment->setFieldNoDemand('CURRENCY', $order->getField('CURRENCY'));
			$shipment->setFieldNoDemand('PRICE_DELIVERY', $basePriceDelivery);
		}
		$this->updateShipmentQuantityFromDocument($arDocument, $shipment);
	}

	function updatePaymentFromDocument(array $arDocument, \Bitrix\Sale\Payment $payment, \Bitrix\Sale\Order $order)
	{
		$this->logMessage("UpdatePayment: ");
		$this->logMessage("ID: ".$arDocument['ID']);
		$this->logMessage("ORDER_ID: ".$arDocument['ORDER_ID']);
		$this->logMessage("Payment.VERSION_1C: ".$payment->getField('VERSION_1C'));
		$this->logMessage("Document.VERSION_1C: ".$arDocument["VERSION_1C"]);

		if($arDocument["VERSION_1C"] != $payment->getField('VERSION_1C') || (strlen($payment->getField('VERSION_1C')) <= 0 || strlen($arDocument["VERSION_1C"]) <= 0)) // skip update if the same version
		{
			$this->logMessage("Payment.isPaid: ".($payment->isPaid()? 'Y':'N'));

			if (!$payment->isPaid())
			{
				$this->logMessage("Payment.SUM: ".$payment->getField('SUM'));
				$this->logMessage("Document.AMOUNT: ".$arDocument["AMOUNT"]);
				$this->logMessage("Payment.isPaid: ".$payment->getField('COMMENT'));
				$this->logMessage("Document.isPaid: ".$arDocument["COMMENT"]);

				if ($arDocument["AMOUNT"] != $payment->getField('SUM'))
					$payment->setField("SUM", $arDocument["AMOUNT"]);
				if (strlen($arDocument["COMMENT"]) > 0 && $arDocument["COMMENT"] != $payment->getField('COMMENT'))
					$payment->setField("COMMENTS", $arDocument["COMMENT"]);
			}
		}

		$this->logMessage("Document.CANCELED: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_CANCELED")]);
		$this->logMessage("Document.CANCEL: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_CANCEL")]);

		if($arDocument["TRAITS"][GetMessage("CC_BSC1_CANCELED")] == "true" || $arDocument["TRAITS"][GetMessage("CC_BSC1_CANCEL")] == "true")
		{
			if($payment->isPaid())
			{
				$payment->setField("PAID","N");
				$payment->setField("COMMENTS",$arDocument["COMMENT"]);
			}
		}
		else
		{
			if($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_RETURN")] == "true")
			{
				$payment->setField("IS_RETURN", 'Y');
				$payment->setField("PAY_RETURN_COMMENT", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_RETURN_REASON")]);
			}
		}
	}
	function addPaymentFromDocumentByOrder(array $arDocument, \Bitrix\Sale\Order $order)
	{
		$this->logMessage("NewPayment: ");
		$this->logMessage("ORDER_ID: ".$arDocument['ORDER_ID']);
		$this->logMessage("CURRENCY: ".CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1")));
		$this->logMessage("Document.VERSION_1C: ".$arDocument["VERSION_1C"]);
		$this->logMessage("Document.AMOUNT: ".$arDocument["AMOUNT"]);
		$this->logMessage("Document.COMMENT: ".$arDocument["COMMENT"]);
		$this->logMessage("Document.PAYED: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED")]);
		$this->logMessage("Document.PAYED_DATE: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")]);
		$this->logMessage("Document.PAYED_NUM: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]);

		$paymentServiceId =	intval(\Bitrix\Main\Config\Option::get('sale', self::getOptionNameByType(), 0));
		if($paymentServiceId<=0)
			$paymentServiceId = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();

		if ($paySystem = \Bitrix\Sale\PaySystem\Manager::getObjectById($paymentServiceId))
		{
			/** @var Bitrix\Sale\Payment $payment */
			$paymentCollection = $order->getPaymentCollection();
			$payment = $paymentCollection->createItem($paySystem);
			$payment->setField("SUM", $arDocument["AMOUNT"]);
			$payment->setField("CURRENCY", CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1"))) ;

			$payment->setField("COMMENTS", $arDocument["COMMENT"]);

			if ($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED")] == "true")
			{
				$this->logMessage("Payment.Paid: Y");

				$payment->setField('PAID', 'Y');

				if (strlen($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")]) > 0)
				{
					$date = new Bitrix\Main\Type\Date(CDatabase::FormatDate(str_replace("T", " ", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG)));
					$payment->setField("PAY_VOUCHER_DATE", $date);
				}

				if (strlen($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]) > 0)
					$payment->setField("PAY_VOUCHER_NUM", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")]);
			}

			return $payment;

		}
		else
			$this->strErrorDocument .= "\n\r ".GetMessage("CC_BSC1_PAYMENT_ERROR_7", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).GetMessage("CC_BSC1_ORDER_NOT_FOUND");

		return null;
	}

	function addOrderWithoutShipmentsPayments(array $arDocument, $addOrderField=array())
	{
		$this->logMessage("NewShipmentNewOrder: ");

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$key = $eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', Array($this, "onBeforeSaveOrderWithoutShipmentsPayments"));

		$arOrder = $this->saveOrderCompatibility($arDocument, $addOrderField);

		removeEventHandler('sale', 'OnSaleOrderBeforeSaved', $key);

		return $arOrder;
	}
	function addShipmentFromDocumentByOrder(array $arDocument, \Bitrix\Sale\Order $order)
	{
		$this->logMessage("NewShipment: ");

		$serviceId = \Bitrix\Main\Config\Option::get('sale', '1C_IMPORT_DEFAULT_SHIPMENT_SERVICE', 0);
		if(intval($serviceId)<=0)
		{
			$serviceId = Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			if($serviceId<=0)
			{
				\CSaleDelivery::createNoDeliveryServiceAgent();
				$serviceId = Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			}
		}
		$service = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($serviceId);

		$shipmentCollection = $order->getShipmentCollection();

		$shipment = $shipmentCollection->createItem($service);

		$shipment->setField('DELIVERY_NAME', $service->getName());

		$this->updateShipmentQuantityFromDocument($arDocument, $shipment);

		return $shipment;
	}
	function updateShipmentQuantityFromDocument(array $arDocument, \Bitrix\Sale\Shipment $shipment)
	{
		$this->logMessage("UpdateShipment: ");
		$this->logMessage("ID: ".$arDocument['ID']);
		$this->logMessage("ORDER_ID: ".$arDocument['ORDER_ID']);
		$this->logMessage("Shipment.VERSION_1C: ".$shipment->getField('VERSION_1C'));
		$this->logMessage("Document.VERSION_1C: ".$arDocument["VERSION_1C"]);
		$this->logMessage("Document.items: ".print_r($arDocument['items'], true));
		$this->logMessage("shipment.ID: ".$shipment->getId());

		if ($this->checkVersion1C($shipment)) // skip update if the same version
		{
			$basket = $shipment->getCollection()->getOrder()->getBasket();

			foreach ($basket as $basketItem)
			{
				foreach ($arDocument['items'] as $k=>$item)
				{
					if($this->existsBasketItem($basketItem, $item))
						$this->logMessage("Document.Price BasketItem.Price: ".$item[$basketItem->getField('PRODUCT_XML_ID')]['PRICE']." ".$basketItem->getField('PRICE'));


					if(
						$this->existsBasketItem($basketItem, $item) &&
						$item[$basketItem->getField('PRODUCT_XML_ID')]['PRICE'] == $basketItem->getField('PRICE')
					)
					{
						if(!$this->checkConditionForShipmentByBasket($basketItem, $shipment))
							continue;

						$shipmentItemCollection = $shipment->getShipmentItemCollection();
						$shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode());
						if ($shipmentItem === null)
							$shipmentItemQuantity = 0;
						else
							$shipmentItemQuantity = $shipmentItem->getQuantity();

						$this->logMessage("shipmentItemQuantity: ".$shipmentItemQuantity);

						$externalQuantity = intval($item[$basketItem->getField('PRODUCT_XML_ID')]['QUANTITY']);
						$this->logMessage("externalQuantity: ".$externalQuantity);

						if($externalQuantity < $shipmentItemQuantity)
						{
							$needQuantity = $shipmentItemQuantity-$externalQuantity;

							$this->setShipmentItemQuantity(array('minus',$needQuantity), $basketItem, $shipment);
						}
						elseif($externalQuantity > $shipmentItemQuantity)
						{
							$availableQuantityByProducts = $this->getAvailableQuentityProduct($basketItem, $shipment);
							$this->logMessage("availableQuantityByProducts: ".$availableQuantityByProducts);

							if($externalQuantity <= $availableQuantityByProducts)
							{
								$needQuantity = $externalQuantity-$shipmentItemQuantity;
								$this->logMessage("needQuantity: ".$needQuantity);

								$this->updateShipmentItemQuantity($needQuantity, $basketItem, $shipment);

							}
							elseif(\Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_UPDATE_BASKET_QUANTITY", 'Y')=='Y')
							{
								$this->updateBasketNeedExternalQuantity($externalQuantity, $availableQuantityByProducts, $basketItem, $shipment);
							}
							else
							{
								$this->strErrorDocument .= "\n\r ".GetMessage("CC_BSC1_SHIPMENT_ERROR_8", Array('#ID#'=>$shipment->getId(),'#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
							}
						}

						$arDocument['items'][$k][$basketItem->getField('PRODUCT_XML_ID')]["CHECKED"] = "Y";

					}
					elseif(
							$this->existsBasketItem($basketItem, $item) &&
							$item[$basketItem->getField('PRODUCT_XML_ID')]['PRICE'] <> $basketItem->getField('PRICE')
					)
					{
						$this->addNeedModifyItem('CHANGE_PRODUCTS',array(
								'PRODUCT_XML_ID'=>$basketItem->getField('PRODUCT_XML_ID'),
								'PRICE_ORIG'=>$basketItem->getField('PRICE'),
								'PRICE_NEW'=>$item[$basketItem->getField('PRODUCT_XML_ID')]['PRICE_ONE'],
								'BASKET_ID'=>$basketItem->getId(),
								'SHIPMENT_ID'=>$shipment->getId(),
						));
					}
				}
			}

			if(!empty($arDocument["items"]))
			{
				$newBasketItems = array();
				foreach ($arDocument["items"] as $k=>$item)
				{
					foreach ($item as $itemID => $arItem)
					{
						if ($arItem["CHECKED"] != "Y")
						{
							if ($product = $this->getProductChangePrice($itemID, $arItem['PRICE_ONE']))
							{
								$this->strErrorDocument .= GetMessage("CC_BSC1_SHIPMENT_ERROR_9", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
							}
							else
							{
								$newBasketItems['items'][$k][$itemID] = $arItem;
							}
						}
					}
				}


				if(is_array($newBasketItems) && count($newBasketItems)>0)
				{
					$newBasketItems['ORDER_ID'] = $arDocument['ORDER_ID'];
					$newBasketItems['CURRENCY'] = CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1"));
					$this->addBasketForShipment($arDocument, $newBasketItems, $shipment);
				}
			}
		}

		if(!$shipment->isShipped())
		{
			foreach ($arDocument['items'] as $item)
			{
				foreach ($item as $arItem)
				{
					if($arItem["TYPE"] == GetMessage("CC_BSC1_SERVICE"))
					{
						if (IntVal($arItem["PRICE"]) != IntVal($shipment->getField('PRICE_DELIVERY')))
						{
							$shipment->setFieldNoDemand("PRICE_DELIVERY", $arItem["PRICE"]);
							$shipment->setFieldNoDemand('BASE_PRICE_DELIVERY', $arItem["PRICE"]);
							$shipment->setFieldNoDemand('CURRENCY',CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1")));
						}

					}
				}
			}
			if (strlen($arDocument["COMMENT"]) > 0 && $arDocument["COMMENT"] != $shipment->getField('COMMENT'))
				$shipment->setField("COMMENTS", $arDocument["COMMENT"]);
		}

		$this->logMessage("Document.DELIVERY_NUM: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")]);
		$this->logMessage("Document.TRACKING_NUMBER: ".$arDocument['TRACKING_NUMBER']);
		$this->logMessage("Document.CANCELED: ".$arDocument["TRAITS"][GetMessage("CC_BSC1_CANCELED")]);


		$statusPartialDelivery = \Bitrix\Main\Config\Option::get("sale", "1C_FINAL_STATUS_ON_DELIVERY", "F");
		if(strlen($statusPartialDelivery)>0 && $shipment->getCollection()->getOrder()->getField("STATUS_ID") != "F" && $shipment->getField("STATUS_ID") != $statusPartialDelivery)
		{
			$shipment->setField('STATUS_ID', $statusPartialDelivery);
		}
		if(strlen($arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")])>0)
			$shipment->setField("DELIVERY_DOC_NUM", $arDocument["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")]);

		if (isset($arDocument['TRACKING_NUMBER']) && strval($arDocument['TRACKING_NUMBER']) != '')
			$shipment->setField('TRACKING_NUMBER', $arDocument['TRACKING_NUMBER']);

		if($arDocument["TRAITS"][GetMessage("CC_BSC1_CANCELED")] == "true")
		{
			if($shipment->isShipped())
			{
				$this->logMessage("Shipment.DEDUCTED: N");
				$shipment->setField('DEDUCTED','N');
			}
		}
	}

	function deleteShipmentItemsByDocument($arDocument, \Bitrix\Sale\Shipment $shipment)
	{
		if ($this->checkVersion1C($shipment)) // skip update if the same version
		{
			/** @var \Bitrix\Sale\BasketItem $basketItems */
			$basket = $shipment->getCollection()->getOrder()->getBasket();
			foreach($basket as $basketItem)
			{
				foreach($arDocument['items'] as $item)
				{
					if(!$this->existsBasketItem($basketItem, $item))
					{
						$shipmentItems = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketItem->getId());
						if (!is_null($shipmentItems))
						{
							$shipmentItems->delete();
							$this->logMessage("BasketItem: " . "[" . $shipmentItems->getId() . "]" . $shipmentItems->getField('NAME'));
						}
					}
				}
			}
		}
	}

	function updateShipmentNeedQuantity($externalQuantity, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment, $shipmentItemFields)
	{
		$availableQuantityByProducts = $this->getAvailableQuentityProduct($basketItem->getId());

		if(empty($availableQuantityByProducts['SYSTEM'][$basketItem->getField('PRODUCT_XML_ID')]))
		{
			$systemShipmentQuantity = 0;
		}
		else
		{
			$systemShipmentQuantity = intval($availableQuantityByProducts['SYSTEM'][$basketItem->getField('PRODUCT_XML_ID')]);
		}

		$needQuantity = $externalQuantity-$shipmentItemFields['QUANTITY'];

		if($systemShipmentQuantity >= $needQuantity)
		{
			$this->updateShipmentNeedQuantityFromSystem($needQuantity, $basketItem, $shipment);
		}
		else
		{
			$needQuantityWithoutSystem = $needQuantity-$systemShipmentQuantity;

			$this->updateShipmentNeedQuantityFromBasketItems( $needQuantityWithoutSystem, $needQuantity, $basketItem, $shipment);
		}
	}
	function updateShipmentNeedQuantityFromBasketItems( $needQuantityWithoutSystem, $needQuantity, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$this->logMessage("updateShipmentNeedQuantityFromBasketItems.needQuantityWithoutSystem: ".$needQuantityWithoutSystem);
		$this->logMessage("updateShipmentNeedQuantityFromBasketItems.needQuantity: ".$needQuantity);

		$order = $basketItem->getCollection()->getOrder();

		foreach($order->getShipmentCollection() as $shipmentAny)
		{
			if($shipmentAny->isSystem())
				continue;
			if($shipmentAny->getId() == $shipment->getId())
				continue;
			if(!$this->checkConditionForShipmentByBasket($basketItem, $shipmentAny))
				continue;

			$shipmentItemAny = $shipmentAny->getShipmentItemCollection()->getItemByBasketCode($basketItem->getBasketCode());
			if($shipmentItemAny === null)
				continue;

			$this->logMessage("updateShipmentNeedQuantityFromBasketItems.BasketCode: ".$basketItem->getBasketCode());
			$this->logMessage("updateShipmentNeedQuantityFromBasketItems.shipmentItemQuantity: ".$shipmentItemAny->getQuantity());

			if($needQuantityWithoutSystem < $shipmentItemAny->getQuantity())
			{
				$this->setShipmentItemQuantity(array('minus',$needQuantityWithoutSystem), $basketItem, $shipmentAny);

				$needQuantityWithoutSystem = 0;
			}
			elseif($needQuantityWithoutSystem >= $shipmentItemAny->getQuantity())
			{
				$shipmentItemAnyQuantity = 0;
				$shipmentItemAnyQuantity = $shipmentItemAny->getQuantity();
				$this->setShipmentItemQuantity(array('minus',$shipmentItemAnyQuantity), $basketItem, $shipmentAny);

				$needQuantityWithoutSystem = $needQuantityWithoutSystem - $shipmentItemAnyQuantity;
				$this->logMessage("updateShipmentNeedQuantityFromBasketItems.NewNeedQuantityWithoutSystem: ".$needQuantityWithoutSystem);
			}

			if($needQuantityWithoutSystem == 0)
			{
				$this->setShipmentItemQuantity(array('plus',$needQuantity), $basketItem, $shipment);
				break;
			}
		}
	}
	function updateShipmentItemQuantity($needQuantity, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$systemShipmentQuantity = $this->getAvailableSystemQuentityProduct($basketItem, $shipment);
		$this->logMessage("updateShipmentItemQuantity.systemShipmentQuantity: ".$systemShipmentQuantity);

		if($systemShipmentQuantity >= $needQuantity)
		{

			$this->setShipmentItemQuantity(array('plus',$needQuantity), $basketItem, $shipment);
		}
		else
		{
			$needQuantityWithoutSystem = $needQuantity-$systemShipmentQuantity;
			$this->logMessage("updateShipmentItemQuantity.needQuantityWithoutSystem: ".$needQuantityWithoutSystem);

			$this->updateShipmentNeedQuantityFromBasketItems($needQuantityWithoutSystem, $needQuantity, $basketItem, $shipment);
		}
	}
	function setShipmentItemQuantity($needQuantity, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$this->logMessage("setShipmentItemQuantity.needQuantity: ".print_r($needQuantity,true));

		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode());
		if ($shipmentItem === null)
			$shipmentItem = $shipmentItemCollection->createItem($basketItem);

		if($needQuantity[0]=='plus')
		{
			$r=$shipmentItem->setQuantity($shipmentItem->getQuantity()+ $needQuantity[1]);
		}
		elseif($needQuantity[0]=='minus')
		{
			if($shipmentItem->getField('QUANTITY')==$needQuantity[1])
				$r=$shipmentItem->delete();
			else
				$r=$shipmentItem->setQuantity($shipmentItem->getQuantity()+($needQuantity[1] * -1));
		}
		if(!$r->isSuccess())
		{
			$error = array_shift($r->getErrors());
			$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_10", Array('#ID#'=>$shipment->getId(),'#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID())).$error->getMessage();

		}
	}

	function updateShipmentNeedQuantityFromSystem($Quantity, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$needQuantity = array('plus',$Quantity);

		$this->setShipmentItemQuantity($Quantity, $basketItem, $shipment);
	}

	function updateBasketNeedExternalQuantity($externalQuantity, $availableQuantityByProducts, \Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{

		$needQuantity = $externalQuantity - $availableQuantityByProducts;
		$this->logMessage("updateBasketNeedExternalQuantity.needQuantity: ".$needQuantity);
		$this->logMessage("updateBasketNeedExternalQuantity.basketId: ".$basketItem->getId());

		$fields = array();
		$fields["QUANTITY"] = $basketItem->getField('QUANTITY')+$needQuantity;
		$basketItem->setFields($fields);

		$availableQuantityByProducts = $this->getAvailableQuentityProduct($basketItem, $shipment);
		$this->logMessage("updateBasketNeedExternalQuantity.availableQuantityByProducts: ".$availableQuantityByProducts);
		$this->logMessage("updateBasketNeedExternalQuantity.externalQuantity: ".$externalQuantity);

		if($externalQuantity <= $availableQuantityByProducts)
		{
			$this->updateShipmentItemQuantity($externalQuantity, $basketItem, $shipment);
		}
		else
			$this->strErrorDocument .= "\n\r".GetMessage("CC_BSC1_SHIPMENT_ERROR_11", Array('#ID#'=>$shipment->getId(),'#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
	}

	function setOperationType($type)
	{
		switch ($type)
		{
			case GetMessage("CC_BSC1_PAYMENT_C"):
				self::$operationType = 'pay_system_c_operation';
				break;
			case GetMessage("CC_BSC1_PAYMENT_B"):
				self::$operationType = 'pay_system_b_operation';
				break;
			case GetMessage("CC_BSC1_SHIPMENT"):
				self::$operationType = 'shipment_operation';
				break;
			case GetMessage("CC_BSC1_ORDER"):
				self::$operationType = 'order_operation';
				break;
		}
	}
	function setVersion1C($version1C)
	{
		$this->version1C = $version1C;
	}
	function setXMLDocumentID($xmlid)
	{
		$this->xmlid1C = $xmlid;
	}
	function setOrderIdOriginal($code)
	{
		$this->orderIdOrig = $code;
	}
	function getVersion1C()
	{
		return $this->version1C;
	}
	function getXMLDocumentID()
	{
		return $this->xmlid1C;
	}
	function getOrderIdOriginal()
	{
		return $this->orderIdOrig;
	}
	function checkConditionForShipmentByBasket(\Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		if ($basketItem->getCollection()->getOrder()->getField("STATUS_ID") == "F")
			return false;

		if(!$this->checkVersion1C($shipment))
			return false;

		if ($shipment->isShipped())
			return false;

		return true;
	}
	function SyncShipmenttoBasket($arDocument)
	{
		$order = \Bitrix\Sale\Order::load($arDocument["ID"]);
		if($arDocument["VERSION_1C"] != $order->getField("VERSION_1C") || (strlen($order->getField("VERSION_1C")) <= 0 || strlen($arDocument["VERSION_1C"]) <= 0)) // skip update if the same version
		{
			$order = \Bitrix\Sale\Order::load($arDocument["ID"]);
			$shipmentItems = $order->getShipmentCollection();
			$basketItems = $order->getBasket();
			$documentItems = $arDocument['items'];
			foreach($basketItems as $basketItem)
			{
				if(!empty($documentItems[$basketItem->getField('PRODUCT_XML_ID')]))
				{
					$externalQuantity = intval($documentItems[$basketItem->getField('PRODUCT_XML_ID')]['QUANTITY']);

					if($basketItem->getQuantity() + $externalQuantity <=0 )
					{
						$basketItem->delete();
					}
					else
					{
						$availableQuantity = 0;
						foreach($shipmentItems as $shipment)
						{
							if(!$this->checkConditionForShipmentByBasket($basketItem, $shipment))
								continue;

							/** @var Bitrix\Sale\Shipment $shipment */
							$shipmentItem = $shipment->getShipmentItemCollection();

							/** @var Bitrix\Sale\ShipmentItem $shipmentItem */
							if($shipmentItem !== null)
								$availableQuantity =+ $shipmentItem->getBasketItemQuantity($basketItem);
						}

						if($externalQuantity < $availableQuantity)
						{
							foreach($order->getShipmentCollection() as $shipmentAny)
							{
								if($shipmentAny->isSystem())
									continue;

								if(!$this->checkConditionForShipmentByBasket($basketItem, $shipmentAny))
									continue;

								$shipmentItemAny = $shipmentAny->getShipmentItemCollection()->getItemByBasketCode($basketItem->getBasketCode());
								if($shipmentItemAny === null)
									continue;

								$needQuantity = $availableQuantity-$externalQuantity;
								if($needQuantity < $shipmentItemAny->getQuantity())
								{
									$this->setShipmentItemQuantity(array('minus',$needQuantity), $basketItem, $shipmentAny);

									$externalQuantity = 0;
								}
								elseif($needQuantity >= $shipmentItemAny->getQuantity())
								{
									$this->setShipmentItemQuantity(array('minus',$shipmentItemAny->getQuantity()), $basketItem, $shipmentAny);

									$needQuantity =-  $shipmentItemAny->getQuantity();
								}

								if($needQuantity==0)
									break;
							}
						}
					}
				}
				else
				{
					$basketItem->delete();
				}
				$order->isShipped();
			}
		}
	}
	function checkVersion1C(\Bitrix\Sale\Shipment $shipment)
	{
		if ($this->getVersion1C() != $shipment->getField('VERSION_1C') || (strlen($shipment->getField('VERSION_1C')) <= 0 || strlen($this->getVersion1C()) <= 0))
			return true;
		else
			return false;
	}
	function setVersionSchema($versionSchema=false)
	{
		$_SESSION['versionSchema'] = $versionSchema;
	}
	function setPersonTypesID()
	{
		if(empty($this->arPersonTypesIDs))
		{
			$dbPT = CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => \Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1")));
			while($arPT = $dbPT->Fetch())
			{
				$this->arPersonTypesIDs[] = $arPT["ID"];
			}
		}
	}
	function setExportInfo()
	{
		if(empty($this->arExportInfo))
		{
			$dbExport = CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $this->arPersonTypesIDs));
			while($arExport = $dbExport->Fetch())
			{
				$this->arExportInfo[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
			}
		}
	}
	function getPersonType($arOrder)
	{
		foreach($this->arExportInfo as $pt => $value)
		{
			if(
			(($value["IS_FIZ"] == "Y" && $arOrder["AGENT"]["TYPE"] == "FIZ")
					|| ($value["IS_FIZ"] == "N" && $arOrder["AGENT"]["TYPE"] != "FIZ"))
			)
				$arOrder["PERSON_TYPE_ID"] = $pt;
		}
		return $arOrder;
	}
	function getExportInfo($arOrder)
	{
		$arAgent = $this->arExportInfo[$arOrder["PERSON_TYPE_ID"]];
		foreach($arAgent as $k => $v)
		{
			if(empty($v) ||
					(
							(empty($v["VALUE"]) || $v["TYPE"] != "PROPERTY") &&
							(empty($arOrder["USER_PROPS"])
									|| (is_array($v) && is_string($v["VALUE"]) && empty($arOrder["USER_PROPS"][$v["VALUE"]]))
							)
					)
			)
				unset($arAgent[$k]);
		}
		return $arAgent;
	}
	function prepareOrderFields($arOrder)
	{
		return array(
				"SITE_ID" => \Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1"),
				"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
				"PAYED" => "N",
				"CANCELED" => "N",
				"STATUS_ID" => "N",
				"PRICE" => $arOrder["AMOUNT"],
				"CURRENCY" => CSaleLang::GetLangCurrency(\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1")),
				"USER_ID" => $arOrder["USER_ID"],
				"TAX_VALUE" => doubleval($arOrder["TAX"]["VALUE_MONEY"]),
				"COMMENTS" => $arOrder["COMMENT"],
				"BASKET_ITEMS" => array(),
				"TAX_LIST" => array(),
				"ORDER_PROP" => array(),
		);
	}
	function getOrderTaxList($arOrder)
	{
		$orderFields = array();
		if(!empty($arOrder["TAX"]))
		{
			$orderFields[] = array(
					"NAME" => $arOrder["TAX"]["NAME"],
					"IS_PERCENT" => "Y",
					"VALUE" => $arOrder["TAX"]["VALUE"],
					"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
					"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
			);
		}
		return $orderFields;
	}
	function getBasketProperty($item)
	{
		$result = array();
		if(is_array($item['ATTRIBUTES']))
		{
			foreach($item['ATTRIBUTES'] as $name=>$value)
			{
				$result[] = array('NAME'=>$name,'VALUE'=>$value);
			}
		}

		return $result;
	}
	function getOrderProperty($arAgent, $orderFields, $arOrder)
	{
		foreach($arAgent as $k => $v)
		{
			if(!empty($arOrder["ORDER_PROPS"][$k]))
			{
				$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["ORDER_PROPS"][$k];
			}
			if(empty($orderFields["ORDER_PROP"][$v["VALUE"]]) && !empty($arOrder["USER_PROPS"][$v["VALUE"]]))
			{
				$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["USER_PROPS"][$v["VALUE"]];
			}
		}
		return $orderFields;
	}
	function getOptionNameByType()
	{
		$optionName = '';
		switch(self::$operationType)
		{
			case 'pay_system_b_operation':
				$optionName = '1C_IMPORT_DEFAULT_PS_B';
				break;
			case 'pay_system_c_operation':
				$optionName = '1C_IMPORT_DEFAULT_PS';
				break;
		}
		return $optionName;
	}
	function getVersionSchema()
	{
		return doubleval(str_replace(" ", "", str_replace(",", ".", (!empty($_SESSION['versionSchema']) ? $_SESSION['versionSchema'] : self::DEFUALT_VERSION))));
	}

	function addNeedModifyItem($type,$id)
	{
		$this->needModifyItem[$type][]=$id;
	}
	function addModifyItem($type,$id)
	{
		if(!in_array($id, $this->modifyItem[$type]))
			$this->modifyItem[$type][]=$id;
	}

	function getProductChangePrice($itemID,$priceNew=null)
	{
		if (!empty($this->needModifyItem['CHANGE_PRODUCTS']) && count($this->needModifyItem['CHANGE_PRODUCTS']) > 0)
		{
			foreach ($this->needModifyItem['CHANGE_PRODUCTS'] as $products)
			{
				if ($products['PRODUCT_XML_ID'] == $itemID)
				{

					if(!empty($priceNew))
					{
						if($products['PRICE_NEW'] == $priceNew)
							return $products;
					}
					else
						return $products;
				}
			}
		}
		return false;
	}
	function getAvailableSystemQuentityProduct(\Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$systemQuantity = 0;
		$systemShipment = $shipment->getCollection()->getSystemShipment();
		$systemShipmentItem = $systemShipment->getShipmentItemCollection();
		if (!is_null($systemShipmentItem))
		{
			$sipmentItem = $systemShipmentItem->getItemByBasketCode($basketItem->getBasketCode());
			if($sipmentItem !== null)
				$systemQuantity = $sipmentItem->getQuantity();
		}
		return $systemQuantity;
	}
	function getAvailableQuentityProduct(\Bitrix\Sale\BasketItem $basketItem, \Bitrix\Sale\Shipment $shipment)
	{
		$quantity = 0;
		$order = $basketItem->getCollection()->getOrder();
		foreach($order->getShipmentCollection() as $shipment)
		{
			if($this->checkConditionForShipmentByBasket($basketItem, $shipment))
			{
				$sipmentItem = $shipment->getShipmentItemCollection()->getItemByBasketCode($basketItem->getBasketCode());
				if($sipmentItem !== null)
					$quantity +=$sipmentItem->getQuantity();
			}
		}
		return $quantity;
	}

	function getUserByProperty($arOrder)
	{
		if(!empty($arOrder["AGENT"]) && strlen($arOrder["AGENT"]["ID"]) > 0)
		{
			$arOrder["PERSON_TYPE_ID"] = 0;
			$arOrder["USER_ID"] = 0;
			$arErrors = array();
			$dbUProp = CSaleOrderUserProps::GetList(array(), array("XML_ID" => $arOrder["AGENT"]["ID"]), false, false, array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C"));
			if ($arUProp = $dbUProp->Fetch()) {
				$arOrder["USER_ID"] = $arUProp["USER_ID"];
				$arOrder["PERSON_TYPE_ID"] = $arUProp["PERSON_TYPE_ID"];
				$arOrder["USER_PROFILE_ID"] = $arUProp["ID"];
				$arOrder["USER_PROFILE_VERSION"] = $arUProp["VERSION_1C"];

				$dbUPropValue = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $arUProp["ID"]));
				while ($arUPropValue = $dbUPropValue->Fetch()) {
					$arOrder["USER_PROPS"][$arUPropValue["ORDER_PROPS_ID"]] = $arUPropValue["VALUE"];
				}
			} else {
				if (strlen($arOrder["AGENT"]["ID"]) > 0) {
					$arAI = explode("#", $arOrder["AGENT"]["ID"]);
					if (IntVal($arAI[0]) > 0) {
						$dbUser = CUser::GetByID($arAI[0]);
						if ($arU = $dbUser->Fetch()) {
							if (htmlspecialcharsback(substr(htmlspecialcharsbx($arU["ID"] . "#" . $arU["LOGIN"] . "#" . $arU["LAST_NAME"] . " " . $arU["NAME"] . " " . $arU["SECOND_NAME"]), 0, 80)) == $arOrder["AGENT"]["ID"]) {
								$arOrder["USER_ID"] = $arU["ID"];
							}
						}
					}
				}

				if (IntVal($arOrder["USER_ID"]) <= 0) {
					//create new user
					$arUser = array(
							"NAME" => $arOrder["AGENT"]["ITEM_NAME"],
							"EMAIL" => $arOrder["AGENT"]["CONTACT"]["MAIL_NEW"],
					);

					if (strlen($arUser["NAME"]) <= 0)
						$arUser["NAME"] = $arOrder["AGENT"]["CONTACT"]["CONTACT_PERSON"];
					if (strlen($arUser["EMAIL"]) <= 0)
						$arUser["EMAIL"] = "buyer" . time() . GetRandomCode(2) . "@" . $_SERVER["SERVER_NAME"];

					$arOrder["USER_ID"] = CSaleUser::DoAutoRegisterUser($arUser["EMAIL"], $arUser["NAME"],\Bitrix\Main\Config\Option::get("sale", "1C_SITE_NEW_ORDERS", "s1"), $arErrors, array("XML_ID"=>$arOrder["AGENT"]["ID"]));
				}
			}
		}
		return $arOrder;
	}

	function elementHandler($path, $attr)
	{
		$val = $attr[GetMessage("SALE_EXPORT_FORM_SUMM")];
		if(strlen($val) > 0)
		{
			if(preg_match("#".GetMessage("SALE_EXPORT_FORM_CRD")."=(.);{0,1}#", $val, $match))
			{
				$this->sdp = $match[1];
			}
		}

		self::setVersionSchema($attr[GetMessage("CC_BSC1_COM_INFO_VARSION")]);
	}

	function existsBasketItem(\Bitrix\Sale\BasketItem $basketItem, array $item = array())
	{
		if(!empty($item[$basketItem->getField("PRODUCT_XML_ID")]))
		{
			$propertyItem = $this->getBasketProperty($item[$basketItem->getField("PRODUCT_XML_ID")]);

			$propertyBasketItem = array();
			/** @var BasketPropertiesCollection $basketPropertyCollection */
			if($basketPropertyCollection = $basketItem->getPropertyCollection())
				$propertyBasketItem = $basketPropertyCollection->getPropertyValues();

			if(!empty($propertyItem) && is_array($propertyItem))
			{
				if($basketPropertyCollection->isPropertyAlreadyExists($propertyItem))
					return true;
			}
			elseif(count($propertyBasketItem)<=0)
				return true;
		}
		return false;
	}

	function nodeHandler(CDataXML $value)
	{
		$value = $value->GetArray();
		$this->strErrorDocument = '';

		if(!empty($value[GetMessage("CC_BSC1_DOCUMENT")]))
		{
			$value = $value[GetMessage("CC_BSC1_DOCUMENT")];

			$arDocument = $this->collectDocumentInfo($value);

			if(!empty($arDocument))
			{
				$this->logMessage("StartExchange:");
				$this->logMessage("VersionSchema: ".self::getVersionSchema());

				if (self::getVersionSchema() >= self::PARTIAL_VERSION)
				{
					if(\Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control', 'N')=='Y' || \Bitrix\Main\Config\Option::get('catalog', 'enable_reservation', 'N')=='Y')
						$this->strErrorDocument .= "\n ".GetMessage("CC_BSC1_USE_STORE_SALE");
					else
					{
						if(\Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N') <> 'Y')
							$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_CONVERT_SALE");
						else
						{
							$this->logMessage("OperationType: ".$arDocument['OPERATION_TYPE']);

							switch($arDocument['OPERATION_TYPE'])
							{
								case 'order_operation':

									/** @var Bitrix\Sale\Order $order */
									if(strlen($arDocument["XML_1C_DOCUMENT_ID"])>0)
									{
										$this->setVersion1C($arDocument["VERSION_1C"]);
										$this->setXMLDocumentID($arDocument["XML_1C_DOCUMENT_ID"]);

										$this->logMessage("Document.XML_1C_DOCUMENT_ID: ".$arDocument['XML_1C_DOCUMENT_ID']);
										$this->logMessage("Document.VERSION_1C: ".$arDocument['VERSION_1C']);

										if(intval($arDocument["ID"])>0)
										{
											$this->logMessage("UpdateOrder:");
											$this->logMessage("ID: ".$arDocument['ID']);

											$this->updateOrderWithoutShipmentsPayments($arDocument);
											if(strlen($this->strErrorDocument)<=0)
											{
												$order = \Bitrix\Sale\Order::load($arDocument["ID"]);
												$order->setField('UPDATED_1C', 'Y');
												$order->setField('VERSION_1C', $this->getVersion1C());
												$order->setField('ID_1C', $this->getXMLDocumentID());
												$r = $order->save();
												if (!$r->isSuccess())
													$this->strErrorDocument .= array_shift($r->getErrors())->getMessage();
											}
										}
										elseif(\Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_NEW_ORDERS", "Y") == "Y")
										{
											$this->logMessage("NewOrder:");

											$arOrder = $this->addOrderWithoutShipmentsPayments($arDocument);
											if(intval($arOrder['ID'])>0)
											{
												$order = \Bitrix\Sale\Order::load($arOrder["ID"]);
												if(strlen($this->strErrorDocument)<=0)
												{
													$order->setField('EXTERNAL_ORDER','Y');
													$order->setField('UPDATED_1C','Y');
													$order->setField('VERSION_1C', $this->getVersion1C());
													$order->setField('ID_1C', $this->getXMLDocumentID());
													if(strlen($arDocument["DATE"])>0)
														$order->setField('DATE_INSERT', new Bitrix\Main\Type\DateTime(CDatabase::FormatDate($arDocument["DATE"]." ".$arDocument["TIME"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG))));
													$r = $order->save();
													if(!$r->isSuccess())
														$this->strErrorDocument .= array_shift($r->getErrors())->getMessage();
												}
											}
											else
												$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_2", Array('#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
										}
									}
									else
									{
										$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_ORDER_ERROR_1");
									}
									break;
								case 'pay_system_b_operation':
								case 'pay_system_c_operation':

									/** @var Bitrix\Sale\Order $order */
									if(isset($arDocument['PAYMENT_ORDER_ID']) && strlen($arDocument['ORDER_ID'])<=0)
										$arDocument['ORDER_ID'] = $arDocument['PAYMENT_ORDER_ID'];

									if(strlen($arDocument["XML_1C_DOCUMENT_ID"])>0)
									{
										$this->setVersion1C($arDocument["VERSION_1C"]);
										$this->setXMLDocumentID($arDocument["XML_1C_DOCUMENT_ID"]);

										$this->logMessage("Document.XML_1C_DOCUMENT_ID: ".$arDocument['XML_1C_DOCUMENT_ID']);
										$this->logMessage("Document.VERSION_1C: ".$arDocument['VERSION_1C']);

										if(strlen($arDocument["ID"])>0)
										{
											if($arDocument['ORDER_ID'] !== false)
											{

												$order = \Bitrix\Sale\Order::load($arDocument['ORDER_ID']);

												if ($order->getField("STATUS_ID") == "F")
													$this->strErrorDocument .= "\n ".GetMessage("CC_BSC1_PAYMENT_ERROR_1", Array("#ID#" => $arDocument["ID"],'#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
												else
												{
													/** @var Bitrix\Sale\Payment $payment */
													$payments = $order->getPaymentCollection();

													if ($payment = $payments->getItemById($arDocument['ID']))
													{
														$this->updatePaymentFromDocument($arDocument, $payment, $order);

														if(strlen($this->strErrorDocument)<=0)
														{
															$this->Paid($payment, $arDocument);

															$payment->setField('UPDATED_1C','Y');
															$payment->setField('VERSION_1C', $this->getVersion1C());
															$payment->setField('ID_1C',$this->getXMLDocumentID());
															$order->setField('UPDATED_1C', 'Y');

															$r = $order->save();
															if(!$r->isSuccess())
																$this->strErrorDocument .= array_shift($r->getErrors())->getMessage();
														}
													}
													else
														$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_PAYMENT_ERROR_3", Array("#ID#" => $arDocument["ID"], '#ORDER_ID#'=>$arDocument['ORDER_ID'], '#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
												}
											}
											else
												$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_PAYMENT_ERROR_4", Array("#ID#" => $arDocument["ID"]));
										}
										elseif (\Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_NEW_PAYMENT", "Y") == 'Y') // create new payment (ofline 1C))
										{
											if ($arDocument['ORDER_ID'] !== false)
											{
												$order = \Bitrix\Sale\Order::load($arDocument['ORDER_ID']);
												if($order)
												{
													$payment = $this->addPaymentFromDocumentByOrder($arDocument, $order);
													if(strlen($this->strErrorDocument)<=0 && !is_null($payment))
													{
														$this->Paid($payment, $arDocument);

														$payment->setField('EXTERNAL_PAYMENT','Y');
														$payment->setField('UPDATED_1C','Y');
														$payment->setField('VERSION_1C', $this->getVersion1C());
														$payment->setField('ID_1C',$this->getXMLDocumentID());
														$order->setField('UPDATED_1C', 'Y');

														$r = $order->save();
														if(!$r->isSuccess())
															$this->strErrorDocument .= array_shift($r->getErrors())->getMessage();
													}
												}
												else
												{
													$this->strErrorDocument .= "\n\r ".GetMessage("CC_BSC1_PAYMENT_ERROR_8",array('#ORDER_ID#'=>$order->getId(),'#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
												}
											}
											else
												$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_PAYMENT_ERROR_5",array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
										}
									}
									else
									{
										$this->strErrorDocument .= "\n ".GetMessage("CC_BSC1_PAYMENT_ERROR_6");
									}
									break;
								case 'shipment_operation':

									if(isset($arDocument['SHIPMENT_ORDER_ID']) && strlen($arDocument['ORDER_ID'])<=0)
										$arDocument['ORDER_ID'] = $arDocument['SHIPMENT_ORDER_ID'];

									if(strlen($arDocument["XML_1C_DOCUMENT_ID"])>0)
									{
										$this->setVersion1C($arDocument["VERSION_1C"]);
										$this->setXMLDocumentID($arDocument["XML_1C_DOCUMENT_ID"]);
										$this->setOrderIdOriginal($arDocument["ORDER_ID_ORIG"]);

										$this->logMessage("Document.XML_1C_DOCUMENT_ID: ".$arDocument['XML_1C_DOCUMENT_ID']);
										$this->logMessage("Document.VERSION_1C: ".$arDocument['VERSION_1C']);
										$this->logMessage("Document.ORDER_ID_ORIG: ".$arDocument['ORDER_ID_ORIG']);

										if(strlen($arDocument["ID"])>0)
										{
											if ($arDocument['ORDER_ID'] !== false)
											{
												/** @var Bitrix\Sale\Order $order */
												$order = \Bitrix\Sale\Order::load($arDocument['ORDER_ID']);

												if ($order->getField("STATUS_ID") == "F")
													$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_1", Array("#ID#" => $arDocument["ID"],'#ORDER_ID#'=>$arDocument['ORDER_ID'], '#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
												else
												{
													if ($shipment = $order->getShipmentCollection()->getItemById($arDocument['ID']))
													{
														/** @var Bitrix\Sale\Shipment $shipment */
														if (!$shipment->isShipped())
														{
															$this->deleteShipmentItemsByDocument($arDocument, $shipment);

															$this->updateShipmentQuantityFromDocument($arDocument, $shipment);

															if(strlen($this->strErrorDocument)<=0)
															{
																$this->Ship($shipment, $arDocument);

																if(strlen($this->strErrorDocument)<=0)
																{
																	//$shipment->setField('EXTERNAL_DELIVERY','N');
																	$shipment->setField('UPDATED_1C','Y');
																	$shipment->setField('VERSION_1C', $this->getVersion1C());
																	$shipment->setField('ID_1C',$this->getXMLDocumentID());
																	$order->setField('UPDATED_1C',"Y");
																}
																$r=$order->save();
																if (!$r->isSuccess())
																	$this->strErrorDocument .= array_shift($r->getErrorMessages());
															}
														}
														else
															$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_2", Array("#ID#" => $arDocument["ID"],'#ORDER_ID#'=>$arDocument['ORDER_ID'],'#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
													}
													else
														$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_3", Array("#ID#" => $arDocument["ID"],'#ORDER_ID#'=>$arDocument['ORDER_ID'],'#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
												}
											}
											else
												$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_4", Array("#ID#" => $arDocument["ID"],'#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
										}
										elseif($arDocument['ORDER_ID']!==false && \Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_NEW_SHIPMENT", 'Y')=='Y')
										{
											/** @var Bitrix\Sale\Order $order */
											$order = \Bitrix\Sale\Order::load($arDocument['ORDER_ID']);

											if ($order->getField("STATUS_ID") == "F")
												$this->strErrorDocument .= "\n".GetMessage("CC_BSC1_SHIPMENT_ERROR_1", Array("#ID#" => $arDocument["ID"],'#ORDER_ID#'=>$arDocument['ORDER_ID'],'#XML_1C_DOCUMENT_ID#'=>$arDocument['XML_1C_DOCUMENT_ID']));
											else
											{
												$shipment = $this->addShipmentFromDocumentByOrder($arDocument, $order);

												if(strlen($this->strErrorDocument)<=0)
												{
													$this->Ship($shipment, $arDocument);

													$shipment->setField('VERSION_1C',$this->getVersion1C());
													$shipment->setField('ID_1C', $this->getXMLDocumentID());
													$shipment->setField('EXTERNAL_DELIVERY','Y');
													$shipment->setField('UPDATED_1C','Y');
													$order->setField('UPDATED_1C',"Y");

													$r=$order->save();
													if (!$r->isSuccess())
														$this->strErrorDocument .= array_shift($r->getErrorMessages());
												}
											}
										}
										elseif(\Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_NEW_ORDER_NEW_SHIPMENT", "Y") == 'Y') // create new shipment (ofline 1C))
										{
											/** @var Bitrix\Sale\Order $order */
											$arOrder = $this->addOrderWithoutShipmentsPayments($arDocument);
											if($arOrder['ID']>0)
											{
												$order = \Bitrix\Sale\Order::load($arOrder['ID']);
												$shipment = $this->addShipmentFromDocumentByOrder($arDocument, $order);

												if(strlen($this->strErrorDocument)<=0)
												{
													$this->Ship($shipment, $arDocument);

													$shipment->setField('VERSION_1C',$this->getVersion1C());
													$shipment->setField('ID_1C', $this->getXMLDocumentID());
													$shipment->setField('EXTERNAL_DELIVERY','Y');
													$shipment->setField('UPDATED_1C','Y');
													$order->setField('VERSION_1C',$this->getVersion1C());
													$order->setField('ID_1C',$this->getOrderIdOriginal());
													$order->setField('EXTERNAL_ORDER','Y');
													$order->setField('UPDATED_1C',"Y");


													$r=$order->save();
													if (!$r->isSuccess())
														$this->strErrorDocument .= array_shift($r->getErrorMessages());
												}
											}
											else
												$this->strErrorDocument .= "\n\r ".GetMessage("CC_BSC1_SHIPMENT_ERROR_7", Array('#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));;

										}
										else
										{
											$this->strErrorDocument .= "\n\r".GetMessage("CC_BSC1_SHIPMENT_ERROR_5", Array("#ID#" => $arDocument["ID"],'#XML_1C_DOCUMENT_ID#'=>$this->getXMLDocumentID()));
										}
									}
									else
									{
										$this->strErrorDocument .= "\n\r ".GetMessage("CC_BSC1_SHIPMENT_ERROR_6", Array("#ID#" => $arDocument["ID"]));
									}
									break;
							}
						}
					}
					$this->logMessage("FinalExchange \r\n\r\n");
				}
				else
				{
					self::oldSaveOrder($arDocument);
				}
			}
			$this->strError .= $this->strErrorDocument;
		}
		elseif(\Bitrix\Main\Config\Option::get("sale", "1C_IMPORT_NEW_ORDERS", "Y") == "Y")
		{

			$value = $value[GetMessage("CC_BSC1_AGENT")]["#"];
			$arAgentInfo = $this->collectAgentInfo($value);

			if(!empty($arAgentInfo["AGENT"]))
			{
				$mode = false;
				$arErrors = array();
				$dbUProp = CSaleOrderUserProps::GetList(array(), array("XML_ID" => $arAgentInfo["AGENT"]["ID"]), false, false, array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C"));
				if($arUProp = $dbUProp->Fetch())
				{
					if($arUProp["VERSION_1C"] != $arAgentInfo["AGENT"]["VERSION"])
					{
						$mode = "update";
						$arAgentInfo["PROFILE_ID"] = $arUProp["ID"];
						$arAgentInfo["PERSON_TYPE_ID"] = $arUProp["PERSON_TYPE_ID"];
					}
				}
				else
				{
					$arUser = array(
							"NAME" => $arAgentInfo["AGENT"]["ITEM_NAME"],
							"EMAIL" => $arAgentInfo["AGENT"]["CONTACT"]["MAIL_NEW"],
					);

					if(strlen($arUser["NAME"]) <= 0)
						$arUser["NAME"] = $arAgentInfo["AGENT"]["CONTACT"]["CONTACT_PERSON"];

					$emServer = $_SERVER["SERVER_NAME"];
					if(strpos($_SERVER["SERVER_NAME"], ".") === false)
						$emServer .= ".bx";
					if(strlen($arUser["EMAIL"]) <= 0)
						$arUser["EMAIL"] = "buyer".time().GetRandomCode(2)."@".$emServer;
					$arAgentInfo["USER_ID"] = CSaleUser::DoAutoRegisterUser($arUser["EMAIL"], $arUser["NAME"], $this->arParams["SITE_NEW_ORDERS"], $arErrors, array("XML_ID"=>$arAgentInfo["AGENT"]["ID"]));

					if(IntVal($arAgentInfo["USER_ID"]) > 0)
					{
						$mode = "add";
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_USER_PROBLEM", Array("#ID#" => $arAgentInfo["AGENT"]["ID"]));
						if(!empty($arErrors))
						{
							foreach($arErrors as $v)
							{
								$this->strError .= "\n".$v["TEXT"];
							}
						}
					}
				}

				if($mode)
				{
					if(empty($arPersonTypesIDs))
					{
						$dbPT = CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => $this->arParams["SITE_NEW_ORDERS"]));
						while($arPT = $dbPT->Fetch())
						{
							$arPersonTypesIDs[] = $arPT["ID"];
						}
					}

					if(empty($arExportInfo))
					{
						$dbExport = CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $arPersonTypesIDs));
						while($arExport = $dbExport->Fetch())
						{
							$arExportInfo[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
						}
					}

					if(IntVal($arAgentInfo["PERSON_TYPE_ID"]) <= 0)
					{
						foreach($arExportInfo as $pt => $value)
						{
							if(($value["IS_FIZ"] == "Y" && $arAgentInfo["AGENT"]["TYPE"] == "FIZ")
									|| ($value["IS_FIZ"] == "N" && $arAgentInfo["AGENT"]["TYPE"] != "FIZ")
							)
								$arAgentInfo["PERSON_TYPE_ID"] = $pt;
						}
					}

					if(IntVal($arAgentInfo["PERSON_TYPE_ID"]) > 0)
					{
						$arAgentInfo["ORDER_PROPS_VALUE"] = array();
						$arAgentInfo["PROFILE_PROPS_VALUE"] = array();

						$arAgent = $arExportInfo[$arAgentInfo["PERSON_TYPE_ID"]];

						foreach($arAgent as $k => $v)
						{
							if(strlen($v["VALUE"]) <= 0 || $v["TYPE"] != "PROPERTY")
								unset($arAgent[$k]);
						}

						foreach($arAgent as $k => $v)
						{
							if(!empty($arAgentInfo["ORDER_PROPS"][$k]))
								$arAgentInfo["ORDER_PROPS_VALUE"][$v["VALUE"]] = $arAgentInfo["ORDER_PROPS"][$k];
						}

						if (IntVal($arAgentInfo["PROFILE_ID"]) > 0)
						{
							CSaleOrderUserProps::Update($arUProp["ID"], array("VERSION_1C" => $arAgentInfo["AGENT"]["VERSION"], "NAME" => $arAgentInfo["AGENT"]["AGENT_NAME"]));
							$dbUPV = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $arAgentInfo["PROFILE_ID"]));
							while($arUPV = $dbUPV->Fetch())
							{
								$arAgentInfo["PROFILE_PROPS_VALUE"][$arUPV["ORDER_PROPS_ID"]] = array("ID" => $arUPV["ID"], "VALUE" => $arUPV["VALUE"]);
							}
						}

						if(empty($arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]]))
						{
							$dbOrderProperties = CSaleOrderProps::GetList(
									array("SORT" => "ASC"),
									array(
											"PERSON_TYPE_ID" => $arAgentInfo["PERSON_TYPE_ID"],
											"ACTIVE" => "Y",
											"UTIL" => "N",
											"USER_PROPS" => "Y",
									),
									false,
									false,
									array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
							);
							while ($arOrderProperties = $dbOrderProperties->Fetch())
							{
								$arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]][] = $arOrderProperties;
							}
						}

						foreach($arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]] as $arOrderProperties)
						{
							$curVal = $arAgentInfo["ORDER_PROPS_VALUE"][$arOrderProperties["ID"]];

							if (strlen($curVal) > 0)
							{
								if (IntVal($arAgentInfo["PROFILE_ID"]) <= 0)
								{
									$arFields = array(
											"NAME" => $arAgentInfo["AGENT"]["AGENT_NAME"],
											"USER_ID" => $arAgentInfo["USER_ID"],
											"PERSON_TYPE_ID" => $arAgentInfo["PERSON_TYPE_ID"],
											"XML_ID" => $arAgentInfo["AGENT"]["ID"],
											"VERSION_1C" => $arAgentInfo["AGENT"]["VERSION"],
									);
									$arAgentInfo["PROFILE_ID"] = CSaleOrderUserProps::Add($arFields);
								}
								if(IntVal($arAgentInfo["PROFILE_ID"]) > 0)
								{
									$arFields = array(
											"USER_PROPS_ID" => $arAgentInfo["PROFILE_ID"],
											"ORDER_PROPS_ID" => $arOrderProperties["ID"],
											"NAME" => $arOrderProperties["NAME"],
											"VALUE" => $curVal
									);
									if(empty($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]))
									{
										CSaleOrderUserPropsValue::Add($arFields);
									}
									elseif($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["VALUE"] != $curVal)
									{
										CSaleOrderUserPropsValue::Update($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["ID"], $arFields);
									}
								}
							}
						}
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_PERSON_TYPE_PROBLEM", Array("#ID#" => $arAgentInfo["AGENT"]["ID"]));
					}
				}
			}
			else
			{
				$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_NO_AGENT_ID");
			}
		}
	}

	function ToFloat($str)
	{
		static $search = false;
		static $replace = false;
		if(!$search)
		{
			if(strlen($this->sdp))
			{
				$search = array("\xc2\xa0", "\xa0", " ", $this->sdp, ",");
				$replace = array("", "", "", ".", ".");
			}
			else
			{
				$search = array("\xc2\xa0", "\xa0", " ", ",");
				$replace = array("", "", "", ".");
			}
		}

		$res1 = str_replace($search, $replace, $str);
		$res2 = doubleval($res1);

		return $res2;
	}

	function ToInt($str)
	{
		static $search = false;
		static $replace = false;
		if(!$search)
		{
			if(strlen($this->sdp))
			{
				$search = array("\xa0", " ", $this->sdp, ",");
				$replace = array("", "", ".", ".");
			}
			else
			{
				$search = array("\xa0", " ", ",");
				$replace = array("", "", ".");
			}
		}

		$res1 = str_replace($search, $replace, $str);
		$res2 = intval($res1);

		return $res2;
	}

	function collectDocumentInfo($value)
	{
		$bNeedFull = false;
		$arOrder = array();

		$arOrder["OPERATION"] = $value["#"][GetMessage("CC_BSC1_OPERATION")][0]["#"];

		self::setOperationType($value["#"][GetMessage("CC_BSC1_OPERATION")][0]["#"]);

		$arOrder["OPERATION_TYPE"] = self::$operationType ;

		if(in_array(
				self::$operationType,
				array(
						'shipment_operation',
						'pay_system_c_operation',
						'pay_system_b_operation',
						'order_operation'
				))
		)
		{

			$accountNumberPrefix = \Bitrix\Main\Config\Option::get("sale", "1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX", "");
			$arOrder["ID"] = $value["#"][GetMessage("CC_BSC1_NUMBER")][0]["#"];
			$arOrder["XML_1C_DOCUMENT_ID"] = $value["#"][GetMessage("CC_BSC1_ID")][0]["#"];


			switch (self::$operationType)
			{
				case 'pay_system_c_operation':
				case 'pay_system_b_operation':
					$paymentResult = $this->getDocumentId('Payment', $arOrder["ID"], $arOrder["XML_1C_DOCUMENT_ID"]);

					if(isset($paymentResult['ID']))
						$arOrder['ID'] = $paymentResult['ID'];
					if(isset($paymentResult['ORDER_ID']))
						$arOrder['PAYMENT_ORDER_ID'] = $paymentResult['ORDER_ID'];
					break;
				case 'shipment_operation':
					$shipmentResult = $this->getDocumentId('Shipment', $arOrder["ID"], $arOrder["XML_1C_DOCUMENT_ID"]);

					if(isset($shipmentResult['ID']))
						$arOrder['ID'] = $shipmentResult['ID'];
					if(isset($shipmentResult['ORDER_ID']))
						$arOrder['SHIPMENT_ORDER_ID'] = $shipmentResult['ORDER_ID'];
					break;
				case 'order_operation':
					$orderResult = $this->getDocumentId('Order', $arOrder["ID"], $arOrder["XML_1C_DOCUMENT_ID"]);
					if(isset($orderResult['ID']))
						$arOrder['ID'] = $orderResult['ID'];

					break;
			}

			if($accountNumberPrefix == "" || (strlen($arOrder["ID"]) > 0 && strpos($arOrder["ID"], $accountNumberPrefix) === 0) || strlen($arOrder["ID"]) <= 0)
			{
				switch (self::$operationType)
				{
					case 'pay_system_c_b_operation':
					case 'pay_system_c_operation':
					case 'pay_system_b_operation':
						$arOrder["ORDER_ID_ORIG"] = $value["#"][GetMessage("CC_BSC1_NUMBER_BASE")][0]["#"];
						$arOrder["ORDER_ID"] = $value["#"][GetMessage("CC_BSC1_NUMBER_BASE")][0]["#"];

						$arOrder["ORDER_ID"] = $this->getOrderIdByDocument($arOrder["ORDER_ID"]);

						break;
					case 'shipment_operation':
						$arOrder["ORDER_ID_ORIG"] = $value["#"][GetMessage("CC_BSC1_NUMBER_BASE")][0]["#"];
						$arOrder["ORDER_ID"] = $value["#"][GetMessage("CC_BSC1_NUMBER_BASE")][0]["#"];

						$arOrder["ORDER_ID"] = $this->getOrderIdByDocument($arOrder["ORDER_ID"]);

						break;
					case 'order_operation':
						//if ($accountNumberPrefix != "")
						//	$arOrder["ID"] = substr($arOrder["ID"], strlen($accountNumberPrefix));
						break;
				}

				$arOrder["AMOUNT"] = $value["#"][GetMessage("CC_BSC1_SUMM")][0]["#"];
				$arOrder["AMOUNT"] = $this->ToFloat($arOrder["AMOUNT"]);

				$arOrder["COMMENT"] = $value["#"][GetMessage("CC_BSC1_COMMENT")][0]["#"];

				$arOrder["VERSION_1C"] = $value["#"][GetMessage("CC_BSC1_VERSION_1C")][0]["#"];
				$arOrder["ID_1C"] = $value["#"][GetMessage("CC_BSC1_ID_1C")][0]["#"];
				$arOrder["DATE"] = $value["#"][GetMessage("CC_BSC1_1C_DATE")][0]["#"];

				$arOrder["OPERATION"] = $value["#"][GetMessage("CC_BSC1_OPERATION")][0]["#"];
				$arOrder["TRAITS"] = array();

				switch (self::$operationType)
				{
					case 'order_operation':
						//if (strlen($arOrder["ID"]) <= 0 && strlen($arOrder["ID_1C"]) > 0)
							$bNeedFull = true;
						break;
					case 'shipment_operation':
						$bNeedFull = true;
						break;
				}

				if (is_array($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")]) && !empty($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")])) {
					foreach ($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")] as $val) {
						$arOrder["TRAITS"][$val["#"][GetMessage("CC_BSC1_NAME")][0]["#"]] = $val["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
					}
				}

				$taxValue = 0;
				$taxName = "";
				$arOrder["items"] = array();
				$basketItems = array();
				if (is_array($value["#"][GetMessage("CC_BSC1_ITEMS")][0]["#"][GetMessage("CC_BSC1_ITEM")])) {
					foreach ($value["#"][GetMessage("CC_BSC1_ITEMS")][0]["#"][GetMessage("CC_BSC1_ITEM")] as $val) {

						$val = $val["#"];
						$productID = $val[GetMessage("CC_BSC1_ID")][0]["#"];

						$discountPrice = "";
						$priceAll = $this->ToFloat($val[GetMessage("CC_BSC1_SUMM")][0]["#"]);
						$priceone = $this->ToFloat($val[GetMessage("CC_BSC1_PRICE_PER_UNIT")][0]["#"]);
						if (DoubleVal($priceone) <= 0)
							$priceone = $this->ToFloat($val[GetMessage("CC_BSC1_PRICE_ONE")][0]["#"]);

						$quantity = $this->ToFloat($val[GetMessage("CC_BSC1_QUANTITY")][0]["#"]);
						if (doubleval($quantity) > 0) {
							$price = roundEx($priceAll / $quantity, 4);
							$priceone = roundEx($priceone, 4);

							if ($priceone != $price)
								$discountPrice = DoubleVal($priceone - $price);


							//DISCOUNTS!
							$basketItems = Array(
									"NAME" => $val[GetMessage("CC_BSC1_NAME")][0]["#"],
									"PRICE" => $price,
									"PRICE_ONE" => $priceone,
									"QUANTITY" => $quantity,
									"DISCOUNT_PRICE" => $discountPrice,
							);


							if (is_array($val[GetMessage("CC_BSC1_ITEM_UNIT")]) && is_array($val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"])) {
								$basketItems["MEASURE_CODE"] = $val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"][GetMessage("CC_BSC1_ITEM_UNIT_CODE")][0]["#"];
								$basketItems["MEASURE_NAME"] = $val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"][GetMessage("CC_BSC1_ITEM_UNIT_NAME")][0]["#"];
							}

							if (is_array($val[GetMessage("CC_BSC1_PROPS_ITEMS")][0]["#"][GetMessage("CC_BSC1_PROP_ITEM")])) {
								foreach ($val[GetMessage("CC_BSC1_PROPS_ITEMS")][0]["#"][GetMessage("CC_BSC1_PROP_ITEM")] as $val1)
								{
									$basketItems["ATTRIBUTES"][$val1["#"][GetMessage("CC_BSC1_NAME")][0]["#"]] = $val1["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
								}

							}

							if (is_array($val[GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")])) {
								foreach ($val[GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")] as $val1) {
									if ($val1["#"][GetMessage("CC_BSC1_NAME")][0]["#"] == GetMessage("CC_BSC1_ITEM_TYPE"))
									{
										$basketItems["TYPE"] = $val1["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
									}

								}
							}

							if (strlen($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_NAME")][0]["#"]) > 0) {
								$taxValueTmp = $val[GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_TAX_VALUE")][0]["#"];
								$basketItems["VAT_RATE"] = $taxValueTmp / 100;

								if (IntVal($taxValueTmp) > IntVal($taxValue)) {
									$taxName = $val[GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_NAME")][0]["#"];
									$taxValue = $taxValueTmp;
								}
							}
						}

						if (self::getVersionSchema() >= self::PARTIAL_VERSION)
							$arOrder["items"][][$productID] = $basketItems;
						else
							$arOrder["items"][$productID] = $basketItems;
					}
				}

				if(IntVal($taxValue)>0)
				{
					$price = $this->ToFloat($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_SUMM")][0]["#"]);
					$arOrder["TAX"] = Array(
							"NAME" => $taxName,
							"VALUE" =>$taxValue,
							"IS_IN_PRICE" => ($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_IN_PRICE")][0]["#"]=="true"?"Y":"N"),
							"VALUE_MONEY" => $price,
					);
				}

				if($bNeedFull)
				{
					IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/sale/general/export.php");
					$arOrder["DATE"] = $value["#"][GetMessage("CC_BSC1_1C_DATE")][0]["#"];
					$arOrder["TIME"] = $value["#"][GetMessage("CC_BSC1_1C_TIME")][0]["#"];

					if(!empty($value["#"][GetMessage("SALE_EXPORT_CONTRAGENTS")][0]["#"]))
					{
						$arAgentInfo = $this->collectAgentInfo($value["#"][GetMessage("SALE_EXPORT_CONTRAGENTS")][0]["#"][GetMessage("SALE_EXPORT_CONTRAGENT")][0]["#"]);
						$arOrder["AGENT"] = $arAgentInfo["AGENT"];
						$arOrder["ORDER_PROPS"] = $arAgentInfo["ORDER_PROPS"];

						if(strlen($arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")]) > 0)
						{
							if(!empty($arOrder["AGENT"]["REGISTRATION_ADDRESS"]))
								$arOrder["AGENT"]["REGISTRATION_ADDRESS"]["PRESENTATION"] = $arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")];
							if(!empty($arOrder["AGENT"]["ADDRESS"]))
								$arOrder["AGENT"]["ADDRESS"]["PRESENTATION"] = $arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")];
						}
					}
				}
			}
		}
		return $arOrder;
	}

	function collectAgentInfo($data = array())
	{
		if(empty($data))
			return false;
		IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/sale/general/export.php");

		$result = array();
		$schema = array("ID", "VERSION", "ITEM_NAME", "OFICIAL_NAME", "FULL_NAME", "INN", "KPP", "OKPO_CODE", "EGRPO", "OKVED", "OKDP", "OKOPF", "OKFC", "OKPO",
				"REGISTRATION_ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
				"UR_ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
				"ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
				"CONTACTS" => array("CONTACT" => array("WORK_PHONE_NEW", "MAIL_NEW")),
				"REPRESENTATIVES" => array("REPRESENTATIVE" => array("CONTACT_PERSON")),

		);

		foreach($schema as $k => $v)
		{
			if(is_array($v))
			{
				if(isset($data[GetMessage("SALE_EXPORT_".$k)]) && !empty($data[GetMessage("SALE_EXPORT_".$k)][0]["#"]))
				{
					$adr = $data[GetMessage("SALE_EXPORT_".$k)][0]["#"];
					foreach($v as $kk => $vv)
					{
						if(is_array($vv))
						{
							if(isset($adr[GetMessage("SALE_EXPORT_".$kk)]) && !empty($adr[GetMessage("SALE_EXPORT_".$kk)][0]["#"]) > 0)
							{
								foreach($vv as $vvv)
								{
									foreach($adr[GetMessage("SALE_EXPORT_".$kk)] as $val)
									{
										if($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"] == GetMessage("SALE_EXPORT_".$vvv)
												&& strlen($val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"]) > 0
										)
											$result["AGENT"][$kk][$vvv] = $val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"];
										elseif(empty($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"]) && $val["#"][GetMessage("SALE_EXPORT_RELATION")][0]["#"] == GetMessage("SALE_EXPORT_CONTACT_PERSON"))
											$result["AGENT"]["CONTACT"][$vvv] = $val["#"][GetMessage("SALE_EXPORT_ITEM_NAME")][0]["#"];

									}
								}
							}
						}
						else
						{
							if(isset($adr[GetMessage("SALE_EXPORT_".$vv)]) && strlen($adr[GetMessage("SALE_EXPORT_".$vv)][0]["#"]) > 0)
							{
								$result["AGENT"][$k][$vv] = $adr[GetMessage("SALE_EXPORT_".$vv)][0]["#"];
							}
							else
							{
								if(!empty($adr[GetMessage("SALE_EXPORT_ADDRESS_FIELD")]))
								{
									foreach($adr[GetMessage("SALE_EXPORT_ADDRESS_FIELD")] as $val)
									{
										if($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"] == GetMessage("SALE_EXPORT_".$vv)
												&& strlen($val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"]) > 0
										)
											$result["AGENT"][$k][$vv] = $val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"];
									}
								}
							}
						}
					}
				}
			}
			else
			{
				if(isset($data[GetMessage("SALE_EXPORT_".$v)]) && strlen($data[GetMessage("SALE_EXPORT_".$v)][0]["#"]) > 0)
					$result["AGENT"][$v] = $data[GetMessage("SALE_EXPORT_".$v)][0]["#"];
			}
		}

		$result["AGENT"]["AGENT_NAME"] = $result["AGENT"]["ITEM_NAME"];
		$result["AGENT"]["CONTACT"]["EMAIL"] = $result["AGENT"]["CONTACT"]["MAIL_NEW"];
		$result["AGENT"]["CONTACT"]["PHONE"] = $result["AGENT"]["CONTACT"]["WORK_PHONE_NEW"];
		$result["AGENT"]["OKPO"] = $result["AGENT"]["OKPO_CODE"];


		$result["ORDER_PROPS"] = array();
		foreach($result["AGENT"] as $k => $v)
		{
			if(!is_array($v) && !empty($v))
				$result["ORDER_PROPS"][$k] = $v;
			else
			{
				if($k == "CONTACT")
				{
					$result["ORDER_PROPS"]["EMAIL"] = $v["MAIL_NEW"];
					$result["ORDER_PROPS"]["PHONE"] = $v["WORK_PHONE_NEW"];
				}
				elseif($k == "REPRESENTATIVE")
				{
					$result["ORDER_PROPS"]["CONTACT_PERSON"] = $v["CONTACT_PERSON"];
				}
				elseif($k == "REGISTRATION_ADDRESS" || $k == "UR_ADDRESS")
				{
					$result["ORDER_PROPS"]["ADDRESS_FULL"] = $v["PRESENTATION"];
					$result["ORDER_PROPS"]["INDEX"] = $v["POST_CODE"];
					foreach($v as $k1 => $v1)
					{
						if(strlen($v1) > 0 && empty($result["ORDER_PROPS"][$k1]))
							$result["ORDER_PROPS"][$k1] = $v1;
					}
				}
				elseif($k == "ADDRESS")
				{
					$result["ORDER_PROPS"]["F_ADDRESS_FULL"] = $v["PRESENTATION"];
					$result["ORDER_PROPS"]["F_INDEX"] = $v["POST_CODE"];
					foreach($v as $k1 => $v1)
					{
						if(strlen($v1) > 0 && empty($result["ORDER_PROPS"]["F_".$k1]))
							$result["ORDER_PROPS"]["F_".$k1] = $v1;
					}
				}
			}
		}

		if(strlen($result["AGENT"]["OFICIAL_NAME"]) > 0 && strlen($result["AGENT"]["INN"]) > 0)
			$result["AGENT"]["TYPE"] = "UR";
		elseif(strlen($result["AGENT"]["INN"]) > 0)
			$result["AGENT"]["TYPE"] = "IP";
		else
			$result["AGENT"]["TYPE"] = "FIZ";

		return $result;
	}

	function prepareProduct4Basket($itemID, $arItem, $orderId, $orderInfo)
	{
		$arFields = array();
		if(CModule::IncludeModule("iblock"))
		{
			$dbIBlockElement = CIBlockElement::GetList(array(), array("XML_ID" => $itemID, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "Y"), false, false, array("ID", "IBLOCK_ID", "XML_ID", "NAME", "DETAIL_PAGE_URL"));
			if($arIBlockElement = $dbIBlockElement->Fetch())
			{
				if(empty($this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]))
				{
					$dbIBlock = CIBlock::GetList(
							array(),
							array("ID" => $arIBlockElement["IBLOCK_ID"])
					);
					if ($arIBlock = $dbIBlock->Fetch())
					{
						$this->arIBInfo[$arIBlockElement["IBLOCK_ID"]] = $arIBlock;
					}
				}

				$arProps[] = array(
						"NAME" => "Catalog XML_ID",
						"CODE" => "CATALOG.XML_ID",
						"VALUE" => $this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]["XML_ID"]
				);

				$arProps[] = array(
						"NAME" => "Product XML_ID",
						"CODE" => "PRODUCT.XML_ID",
						"VALUE" => $arIBlockElement["XML_ID"]
				);
				$arProduct = CCatalogProduct::GetByID($arIBlockElement["ID"]);

				$arFields = array(
						"ORDER_ID" => $orderId,
						"PRODUCT_ID" => $arIBlockElement["ID"],
						"PRICE" => $arItem["PRICE"],
						"CURRENCY" => $orderInfo["CURRENCY"],
						"WEIGHT" => $arProduct["WEIGHT"],
						"QUANTITY" => $arItem["QUANTITY"],
						"LID" => $orderInfo["LID"],
						"DELAY" => "N",
						"CAN_BUY" => "Y",
						"NAME" => $arIBlockElement["NAME"],
						"MODULE" => "catalog",
						"NOTES" => $arProduct["CATALOG_GROUP_NAME"],
						"PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider",
						"DETAIL_PAGE_URL" => $arIBlockElement["DETAIL_PAGE_URL"],
						"CATALOG_XML_ID" => $this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]["XML_ID"],
						"PRODUCT_XML_ID" => $arIBlockElement["XML_ID"],
						"IGNORE_CALLBACK_FUNC" => "Y",
						"VAT_RATE" => $arItem["VAT_RATE"],
						"CUSTOM_PRICE"=>"Y",
				);
			}
		}

		if(empty($arFields))
		{
			$arFields = array(
					"ORDER_ID" => $orderId,
					"PRICE" => $arItem["PRICE"],
					"CURRENCY" => $orderInfo["CURRENCY"],
					"QUANTITY" => $arItem["QUANTITY"],
					"LID" => $orderInfo["LID"],
					"DELAY" => "N",
					"CAN_BUY" => "Y",
					"NAME" => $arItem["NAME"],
					"MODULE" => "1c_exchange",
					"PRODUCT_PROVIDER_CLASS" => false,
					"CATALOG_XML_ID" => "1c_exchange",
					"PRODUCT_XML_ID" => $itemID,
					"IGNORE_CALLBACK_FUNC" => "Y",
					"VAT_RATE" => $arItem["VAT_RATE"],
					"DISCOUNT_PRICE" => $arItem["DISCOUNT_PRICE"],
					"CUSTOM_PRICE" => "Y",
			);
			if($this->bNewVersion)
			{
				$arFields["MEASURE_CODE"] = $arItem["MEASURE_CODE"];
				$arFields["MEASURE_NAME"] = $arItem["MEASURE_NAME"];
			}

			$ri = new RandomSequence($itemID);
			$arFields["PRODUCT_ID"] = $ri->rand(1000000, 9999999);
		}
		if(strlen($arFields["LID"]) <= 0)
			$arFields["LID"] = $orderInfo["SITE_ID"];

		return $arFields;
	}
	function documentMustHaveProducts($arDocument)
	{
		foreach($arDocument["items"] as $items)
		{
			foreach ($items as $itemID => $arItem)
			{
				if($arItem["TYPE"] == GetMessage("CC_BSC1_ITEM"))
				{
					return true;
				}
			}
		}

		return false;
	}

	function oldSaveOrder($arOrder)
	{
		if (strlen($arOrder["ID"]) <= 0 && strlen($arOrder["ID_1C"]) > 0)//try to search order from 1C
		{
			$dbOrder = CSaleOrder::GetList(array("ID" => "DESC"), array("ID_1C" => $arOrder["ID_1C"]), false, false, array("ID", "ID_1C"));
			if ($orderInfo = $dbOrder->Fetch()) {
				$arOrder["ID"] = $orderInfo["ID"];
			}
		}
		if(strlen($arOrder["ID"]) > 0) // exists site order
		{
			$dbOrder = CSaleOrder::GetList(array(), array("ACCOUNT_NUMBER" => $arOrder["ID"]), false, false, array("ID", "LID", "PERSON_TYPE_ID", "PAYED", "DATE_PAYED", "CANCELED", "DATE_CANCELED", "REASON_CANCELED", "STATUS_ID", "DATE_STATUS", "PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE", "PRICE_DELIVERY", "ALLOW_DELIVERY", "DATE_ALLOW_DELIVERY", "PRICE", "CURRENCY", "DISCOUNT_VALUE", "USER_ID", "PAY_SYSTEM_ID", "DELIVERY_ID", "DATE_INSERT", "DATE_INSERT_FORMAT", "DATE_UPDATE", "USER_DESCRIPTION", "ADDITIONAL_INFO", "COMMENTS", "TAX_VALUE", "DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE", "STORE_ID", "ACCOUNT_NUMBER", "VERSION", "VERSION_1C", "ID_1C"));
			if($orderInfo = $dbOrder->Fetch())
			{
				if($arOrder["VERSION_1C"] != $orderInfo["VERSION_1C"] || (strlen($orderInfo["VERSION_1C"]) <= 0 || strlen($arOrder["VERSION_1C"]) <= 0)) // skip update if the same version
				{
					$arOrderFields = array();
					$orderId = $orderInfo["ID"];
					CSaleOrderChange::AddRecord($orderId, "ORDER_1C_IMPORT");
					if($arOrder["ID_1C"] != $orderInfo["ID_1C"])
						$arOrderFields["ID_1C"] = $arOrder["ID_1C"];
					$arOrderFields["VERSION_1C"] = $arOrder["VERSION_1C"];

					if($orderInfo["PAYED"] != "Y" && $orderInfo["ALLOW_DELIVERY"] != "Y" && $orderInfo["STATUS_ID"] != "F")
					{
						$dbOrderTax = CSaleOrderTax::GetList(
								array(),
								array("ORDER_ID" => $orderId),
								false,
								false,
								array("ID", "TAX_NAME", "VALUE", "VALUE_MONEY", "CODE", "IS_IN_PRICE")
						);
						$bTaxFound = false;
						if($arOrderTax = $dbOrderTax->Fetch())
						{
							$bTaxFound = true;
							if(IntVal($arOrderTax["VALUE_MONEY"]) != IntVal($arOrder["TAX"]["VALUE_MONEY"]) || IntVal($arOrderTax["VALUE"]) != IntVal($arOrder["TAX"]["VALUE"]) || ($arOrderTax["IS_IN_PRICE"] != $arOrder["TAX"]["IS_IN_PRICE"]))
							{
								if(IntVal($arOrder["TAX"]["VALUE"])>0)
								{
									$arFields = Array(
											"TAX_NAME" => $arOrder["TAX"]["NAME"],
											"ORDER_ID" => $orderId,
											"VALUE" => $arOrder["TAX"]["VALUE"],
											"IS_PERCENT" => "Y",
											"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
											"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
											"CODE" => "VAT1C",
											"APPLY_ORDER" => "100"
									);
									CSaleOrderTax::Update($arOrderTax["ID"], $arFields);
									$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
								}
								else
								{
									CSaleOrderTax::Delete($arOrderTax["ID"]);
									$arOrderFields["TAX_VALUE"] = 0;
								}
							}
						}

						if(!$bTaxFound)
						{
							if(IntVal($arOrder["TAX"]["VALUE"])>0)
							{
								$arFields = Array(
										"TAX_NAME" => $arOrder["TAX"]["NAME"],
										"ORDER_ID" => $orderId,
										"VALUE" => $arOrder["TAX"]["VALUE"],
										"IS_PERCENT" => "Y",
										"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
										"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"]
								);
								CSaleOrderTax::Add($arFields);
								$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
							}
						}

						$arShoppingCart = array();
						$bNeedUpdate = false;
						$dbBasket = CSaleBasket::GetList(
								array("NAME" => "ASC"),
								array("ORDER_ID" => $orderId),
								false,
								false,
								array(
										"ID",
										"QUANTITY",
										"CANCEL_CALLBACK_FUNC",
										"MODULE",
										"PRODUCT_ID",
										"PRODUCT_PROVIDER_CLASS",
										"RESERVED",
										"RESERVE_QUANTITY",
										"TYPE",
										"SET_PARENT_ID",
										"PRICE",
										"VAT_RATE",
										"DISCOUNT_PRICE",
										"PRODUCT_XML_ID",
								)
						);

						while ($arBasket = $dbBasket->Fetch())
						{
							$arFields = Array();
							if(!empty($arOrder["items"][$arBasket["PRODUCT_XML_ID"]]))
							{
								if($arBasket["QUANTITY"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["QUANTITY"])
									$arFields["QUANTITY"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["QUANTITY"];
								if($arBasket["PRICE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["PRICE"])
									$arFields["PRICE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["PRICE"];
								if($arBasket["VAT_RATE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["VAT_RATE"])
									$arFields["VAT_RATE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["VAT_RATE"];
								if($arBasket["DISCOUNT_PRICE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["DISCOUNT_PRICE"])
									$arFields["DISCOUNT_PRICE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["DISCOUNT_PRICE"];

								if(count($arFields)>0)
								{
									$arFields["ID"] = $arBasket["ID"];
									if(DoubleVal($arFields["QUANTITY"]) <= 0)
										$arFields["QUANTITY"] = $arBasket["QUANTITY"];
									$bNeedUpdate = true;
									$arShoppingCart[] = $arFields;
								}
								else
								{
									$arShoppingCart[] = $arBasket;
								}
								//CSaleBasket::Update($arBasket["ID"], $arFields);

								$arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["CHECKED"] = "Y";
							}
							else
							{
								if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true" && $orderInfo["CANCELED"] == "N")
								{
									$bNeedUpdate = true;
									//CSaleBasket::Delete($arBasket["ID"]);
								}
							}
						}

						if(!empty($arOrder["items"]))
						{
							foreach ($arOrder["items"] as $itemID => $arItem)
							{
								if ($arItem["CHECKED"] != "Y")
								{
									if ($arItem["TYPE"] == GetMessage("CC_BSC1_ITEM"))
									{
										if ($arBasketFields = $this->prepareProduct4Basket($itemID, $arItem, $orderId, $orderInfo))
										{
											$arShoppingCart[] = $arBasketFields;
											$bNeedUpdate = true;
										}
									}
									elseif ($arItem["TYPE"] == GetMessage("CC_BSC1_SERVICE"))
									{
										if (IntVal($arItem["PRICE"]) != IntVal($orderInfo["PRICE_DELIVERY"]))
											$arOrderFields["PRICE_DELIVERY"] = $arItem["PRICE"];
									}
								}
							}
						}

						if($bNeedUpdate)
						{
							$arErrors = array();
							CSaleBasket::DoSaveOrderBasket($orderId, $orderInfo["LID"], $orderInfo["USER_ID"], $arShoppingCart, $arErrors);
						}

						if(DoubleVal($arOrder["AMOUNT"]) > 0 && $arOrder["AMOUNT"] != $orderInfo["PRICE"])
							$arOrderFields["PRICE"] = $arOrder["AMOUNT"];
						if(DoubleVal($orderInfo["DISCOUNT_VALUE"]) > 0)
							$arOrderFields["DISCOUNT_VALUE"] = 0;
						if(strlen($arOrder["COMMENT"]) > 0 && $arOrder["COMMENT"] != $orderInfo["COMMENTS"])
							$arOrderFields["COMMENTS"] = $arOrder["COMMENT"];
						$arOrderFields["UPDATED_1C"] = "Y";
						if(!empty($arOrderFields))
							CSaleOrder::Update($orderId, $arOrderFields);
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_FINAL_NOT_EDIT", Array("#ID#" => $orderId));
					}
				}

				$arAditFields = Array();
				if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] == "true")
				{
					if($orderInfo["CANCELED"] == "N")
					{
						CSaleOrder::CancelOrder($orderInfo["ID"], "Y", $arOrder["COMMENT"]);
						$arAditFields["UPDATED_1C"] = "Y";
					}
				}
				else
				{
					if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true")
					{
						if($orderInfo["CANCELED"] == "Y")
						{
							CSaleOrder::CancelOrder($orderInfo["ID"], "N", $arOrder["COMMENT"]);
							$arAditFields["UPDATED_1C"] = "Y";
						}
					}

					if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")])>1)
					{
						if($orderInfo["PAYED"]=="N")
							CSaleOrder::PayOrder($orderInfo["ID"], "Y");
						$arAditFields["PAY_VOUCHER_DATE"] = CDatabase::FormatDate(str_replace("T", " ", $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG));
						if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")])>0)
							$arAditFields["PAY_VOUCHER_NUM"] = $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")];
						$arAditFields["UPDATED_1C"] = "Y";
					}

					if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_DATE")])>1)
					{
						if($orderInfo["ALLOW_DELIVERY"]=="N")
							CSaleOrder::DeliverOrder($orderInfo["ID"], "Y");
						$arAditFields["DATE_ALLOW_DELIVERY"] = CDatabase::FormatDate(str_replace("T", " ", $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_DATE")]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG));
						$arAditFields["DELIVERY_DOC_DATE"] = $arAditFields["DATE_ALLOW_DELIVERY"];

						if(strlen($this->arParams["FINAL_STATUS_ON_DELIVERY"])>0 && $orderInfo["STATUS_ID"] != "F" && $orderInfo["STATUS_ID"] != $this->arParams["FINAL_STATUS_ON_DELIVERY"])
							CSaleOrder::StatusOrder($orderInfo["ID"], $this->arParams["FINAL_STATUS_ON_DELIVERY"]);
						if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")])>0)
							$arAditFields["DELIVERY_DOC_NUM"] = $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")];
						$arAditFields["UPDATED_1C"] = "Y";
					}
				}


				if($this->arParams["CHANGE_STATUS_FROM_1C"] && strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])>0)
				{
					if($orderInfo["STATUS_ID"] != $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])
					{
						CSaleOrder::StatusOrder($orderInfo["ID"], $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")]);
						$arAditFields["UPDATED_1C"] = "Y";
					}
				}

				if(count($arAditFields)>0)
					CSaleOrder::Update($orderInfo["ID"], $arAditFields);
			}
			else
				$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_NOT_FOUND", Array("#ID#" => $arOrder["ID"]));
		}
		elseif($this->arParams["IMPORT_NEW_ORDERS"] == "Y") // create new order (ofline 1C)
		{
			if(!empty($arOrder["AGENT"]) && strlen($arOrder["AGENT"]["ID"]) > 0)
			{
				$arOrder["PERSON_TYPE_ID"] = 0;
				$arOrder["USER_ID"] = 0;
				$arErrors = array();
				$dbUProp = CSaleOrderUserProps::GetList(array(), array("XML_ID" => $arOrder["AGENT"]["ID"]), false, false, array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C"));
				if($arUProp = $dbUProp->Fetch())
				{
					$arOrder["USER_ID"] = $arUProp["USER_ID"];
					$arOrder["PERSON_TYPE_ID"] = $arUProp["PERSON_TYPE_ID"];
					$arOrder["USER_PROFILE_ID"] = $arUProp["ID"];
					$arOrder["USER_PROFILE_VERSION"] = $arUProp["VERSION_1C"];

					$dbUPropValue = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $arUProp["ID"]));
					while($arUPropValue = $dbUPropValue->Fetch())
					{
						$arOrder["USER_PROPS"][$arUPropValue["ORDER_PROPS_ID"]] = $arUPropValue["VALUE"];
					}
				}
				else
				{
					if(strlen($arOrder["AGENT"]["ID"]) > 0)
					{
						$arAI = explode("#", $arOrder["AGENT"]["ID"]);
						if(IntVal($arAI[0]) > 0)
						{
							$dbUser = CUser::GetByID($arAI[0]);
							if($arU = $dbUser->Fetch())
							{
								if(htmlspecialcharsback(substr(htmlspecialcharsbx($arU["ID"]."#".$arU["LOGIN"]."#".$arU["LAST_NAME"]." ".$arU["NAME"]." ".$arU["SECOND_NAME"]), 0, 80)) == $arOrder["AGENT"]["ID"])
								{
									$arOrder["USER_ID"] = $arU["ID"];
								}
							}
						}
					}

					if(IntVal($arOrder["USER_ID"]) <= 0)
					{
						//create new user
						$arUser = array(
								"NAME"  => $arOrder["AGENT"]["ITEM_NAME"],
								"EMAIL" => $arOrder["AGENT"]["CONTACT"]["MAIL_NEW"],
						);

						if (strlen($arUser["NAME"]) <= 0)
							$arUser["NAME"] = $arOrder["AGENT"]["CONTACT"]["CONTACT_PERSON"];
						if (strlen($arUser["EMAIL"]) <= 0)
							$arUser["EMAIL"] = "buyer".time().GetRandomCode(2)."@".$_SERVER["SERVER_NAME"];

						$arOrder["USER_ID"] = CSaleUser::DoAutoRegisterUser($arUser["EMAIL"], $arUser["NAME"], $this->arParams["SITE_NEW_ORDERS"], $arErrors, array("XML_ID"=>$arOrder["AGENT"]["ID"]));
					}
				}

				if(empty($arPersonTypesIDs))
				{
					$dbPT = CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => $this->arParams["SITE_NEW_ORDERS"]));
					while($arPT = $dbPT->Fetch())
					{
						$arPersonTypesIDs[] = $arPT["ID"];
					}
				}

				if(empty($arExportInfo))
				{
					$dbExport = CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $arPersonTypesIDs));
					while($arExport = $dbExport->Fetch())
					{
						$arExportInfo[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
					}
				}

				if(IntVal($arOrder["PERSON_TYPE_ID"]) <= 0)
				{
					foreach($arExportInfo as $pt => $value)
					{
						if(
						(($value["IS_FIZ"] == "Y" && $arOrder["AGENT"]["TYPE"] == "FIZ")
								|| ($value["IS_FIZ"] == "N" && $arOrder["AGENT"]["TYPE"] != "FIZ"))
						)
							$arOrder["PERSON_TYPE_ID"] = $pt;
					}
				}

				if(IntVal($arOrder["PERSON_TYPE_ID"]) > 0)
				{
					$arAgent = $arExportInfo[$arOrder["PERSON_TYPE_ID"]];
					foreach($arAgent as $k => $v)
					{
						if(empty($v) ||
								(
										(empty($v["VALUE"]) || $v["TYPE"] != "PROPERTY") &&
										(empty($arOrder["USER_PROPS"])
												|| (is_array($v) && is_string($v["VALUE"]) && empty($arOrder["USER_PROPS"][$v["VALUE"]]))
										)
								)
						)
							unset($arAgent[$k]);
					}

					if(IntVal($arOrder["USER_ID"]) > 0)
					{
						$orderFields = array(
								"SITE_ID" => $this->arParams["SITE_NEW_ORDERS"],
								"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
								"PAYED" => "N",
								"CANCELED" => "N",
								"STATUS_ID" => "N",
								"PRICE" => $arOrder["AMOUNT"],
								"CURRENCY" => CSaleLang::GetLangCurrency($this->arParams["SITE_NEW_ORDERS"]),
								"USER_ID" => $arOrder["USER_ID"],
								"TAX_VALUE" => doubleval($arOrder["TAX"]["VALUE_MONEY"]),
								"COMMENTS" => $arOrder["COMMENT"],
								"BASKET_ITEMS" => array(),
								"TAX_LIST" => array(),
								"ORDER_PROP" => array(),
						);
						$arAditFields = array(
								"EXTERNAL_ORDER" => "Y",
								"ID_1C" => $arOrder["ID_1C"],
								"VERSION_1C" => $arOrder["VERSION_1C"],
								"UPDATED_1C" => "Y",
								"DATE_INSERT" => CDatabase::FormatDate($arOrder["DATE"]." ".$arOrder["TIME"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG)),
						);

						foreach($arOrder["items"] as $productID => $val)
						{
							$orderFields["BASKET_ITEMS"][] = $this->prepareProduct4Basket($productID, $val, false, $orderFields);
						}

						if(!empty($arOrder["TAX"]))
						{
							$orderFields["TAX_LIST"][] = array(
									"NAME" => $arOrder["TAX"]["NAME"],
									"IS_PERCENT" => "Y",
									"VALUE" => $arOrder["TAX"]["VALUE"],
									"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
									"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
							);
						}

						foreach($arAgent as $k => $v)
						{
							if(!empty($arOrder["ORDER_PROPS"][$k]))
							{
								$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["ORDER_PROPS"][$k];
							}
							if(empty($orderFields["ORDER_PROP"][$v["VALUE"]]) && !empty($arOrder["USER_PROPS"][$v["VALUE"]]))
							{
								$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["USER_PROPS"][$v["VALUE"]];
							}
						}

						if($arOrder["ID"] = CSaleOrder::DoSaveOrder($orderFields, $arAditFields, 0, $arErrors))
						{
							$arAditFields = array("UPDATED_1C" => "Y");
							CSaleOrder::Update($arOrder["ID"], $arAditFields);

							//add/update user profile
							if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
							{
								if($arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"])
									CSaleOrderUserProps::Update($arOrder["USER_PROFILE_ID"], array("VERSION_1C" => $arOrder["AGENT"]["VERSION"], "NAME" => $arOrder["AGENT"]["AGENT_NAME"]));
								$dbUPV = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" =>$arOrder["USER_PROFILE_ID"]));
								while($arUPV = $dbUPV->Fetch())
								{
									$arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arUPV["ORDER_PROPS_ID"]] = array("ID" => $arUPV["ID"], "VALUE" => $arUPV["VALUE"]);
								}
							}

							if(IntVal($arOrder["USER_PROFILE_ID"]) <= 0 || (IntVal($arOrder["USER_PROFILE_ID"]) > 0 && $arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"]))
							{
								$dbOrderProperties = CSaleOrderProps::GetList(
										array("SORT" => "ASC"),
										array(
												"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
												"ACTIVE" => "Y",
												"UTIL" => "N",
												"USER_PROPS" => "Y",
										),
										false,
										false,
										array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
								);
								while ($arOrderProperties = $dbOrderProperties->Fetch())
								{
									$curVal = $orderFields["ORDER_PROP"][$arOrderProperties["ID"]];

									if (strlen($curVal) > 0)
									{
										if (IntVal($arOrder["USER_PROFILE_ID"]) <= 0)
										{
											$arFields = array(
													"NAME" => $arOrder["AGENT"]["AGENT_NAME"],
													"USER_ID" => $arOrder["USER_ID"],
													"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
													"XML_ID" => $arOrder["AGENT"]["ID"],
													"VERSION_1C" => $arOrder["AGENT"]["VERSION"],
											);
											$arOrder["USER_PROFILE_ID"] = CSaleOrderUserProps::Add($arFields);
										}
										if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
										{
											$arFields = array(
													"USER_PROPS_ID" => $arOrder["USER_PROFILE_ID"],
													"ORDER_PROPS_ID" => $arOrderProperties["ID"],
													"NAME" => $arOrderProperties["NAME"],
													"VALUE" => $curVal
											);
											if(empty($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]))
											{
												CSaleOrderUserPropsValue::Add($arFields);
											}
											elseif($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["VALUE"] != $curVal)
											{
												CSaleOrderUserPropsValue::Update($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["ID"], $arFields);
											}
										}
									}
								}
							}
						}
						else
						{
							$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_ADD_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
						}
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_USER_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
						if(!empty($arErrors))
						{
							foreach($arErrors as $v)
							{
								$this->strError .= "\n".$v["TEXT"];
							}
						}
					}
				}
				else
				{
					$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_PERSON_TYPE_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
				}
			}
			else
			{
				$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_NO_AGENT_ID", Array("#ID#" => $arOrder["ID_1C"]));
			}
		}
	}
}
?>